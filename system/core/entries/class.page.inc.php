<?php

/**
 * Generic functions for page interactions.
 *
 * This class handles database interaction and file uploads for most publicly
 * displayed pages built on the EnnuiCMS platform.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 */
class Page extends AdminUtilities
{
    /**
     * Image dimensions
     * @var array
     */
    public $img_dims = array(
                    'w' => IMG_MAX_WIDTH,
                    'h' => IMG_MAX_HEIGHT,
                    't' => IMG_THUMB_SIZE
                ),

    /**
     * Storage for the array pieces
     * @var string
     */
           $url0, $url1, $url2, $url3, $url4, $url5,

    /**
     * Page template file name
     * @var string
     */
           $template = 'default.inc',

    /**
     * The page type as stored in the pages table
     * @var string
     */
           $page_type,

    /**
     * Registered actions for form processing
     * @var array
     */
           $access_points;

    /**
     * Loads the mysqli object and organizes the URL into variables
     *
     * @param object $mysqli
     * @param array $url_array
     */
    public function __construct( $url_array=NULL )
    {
        // Creates a database object
        parent::__construct();

        // Store the URL components as class properties
        for( $i=0, $c=count($url_array); $i<$c; ++$i )
        {
            if ( !empty($url_array[$i]) )
            {
                $prop = "url$i";
                $this->$prop = $url_array[$i];
            }
        }

        // Identify the class being used
        $this->page_type = $this->get_page_data_by_slug($this->url0)->type;

        // Register access points
        $this->register_core_actions();
    }

    protected function register_core_actions(  )
    {
        $this->access_points = array(
            'entry-write' => 'save_entry',
            'entry-edit' => 'display_admin',
            'entry-delete' => 'confirm_delete',
            'entry-search' => 'handle_search',
            'user-login' => 'login',
            'user-create' => 'create_user',
            'user-verify' => 'verify_user',
            'menu-update' => 'update_menu',
            'comment-write' => 'save_comment',
            'comment-flag' => 'confirm_flag_comment',
            'comment-flag-confirmed' => 'flag_comment',
            'contact-form' => 'sendMessage'
        );
    }

    /**
     * Generates a formatted title string for the <title> element
     * 
     * @param object $menuPage  Data related to the current page
     * @return string           The formatted title string
     */
    public function get_page_title( $menuPage )
    {
        // Create aliases for easy use
        $title = SITE_TITLE;
        $sep = SITE_TITLE_SEPARATOR;

        // Store the page name
        $page = $menuPage->page_name;

        // Set the page name if nothing was stored
        if( empty($page) )
        {
            $page = ucwords($this->url0);
        }

        // If the page matches the default, leave the $page var blank
        $page = DB_Actions::get_default_page()!==$this->url0 ? "$page $sep $title" : $title;

        // These values require special treatment
        $lookup = array(
                'tag', 'more'
            );

        // Special case title displays
        if( strtolower($menuPage->page_name)==='search' && isset($this->url1) )
        {
            $entry = 'Search for "' . $this->url1 . '" on ' . SITE_NAME;
        }

        else if( in_array($this->url1, $lookup) && isset($this->url2) )
        {
            $entry_title = ucwords(str_replace('-', ' ', $this->url2));
            $entry = ucwords(str_replace('-', ' ', $this->url1))
                    . ': ' . $entry_title;
        }

        // If an entry title exists, put it in the title
        else if( isset($this->url1) && !empty($this->entries[0]->title) )
        {
            $entry = $this->entries[0]->title;
        }

        // Otherwise, leave it blank
        else
        {
            $entry = $page;
        }

        // Put it all together and return the title string
        return $entry;
    }

