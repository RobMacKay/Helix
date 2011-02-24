<?php

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
class SiteAdmin extends DB_Actions
{

    public function __construct(  )
    {

    }

    //TODO Add a menu
    public function display_site_options(  )
    {
        // Make sure the user is logged in before showing any options
        if( AdminUtilities::check_clearance(1) )
        {
            // Set up the break for menu items
            $tab = str_repeat(' ', 4);
            $break = "</li>\n$tab<li>";

            // Create the unordered list and display user info
            $options = '<ul id="admin-site-options">' . "\n" . $tab 
                    . '<li class="info-box">You are logged in as <strong>'
                    . $_SESSION['user']['name'] . '</strong>' . $break;

            // If the user has clearance, allow for site page & category editing
            if( AdminUtilities::check_clearance(2) )
            {
                $options .= '<a href="/siteadmin/pages">Edit Site Pages</a>'
                        . $break
                        . '<a href="/siteadmin/categories">Edit Entry '
                        . 'Categories<a/>' . $break;
            }

            // If the user has high enough clearance, they can manage admins
            if( AdminUtilities::check_clearance(2) )
            {
                $options .= '<a href="/admin/manage">Manage Administrators</a>'
                        . $break;
            }

            return $options . '<a href="/admin/logout">Logout</a></li>' . "\n"
                . '</ul><!-- end #admin-site-options -->';
        }

        // Return nothing to users who are not logged in
        else
        {
            return NULL;
        }
    }

    

}
