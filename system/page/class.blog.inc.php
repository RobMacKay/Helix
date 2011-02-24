<?php

class Blog extends Page
{

    /**
     * Loads the page entries and outputs HTML markup to display them
     *
     * @return string The formatted entries
     */
    public function display_public()
    {
        // If an entry URL is passed, load the corresponding data
        if( isset($this->url1) && $this->url1!=='tag' )
        {
            $this->get_entry_by_url($this->url1);

            $this->template = $this->url0 . '-full.inc';
        }

        // If viewing by tag, load the corresponding entries
        else if( $this->url1==='tag' )
        {
            // If no tag was passed in the URL, send the user to the main page
            if( empty($this->url2) )
            {
                header('Location: /'.$this->url0);
            }

            // The page of entries to display
            $url3 = isset($this->url3) ? $this->url3 : 1;

            // Total number of entries to display per page
            $this->entry_limit = BLOG_PREVIEW_NUM;
            $prev = BLOG_PREVIEW_NUM;

            // Determine the entry to use as the starting point
            $start_num = $prev*$url3-$prev>0 ? $prev*$url3-$prev : 0;

            // If this is a tag supplied, load corresponding entries
            if( $this->url2!=='recent' )
            {
                $extra->header->title = "Entries About "
                        . ucwords(str_replace('-', ' ', $this->url2));

                $this->get_entries_by_tag($this->url2, $start_num);
            }

            // If recent entries are being displayed, load them here
            else
            {
                $extra->header->title = "Recent Entries, Page " . $this->url3;

                $this->get_all_entries($start_num);
            }

            $this->template = $this->url0 . '-search.inc';
        }

        // If no parameters were passed, get the latest entries
        else
        {
            $this->entry_limit = BLOG_PREVIEW_NUM;
            $this->get_all_entries();

            $this->template = $this->url0 . '-preview.inc';
        }

        // Organize the data
        $this->generate_template_tags();

        // Extra markup for the template header and/or footer
        $extra->header->admin = $this->admin_general_options($this->url0);
        $extra->footer->pagination = $this->paginate_entries();

        // Return the entry as formatted by the template
        return $this->generate_markup($extra);
    }

    public function display_admin(  )
    {
        try
        {
            // Load form values
            $this->get_entry_by_id((int) $_POST['entry_id']);

            // Create a new form object and set submission properties
            $form = new Form();
            $form->form_id = 'ecms-edit-form';

            $form->page = $this->url0;
            $form->action = 'entry-write';
            $form->entry_id = (int) $_POST['entry_id'];

            // Make the entry values available to the form if they exist
            $form->entry = isset($this->entries[0]) ? $this->entries[0] : array();

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'title',
                    'class'=>'input-text',
                    'label'=>'Entry Title'
                ),
                array(
                    'type'=>'file',
                    'name'=>'image',
                    'class'=>'input-text',
                    'label'=>'Main Image'
                ),
                array(
                    'name'=>'caption',
                    'class'=>'input-text',
                    'label'=>'Main Image Caption'
                ),
                array(
                    'type' => 'textarea',
                    'class'=>'input-textarea',
                    'name'=>'entry',
                    'label'=>'Entry Body'
                ),
                array(
                    'name'=>'tags',
                    'class'=>'input-text',
                    'label'=>'Tags'
                ),
                array(
                    'type' => 'textarea',
                    'class'=>'input-textarea',
                    'name'=>'excerpt',
                    'label'=>'Excerpt (Meta Description)'
                ),
                array(
                    'name'=>'slug',
                    'class'=>'input-text',
                    'label'=>'URL Slug for This Entry'
                ),
                array(
                    'type' => 'submit',
                    'class'=>'input-submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                )
            );

            return $form;
        }
        catch ( Exception $e )
        {
            Error::logException($e);
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
        parent::generate_template_tags();

        // Add custom tags here
        foreach( $this->entries as $entry )
        {
            $entry->comment_count = $this->get_comment_count($entry->entry_id);
            $entry->comment_text = $entry->comment_count===1 ? 'comment' : 'comments';

            $entry->tags = $this->_format_tags($entry->tags);

            // For full entries, load comments and the comment form
            if( !empty($this->url1) )
            {
                $comments = new Comments(array('comments'));
                $entry->comments = $comments->display_entry_comments($entry->entry_id);
            }
        }
    }

    private function _format_tags( $tags )
    {
        $markup = NULL;

        $c = array_map('trim', explode(',', $tags));

        for( $i=0, $count=count($c); $i<$count; ++$i )
        {
            $tag = str_replace(' ', '-', $c[$i]);
            $markup .= "<a href=\"/$this->url0/tag/$tag/\">$c[$i]</a>";
            $comma = ($count > 2) ? ',' : NULL;

            if( $i < $count-2 )
            {
                $markup .= $comma.' ';
            }

            if( $i == $count-2 )
            {
                $markup .= $comma.' and ';
            }
        }

        return $markup;
    }

