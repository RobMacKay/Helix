<?php

/**
 * Builds and manages the site menu/page structure
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
class Menu extends DB_Actions
{

    public $url_array = array();

    private $_menu = array();

    public function __construct( $url_array=array() )
    {
        parent::__construct();

        $this->url_array = $url_array;
        $this->_sort_menu_data($this->load_page_data());
    }

    public function display_public(  )
    {
        // Check if

        // Initialize the array
        $display_items = array();

        // Loop through the existing menu pages and output info
        foreach( $this->_menu as $item )
        {
            // Grab the item's ID
            $id = (int) $item['page_id'];

            // Text input to allow the user to edit the display name
            $name_input = '<input type="text" name="item-' . $id . '[page_name]" '
                    . 'value="' . $item['page_name'] . '" />';

            // Text input to allow the user to edit the display name
            $slug_input = '<input type="text" name="item-' . $id . '[page_slug]" '
                    . 'value="' . $item['page_slug'] . '" />';

            // Load available page types and plugins
            $types = array(
                    'system' => $this->_get_available_page_types(CMS_PATH.'page/'),
                    'plugins' => $this->_get_available_page_types('./assets/plugins/')
                );

            // Set up option groups for page types
            $type_select = '<select name="item-' . $id . '[type]">';
            foreach( $types as $group=>$types )
            {
                $group = array('<optgroup label="' . ucfirst($group) . '">');

                foreach( $types as $type )
                {
                    $selected = $type===$item['type'] ? ' selected="selected"' : NULL;
                    $group[] = '<option' . $selected . '>' . $type . '</option>';
                }

                $group[] = '</optgroup>';

                $type_select .= implode('', $group);
            }
            $type_select .= '</select>';

            // Checkbox to determine if the item should be shown in the menu
            $checked = $item['hide_in_menu']==1 ? ' checked="checked"' : NULL;
            $show_checkbox = '<input type="checkbox" name="item-' . $id
                    . '[hide_in_menu]" value="1"' . $checked . ' />';

            // Checkbox to determine if the item should be shown in the menu
            $checked = $item['show_full']==1 ? ' checked="checked"' : NULL;
            $full_checkbox = '<input type="checkbox" name="item-' . $id
                    . '[show_full]" value="1"' . $checked . ' />';

            //TODO Arrange existing pages into a select for heirarchy selection
            $parent_select = NULL;

            // Assemble the inputs into a table row
            $display_items[] = '<tr><td>' . $name_input . '</td><td>'
                    . $slug_input . '</td><td>' . $type_select . '</td>'
                    . '<td>' . $show_checkbox . '</td><td>' . $full_checkbox
                    . '</td><td>' . $parent_select . '</td></tr>';
        }

        echo '<h2>Edit Site Pages and Content Areas</h2>'
                . '<h3>Submitted POST Data</h3>'
                . '<pre>' . print_r($_POST, TRUE) . '</pre>'
                . '<h3>Menu Page Editing</h3>'
                . '<form action="/menu" method="post">'
                . '<table id="menu-page-edit"><tbody>'
                . '<tr><th>Page Display Name</th><th>Page Slug</th>'
                . '<th>Page Type</th><th>Hide in Menu?</th>'
                . '<th>Display as Full Page?</th><th>Parent Page</th></tr>'
                . implode('', $display_items) . '<tr><td colspan="6">'
                . '<input type="submit" value="Update Menu" /></td></tr>'
                . '</tbody></table></form>';
    }

    public function generate_menu_markup( $is_sub=FALSE, $subid=NULL, $use=NULL, $indent_width=12 )
    {
        // Check if we're handling a sub-menu or not
        $attr = !$is_sub ? ' id="menu"' : ' class="submenu ' . $subid . '"';
        $closing_comment = !$is_sub ? "#menu" : ".submenu";

        // For sub-menus, use just that piece of the array (passed in $use)
        $menu_array = isset($use) ? $use : $this->_menu;

        // Start the menu markup
        $indent = str_repeat(" ", $indent_width);
        $menu = $indent . "<ul$attr>";

        // Loop through the array to extract element values
        foreach( $menu_array as $page )
        {
            // Loop through each page's data array
            foreach( $page as $key=>$val )
            {
                // Check for sub-menus and handle them if present
                if( is_array($val) )
                {
                    $sub = "\n".self::generate_menu_markup(TRUE, $page_slug, $val, 20);
                    if( array_key_exists($this->url_array[0], $val) )
                    {
                        $extra = isset($extra) ? $extra . ' parent' : 'parent';
                    }
                }

                // Set $sub to NULL and store the value in a variable otherwise
                else
                {
                    $sub = NULL;
                    $$key = $val;
                }
            }

            // Make sure URL exists to avoid errors
            if( !isset($page_slug) )
            {
                $page_slug = $page_name;
            }

            // If a class element is set, add it to the <li> tag
            if( !isset($extra) )
            {
                $extra = NULL;
            }

            // Determine if the element matches the current page
            $sel = $page_slug===$this->url_array[0] ? ' class="selected '.$extra.'"' : ' class="'.$extra.'"';

            // Make sure the page should be directly viewable
            if( !isset($show_full) || (int) $show_full===1 )
            {
                // If the item is hidden, don't build markup for it
                if( isset($hide_in_menu) && (int) $hide_in_menu===1 )
                {
                    continue;
                }

                // Check if the URL is external
                $page_slug = stripos($page_slug, 'http://', 0) ? $page_slug : "/$page_slug";

                // Use the created variables to output HTML
                $menu .= "\n" . str_repeat(" ", $indent_width+4) 
                        . "<li$sel>\n"
                        . str_repeat(" ", $indent_width+8)
                        . "<a href=\"$page_slug\" class=\"$extra\">$page_name</a>$sub\n"
                        . str_repeat(" ", $indent_width+4) . "</li>";
            }

            // Destroy the variables to ensure they're reset on each iteration
            unset($page_slug, $page_name, $sub, $extra, $hide_in_menu, $show_full);
        }

        return $menu . "\n" . $indent . "</ul><!-- end $closing_comment -->";
    }

    public function display_menu_form( $page_id=NULL )
    {
        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = empty($page_id) ? "Create a Menu Page" : "Edit a Menu Page";
            $form->page = 'menu';
            $form->action = 'menu-update';
            $form->entry_id = $page_id;

            // Load form values
            $form->entry = $this->get_page_data_by_id($page_id);

            $available_types = $this->_get_available_page_types();

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'page_name',
                    'label'=>'Display Name'
                ),
                array(
                    'name'=>'page_slug',
                    'label'=>'Slug (Web Address)',
                    'tooltip' => 'This can be either relative (page-slug) or '
                            . 'absolute (http://example.com/)'
                ),
                array(
                    'type'=>'select',
                    'name'=>'type',
                    'label'=>'Class Type',
                    'options' => $available_types
                ),
                array(
                    'name'=>'menu_order',
                    'label'=>'Order'
                ),
                array(
                    'name'=>'show_full',
                    'label'=>'Show Entry Full Page?'
                ),
                array(
                    'name'=>'hide_in_menu',
                    'label'=>'Hide From the Menu?'
                ),
                array(
                    'name'=>'parent_id',
                    'label'=>'Make this a submenu of what page?'
                ),
                array(
                    'name'=>'extra',
                    'label'=>'Extra'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                ),
                array(
                    'type'=>'hidden',
                    'name'=>'page_id',
                    'value'=>$page_id
                )
            );

            echo $form;
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }
    }

    public function update_menu()
    {
        // Clean up the posted data
        foreach( $_POST as $key=>$val )
        {
//            if( $key==='page_slug' && SIV::validate($val, SIV::SLUG) )
//            {
//                $$key = $val;
//            }
//            else
//            {
                //TODO Add error handling and send back to form
//            }
            $$key = SIV::clean_output($val, FALSE, FALSE);
        }

        $sql = 'INSERT INTO `'.DB_NAME.'`.`'.DB_PREFIX.'pages`
                (
                    `page_id`, `page_name`, `page_slug`, `type`, `menu_order`,
                    `show_full`, `hide_in_menu`, `parent_id`, `extra`
                )
                VALUES
                (
                    :page_id, :page_name, :page_slug, :type, :menu_order,
                    :show_full, :hide_in_menu, :parent_id, :extra
                )
                ON DUPLICATE KEY UPDATE
                    `page_name`=:page_name, `page_slug`=:page_slug,
                    `type`=:type, `menu_order`=:menu_order,
                    `show_full`=:show_full, `hide_in_menu`=:hide_in_menu,
                    `parent_id`=:parent_id, `extra`=:extra';

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page_id", $page_id, PDO::PARAM_INT);
            $stmt->bindParam(":page_name", $page_name, PDO::PARAM_STR);
            $stmt->bindParam(":page_slug", $page_slug, PDO::PARAM_STR);
            $stmt->bindParam(":type", $type, PDO::PARAM_STR);
            $stmt->bindParam(":menu_order", $menu_order, PDO::PARAM_INT);
            $stmt->bindParam(":show_full", $show_full, PDO::PARAM_INT);
            $stmt->bindParam(":hide_in_menu", $hide_in_menu, PDO::PARAM_INT);
            $stmt->bindParam(":parent_id", $parent_id, PDO::PARAM_INT);
            $stmt->bindParam(":extra", $extra, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->errorCode()==='00000';

            $stmt->closeCursor();

            return $result;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _sort_menu_data( $menu_array=array() )
    {
        foreach( $menu_array as $page )
        {
            if( (int) $page['parent_id']===0 )
            {
                // This feels clunky and could probably be done much better
                if( array_key_exists($page['menu_order'], $this->_menu) )
                {
                    if( array_key_exists('sub', $this->_menu[$page['menu_order']]) )
                    {
                        $sub = $this->_menu[$page['menu_order']]['sub'];
                    }
                    else
                    {
                        $sub = NULL;
                    }
                    $this->_menu[$page['menu_order']] = $page;
                    $this->_menu[$page['menu_order']]['sub'] = $sub;
                }
                else
                {
                    $this->_menu[$page['menu_order']] = $page;
                }
            }
            else
            {
                $parent_id = $page['parent_id'];
                foreach( $menu_array as $val )
                {
                    if( $val['page_id']==$parent_id )
                    {
                        $key_to_use = $val['menu_order'];
                        break;
                    }
                }
                $this->_menu[$key_to_use]['sub'][$page['menu_order']] = $page;
            }
        }
        ksort($this->_menu);
    }

    private function _get_available_page_types( $dir=NULL, &$page_types=array() )
    {
        $dir_array = array(
                './assets/plugins/',
                CMS_PATH . 'page/'
            );

        if( isset($dir) )
        {
            $dir_array = array($dir.'/');
        }

        foreach( $dir_array as $dir )
        {
            if( is_dir($dir) )
            {
                $folder = opendir($dir);
                if( $folder!==FALSE )
                {
                    while( FALSE!==($file=readdir($folder)) )
                    {
                        if( $file !=='.' && $file!=='..' && is_file($dir.$file)
                                && substr($file, -4)==='.php' )
                        {
                            $pat = array(
                                    '/ecms\.([\w-]+?)\.inc\.php/i',
                                    '/class\.([\w-]+?)\.inc\.php/i'
                                );
                            $rep = array('$1', '$1');
                            $page_types[] = ucfirst(preg_replace($pat, $rep, $file));
                        }
                        else if( is_dir($dir.$file) && $file !=='.' && $file!=='..' )
                        {
                            $this->_get_available_page_types($dir.$file, $page_types);
                        }
                    }

                    closedir($folder);
                }
                else
                {
                    throw new Exception("Couldn't open $dir!");
                }
            }
        }

        return $page_types;
    }

    public function __toString()
    {
        return self::generate_menu_markup();
    }

}
