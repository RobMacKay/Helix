<?php

/**
 * Methods to display and edit pages with multiple simple entries
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Multi extends Page
{

    /**
     * Loads the page entries and outputs HTML markup to display them
     *
     * @return string the formatted entries
     */
    public function display_public()
    {
        // If logged in, show the admin options (if JavaScript is disabled)
        if( isset($this->url1) && $this->url1==='admin'
                && AdminUtilities::check_clearance(1) )
        {
            // Load the entry ID if one was passed
            $id = isset($this->url2) ? (int) $this->url2 : NULL;

            // Output the admin controls
            return $this->display_admin($id);
        }

        // If an entry URL is passed, load that entry only and output it
        else if( isset($this->url1) && $this->url1!=='more' )
        {
            // Load the entry by its URL
            $this->get_entry_by_url($this->url1);

            // Avoid a notice
            $extra = (object) array();

            // Set the template
            $this->template = $this->url0 . '-full.inc';
        }

        // Displays the entries for the page
        else
        {
            // If the entries are paginated, this determines what page to show
            if ( isset($this->url1) && $this->url1==='more' )
            {
                $offset = isset($this->url2) ? $limit*($this->url2-1) : 0;
            }
            else
            {
                $offset = 0;
            }

            // Load most recent entries for a preview if no entry was selected
            $this->get_all_entries($offset);

            // Add the admin options for preview entries
            $extra->header->admin = $this->admin_general_options($this->url0);

            // Set the template
            $this->template = $this->url0 . '-preview.inc';
        }

        // Organize the data
        $this->generate_template_tags();

        // Return the entry as formatted by the template
        return $this->generate_markup($extra);
    }

    /**
     * Outputs the editing controls for a given entry
     *
     * @param int $id the ID of the entry to be edited
     * @return string HTML markup to display the editing form
     */
    public function display_admin(  )
    {
        try
        {
            // Load form values
            $this->get_entry_by_id((int) $_POST['entry_id']);

            // Create a new form object and set submission properties
            $form = new Form(array('legend'=>'Create a New Entry'));
            $form->form_id = 'ecms-edit-form';
            $form->page = $this->url0;
            $form->action = 'entry-write';
            $form->entry_id = (int) $_POST['entry_id'];

            // Make the entry values available to the form if they exist
            $form->entry = isset($this->entries[0]) ? $this->entries[0] : array();

            // Set up input information
            $input_arr = array(
                array(
                    'name'=>'title',
                    'class'=>'input-text',
                    'label'=>'Entry Title'
                ),
                array(
                    'type' => 'textarea',
                    'class'=>'input-textarea',
                    'name'=>'entry',
                    'label'=>'Entry Body'
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
                    'label'=>'URL'
                ),
                array(
                    'type' => 'submit',
                    'class'=>'input-submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                )
            );

            // Build the inputs
            foreach( $input_arr as $input )
            {
                $form->input($input);
            }
        }
        catch( Exception $e )
        {
            ECMS_Error::logException($e);
        }

        return $form;
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
    }

}