    static function display_categories( $n=10, $page_slug='blog', $class='categories', $twocol=FALSE )
    {
        $cat = self::_get_popular_categories($page_slug);

        $i = 0;
        $list = NULL;
        foreach ( $cat as $category => $number )
        {
            if ( ++$i>$n )
            {
                break;
            }
            else if ( $i%($n/2+1)==0 && $twocol===TRUE )
            {
                $list .= "
                    </ul>
                    <ul class=\"$class\">";
            }

            $list .= "
                        <li> <a href=\"/$page_slug/category/$category\">"
                . ucwords(str_replace('-', ' ', $category)) . "</a></li>";
        }

        return "
                    <ul class=\"$class\">$list
                    </ul>";
    }

    private function _get_popular_categories( $page_slug )
    {
        $category_array = array();

        try
        {
            $sql = "SELECT `tags`
                    FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                        WHERE `page_id`=(
                            SELECT `page_id`
                            FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                                WHERE `page_slug`=:page_slug
                                LIMIT 0, 1
                        )";

            $stmt = DB_Connect::create()->db->prepare($sql);
            $stmt->bindParam(':page_slug', $page_slug, PDO::PARAM_STR);
            $stmt->execute();
            $categories_array = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach( $categories_array as $categories )
            {
                $temp_array = explode(',', strtolower($categories->tags));
                foreach( $temp_array as $category )
                {
                    if ( empty($category) )
                    {
                        continue;
                    }

                    $c = str_replace(' ', '-', trim($category));
                    if(array_key_exists($c, $category_array))
                    {
                        $category_array[$c] += 1;
                    }
                    else
                    {
                        $category_array[$c] = 1;
                    }
                }
            }
            $stmt->closeCursor();
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }

        arsort($category_array);
        return $category_array;
    }

    public static function display_posts($num=8, $page='blog', $filter="recent", $id="latest-blogs")
    {
        // Load the page ID
        $page = Menu::get_page_data_by_slug($page);

        // Determine which posts to retreive
        if ( $filter=="recent" )
        {
            $sql = "SELECT `title`, `slug`
                    FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                    WHERE `page_id`='$page->page_id'
                    ORDER BY `created` DESC
                    LIMIT 0, $num";
        }
        elseif ( $filter=="featured" )
        {
            $sql = "SELECT `entry_id`, `title`, `slug`
                    FROM `".DB_NAME."`.`".DB_PREFIX."featured`
                    LEFT JOIN `".DB_NAME."`.`".DB_PREFIX."entries`
                    USING( `entry_id` )
                    LIMIT 0, $num";
        }

        //TODO: Convert to PDO
        $dbo = new DB_Connect();
        try
        {
            $stmt = $dbo->db->prepare($sql);
            $stmt->execute();

            $list = NULL;
            $lb = "\n".str_repeat(' ', 24);
            while( $entry = $stmt->fetch(PDO::FETCH_OBJ) )
            {
                $url = isset($entry->slug) ? $entry->slug : urlencode($entry->title);
                $link = SITE_URL . $page->page_slug . "/" . $url;
                $title = stripslashes($entry->title);
                $list .= "$lb<li><a href=\"$link\">$title</a></li>";
            }
            $stmt->closeCursor();
        }
        catch ( Exception $e )
        {
            FB::error($e);
            throw new Exception ( "Could not load entries." );
        }

        $lb = "\n" . str_repeat(' ', 20);
        return "$lb<ul id=\"$id\">$list$lb</ul><!-- end #$id -->";
    }

    static function display_most_commented($num=8, $page='blog')
    {
            $dbo = new DB_Connect();

            /*
             * Load comment counts and titles for the
             */
            $sql = "SELECT
                COUNT(*) AS `num_comments`, `title`, `slug`
                            FROM `".DB_PREFIX."comments`
                            LEFT JOIN `".DB_PREFIX."entries`
                                    USING( `entry_id` )
                            GROUP BY `".DB_PREFIX."comments`.`entry_id`
                            ORDER BY `num_comments` DESC
                            LIMIT 0, $num";
            try
            {
                    $stmt = $dbo->db->prepare($sql);
        $stmt->execute();

        $list = NULL;
        $lb = "\n".str_repeat(' ', 24);
                    while ( $entry = $stmt->fetch(PDO::FETCH_OBJ) )
                    {
                            $title = stripslashes($entry->title);
            $url = isset($entry->slug) ? $entry->slug : urlencode($entry->title);
                            $link = "/$page/" . $url;
                            $list .= "$lb<li><a href=\"$link\">$title</a></li>";
                    }

        $lb = "\n" . str_repeat(' ', 20);
                    return "$lb<ul id=\"most-commented\">$list$lb</ul><!-- end #most-commented -->";
            }
            catch ( Exception $e )
            {
                    FB::log($e);
        throw new Exception ( "Couldn't load popular entries." );
            }
    }

}
