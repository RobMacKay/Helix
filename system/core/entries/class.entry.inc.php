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
class Entry
{

    public $entry_id="",
           $page_id="",
           $title="",
           $entry="",
           $excerpt="",
           $slug="",
           $tags="",
           $extra_props=array(),
           $author="",
           $created="";

    public function __construct( $entry_array=array() )
    {
        // If the configuration was passed in individual variables, store them
        if ( !is_array($entry_array) )
        {
            throw new Exception("Entry data must be an array.");
        }
        else
        {
            // Loop through the array and store the values
            foreach( $entry_array as $key=>$val )
            {
                // Store each property
                $this->$key = $val;
            }
        }
    }

    public function __set( $name, $val )
    {
        $this->extra_props[$name] = $val;
    }

    public function __get( $name )
    {
        return isset($this->extra_props[$name]) ? $this->extra_props[$name] : NULL;
    }

    public function __isset( $name )
    {
        return isset($this->extra_props[$name]);
    }
}
