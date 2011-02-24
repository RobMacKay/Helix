<?php

/**
 * A set of utility functions
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
class Utilities
{

    /**
     * Generates an excerpt of a given number of words
     *
     * @param string $text  The text to excerpt
     * @param int $limit    The number of words to include in the excerpt
     * @return string       The excerpt
     */
    public static function text_preview( $text, $limit=45, $wrap_ptags=TRUE )
    {
        /*
         * Remove newlines, replace heading tags with strong tags, and swap out
         * paragraph tags for line breaks
         */
        $pat = array(
                "/\n++/is",
                "/<h(?:2|3)(.*?)>/i",
                "/<\/h(?:2|3)>/i",
                "/<p>/i",
                "/<\/p>(?:\n*)/is"
            );
        $rep = array(
                "",
                "<strong>",
                "</strong><br /><br />",
                "",
                "<br /><br />"
            );
        $text = preg_replace($pat, $rep, $text);

        $text = strip_tags($text, '<strong><br><a>');

        /*
         * Check for empty tags, leading line breaks, and any instances of more
         * than two line breaks to avoid broken layouts
         */
        $pat2 = array(
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\/\\1>/is",
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\\1>/is",
                "/^(?:<br ?\/>)*\s*/is",
                "/(?:<br ?\/>(?:\n|&nbsp;)*){3,}+/is"
            );
        $rep2 = array(
                "",
                "",
                "",
                "<br /><br />");
        $preview = preg_replace($pat2, $rep2, $text);


        $words = explode(' ', $preview);

        if ( $limit<count($words) )
        {
            $prev_array = array_slice($words, 0, $limit-1);

            // Remove trailing punctuation and add the last word
            array_push($prev_array, strtr($words[$limit], array('.'=>'', ','=>'')));

            // Create the string and add an ellipsis
            $preview = implode(' ', $prev_array) . '&#x2026;';
        }

        $tags_to_close = array('strong', 'a');
        foreach( $tags_to_close as $tag )
        {
            $tag_open = preg_match_all('#<'.$tag.'\b[^>]*>#is', $preview, $out);
            $tag_open = preg_match_all('#</'.$tag.'>#is', $preview, $out);
            if( $tag_open>$tag_close )
            {
                $preview .= "</$tag>";
            }
        }

        // If the flag is set, wrap the output in a paragraph tag
        return $wrap_ptags===TRUE ? '<p>' . $preview . '</p>' : $preview;
    }

    /**
     * Converts a given string to its hexadecimal equivalent with obfuscation
     *
     * @param string $string    The string to convert
     * @param int $obfuscation  The offset with which to obfuscate the string
     * @return string           The hexadecimal string
     */
    static function strtohex( $string, $obfuscation=10 )
    {
        // Loop through the string's characters and convert them to hex
        for( $hex=NULL, $i=0, $c=strlen($string); $i<$c; ++$i )
        {
            $hex .= dechex(ord($string[$i])+$obfuscation);
        }

        return $hex;
    }

    /**
     * Converts an obfuscated hexadecimal string back to the original string val
     *
     * @param string $hex       The hexadecimal value to convert
     * @param int $obfuscation  The offset with which the string was obfuscated
     * @return string           The converted string
     */
    static function hextostr( $hex, $obfuscation=10 )
    {
        $string = NULL;
        for( $string=NULL, $i=0, $c=strlen($hex); $i<$c; ++$i )
        {
            $string .= chr(hexdec($hex[$i].$hex[++$i])-$obfuscation);
        }
        return $string;
    }

    static function copyright_year($created)
    {
        $current = date('Y', time());
        return ($current>$created) ? $created.'-'.$current : $current;
    }

    public static function read_url()
    {
        // Get the document root
        $root = dirname($_SERVER['SCRIPT_FILENAME']);

        // Make sure the root has a trailing slash
        if( substr($root, -1)!=='/' )
        {
            $root .= '/';
        }

        // Get any subfolders out of the path
        $sublevels = dirname($_SERVER['SCRIPT_NAME']);

        // Load the URI
        $address_bar_uri = $_SERVER['REQUEST_URI'];

        // Remove any subfolders from consideration as variables
        if( $sublevels!=='/' )
        {
            $to_parse = str_replace($sublevels, NULL, $address_bar_uri);
        }
        else
        {
            $to_parse = $address_bar_uri;
        }

        // Separate URI variables from the query string
        $script_vars = explode('?', $to_parse);

        // Only store the URI variables
        $request = $script_vars[0];

        // Check for double slashes
        $absolute_file_path = str_replace('//', '/', $root . $request);

        // Check if the URI is requesting a valid file and load it if so
        if( file_exists($absolute_file_path)
                && $_SERVER['SCRIPT_NAME']!==$absolute_file_path
                && $request!=="/" )
        {
            // To make sure
            if( substr($absolute_file_path, -1)==='/' )
            {
                $request .= 'index.php';
            }

            FB::log($absolute_file_path, "Requested File");
            require_once $absolute_file_path;
            exit;
        }
        else
        {
            $url = SIV::clean_output($request, FALSE, FALSE);
            $url_array=explode("/",$url);
            array_shift($url_array);
        }

        if( !isset($url_array[0]) || strlen($url_array[0])<1 )
        {
            $url_array[0] = DB_Actions::get_default_page();
        }

        return $url_array;
    }

    public static function make_url($string)
    {
        if ( !empty($string) )
        {
            $pattern = array('/[^\w\s]+/', '/\s+/');
            $replace = array('', '-');
            return preg_replace($pattern, $replace, trim(strtolower($string)));
        }
        else { return NULL; }
    }

    public static function get_permalink(  )
    {
        $relative = $_SERVER['REQUEST_URI'];
        $no_hash = array_shift(explode('#', $relative));
        $no_query = array_shift(explode('?', $no_hash));
        $no_leading_slash = strpos($no_query, '/')===0 ? substr($no_query, 1) : $no_query;

        return SITE_URL . $no_leading_slash;
    }

    /**
     * Checks for the existence of a cached file with the ID passed
     *
     * @param string $cache_id  A string by which the cache is identified
     * @return mixed            The cached data if saved, else boolean FALSE
     */
    public static function check_cache($cache_id)
    {
        $cache_filepath = self::_generate_cache_filepath($cache_id);

        /*
         * If the cached file exists and is within the time limit defined in
         * CACHE_EXPIRES, load the cached data. Does not apply if the user is
         * logged in
         */
        if( file_exists($cache_filepath)
                && time()-filemtime($cache_filepath)<=CACHE_EXPIRES
                && !AdminUtilities::check_clearance(1) )
        {
            $cache = file_get_contents($cache_filepath);

            FB::warn("Data loaded from cache ($cache_filepath)");

            return unserialize($cache);
        }

        return FALSE;
    }

    /**
     * Caches data for future reuse
     * 
     * @param string $cache_id  The ID with which to identify the cached data
     * @param mixed $data       The cached data (usually an array)
     * @return string           The name of the cache file
     */
    public static function save_cache($cache_id, $data)
    {
        $cache_filepath = self::_generate_cache_filepath($cache_id);

        $fp = fopen($cache_filepath, "w");
        fwrite($fp, serialize($data));
        fclose($fp);

        return $cache_filepath;
    }

    private function _generate_cache_filepath( $cache_id )
    {
        return CACHE_DIR . md5($cache_id) . '.cache';
    }

    /**
     * Cleans a file name and stores an uploaded file
     * @param array $file_info  The info array for the uploaded file
     */
    public static function store_uploaded_file( $file_info )
    {
        $dir = FILE_SAVE_DIR;

        // Make sure all spaces are replaced with underscores
        $name = Utilities::make_url($name).'.pdf';

        // If the directory doesn't exist, create it
        if( !is_dir($dir) )
        {
            mkdir($dir, 0777, TRUE)
                    or ECMS_Error::log_exception(
                                new Exception("Could not create the directory '$dir'.")
                            );
        }

        // Place the uploaded file into the directory
        move_uploaded_file($files['tmp_name'],$dir.$name);

        return $dir.$name;
    }

    /**
     * Loads a file or an array of files into memory after parsing PHP inside
     *
     * @param mixed $filepath   A file path or array of file paths
     * @param array $var_arr    An array of variables to be passed to files
     * @return string
     */
    public static function load_file( $filepath, $var_arr=array() )
    {
        // Start an output buffer
        ob_start();

        // Check if an array of file paths was supplied
        if( is_array($filepath) )
        {
            // Loop through each path
            foreach( $filepath as $file )
            {
                // If variables for the file exist, extract and define them
                if( array_key_exists($file, $var_arr) )
                {
                    foreach( $var_arr[$file] as $key=>$val )
                    {
                        $$key = $val;
                    }
                }

                // Make sure the file exists, then load it
                if( file_exists($file) )
                {
                    require_once $file;
                }
                else
                {
                    ECMS_Error::log_exception(
                            new Exception("Failed to load $file")
                        );
                }
            }
        }

        // If only one file path was supplied
        else
        {
            // Check if variables were supplied for the file
            if( count($var_arr>=1) )
            {
                foreach( $var_arr as $key=>$val )
                {
                    $$key = $val;
                }
            }

            // Make sure the file exists, then load it
            if( file_exists($filepath) )
            {
                require_once $filepath;
            }
            else
            {
                ECMS_Error::log_exception(
                        new Exception("Failed to load $filepath")
                    );
            }
        }

        // Return the buffer contents
        return ob_get_clean();
    }

    /**
     * Deletes a file from the filesystem
     * 
     * @param string $file_name The file to be deleted
     * @return bool             TRUE on success, FALSE on failure
     */
    public function delete_file( $file_name )
    {
        // Make sure the passed value is actually a file
        if( is_file($file_name) )
        {
            return unlink($file_name);
        }
        else
        {
            return FALSE;
        }
    }

    public static function named2decimal( $string )
    {
        return preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/',
                               array('Utilities', 'convert_entity'), $string);
    }

    public static function convert_entity( $matches )
    {
        static $table = array(
            'quot'     => '&#34;',
            'amp'      => '&#38;',
            'lt'       => '&#60;',
            'gt'       => '&#62;',
            'OElig'    => '&#338;',
            'oelig'    => '&#339;',
            'Scaron'   => '&#352;',
            'scaron'   => '&#353;',
            'Yuml'     => '&#376;',
            'circ'     => '&#710;',
            'tilde'    => '&#732;',
            'ensp'     => '&#8194;',
            'emsp'     => '&#8195;',
            'thinsp'   => '&#8201;',
            'zwnj'     => '&#8204;',
            'zwj'      => '&#8205;',
            'lrm'      => '&#8206;',
            'rlm'      => '&#8207;',
            'ndash'    => '&#8211;',
            'mdash'    => '&#8212;',
            'lsquo'    => '&#8216;',
            'rsquo'    => '&#8217;',
            'sbquo'    => '&#8218;',
            'ldquo'    => '&#8220;',
            'rdquo'    => '&#8221;',
            'bdquo'    => '&#8222;',
            'dagger'   => '&#8224;',
            'Dagger'   => '&#8225;',
            'permil'   => '&#8240;',
            'lsaquo'   => '&#8249;',
            'rsaquo'   => '&#8250;',
            'euro'     => '&#8364;',
            'fnof'     => '&#402;',
            'Alpha'    => '&#913;',
            'Beta'     => '&#914;',
            'Gamma'    => '&#915;',
            'Delta'    => '&#916;',
            'Epsilon'  => '&#917;',
            'Zeta'     => '&#918;',
            'Eta'      => '&#919;',
            'Theta'    => '&#920;',
            'Iota'     => '&#921;',
            'Kappa'    => '&#922;',
            'Lambda'   => '&#923;',
            'Mu'       => '&#924;',
            'Nu'       => '&#925;',
            'Xi'       => '&#926;',
            'Omicron'  => '&#927;',
            'Pi'       => '&#928;',
            'Rho'      => '&#929;',
            'Sigma'    => '&#931;',
            'Tau'      => '&#932;',
            'Upsilon'  => '&#933;',
            'Phi'      => '&#934;',
            'Chi'      => '&#935;',
            'Psi'      => '&#936;',
            'Omega'    => '&#937;',
            'alpha'    => '&#945;',
            'beta'     => '&#946;',
            'gamma'    => '&#947;',
            'delta'    => '&#948;',
            'epsilon'  => '&#949;',
            'zeta'     => '&#950;',
            'eta'      => '&#951;',
            'theta'    => '&#952;',
            'iota'     => '&#953;',
            'kappa'    => '&#954;',
            'lambda'   => '&#955;',
            'mu'       => '&#956;',
            'nu'       => '&#957;',
            'xi'       => '&#958;',
            'omicron'  => '&#959;',
            'pi'       => '&#960;',
            'rho'      => '&#961;',
            'sigmaf'   => '&#962;',
            'sigma'    => '&#963;',
            'tau'      => '&#964;',
            'upsilon'  => '&#965;',
            'phi'      => '&#966;',
            'chi'      => '&#967;',
            'psi'      => '&#968;',
            'omega'    => '&#969;',
            'thetasym' => '&#977;',
            'upsih'    => '&#978;',
            'piv'      => '&#982;',
            'bull'     => '&#8226;',
            'hellip'   => '&#8230;',
            'prime'    => '&#8242;',
            'Prime'    => '&#8243;',
            'oline'    => '&#8254;',
            'frasl'    => '&#8260;',
            'weierp'   => '&#8472;',
            'image'    => '&#8465;',
            'real'     => '&#8476;',
            'trade'    => '&#8482;',
            'alefsym'  => '&#8501;',
            'larr'     => '&#8592;',
            'uarr'     => '&#8593;',
            'rarr'     => '&#8594;',
            'darr'     => '&#8595;',
            'harr'     => '&#8596;',
            'crarr'    => '&#8629;',
            'lArr'     => '&#8656;',
            'uArr'     => '&#8657;',
            'rArr'     => '&#8658;',
            'dArr'     => '&#8659;',
            'hArr'     => '&#8660;',
            'forall'   => '&#8704;',
            'part'     => '&#8706;',
            'exist'    => '&#8707;',
            'empty'    => '&#8709;',
            'nabla'    => '&#8711;',
            'isin'     => '&#8712;',
            'notin'    => '&#8713;',
            'ni'       => '&#8715;',
            'prod'     => '&#8719;',
            'sum'      => '&#8721;',
            'minus'    => '&#8722;',
            'lowast'   => '&#8727;',
            'radic'    => '&#8730;',
            'prop'     => '&#8733;',
            'infin'    => '&#8734;',
            'ang'      => '&#8736;',
            'and'      => '&#8743;',
            'or'       => '&#8744;',
            'cap'      => '&#8745;',
            'cup'      => '&#8746;',
            'int'      => '&#8747;',
            'there4'   => '&#8756;',
            'sim'      => '&#8764;',
            'cong'     => '&#8773;',
            'asymp'    => '&#8776;',
            'ne'       => '&#8800;',
            'equiv'    => '&#8801;',
            'le'       => '&#8804;',
            'ge'       => '&#8805;',
            'sub'      => '&#8834;',
            'sup'      => '&#8835;',
            'nsub'     => '&#8836;',
            'sube'     => '&#8838;',
            'supe'     => '&#8839;',
            'oplus'    => '&#8853;',
            'otimes'   => '&#8855;',
            'perp'     => '&#8869;',
            'sdot'     => '&#8901;',
            'lceil'    => '&#8968;',
            'rceil'    => '&#8969;',
            'lfloor'   => '&#8970;',
            'rfloor'   => '&#8971;',
            'lang'     => '&#9001;',
            'rang'     => '&#9002;',
            'loz'      => '&#9674;',
            'spades'   => '&#9824;',
            'clubs'    => '&#9827;',
            'hearts'   => '&#9829;',
            'diams'    => '&#9830;',
            'nbsp'     => '&#160;',
            'iexcl'    => '&#161;',
            'cent'     => '&#162;',
            'pound'    => '&#163;',
            'curren'   => '&#164;',
            'yen'      => '&#165;',
            'brvbar'   => '&#166;',
            'sect'     => '&#167;',
            'uml'      => '&#168;',
            'copy'     => '&#169;',
            'ordf'     => '&#170;',
            'laquo'    => '&#171;',
            'not'      => '&#172;',
            'shy'      => '&#173;',
            'reg'      => '&#174;',
            'macr'     => '&#175;',
            'deg'      => '&#176;',
            'plusmn'   => '&#177;',
            'sup2'     => '&#178;',
            'sup3'     => '&#179;',
            'acute'    => '&#180;',
            'micro'    => '&#181;',
            'para'     => '&#182;',
            'middot'   => '&#183;',
            'cedil'    => '&#184;',
            'sup1'     => '&#185;',
            'ordm'     => '&#186;',
            'raquo'    => '&#187;',
            'frac14'   => '&#188;',
            'frac12'   => '&#189;',
            'frac34'   => '&#190;',
            'iquest'   => '&#191;',
            'Agrave'   => '&#192;',
            'Aacute'   => '&#193;',
            'Acirc'    => '&#194;',
            'Atilde'   => '&#195;',
            'Auml'     => '&#196;',
            'Aring'    => '&#197;',
            'AElig'    => '&#198;',
            'Ccedil'   => '&#199;',
            'Egrave'   => '&#200;',
            'Eacute'   => '&#201;',
            'Ecirc'    => '&#202;',
            'Euml'     => '&#203;',
            'Igrave'   => '&#204;',
            'Iacute'   => '&#205;',
            'Icirc'    => '&#206;',
            'Iuml'     => '&#207;',
            'ETH'      => '&#208;',
            'Ntilde'   => '&#209;',
            'Ograve'   => '&#210;',
            'Oacute'   => '&#211;',
            'Ocirc'    => '&#212;',
            'Otilde'   => '&#213;',
            'Ouml'     => '&#214;',
            'times'    => '&#215;',
            'Oslash'   => '&#216;',
            'Ugrave'   => '&#217;',
            'Uacute'   => '&#218;',
            'Ucirc'    => '&#219;',
            'Uuml'     => '&#220;',
            'Yacute'   => '&#221;',
            'THORN'    => '&#222;',
            'szlig'    => '&#223;',
            'agrave'   => '&#224;',
            'aacute'   => '&#225;',
            'acirc'    => '&#226;',
            'atilde'   => '&#227;',
            'auml'     => '&#228;',
            'aring'    => '&#229;',
            'aelig'    => '&#230;',
            'ccedil'   => '&#231;',
            'egrave'   => '&#232;',
            'eacute'   => '&#233;',
            'ecirc'    => '&#234;',
            'euml'     => '&#235;',
            'igrave'   => '&#236;',
            'iacute'   => '&#237;',
            'icirc'    => '&#238;',
            'iuml'     => '&#239;',
            'eth'      => '&#240;',
            'ntilde'   => '&#241;',
            'ograve'   => '&#242;',
            'oacute'   => '&#243;',
            'ocirc'    => '&#244;',
            'otilde'   => '&#245;',
            'ouml'     => '&#246;',
            'divide'   => '&#247;',
            'oslash'   => '&#248;',
            'ugrave'   => '&#249;',
            'uacute'   => '&#250;',
            'ucirc'    => '&#251;',
            'uuml'     => '&#252;',
            'yacute'   => '&#253;',
            'thorn'    => '&#254;',
            'yuml'     => '&#255;'
        );

        // Entity not found? Destroy it.
        return isset($table[$matches[1]]) ? $table[$matches[1]] : '';
    }

}
