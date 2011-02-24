<?php

/**
 * Abstract class to provide the basic structure of a Helix plugin
 *
 * PHP version 5
 *
 * LICENSE: Dual licensed under the MIT or GPL licenses.
 *
 * @author    Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2011 Copter Labs, Inc.
 * @license   MIT License <http://www.opensource.org/licenses/mit-license.html>
 * @license   GPL License <http://www.gnu.org/licenses/gpl-3.0.txt>
 */
abstract class Helix_Plugin extends Page
{
    public function __construct( $url_array=array() )
    {
        parent::__construct($url_array);

        $this->initialize();
    }

    protected function initialize(  )
    {
        if( BUILD_DATABASE===TRUE )
        {
            $sql_filepath = 'assets/plugins/' . strtolower($this->page_type)
                    . '/assets/sql/build_plugin_tables.sql';

            // If custom DB tables are required for the plugin, build them here
            if( file_exists($sql_filepath) and is_readable($sql_filepath) )
            {
                $sql = Utilities::load_file($sql_filepath);

                try
                {
                    $this->db->query($sql);
                }
                catch( Exception $e )
                {
                    ECMS_Error::log_exception($e);
                }
            }
        }

        // Add custom actions for the plugin or allow overwrite of core actions
        $this->access_points = array_merge($this->access_points, $this->register_custom_actions());
    }

    /**
     * Method to determine output for the plugin when the public view is loaded
     * @abstract
     */
    abstract public function display_public(  );

    /**
     * Outputs the markup to display editing controls for the plugin
     * @abstract
     */
    abstract public function display_admin(  );

    /**
     * Defines custom actions for form processing and the methods they call
     *
     * Example:
     * <code>
     *      protected function register_custom_actions(  )
     *      {
     *          return array(
     *              'custom-action' => 'custom_method'
     *          );
     *      }
     * </code>
     *
     * @abstract
     */
    abstract protected function register_custom_actions(  );

}
