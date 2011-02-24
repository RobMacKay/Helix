<?php

class AdminUtilities extends DB_Actions
{

    /**
     * Stores the length of the salt to be used in password hashing
     *
     * @var int
     */
    const SALT_LENGTH = 14;

    public static $valid_session = FALSE;

    protected function admin_general_options( $page )
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $form_action = FORM_ACTION;
            return <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
    <p>
        <a href="/$page/admin" class="hlx-edit">create a new entry</a> |
        <a href="/admin/logout">logout</a>
    </p>
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else { return ''; }
    }

    protected function admin_entry_options($page,$id,$dynamic=true)
    {
        if ( $dynamic === true ) {
            $extra_options = <<<EXTRA_OPTIONS

    <a href="/$page/admin/$id" class="delete">delete this entry</a>
    |
    <a href="/$page/admin" class="hlx-edit">create a new entry</a>
    |
EXTRA_OPTIONS;
        } else {
            $extra_options = NULL;
        }

        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $form_action = FORM_ACTION;
            return <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
    <p>
        You are logged in as {$_SESSION['user']['name']}.<br />
        [ <a href="/$page/admin/$id" class="hlx-edit">edit this entry</a>
        |$extra_options
        <a href="/admin/logout">logout</a> ]
    </p>
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else
        {
            return '';
        }
    }

    protected function admin_simple_options($page,$id)
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            return <<<ADMIN_OPTIONS

<span class="adminsimpleoptions">
    [ <a href="/$page/admin/$id" class="hlx-edit">edit</a>
    | <a href="/$page/admin/delete-post" class="hlx-edit">delete</a> ]
</span>

ADMIN_OPTIONS;
		}
		else
		{
			return '';
		}
	}

    protected function admin_comment_options( $bid, $cid, $email )
    {
        $form_action = FORM_ACTION;
        if ( $this->isLoggedIn() )
        {
            try
            {
                $config = array(
                            'legend'=>'',
                            'class'=>'admin-delete'
                        );
                $form = new Form($config);
                $form->action = "comment_delete";

                $form->input_arr = array(
                    array(
                        'name' => 'bid',
                        'type' => 'hidden',
                        'value' => $bid
                    ),
                    array(
                        'name' => 'cmntid',
                        'type' => 'hidden',
                        'value' => $cid
                    ),
                    array(
                        'name' => 'delete-submit',
                        'type' => 'submit',
                        'value' => 'delete'
                    )
                );

                return $form;
            }
            catch ( Exception $e )
            {
                ECMS_Error::log_exception($e);
            }
        }
        else
        {
            return '';
        }
    }

    /**
     * DEPRECATED: Checks the administrative clearance
     *
     * @deprecated  Use AdminUtilities::check_clearance() instead
     *
     * @param int   $clearance  Required clearance level
     * @return bool             Whether or not the user is logged in
     */
    protected function isLoggedIn($clearance=1)
    {
        return self::check_clearance($clearance);
    }

    /**
     * Checks for a valid session
     *
     * Runs a few checks to make sure the same user agent and IP are used in
     * addition to the check for a token and timeout. Any failure results in a
     * full-on self-destruct for the session.
     *
     * @return boolean  Whether or not a valid session is present
     */
    public static function check_session()
    {
        // If we've already checked this and it's valid, just return TRUE
        if( self::$valid_session===TRUE )
        {
            return TRUE;
        }

        FB::log($_SESSION, "Session Data");
        FB::log(time(), "Current Time");
        // Create a token if one doesn't exist or has timed out
        if ( !isset($_SESSION['ecms']) || $_SESSION['ecms']['ttl']<=time() )
        {
            // Regenerate the session to avoid any unwanted shenanigans
            self::destroy_session();
            self::create_session();

            // Log data for debugging
            FB::log("Session doesn't exist or expired. New session created.");
            FB::log($_SESSION, "New Session");

            return FALSE;
        }

        // If user agent and/or IP don't match, assume hostility: harakiri
        else if ( $_SESSION['ecms']['user-agent']!==$_SERVER['HTTP_USER_AGENT']
                || $_SESSION['ecms']['address']!==$_SERVER['REMOTE_ADDR'] )
        {
            // Log data for debugging
            FB::log("User agent or remote address is mismatched.");

            // Regenerate the session to avoid any unwanted shenanigans
            self::destroy_session();
            self::create_session();

            return FALSE;
        }

        // If a valid session exists, update the timeout and return TRUE
        else if ( is_array($_SESSION['ecms']) )
        {
            $_SESSION['ecms']['ttl'] = time()+600; // 10 minutes from now

            self::$valid_session = TRUE;

            return TRUE;
        }

        // If none of the above conditions are met, something's screwy
        else
        {
            // Log data for debugging
            FB::log("No conditions met. Something is odd.");

            // Regenerate the session to avoid any unwanted shenanigans
            self::destroy_session();
            self::create_session();

            return FALSE;
        }
    }

    /**
     * Checks if a user has a given clearance level
     *
     * @param int $clearance    The clearance level
     * @return boolean          Whether or not the user has clearance
     */
    public static function check_clearance( $clearance=1 )
    {
        // Check for a valid session, logged in user, and proper clearance
        if ( self::check_session()
                && isset($_SESSION['user'])
                && $_SESSION['user']['clearance']>=$clearance )
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Creates a new session
     *
     * @return void
     */
    private static function create_session(  )
    {
        $_SESSION['ecms'] = array(
                'token' => uniqid('ecms-', TRUE),
                'ttl' => time()+1200,
                'address' => $_SERVER['REMOTE_ADDR'],
                'user-agent' => $_SERVER['HTTP_USER_AGENT']
            );
    }

    /**
     * Destroys the current session
     *
     * @return void
     */
    private static function destroy_session(  )
    {
        // Unset the session data for the remainder of script execution
        $_SESSION = array();

        // Delete the session cookie
        if( isset($_COOKIE[session_name()]) )
        {
            $params = session_get_cookie_params();
            setcookie(
                    session_name(), '', time()-864000,
                    $params["path"], $params["domain"],
                    $params["secure"], isset($params["httponly"])
                );
        }

        session_regenerate_id(TRUE);
    }

    /**
     * Generates a salted hash
     *
     * @param string $string    A string to hash
     * @param string $salt      An optional salted hash
     * @return string           The salted hash
     */
    public static function createSaltedHash($string, $salt=NULL)
    {
        // Generate a salt if no salt is passed
        if ( $salt==NULL )
        {
            $salt = substr(md5(time()), 0, self::SALT_LENGTH);
        }

        // Extract the salt from the string if one is passed
        else
        {
            $salt = substr($salt, 0, self::SALT_LENGTH);
        }

        // Add the salt to the hash and return it
        return $salt . sha1($salt . $string);
    }

}
