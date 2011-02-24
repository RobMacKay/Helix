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
class Input
{

    public $type = "text",
           $label = "",
           $value = "",
           $name = "",
           $id = "",
           $class = "",
           $options = array(),
           $checked = "";

    public function __construct( $config_or_type=array(), $label=NULL,
            $value=NULL, $name=NULL, $id=NULL, $class=NULL, $options=array() )
    {
        // If the configuration was passed in individual variables, store them
        if ( !is_array($config_or_type) )
        {
            $this->type = htmlentities($config_or_type, ENT_QUOTES);
            $this->label = htmlentities($label, ENT_QUOTES);
            $this->value = htmlentities($value, ENT_QUOTES);
            $this->name = htmlentities($name, ENT_QUOTES);
            $this->id = htmlentities($id, ENT_QUOTES);
            $this->class = htmlentities($class, ENT_QUOTES);
            $this->options = array_map('htmlentities', $options, array(ENT_QUOTES));
        }
        else
        {
            // Loop through the array and store the values
            foreach( $config_or_type as $key=>$val )
            {
                // Make sure the property exists before storing a value
                if ( isset($this->$key) )
                {
                    if( !is_array($val) )
                    {
                        $this->$key = htmlentities($val, ENT_QUOTES);
                    }
                    else
                    {
                        $this->$key = array_map('htmlentities', $val, array(ENT_QUOTES));
                    }
                }
            }
        }
    }

}
