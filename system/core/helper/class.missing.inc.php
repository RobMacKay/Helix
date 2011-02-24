<?php

class Missing extends Single
{

	public function display_public()
	{
        //TODO Missing pages send a 404 header
        $entry = new Entry;
        $entry->admin = '';
        $entry->slug = '';
        $entry->title = 'That Page Doesn\'t Exist';
        $entry->entry = "<p>If you feel you've reached this page in error, "
                . "please <a href=\"mailto:" . SITE_CONTACT_EMAIL
                . "\">contact the site administrator</a> and let us know.</p>\n"
                . "<p>Sorry for the inconvenience!</p>";
        $this->entries[] = $entry;

        $this->template = 'default.inc';

        // Organize the data
        $this->generate_template_tags();

        // Return the entry as formatted by the template
        return $this->generate_markup($this->entries);
	}

}
