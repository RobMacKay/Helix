<?php

/**
 * Allows login/logout, as well as account creation and notification
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Admin extends Page
{

    private $_sdata;

    public function __construct( $url_array=NULL )
    {
        parent::__construct($url_array);

        // Make sure that an object exists in the session to store data
        if( !isset($_SESSION['verify']) || !is_object($_SESSION['verify']) )
        {
            FB::log("Created a new stdClass for verification data.");
            $_SESSION['verify'] = new stdClass;
            $_SESSION['verify']->error = '0000';
        }

        // Use the private property to modify the session by reference
        $this->_sdata =& $_SESSION['verify'];
    }

    public function display_public()
    {
        // See if the uer is logged in already
        if( AdminUtilities::check_clearance(1) )
        {
            // If so, send them to the index unless they're creating a new user
            if( $this->url1==='create' && AdminUtilities::check_clearance(2) )
            {
                return $this->_create_user_form();
            }

            // If the user is logging out, perform the proper actions
            else if( $this->url1==='logout' )
            {
                $this->logout();
                header( 'Location: /' );
                exit;
            }
            else
            {
                header( 'Location: /' );
                exit;
            }
        }

        // Check if this is a new user coming to verify an account
        else if( $this->url1==='verify' )
        {
            return $this->_verify_user_form();
        }

        // If none of the above are true, display the login form
        else
        {
            return $this->_display_login_form();
        }
    }

    private function _display_login_form()
    {
        if ( $this->url1=='error' )
        {
            $errTxt = "<span>There was an error logging you in. Please check "
                    ."your username and password and try again.</span>";
        }
        else
        {
            $errTxt = NULL;
        }

        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Administrator Login';
            $form->page = 'admin';
            $form->action = 'user-login';

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'username',
                    'label'=>'Username',
                    'class' => 'input-text'
                ),
                array(
                    'name'=>'password',
                    'label'=>'Password',
                    'type' => 'password',
                    'class' => 'input-text'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Login',
                    'class' => 'input-submit'
                )
            );
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }

        return $errTxt.$form;
    }

    public function create_user(  )
    {
        // Make sure the email address is valid
        if( SIV::validate($_POST['email'], SIV::EMAIL) )
        {
            $user_id = (int) $_POST['user_id'];
            $email = $_POST['email'];
            $clearance = (int) $_POST['clearance'];

            // Generate a verification code
            $vcode = sha1(uniqid(mt_rand(100,999), TRUE));
        }
        else
        {
            ECMS_Error::log_exception(
                        new Exception( "Invalid email address.")
                    );
        }

        // Store the new user's email and verification code in the database
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."users`
                (
                    `user_id`, `email`, `vcode`, `clearance`
                )
                VALUES
                (
                    :user_id, :email, :vcode, :clearance
                )
                ON DUPLICATE KEY UPDATE
                    `email`=:email,
                    `clearance`=:clearance";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":vcode", $vcode, PDO::PARAM_STR);
            $stmt->bindParam(":clearance", $clearance, PDO::PARAM_INT);
            $stmt->execute();

            if( $stmt->errorCode()!=='00000' )
            {
                    $err = $stmt->errorInfo();

                    ECMS_Error::log_exception( new Exception($err[2]) );
            }

            $stmt->closeCursor();

            return $this->_send_verification_email($email, $vcode);
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _send_verification_email( $to, $vcode )
    {
        // Generate the From: field data
        if( isset($_SESSION['user']) )
        {
            $from = $_SESSION['user']['name'] . ' <'
                    . $_SESSION['user']['email'] . '>';
        }

        // If no user is logged in, something is wrong: log a message and die
        else
        {
            ECMS_Error::log_exception(
                        new Exception('You must be logged in to do that.')
                    );
        }

        // Create a boundary string to separate the email parts
        $mime_boundary = '_x' . sha1(time()) . 'x';

        // Create the email subject
        $subject = '[' . SITE_NAME . '] An Account Has Been Created for You!';

        // Generate headers for the email
        $headers = <<<MESSAGE
From: $from
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="==PHP-alt$mime_boundary"
MESSAGE;

        // Email location
        $filepath = CMS_PATH . 'core/resources/email/verification.email';

        // Variables for the email
        $var_arr = array(
                'mime_boundary' => $mime_boundary,
                'vcode' => $vcode
            );

        // Load the message
        $message = Utilities::load_file($filepath, $var_arr);

        return mail($to, $subject, $message, $headers);
    }

    private function _create_user_form()
    {
        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Create a New Administrator';
            $form->page = 'admin';
            $form->action = 'user-create';

            // Set up input information
            $form->input_arr = array(
                    array(
                            'name'=>'email',
                            'label'=>'Email Address'
                        ),
                    array(
                            'type' => 'select',
                            'name'=>'clearance',
                            'label'=>'Clearance',
                            'options' => array(1,2,3)
                        ),
                    array(
                            'type' => 'hidden',
                            'name'=>'user_id'
                        ),
                    array(
                            'type' => 'submit',
                            'name' => 'form-submit',
                            'value' => 'Create User'
                        )
                );

            return $form;
        }
        catch( Exception $e )
        {
            Error::logException($e);
        }
    }

    private function _verify_user_form()
    {
        // Load the account to be verified
        $sql = "SELECT
                    `user_id`, `email`
                FROM `".DB_NAME."`.`".DB_PREFIX."users`
                WHERE `vcode`=:vcode
                AND `active`=0
                LIMIT 1";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':vcode', $this->url2, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            FB::log($result);
            $stmt->closeCursor();
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }

        if( isset($result->email) )
        {
            $this->_sdata->error = '0000';
        }
        else
        {
            $this->_sdata->error = '0005';
        }

        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Activate Your Account';
            $form->page = $this->url0;
            $form->action = 'user-verify';

            // Load the error message if one exists
            if( isset($this->_sdata->error)
                    && $this->_sdata->error!=='0000' )
            {
                $form->notice = '<p class="form-error">'
                        . $this->_get_error_message() . '</p>';

                // Load temporary form values
                $form->entry = isset($this->_sdata->temp) ? $this->_sdata->temp : new stdClass;
            }
            else
            {
                FB::log('Verifying the account for ' . $result->email);
            }

            // Set up input information
            $form->input_arr = array(
                    array(
                            'name' => 'username',
                            'label' => 'Choose a Username (8-20 characters using '
                                        . 'only a-z, 0-9, -, and _)'
                        ),
                    array(
                            'name' => 'display',
                            'label' => 'Choose a Display Name (the name shown on '
                                        . 'entries posted by you, i.e. "John Doe")'
                        ),
                    array(
                            'name' => 'password',
                            'label' => 'Choose a Password',
                            'type' => 'password',
                            'id' => 'choose-password'
                        ),
                    array(
                            'name' => 'verify-password',
                            'label' => 'Verify Your Password',
                            'type' => 'password',
                            'id' => 'verify-password'
                        ),
                    array(
                            'name' => 'vcode',
                            'type' => 'hidden',
                            'value' => SIV::clean_output($this->url2, FALSE, FALSE)
                        ),
                    array(
                            'type' => 'submit',
                            'name' => 'form-submit',
                            'value' => 'Activate'
                        )
                );

            return $form;
        }
        catch( Exception $e )
        {
            Error::logException($e);
        }
    }

    public function verify_user(  )
    {
        // Store and clean the POST data
        $this->_store_post_data();

        // Make sure the username is valid
        if( !SIV::validate($_POST['username'], SIV::USERNAME) )
        {
            // Set error message
            $this->_sdata->error = '0001';
        }

        // Make sure the display name is valid
        else if( !SIV::validate($_POST['display'], SIV::STRING) )
        {
            // Set error message
            $this->_sdata->error = '0002';
        }

        // Make sure the password is long enough
        else if( strlen($_POST['password'])<8 )
        {
            // Set error message
            $this->_sdata->error = '0003';
        }

        // Make sure the passwords match
        else if( $_POST['password']!==$_POST['verify-password'] )
        {
            // Set error message
            $this->_sdata->error = '0004';
        }

        // If nothing fails, extract the user data
        else
        {
            // Reset the error code
            $this->_sdata->error = '0000';

            // Grab cleaned data out of the temporary session data
            $username = $this->_sdata->temp->username;
            $display = $this->_sdata->temp->display;
            $vcode = $this->_sdata->temp->vcode;

            // Create a salted hash of the password
            $password = AdminUtilities::createSaltedHash($_POST['password']);
        }

        // Check for errors
        if( $this->_sdata->error!=='0000' )
        {
            // Bounce back to the verification form
            header('Location: /admin/verify/' . $vcode);
            exit;
        }

        // Define the update query
        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."users`
                SET
                    `username`=:username,
                    `display`=:display,
                    `password`=:password,
                    `active`=1
                WHERE `vcode`=:vcode
                AND `active`=0
                LIMIT 1";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':display', $display, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':vcode', $vcode, PDO::PARAM_STR);
            $stmt->execute();

            if( $stmt->errorCode()!=='00000' )
            {
                FB::error($e);
                $err = $stmt->errorInfo();
                ECMS_Error::log_exception( new Exception($err[2]) );
            }

            $stmt->closeCursor();

            $this->_sdata = NULL;

            return TRUE;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _store_post_data(  )
    {
        // Clean up the POST data and store it temporarily
        foreach( $_POST as $key=>$val )
        {
            // Skip password fields for security
            if( $key==='password' || $key==='verify-password' )
            {
                continue;
            }

            // Otherwise, store clean data in the session
            $this->_sdata->temp->$key = SIV::clean_output($val, FALSE, FALSE);
        }
    }

    private function _get_error_message(  )
    {
        $error_codes = array(
                '0000' => NULL, // No errors
                '0001' => 'The username you entered is not valid.',
                '0002' => 'The display name you entered is not valid.',
                '0003' => 'Your password needs to be at least 8 characters.',
                '0004' => 'The passwords you entered don\'t match.',
                '0005' => 'This account has already been verified.'
            );

        if( array_key_exists($this->_sdata->error, $error_codes) )
        {
            return $error_codes[$this->_sdata->error];
        }
        else
        {
            ECMS_Error::log_exception(
                    new Exception(
                            'Unknown comment error occurred using error code "'
                            . $this->_error_code . '".'
                        ),
                    FALSE
                );

            return 'An unknown error occurred.';
        }
    }

    public function login(  )
    {
        // Sanitize the username and store the password for hashing
        if( SIV::validate($_POST['username'], SIV::USERNAME)===TRUE )
        {
            $username = $_POST['username'];
            $password = $_POST['password'];
        }
        else
        {
            return FALSE;
        }
        FB::log($username, "Username");

        // Load user data that matches the supplied username
        $userdata = $this->get_user_data( $username );
        FB::log($userdata);

        // Make sure a user was loaded before continuing
        if( array_key_exists('email', $userdata)
                || array_key_exists('password', $userdata)
                || array_key_exists('username', $userdata)
                || array_key_exists('display', $userdata)
                || array_key_exists('clearance', $userdata) )
        {
            // Extract password hash
            $db_pass = $userdata['password'];

            FB::log($this->createSaltedHash($password, $db_pass), "Password Hash");
            FB::log($db_pass===$this->createSaltedHash($password, $db_pass), "Passwords Match");
            // Make sure the passwords match
            if( $db_pass===$this->createSaltedHash($password, $db_pass)
                    && AdminUtilities::check_session() )
            {
                // Save the user data in a session variable
                $_SESSION['user'] = array(
                        'name' => $userdata['display'],
                        'email' => $userdata['email'],
                        'clearance' => $userdata['clearance']
                    );
                FB::log($_SESSION, "Session");

                // Set a cookie to store the username that expires in 30 days
                setcookie('username', $username, time()+2592000, '/');

                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }

    public function logout()
    {
        $_SESSION = NULL;
        return session_regenerate_id(TRUE);
    }
}
