<?php

require_once 'ChromePhp.php';
require_once 'fb.php';

/**
 * A debugging class to wrap FirePHP and ChromePHP, plus write to an error log
 *
 * PHP version 5
 *
 * LICENSE: Dual licensed under the MIT or GPL licenses.
 *
 * @author    Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @copyright 2011 Copter Labs
 * @license   http://www.opensource.org/licenses/mit-license.html
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */
class DBG
{
    static $debugger;

    public static function create(  )
    {

    }

    public function __call( $name, $msg )
    {
        if( method_exists(self::$debugger, $name) )
        {
            self::$debugger->$name($msg);
        }
    }

    /**
     * Writes an error message to the log (ennui-cms/log/exception.log)
     *
     * @param string $message   The error message
     * @return void
     */
    private static function _write_log( $message )
    {
        // Creates a pointer to the log
        $log = fopen(CMS_PATH . 'log/exception.log', 'a');

        // Appends the message to the log
        fwrite($log, $message);

        // Frees the resource
        fclose($log);
    }

    private function __construct(  )
    {
        // Handles debugging. If TRUE, displays all errors and enables FirePHP logging
        if( ACTIVATE_DEBUG_MODE===TRUE )
        {
            ini_set("display_errors",1);
            ERROR_REPORTING(E_ALL);
            FB::setEnabled(TRUE);
            FB::warn("FirePHP logging is enabled! Sensitive data may be exposed.");
            ChromePhp::warn("ChromePHP logging is enabled! Sensitive data may be exposed.");
        }
        else
        {
            ini_set("display_errors",0);
            error_reporting(0);
            FB::setEnabled(FALSE);
        }
    }

}
