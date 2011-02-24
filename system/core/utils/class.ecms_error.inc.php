<?php

/**
 * An error-handling class
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
class ECMS_Error
{

    /**
     * Logs an exception
     *
     * @param object $exception_object  The Exception object
     * @param bool $is_fatal            Whether or not to stop execution
     * @return void
     */
    public static function log_exception( $exception_object, $is_fatal=TRUE )
    {
        if ( class_exists('FB') )
        {
            FB::error($exception_object);
        }

        // Generates an error message
        $trace = array_pop($exception_object->getTrace());
        $arg_str = implode(',', $trace['args']);
        $method = isset($trace['class']) ? "$trace[class]::$trace[function]" : $trace['function'];
        $err = "[" . date("Y-m-d h:i:s") . "] " . $exception_object->getFile()
                . ":" . $exception_object->getLine()
                . " - Error with message \""
                . $exception_object->getMessage() . "\" was thrown from "
                . "$method ($trace[file]:$trace[line])"
                . " with arguments: ('" . implode("', '", $trace['args'])
                . "')\n";

        // Logs the error message
        self::_write_log($err);

        // Stop script execution if the error was fatal
        if ( $is_fatal===TRUE )
        {
            die( $exception_object->getMessage() );
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
}
