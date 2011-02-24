<?php

/*******************************************************************************
* Initialization — sessions, timers, and output buffering
*******************************************************************************/

// Enable sessions
session_start();

// Start a timer
$start_time = microtime(TRUE);

// Starts output buffering
ob_start();


/*******************************************************************************
* Includes — all required files are loaded
*******************************************************************************/

// Configuration files
require_once CMS_PATH . '../config/general.config';
require_once CMS_PATH . '../config/database.config';
require_once CMS_PATH . '../config/admin.config';
require_once CMS_PATH . '../config/advanced.config';

// Utility classes
require_once CMS_PATH . 'core/utils/class.utilities.inc.php';
require_once CMS_PATH . 'core/utils/class.ecms_error.inc.php';
require_once CMS_PATH . 'core/utils/class.siv.inc.php';

// Database classes
require_once CMS_PATH . 'core/database/class.db_connect.inc.php';
require_once CMS_PATH . 'core/database/class.db_actions.inc.php';

// Admin class
require_once CMS_PATH . 'core/admin/class.adminutilities.inc.php';

// Menu class
require_once CMS_PATH . 'core/menu/class.menu.inc.php';

// Entry classes
require_once CMS_PATH . 'core/entries/class.page.inc.php';
require_once CMS_PATH . 'core/entries/class.entry.inc.php';

// Load required helper classes
require_once CMS_PATH . 'core/helper/class.search.inc.php';
require_once CMS_PATH . 'core/helper/class.comments.inc.php';
require_once CMS_PATH . 'core/helper/class.gravatar.inc.php';

// Abstract plugin class
require_once CMS_PATH . 'core/plugins/abstract.helix_plugin.inc.php';

// Form classes
require_once CMS_PATH . 'core/forms/class.form.inc.php';
require_once CMS_PATH . 'core/forms/class.input.inc.php';

// Image class
require_once CMS_PATH . 'core/images/class.imagecontrol.inc.php';

// FirePHP class for debugging (requires Firefox)
require_once CMS_PATH . 'core/debug/fb.php';


/*******************************************************************************
* Application settings — constants are defined, debug mode set, session checked
*******************************************************************************/

// Define site-wide constants
foreach( $_CONSTANTS as $key=>$value )
{
    define($key, $value);
}

// Handles debugging. If TRUE, displays all errors and enables FirePHP logging
if( ACTIVATE_DEBUG_MODE===TRUE )
{
    ini_set("display_errors",1);
    ERROR_REPORTING(E_ALL);
    FB::setEnabled(TRUE);
    FB::warn("FirePHP logging is enabled! Sensitive data may be exposed.");
}
else
{
    ini_set("display_errors",0);
    error_reporting(0);
    FB::setEnabled(FALSE);
}

// Creates the database tables if set to true
if( BUILD_DATABASE===TRUE )
{
    DB_Actions::build_database();
}

// Check for a valid session
AdminUtilities::check_session();


/*******************************************************************************
* Break apart the URL and determine what data needs to be loaded
*******************************************************************************/

// URL Parsing - Read the URL and break it apart for processing
$url_array = Utilities::read_url();

// Load the menu
$menu = new Menu($url_array);

// Load the page attributes from the menu array
$menu_page = DB_Actions::get_page_data_by_slug( $url_array[0] );

// Check if the page should actually be shown as main content
if( property_exists($menu_page, 'show_full') && $menu_page->show_full!=1 )
{
    header("Location: /".DB_Actions::get_default_page());
    exit;
}

// If the supplied URL doesn't match any menu items, direct to the 404 page
else if( $menu_page===FALSE )
{
    require_once CMS_PATH . 'core/helper/class.missing.inc.php';
    $menu_page->page_name = 'Invalid URL';
    $menu_page->type = 'Missing';
}


/*******************************************************************************
* Initialize the main content class and load entry data
*******************************************************************************/

// Create a new object for the correct page type
try
{
    $main_content = new $menu_page->type($url_array);
}
catch( Exception $e )
{
    ECMS_Error::logException($e);
}

// Load the main entry
$entry = $main_content->display_public();


/*******************************************************************************
* Meta data for the site is created here
*******************************************************************************/

// Load the page title
$title = $main_content->get_page_title($menu_page);

// Load the meta description (must come after $main_content->display_public())
$meta_description = $main_content->get_page_description();


/*******************************************************************************
* Define an autoloader for entry classes
*******************************************************************************/

function __autoload( $classname )
{
    // File names are always lowercase
    $class = strtolower($classname);

    // First, check if a custom plugin exists
    if( file_exists("assets/plugins/$class/hlx.$class.inc.php") )
    {
        $path = "assets/plugins/$class/hlx.$class.inc.php";
    }

    // If not, check the class folder
    else if( file_exists(CMS_PATH . 'page/class.' . $class . '.inc.php') )
    {
        $path = CMS_PATH . 'page/class.' . $class . '.inc.php';
    }

    else
    {
        throw new Exception("That class does not exist.");
    }

    // Include the file
    require_once $path;
    FB::log($path, "Class Loaded");
}