    public function get_page_description()
    {
        if( $this->url0===DB_Actions::get_default_page() && empty($this->url1) )
        {
            return SITE_DESCRIPTION;
        }
        else if( isset($this->entries[0]->excerpt) )
        {
            return htmlentities(strip_tags($this->entries[0]->excerpt), ENT_QUOTES);
        }
        else if( isset($this->entries[0]->entry) )
        {
            $preview = Utilities::textPreview($this->entries[0]->entry, 25);
            return htmlentities(strip_tags($preview), ENT_QUOTES);
        }
        else
        {
            return SITE_DESCRIPTION;
        }
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags()
    {
        // If an entry exists, load the template and insert the data into it
        if( isset($this->entries[0]->title) )
        {
            // Generate the default template tags
            foreach( $this->entries as $entry )
            {
                $this->generate_default_template_tags($entry);
            }
        }

        // If no entry exists, output some default text to avoid a broken layout
        else
        {
            // Set default values if no entries are found and load a template
            $this->generate_default_entry();
        }
    }

    protected function generate_default_template_tags( &$e )
    {
        // Permalink for the entry
        $e->permalink = SITE_URL . $this->url0 . '/' . $e->slug;

        // Relative link
        $e->relative_link = '/' . $this->url0 . '/' . $e->slug;

        // Encoded URL and title values for sharing tools
        $e->encoded_permalink = urlencode($e->permalink);
        $e->encoded_title = urlencode($e->title);

        // Get or generate an excerpt
        if(!empty($e->excerpt) )
        {
            $e->excerpt = '<p>' . nl2br($e->excerpt) . '</p>';
        }
        else
        {
            $e->excerpt = Utilities::text_preview($e->entry, 25);
        }

        // Admin options
        $e->admin = $this->admin_simple_options($this->url0, $e->entry_id);

        // Human-readable date
        $e->date = !empty($e->created) ? date(DATE_FORMAT, $e->created) : NULL;

        // Image options
        $this->generate_image_template_tags($e);
    }

    /**
     * Checks for an image and returns thumbnail, preview, and full-size URLs
     *
     * The entry object is passed by reference, so no return value is necessary
     *
     * @param object &$e
     * @return void
     */
    protected function generate_image_template_tags( &$e )
    {
        // Get the file path
        $img = strpos($e->image, '/')!==0 ? '/' . $e->image : $e->image;
        $filepath = dirname($_SERVER['SCRIPT_FILENAME']).$img;

        // If the image exists, set up image URLs
        if ( !empty($img) && file_exists($filepath) && is_file($filepath) )
        {
            // Extract the file basename
            $bn = basename($img);

            // Display the latest two galleries
            $e->image = $img;
            $e->preview = str_replace($bn, 'preview/'.$bn, $img);
            $e->thumb = str_replace($bn, 'thumbs/'.$bn, $img);
            $e->caption = isset($e->caption) ? $e->caption : $e->title;
        }

        // Otherwise, return default image URLs
        else
        {
            $e->image = '/assets/images/no-image.jpg';
            $e->preview = '/assets/images/no-image.jpg';
            $e->thumb = '/assets/images/no-image-thumb.jpg';
            $e->caption = 'No image';
        }
    }

    protected function generate_default_entry( $admin=NULL )
    {
        // Set default values if no entries are found
        $default = new Entry();
        $default->admin = $this->admin_general_options($this->url0);
        $default->title = "No Entry Found";
        $default->entry = "<p>That entry doesn't appear to exist.</p>";
        $this->generate_image_template_tags($default);
        $this->entries[] = $default;

        // Load the default template
        $this->template = 'default.inc';
    }

    protected function generate_markup( $extra=array() )
    {
        $template = $this->_load_template();
        return $this->_parse_template($template, $extra);
    }

    /**
     * Loads a template with which markup should be formatted
     *
     * @return string The contents of the template file
     */
    private function _load_template(  )
    {
        // Get the current class type to check for plugin-specific templates
        $class = strtolower($this->page_type);

        // Define possible template locations as an array
        $template_path_array = array(
                    'assets/plugins/' . $class . '/assets/templates/' . $this->template,
                    'assets/templates/' . $this->template,
                    CMS_PATH . 'templates/' . $this->template,
                    'assets/plugins/' . $class . '/assets/templates/default.inc',
                    'assets/templates/default.inc',
                    CMS_PATH . 'templates/default.inc'
                );

        // Loop through the locations and return the first available template
        foreach( $template_path_array as $template_path )
        {
            if( file_exists($template_path) && is_readable($template_path) )
            {
                // For debugging, log the template file location
                FB::log($template_path, "Template File");

                // Load the contents of the file and return them
                return file_get_contents($template_path);
            }
        }
    }

    /**
     * Separates the template into header, loop, and footer for parsing
     *
     * @param string $template  The template to use for marking up entries
     * @param array $extra      Additional content for the header/footer
     * @return string           The entry markup
     */
    private function _parse_template( $template, $extra=array() )
    {
        // Extract the loop parameters if they exist
        $params = preg_replace('/.*\{loop\s\[(.*?)\]\}.*/is', "$1", $template);
        if( $params===$template )
        {
            $params = NULL;
        }

        // Define default parameters
        $p = array(
            "max_entries" => MAX_ENTRIES_PER_PAGE,
            "htmlentities" => FALSE,
            "strip_tags" => FALSE,
            "strip_tags_whitelist" => STRIP_TAGS_WHITELIST,
            "text_preview" => FALSE,
            "text_preview_length" => 25,
            "add_first_entry_class" => FALSE
        );

        // If parameters were passed, decode them here
        if( !empty($params) )
        {
            $param_array = json_decode('{'.$params.'}', TRUE);
            foreach( $param_array as $key => $val )
            {
                // Make sure the parameter is valid before saving
                if( array_key_exists($key, $p) )
                {
                    // Overwrite the default parameter
                    $p[$key] = $val;
                }
            }
        }

        // Remove any PHP-style comments from the template
        $comment_pattern = array('#/\*.*?\*/#s', '#(?<!:)//.*#');
        $template = preg_replace($comment_pattern, NULL, $template);

        // Extract the main entry loop from the file
        $loop_pattern = '#.*{loop.*?}(.*?){/loop}.*#is';
        $entry_template = preg_replace($loop_pattern, "$1", $template);

        // Extract the header from the template if one exists
        $header = trim(preg_replace('/^(.*)?{loop.*$/is', "$1", $template));
        if( $header===$template )
        {
            $header = NULL;
        }

        // Extract the footer from the template if one exists
        $footer = trim(preg_replace('#^.*?{/loop}(.*)$#is', "$1", $template));
        if( $footer===$template )
        {
            $footer = NULL;
        }

        // Define a regex to match any template tag
        $tag_pattern = '/{(\w+)}/';

        // Curry the function that will replace the tags with entry data
        $callback = $this->_curry('Page::replace_tags', 3);

        // Process each entry and insert its values into the loop
        $markup = NULL;
        for( $i=0, $c=count($this->entries); $i<$c; ++$i )
        {
            $markup .= preg_replace_callback(
                            $tag_pattern,
                            $callback(serialize($this->entries[$i]), $p),
                            $entry_template
                        );
        }

        // If extra data was passed to fill in the header/footer, parse it here
        if( is_object($extra) )
        {
            foreach( $extra as $key=>$props )
            {
                $$key = preg_replace_callback(
                            $tag_pattern,
                            $callback(serialize($extra->$key), $p),
                            $$key
                        );
            }
        }

        // Return the formatted entries with the header and footer reattached
        return $header . $markup . $footer;
    }

    /**
     * Replaces template tags with entry data
     *
     * @param object $entry     The entry object
     * @param array $params     Parameters for replacement
     * @param array $matches    The matches from preg_replace_callback()
     * @return string           The replaced template value
     */
    public static function replace_tags($entry, $params, $matches)
    {
        // Unserialize the object
        $entry = unserialize($entry);

        // Make sure the template tag has a matching array element
        if( property_exists($entry, $matches[1])
                || ( property_exists($entry, 'extra_props')
                    && array_key_exists($matches[1], $entry->extra_props) ) )
        {
            // Grab the value from the Entry object
            $val = $entry->{$matches[1]};

            // Run htmlentities() is the parameter is set to TRUE
            if ( $params['htmlentities']===TRUE )
            {
                $val = htmlentities($val, ENT_QUOTES);
            }

            // Run strip_tags() if the parameter is set to TRUE
            if ( $params['strip_tags']===TRUE )
            {
                $whitelist = STRIP_TAGS_WHITELIST;
                if ( isset($params['strip_tags_whitelist']) )
                {
                    $whitelist = $params['strip_tags_whitelist'];
                }

                $val = Utilities::strip_tags_attr($val, $whitelist);
            }

            // Create a text preview if one the parameter is set to TRUE
            if ( $params['text_preview']===TRUE && $matches[1]=='entry' )
            {
                $val = Utilities::text_preview($val, $params['text_preview_length']);
            }

            return $val;
        }

        // Otherwise, simply return the tag as is
        else
        {
            return '{' . $matches[1] . '}';
        }
    }

    /**
     * A currying function
     *
     * Currying allows a function to be called in increments. This means that if
     * a function accepts two arguments, it can be curried with only one
     * argument supplied, which returns a new function that will accept the
     * remaining argument and return the output of the original curried function
     * using the two supplied parameters.
     *
     * Example:
     *
     * function add($a, $b)
     * {
     *     return $a + $b;
     * }
     *
     * $func = $this->_curry('add', 2);
     *
     * $func2 = $func(1); // Stores 1 as the first argument of add()
     *
     * echo $func2(2); // Executes add() with 2 as the second arg and outputs 3
     *
     * @param string $function  The name of the function to curry
     * @param int $num_args     The number of arguments the function accepts
     * @return mixed            Function or return of the curried function
     */
    private function _curry($function, $num_args)
    {
        return create_function('', "
            // Store the passed arguments in an array
            \$args = func_get_args();

            // Execute the function if the right number of arguments were passed
            if( count(\$args)>=$num_args )
            {
                return call_user_func_array('$function', \$args);
            }

            // Export the function arguments as executable PHP code
            \$args = var_export(\$args, 1);

            // Return a new function with the arguments stored otherwise
            return create_function('','
                \$a = func_get_args();
                \$z = ' . \$args . ';
                \$a = array_merge(\$z,\$a);
                return call_user_func_array(\'$function\', \$a);
            ');
        ");
    }

    protected function paginate_entries($preview=BLOG_PREVIEW_NUM)
    {
        if ( $this->url0=="search" )
        {
            $page = 'search';
            $link = $this->url1;
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = $this->get_entry_count_by_search($this->url2, $this->url1);
        }
        else
        {
            $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
            $url1 = empty($this->url1) ? 'tag' : $this->url1;
            $url2 = empty($this->url2) ? 'recent' : $this->url2;
            $link = "$url1/$url2";
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = $this->get_entry_count_by_tag();
        }

        $span = 6; // How many pages shown adjacent to current page

        $pagination = "\n<ul id=\"pagination\">";

        /*
         * Determine minimum and maximum page numbers
         */
        $pages = ceil($c/$preview);

        $prev_page = $num-1;
        $f = '    ';
        if($num==1)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "$f<li>\n$f$f<a href=\"/$page/$link/1/\">&#171;</a>"
                    . "\n$f</li>\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$prev_page/\">&#x2039;</a>"
                    . "\n$f</li>";
        }

        $mod = ($span>$num) ? $span-$num : 0;
        $max_mod = ($num+$span>$pages) ? $span-($pages-$num) : 0;
        $max = ($num+$span<=$pages) ? $num+$span+$mod : $pages;
        $max_num = ($max>$pages) ? $pages : $max;
        $min = ($max_num>$span*2) ? $num-$span-$max_mod : 1;
        $min_num = ($min<1) ? 1 : $min;

        for($i=$min_num; $i<=$max_num; ++$i)
        {
            $sel = ($i==$num) ? ' class="selected"' : NULL;
            $pagination .= "\n$f<li$sel>"
                    . "\n$f$f<a href=\"/$page/$link/$i/\">$i</a>\n$f</li>";
        }

        $next_page = $num+1;
        if($next_page>$pages)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$next_page/\">&#x203a;</a>"
                    . "\n$f</li>\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$pages/\">&#187;</a>"
                    . "\n$f</li>";
        }

        return $pagination . "\n</ul><!-- end #pagination -->\n";
    }

    protected function map_action( $action )
    {
        if( array_key_exists($action, $this->access_points) )
        {
            return $this->access_points[$action];
        }
        else
        {
            return FALSE;
        }
    }

    public static function display_posts( $num_or_config=8 )
    {
        $config = (object) array(
            'display_num' => 8,
            'page_slug' => 'blog',
            'filter' => 'recent',
            'list_id' => 'display_posts',
            'template' => 'display_posts.inc',
            'tags' => NULL,
            'entry_id' => NULL
        );

        if ( is_object($num_or_config) )
        {
            foreach ( $num_or_config as $param=>$value )
            {
                if ( property_exists($config, $param) )
                {
                    $config->$param = $value;
                }
            }
        }
        else if ( is_int($num_or_config) )
        {
            $config->display_num = $num_or_config;
        }

        $page_obj = new self(array($config->page_slug));
        $page_obj->template = $config->template;
        $page_obj->entry_limit = $config->display_num;

        $menu_obj = Menu::get_page_data_by_slug($config->page_slug);
        if ( $config->filter==='recent' )
        {
            $page_obj->get_all_entries();
        }
        else if ( $config->filter==='featured' )
        {
            $page_obj->get_featured_entries();
        }
        else if ( $config->filter==='related' && isset($config->tags) )
        {
            $page_obj->get_related_entries(
                    $config->entry_id, $menu_obj->page_id, $config->tags
                );
        }

        $page_obj->generate_template_tags();

        foreach ( $page_obj->entries as &$entry )
        {

            if ( !empty($entry->subtitle) )
            {
                $subtitle = ': ' . $entry->subtitle;
            }
            else
            {
                $subtitle = NULL;
            }

            $entry->title = stripslashes($entry->title . $subtitle);
        }

        $extra_template->header->list_id = $config->list_id;

        // Return the entry as formatted by the template
        return $page_obj->generate_markup($extra_template);
    }

    public function __toString()
    {
        return $this->display_public();
    }

}
