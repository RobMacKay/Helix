<?php

    // Define a path for loading the CMS. Default is ../system/
    define('CMS_PATH', './system/');

    // Initializes the core functionality of the CMS
    require_once CMS_PATH . 'core/init.inc.php';

?>
<!DOCTYPE html>
<html lang="en" xmlns:og="http://ogp.me/ns#">

<head>

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

    <!-- Meta information -->
    <title><?php echo $title; ?></title>
    <meta name="description"
          content="<?php echo $meta_description; ?>" />

    <!-- Token information. Fightin' off the spams. -->
    <meta name="hlx-token"
          content="<?php echo $_SESSION['ecms']['token']; ?>" />

    <!-- So the site looks pretty on Android/iOS -->
    <meta name="HandheldFriendly" content="true" />
    <meta name="viewport"
          content="width=device-width, height=device-height, user-scalable=no" />

    <!-- Facebook Open Graph meta because we're total Facebook whores -->
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>" />
    <meta property="og:url" content="<?php echo Utilities::get_permalink(); ?>" />
    <meta property="og:image" content="<?php echo SITE_APPLE_TOUCH_ICON; ?>" />
<?php
    $fb_type = $main_content->url0==='home' && empty($main_content->url1) ? 'website' : 'article';
?>
    <meta property="og:type" content="<?php echo $fb_type; ?>" />
    <meta property="og:description"
          content="<?php echo $meta_description; ?>" />

    <!-- Google-juice is probably bad for your teeth, but we love the taste! -->
    <link rel="canonical" href="<?php echo Utilities::get_permalink(); ?>" />

    <!-- Favicons. All the cool kids are doing it. -->
    <link rel="apple-touch-icon"
          href="<?php echo SITE_APPLE_TOUCH_ICON; ?>" />
    <link rel="shortcut icon"
          href="<?php echo SITE_FAVICON; ?>"
          type="image/x-icon" />

    <!-- RSS Feeds -->
    <link rel="alternate"
          type="application/rss+xml"
          title="<?php echo SITE_NAME; ?> - RSS 2.0"
          href="<?php echo SITE_RSS; ?>" />

    <!-- CSS File Includes -->
    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/default.css?v1" />
<?php

    // In the event that there's a page-specific CSS file, include it here
    $css_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/assets/css/'
            . $main_content->url0 . '.css';
    if( file_exists($css_file) ):

?>
    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/<?php echo $main_content->url0 ?>.css" />
<?php endif; ?>

    <!-- ThumbBox CSS (Do NOT remove this unless you know what you're doing) -->
    <link rel="stylesheet" type="text/css" media="screen"
          href="/assets/js/thumbbox/css/jquery.thumbbox.css" />
<?php

    // If the user is logged in, load stylesheets for the admin controls
    if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 ):

?>

    <!-- Admin stylesheets -->
    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/admin.css" />
<?php endif; ?>

    <!-- IE stylesheets to be loaded only if the site is loaded in IE -->
    <!--[IF LTE IE 8]>
        <script type="text/javascript"
            src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <link rel="stylesheet" type="text/css" media="screen,projection"
              href="/assets/css/ie.css" />
    <![endif]-->
    <!--[IF LTE IE 7]>
        <link rel="stylesheet" type="text/css" media="screen,projection"
              href="/assets/css/why-are-you-still-using-ie7.css" />
    <![endif]-->

</head>

<body>

<?php

//TODO Get the site options working properly
if( AdminUtilities::check_clearance(1) )
{
    require_once CMS_PATH . 'core/admin/class.siteadmin.inc.php';
    $site_admin_options = new SiteAdmin();
    echo $site_admin_options->display_site_options();
}

?>

    <header>

        <h1 id="header_title"> <a href="/"><?php echo SITE_NAME; ?></a> </h1>

        <nav>
<?php echo $menu; ?>

        </nav>

    </header>

    <div id="content">

        <section class="entrydisplay" id="<?php echo $main_content->url0; ?>">

<!-- BEGIN GENERATED CONTENT -->

<?php echo $entry; ?>


<!-- END GENERATED CONTENT -->

        </section><!-- end .entrydisplay -->

        <aside>

<?php

    $sidebar = new Single(array('sidebar'));
    echo $sidebar;

?>


            <ul id="sidebar-flickr">
                <li class="loading">
                    <img class="/assets/images/ajax-load.gif"
                         alt="Loading recent images from Flickr..." />
                </li>
            </ul><!-- end #sidebar-flickr -->

<?php

    $config = (object) array(
            'display_num' => 6,
            'page_slug' => 'features',
            'filter' => 'recent',
            'list_id' => 'proof-of-concept',
            'template' => 'sb-posts.inc',
            'tags' => NULL,
            'entry_id' => NULL
        );

    echo Page::display_posts($config);

?>

        </aside>

    </div><!-- end #content -->

    <footer>

        <p class="credits">
            All content &copy;
<?php echo Utilities::copyright_year(SITE_CREATED_YEAR), ' ', SITE_NAME; ?> |
            <a href="<?php echo SITE_URL ?>contact">Contact Us</a> |
            <a href="http://www.copterlabs.com"
               rel="external">Website by Copter Labs, Inc.</a>
        </p>

    </footer>

    <!-- Load jQuery and jQuery UI -->
    <script type="text/javascript"
            src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load("jquery", "1");
    </script>

    <!-- ThumbBox powers a lot of ECMS AJAX functionality — it's probably best
         to leave it here unless you know exactly what you're doing -->
    <script type="text/javascript"
            src="/assets/js/thumbbox/jquery.thumbbox.js"></script>


    <!-- Additional scripts for site enhancement. These are optional. -->
    <script type="text/javascript"
            src="/assets/js/jquery.loadflickr.js"></script>
    <script type="text/javascript"
            src="/assets/js/jquery.cookie.js"></script>

<?php
    // If the user is logged in, load JavaScript for the admin controls
    if( $main_content->url0=="admin" ||  AdminUtilities::check_clearance(1) ):
?>

    <!-- Admin JS Files -->
    <script type="text/javascript"
            src="/assets/js/tiny_mce/jquery.tinymce.js"></script>
    <script type="text/javascript"
            src="/assets/js/hlx.admin.js"></script>
<?php endif; ?>

    <!-- Initialization JS File -->
    <script type="text/javascript"
            src="/assets/js/hlx.init.js"></script>
<?php

    // If a Google Analytics user is set, include the Google Analytics code
    if( GOOGLE_ANALYTICS_USER!=='' ):

?>

    <!-- Google Analytics -->
    <script type="text/javascript"
            src="http://www.google-analytics.com/ga.js"></script>
    <script type="text/javascript">
        var pageTracker = _gat._getTracker("<?php echo GOOGLE_ANALYTICS_USER; ?>");
        pageTracker._trackPageview();
    </script>
<?php endif; ?>


</body>

<?php

    // Generate a quick note to let geeks know how long the page render took
    $time = round((microtime(TRUE)-$start_time)*1000);
    echo "<!-- Page rendered by Helix in ", $time, " milliseconds -->";
    FB::log("Page rendered by Helix in $time milliseconds");

?>


</html>
<?php
    // Clean the buffer
    ob_end_flush();
