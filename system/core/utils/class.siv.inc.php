<?php
/**
 * Methods to validate various types of data
 *
 * SIV stands for Simple Input Validation. It aims to add an
 * easy-to-use sanitization and validation utility to any project.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class SIV
{
    /**
     * A regex for alphanumeric strings with spaces and hyphens
     * @var string
     */
    const STRING = '/^[\w\s-]+$/';

    /**
     * A regex to validate email addresses
     * @var string
     */
    const EMAIL = '/^[\w-+]+(\.[\w-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i';

    /**
     * A regular expression for alphanumeric strings with spaces and hyphens
     * @var string
     * TODO: Add a real URL validation regex
     */
    const URL = '/^.*$/';

    /**
     * A regex to validate a 8-20 character alphanumeric w/hyphens username
     * @var string
     */
    const USERNAME = '/[\w-]{8,20}/';

    /**
     * A regex to validate a page with letters a-z, numbers 0-9, and/or hyphens
     * @var string
     */
    const SLUG = '/[a-z0-9-]{1,64}/i';

    /**
     * A generic validation function
     *
     * @param string $string    String to be validated
     * @param string $pattern   Regular expression with which to validate input
     * @return bool             Whether or not the string validates
     */
    public static function validate( $string, $pattern=self::STRING)
    {
        return preg_match($pattern, $string)===1 ? TRUE : FALSE;
    }

    /**
     * Validates an email string, does NOT check mx records.
     *
     * @param str $email - The email adrress to validate.
     * @return bool
     *
     */
    public static function isValidEmail($email)
    {
        return self::validate($email, self::EMAIL);
    }

    /**
     * Check if integer is even, also see isOdd()
     * Uses bit shifting instead of modulus for speed (and funsies).
     *
     * @param int integer to check 
     * @return bool 
     */
    public static function isEven($int)
    {
        return !((int)$int&1) ? TRUE : FALSE;
    }

    /**
     * Check if integer is even, also see isEven()
     * Uses bit shifting instead of modulus for speed (and funsies).
     *
     * @param int integer to check 
     * @return bool 
     */
    public static function isOdd($int)
    {
        return ((int)$int&1) ? TRUE : FALSE;
    }

    public static function clean_output( $str, $preserve_tags=TRUE, $preserve_newlines=TRUE )
    {
        // Standardize newlines for easier handling
        $str = str_replace(array("\r\n", "\r"), "\n", trim($str));

        if( $preserve_tags===FALSE )
        {
            $str = strip_tags($str);
        }

        // Convert HTML entities, don't double-encode
        $str = htmlentities($str, ENT_QUOTES, 'UTF-8', FALSE);

        // Fix MS Word weird characters and non-English characters
        $tr_tbl = array(
                chr(128) => "&#x80;", // € (Euro)
                chr(133) => "&#x85;", // … (ellipsis)
                chr(145) => "&#x91;", // ‘ (left single quote)
                chr(146) => "&#x92;", // ’ (right single quote)
                chr(147) => "&#x93;", // “ (left double quote)
                chr(148) => "&#x94;", // ” (right double quote)
                chr(149) => "&#x95;", // • (bullet)
                chr(150) => "&#x96;", // – (en dash)
                chr(151) => "&#x97;", // — (em dash)
                chr(153) => "&#x99;", // ™ (trademark)
                chr(169) => "&#xa9;", // © (copyright)
                chr(174) => "&#xae;" // ® (reserved)
            );
        $str = strtr($str, $tr_tbl);

        // Remove newlines if the flag is set
        $pat = "/\n+/";
        $rep = $preserve_newlines===TRUE ? "\n\n" : '';
        $str = preg_replace($pat, $rep, trim($str));

        // Clean up post
        $pat = array(
                "/&lt;/",
                "/&gt;/",
                "/&quot;/",
                "/&nbsp;/",
                "/<(?:span|div)(?:.*?)>/",
                "/<\/(?:span|div)>/",
                "/<h1(?:.*?)>/",
                "/<\/h1>/",
                "/[^\n\w\s<>&'\"\/\?#:;,\.=\-\[\]\(\)!%\$@\+\*\\\^~`|{}]/"
            );
        $rep = array(
                '<',
                '>',
                '"',
                ' ',
                '',
                '',
                '<h2>',
                '</h2>',
                ""
            );

        if( $preserve_tags===TRUE )
        {
            $ptags_pat = array(
//                    "/(?<!(?:[pe1-6]>))\n+(?!<[phb])/", // No closing or opening
//                    "/(?<!(?:[pe1-6]>))\n+(?=<[phb])/is", // No closing, opening
//                    "/(?<=(?:[pe1-6]>))\n+(?!<[phb])/is", // Closing, no opening
                    "/<blockquote.*?>\n+(?!<p)/", // Starting blockquote
                    "/(?<!p>)\n+<\/blockquote>/", // Closing blockquote
                    "/^(?!<[phb])/is", // Beginning of the string
                    "/(?<!(?:[pe1-6]>))$/is" // End of string
                );
            $ptags_rep = array(
//                    "</p>\n\n<p>",
//                    "</p>\n\n",
//                    "\n\n<p>",
                    "<blockquote>\n<p>",
                    "</p>\n<blockquote>",
                    "<p>",
                    "$1</p>"
                );
            $pat = array_merge($pat, $ptags_pat);
            $rep = array_merge($rep, $ptags_rep);
        }

        // Clean up weird spacing
        $pat[] = "/[ \t]+/";
        $rep[] = " ";

        return preg_replace($pat, $rep, $str);
    }

    static function strip_tags_attr($str, $allowable_tags=NULL, $clean_attr_content=TRUE)
    {
        $whitelist = NULL;
        if( isset($allowable_tags) )
        {
            // Make sure the arguments are in an acceptable format
            $arg_check = '/^(?:<([a-z])+(?:\[([a-z|])*\])?>)+$/i';
            if( preg_match($arg_check, $allowable_tags) )
            {
                // Match individual tags and the optional attribute whitelist
                $tag_match = '/<([a-z]+)(?:\[([a-z|]*)\])?>/i';
                preg_match_all($tag_match, $allowable_tags, $tags);

                // Loop through the matches and perform the checks
                for( $i=0, $c=count($tags[0]); $i<$c; ++$i )
                {
                    // Add the tag name to the whitelist
                    $whitelist .= "<{$tags[1][$i]}>";

                    // Get all attributes out of the tag
                    $pattern = "/<{$tags[1][$i]}(.*?)>/i";
                    preg_match_all($pattern, $str, $matches);

                    // Break the allowed attributes into an array
                    $allowed_attr_array = explode("|", $tags[2][$i]);
                    FB::log($allowed_attr_array, "Allowed attributes for <{$tags[1][$i]}>");

                    // Break the tag attributes into an array
                    $attrs = explode(' ', trim($matches[1][0]));

                    // Loop through the tag attributes
                    $new_attrs = NULL;
                    foreach( $attrs as $attr )
                    {
                        // If no attribute exists, continue
                        if( empty($attr) )
                        {
                            continue;
                        }

                        // Split the attributes into key=>value pairs
                        $split_attr = explode("=", $attr);

                        // If the attribute is allowed, preserve its value
                        if( in_array($split_attr[0], $allowed_attr_array) )
                        {
                            // If $clean_attr_content is TRUE, validate
                            if( $clean_attr_content===TRUE )
                            {
                                $attr_val = self::clean_output($split_attr[1], FALSE, FALSE);
                            }
                            else
                            {
                                $attr_val = $split_attr[1];
                            }

                            // Add the attribute to the new attribute string
                            $new_attrs .= ' '.$split_attr[0].'='.$attr_val;
                        }
                    }

                    $rep = '<'.$tags[1][$i].$new_attrs.'>';
                    $str = preg_replace($pattern, $rep, $str);
                }
            }
            else
            {
                break;
            }
        }

        return strip_tags($str, $whitelist);
    }

}
