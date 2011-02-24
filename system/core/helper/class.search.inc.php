<?php

// Load required helper classes
require_once CMS_PATH . 'core/helper/class.comments.inc.php';

/**
 * Class description
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
class Search extends Page
{

    public function display_public()
    {
        if( !empty($this->url1) )
        {
            // To make sure entry links for pagination work properly
            $extra->footer->pagination = $this->paginate_entries();

            $this->url0 = 'blog';

            // Get the current page of entries being displayed
            $current_num = isset($this->url2) ? $this->url2 : 1;

            // What entry to use as the starting point
            $start_num = BLOG_PREVIEW_NUM*$current_num-BLOG_PREVIEW_NUM;
            if($start_num< 0)
            {
                $start_num = 0;
            }

            // Sanitize the search string
            $search = htmlentities(urldecode(trim($this->url1)), ENT_QUOTES);

            // Load the entries that match the search
            $this->entry_limit = BLOG_PREVIEW_NUM;
            $this->get_entries_by_search($search, $start_num);

            $this->template = 'search.inc';
        }

        else
        {
            header( 'Location: /' );
            exit;
        }

        // Organize the data
        $this->generate_template_tags();

        // Extra markup for the template header and/or footer
        $extra->header->title = 'Search Results for "'
                . urldecode($this->url1) . '" <span>('
                . $this->get_entry_count_by_search($this->url1) 
                . ' entries found)</span>';

        // Return the entry as formatted by the template
        return $this->generate_markup($extra);
    }

    public function handle_search(  )
    {
        $search_string = urlencode(SIV::clean_output($_POST['search_string'], FALSE, FALSE));
        header('Location: /search/' . $search_string);
        exit;
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags()
    {
        FB::log(debug_backtrace());
        parent::generate_template_tags();

        // Add custom tags here
        foreach( $this->entries as $entry )
        {
            $entry->comment_count = $this->get_comment_count($entry->entry_id);
            $entry->comment_text = $entry->comment_count===1 ? 'comment' : 'comments';

            $entry->tags = $this->_format_tags($entry->tags);
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

	public static function displaySearchBox( $legend="Search the Site" )
	{
        try
		{
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = $legend;
            $form->form_id = 'search-form';

            // Set up hidden form values
            $form->page = 'search';
            $form->action = 'entry-search';

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'search_string',
                    'id'=>'search-string',
                    'label'=>'Search Text',
                    'class'=>'input-text'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'search-submit',
                    'value' => 'Search',
                    'class' => 'input-submit'
                )
            );

            return $form;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
	}

}
