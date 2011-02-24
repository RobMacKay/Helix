<?php

/**
 * A database connection class
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
class DB_Connect
{

    /**
     * A database object for the Ennui CMS
     *
     * @var object  A PDO object
     */
    public $db;

    /**
     * A method to instantiate the database object if one doesn't exist
     * 
     * @return void
     */
    protected function __construct()
    {
        //TODO: Look into a Singleton pattern for this
        if ( !isset($this->db) )
        {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
            try
            {
                // Creates a new
                $this->db = new PDO($dsn, DB_USER, DB_PASS);
                return;
            }
            catch ( Exception $e )
            {
                // Logs the full error stack and die with the message
                FB::log($e);
                die ( "Database Error: ".$e->getMessage() );
            }
        }
    }

    public static function create(  )
    {
        return new self;
    }

}
