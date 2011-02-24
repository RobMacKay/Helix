<?php

require_once CMS_PATH . 'core/helper/class.gravatar.inc.php';

/**
 * Displays and manipulates comments for a given entry
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@copterlabs.com>
 * @author     Drew Douglass <drew.douglass@copterlabs.com>
 * @author     Rob MacKay <rob.mackay@copterlabs.com>
 * @copyright  2010 Copter Labs, Inc.
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Comments extends Page
{

    public $entries = array();

    private $_entry_id,
            $_sdata,
            $_redirect_url,
            $_actions,
            $_challenges = array(
                                array(
                                        'q' => 'Is the sky red or blue?',
                                        'a' => 'blue'
                                    ),
                                array(
                                        'q' => 'Are bananas yellow or pink?',
                                        'a' => 'yellow'
                                    ),
                                array(
                                        'q' => 'Are rocks heavy or light?',
                                        'a' => 'heavy'
                                    )
                            );

    public function __construct( $url_array=array() )
    {
        parent::__construct($url_array);

        // Creates an empty class in the session for comment data if none exists
        if( isset($_SESSION['comments']) && !is_object($_SESSION['comments']) )
        {
            $_SESSION['comments'] = new stdClass;
        }

        // Creates an alias for the comments portion of the session
        $this->_sdata =& $_SESSION['comments'];

        FB::log($_SESSION['comments'], "Comment Session Data");

        // Stores the request URI, sans-query string, for later use
        $this->_redirect_url = array_shift(explode('?', $_SERVER['REQUEST_URI']));
    }

    public function display_public(  )
    {
        $this->_actions = array(
                'notifications' => (object) array(
                        'method' => '_show_notification_options'
                    ),
                'preferences-saved' => (object) array(
                        'method' => '_show_notification_confirmation'
                    )
            );

        // Check if
        if( array_key_exists($this->url1, $this->_actions) )
        {
            $display = $this->{$this->_actions{$this->url1}->method}();
        }
        else
        {
            $display = '<p>Array key not found.</p><pre>URL1: ' . $this->url1
                    . "\n" . print_r($this->_actions, TRUE) . '</pre>';
        }

        return $display;
    }

    private function _show_notification_options(  )
    {
        $comment_id = $this->url2;
        $email = Utilities::hextostr($this->url3);

        // Build a form for editing subscriptions
        $form = new Form;

        $form->page = 'comments';
        $form->legend = 'Update Your Comment Notification Preferences';
        $form->action = 'comment-notification-update';
        $form->form_id = 'update-preferences';

        $form->notice = '<p>You are subscribed to the following entries\' '
                . 'comment sections, and you will receive an email letting you '
                . 'know when a new comment is posted. If you wish to stop '
                . 'receiving notices for a given entry, just uncheck it and '
                . 'click the "Update Your Subscriptions" button.</p>';

        // Load all the entries to which this user subscribes
        $entries = $this->get_comment_subscriptions_by_email($email);

        if( count($entries)>0 )
        {
            // Generate checkboxes in a form to allow them to choose their subs
            foreach( $entries as $entry )
            {
                $form->input_arr[] = array(
                            'type' => 'checkbox',
                            'name'=>'entries[]',
                            'id'=>'entry-' . $entry->entry_id,
                            'class' => 'subscription-checkbox',
                            'label'=>$entry->title,
                            'value' => $entry->entry_id,
                            'checked' => 'checked'
                        );
            }

            // Set up input information
            $form->input_arr[] = array(
                        'type' => 'submit',
                        'name' => 'comment-notification-submit',
                        'class'=>'input-submit',
                        'value' => 'Update Your Subscriptions'
                    );

            $form->input_arr[] = array(
                        'type' => 'submit',
                        'name' => 'comment-notification-cancel',
                        'class'=>'input-submit',
                        'value' => 'Cancel'
                    );

            $form->input_arr[] = array(
                        'type' => 'hidden',
                        'name' => 'email',
                        'value' => $this->url3
                    );

            $form->input_arr[] = array(
                        'type' => 'hidden',
                        'name' => 'return-url',
                        'value' => SITE_URL . 'comments/preferences-saved'
                    );
        }
        else
        {
            $form->notice .= '<h4>You aren\'t signed up for any '
                    . 'notifications</h4><p><a href="' . SITE_URL
                    . '">Home Page</a></p>';
        }

        return $form;
    }

    private function _show_notification_confirmation(  )
    {
        // Set default values if no entries are found
        $default = new Entry();
        $default->admin = NULL;
        $default->title = "Preferences Saved!";
        $default->entry = "<p>Your preferences were successfully modified. "
                . '<a href="' . SITE_URL . '">Go to the Home Page</a></p>';
        $this->entries[] = $default;

        // Load the default template
        $this->template = 'default.inc';

        // Organize the data
        $this->generate_template_tags();

        // Return the entry as formatted by the template
        return $this->generate_markup();
    }

    public function display_entry_comments( $entry_id )
    {
        // Set the entry ID for which comments should be loaded
        $this->_entry_id = (int) $entry_id;

        // Load entry comments
        $this->entries = $this->get_comments_by_entry_id($this->_entry_id);

        // Set the comments template
        $this->template = 'comments.inc';

        if( count($this->entries)===0 )
        {
            $this->entries[0]->name = NULL;
            $this->entries[0]->email = NULL;
            $this->entries[0]->url = NULL;
            $this->entries[0]->created = time();
            $this->entries[0]->entry = "No comments yet!";

            $this->template = 'comments-none.inc';
        }

        // Format entry comments
        $this->generate_template_tags();

        // Load markup to display the "add a comment" form
        $extra->footer->comment_form = $this->_display_comment_form();

        // Return both entry comment markup and the form
        return $this->generate_markup($extra);
    }

	static function display_recent_comments( $num=4, $page='blog' )
	{
		// Load comments and titles for the entries
		$sql = "SELECT
                    `name`,
                    `comment`,
                    `".DB_PREFIX."comments`.`created`,
                    `title`,
                    `slug`
				FROM `".DB_PREFIX."comments`
				LEFT JOIN `".DB_PREFIX."entries`
					USING( `entry_id` )
				ORDER BY `".DB_PREFIX."comments`.`created` DESC
				LIMIT $num";
		try
		{
			$stmt = DB_Connect::create()->db->query($sql);
            FB::log($sql);
			$list = NULL;
            foreach( $stmt->fetchAll(PDO::FETCH_OBJ) as $entry )
			{
				$text = Utilities::text_preview(stripslashes($entry->title), 5, FALSE);
                $url = $entry->slug;
                $comment = Utilities::text_preview($entry->comment, 10, FALSE);
				$link = "/$page/$entry->slug";
				$list .= "
                        <li>$entry->name posted on <a href=\"$link\">$text</a>: $comment</li>";
			}

            $stmt->closeCursor();

			return "
                    <ul id=\"recent-comments\">$list
                    </ul>";
		}
		catch( Exception $e )
		{
			FB::log($e);
            throw new Exception ( "Couldn't load popular entries." );
		}
	}

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags(  )
    {
        // Add custom tags here
        foreach( $this->entries as $comment )
        {
            // Continue if the object isn't a real comment
            if( !isset($comment->comment_id) )
            {
                continue;
            }

            // Check for threaded comments
            $comment->threaded_replies = '';

            $threaded_comments = $this->get_comments_by_entry_id($this->_entry_id, $comment->comment_id);

            if( count($threaded_comments)>0 )
            {
                $fmt = "\n" . str_repeat(' ', 12);
                foreach( $threaded_comments as $threaded )
                {
                    $gravatar = new Gravatar($threaded->email);
                    $comment->threaded_replies .= $fmt
                            . '<div class="threaded-reply">' . $fmt . '    '
                            . '<p class="thread-comment">'
                            . nl2br($threaded->comment) . '</p>' . $fmt . '    '
                            . '<p class="thread-author">' . $gravatar
                            . '<strong>' . $threaded->name . '</strong></p>'
                            . $fmt . '</div><!-- end .threaded-reply -->';
                }
            }

            // Generate a gravatar if the user has one
            $comment->gravatar = new Gravatar($comment->email, GRAVATAR_DEFAULT_IMG_URL);

            // Format the creation date
            $comment->date = date('g:ia M j, Y', $comment->created);

            // For the comment flagging form, store the form action and token
            $comment->form_action = FORM_ACTION;
            $comment->token = $_SESSION['ecms']['token'];

            // Generate admin options if the user is logged in
            $comment->admin = $this->_comment_admin_options(
                                                    $comment->comment_id,
                                                    $comment->email
                                                );

            $comment->comment = nl2br($comment->comment);

            if( !empty($comment->url) )
            {
                $url = preg_match('#^http://#', $comment->url) ? $comment->url : 'http://' . $comment->url;
                $comment->linked_name = '<a href="' . $url
                        . '" rel="nofollow">' . $comment->name . '</a>';
            }
            else
            {
                $comment->linked_name = $comment->name;
            }
        }
    }

    private function _generate_spam_challenge(  )
    {
        $n = mt_rand(0, count($this->_challenges)-1);
        $this->_sdata->challenge = $n;

        return $this->_challenges[$n]['q'];
    }

    private function _verify_spam_challenge(  )
    {
        if( $this->_is_verified_human() )
        {
            return TRUE;
        }
        else
        {
            $given = strtolower(SIV::clean_output($_POST['challenge'], FALSE, FALSE));
            $actual = strtolower($this->_challenges[$this->_sdata->challenge]['a']);

            if( $given===$actual )
            {
                $this->_sdata->verified = 1;
                setcookie('ecms-comment:ishuman', 1, time()+2592000, '/');
                return TRUE;
            }
            else
            {
                unset($this->_sdata->verified);
                unset($_COOKIE['ecms-comment:ishuman']);
                return FALSE;
            }
        }
    }

    private function _is_verified_human(  )
    {
        if( is_object($this->_sdata)
                && property_exists($this->_sdata, 'verified')
                && (int) $this->_sdata->verified===1
                && isset($_COOKIE['ecms-comment:ishuman'])
                && (int) $_COOKIE['ecms-comment:ishuman']===1 )
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    private function _delete_comment( $comment_id )
    {

    }

    private function _get_comment_data(  )
    {
        $comment = new stdClass;

        // If mid-comment, the data will be temporarily stored in the session
        if( isset($_SESSION['comments'])
                && property_exists($this->_sdata, 'temp')
                && is_object($this->_sdata->temp) )
        {
            $comment->name = $this->_sdata->temp->name;
            $comment->email = $this->_sdata->temp->email;
            $comment->url = $this->_sdata->temp->url;
            $comment->comment = $this->_sdata->temp->comment;
        }
        else if( isset($_COOKIE['ecms-comment:name']) )
        {
            $comment->name = $_COOKIE['ecms-comment:name'];
            $comment->email = $_COOKIE['ecms-comment:email'];
            $comment->url = isset($_COOKIE['ecms-comment:url']) ? $_COOKIE['ecms-comment:url'] : NULL;
        }

        return $comment;
    }

    private function _comment_admin_options( $comment_id, $commenter_email )
    {
        if( AdminUtilities::check_clearance(1) )
        {
            $fmt = "\n" . str_repeat(' ', 12);
            return $fmt . '<p class="comment-admin-options">' . $fmt . '    '
                    . '<a href="' . $this->_redirect_url
                    . '?thread_id=' . $comment_id . '#add-comment" '
                    . 'class="reply-to-comment">reply</a>' . $fmt . '    '
                    . '<a href="' . FORM_ACTION
                    . '?page=comments&action=delete-comment&comment_id='
                    . $comment_id . '" class="delete-comment">delete</a>'
                    . $fmt . '    <a href="mailto:' . $commenter_email
                    . '">email commenter</a>' . $fmt . '    <a href="'
                    . FORM_ACTION
                    . '?page=comments&action=ban-commenter&comment_id='
                    . $comment_id . '" class="ban-commenter">ban commenter</a>'
                    . $fmt . '</p><!-- end .comment-admin-options -->';
        }
        else
        {
            return '';
        }
    }

    private function _display_comment_form(  )
    {
        $form = new Form;

        $form->page = 'comments';
        $form->legend = 'Add a Comment';
        $form->action = 'comment-write';
        $form->entry_id = $this->_entry_id;
        $form->form_id = 'add-comment';

        if( isset($this->_sdata->error)
                && $this->_sdata->error!=='0000' )
        {
            $form->notice = '<p class="comment-error">'
                    . $this->_get_comment_error_message() . '</p>';
        }

        // Make the entry values available to the form if they exist
        $form->entry = $this->_get_comment_data();

        // If the admin is trying to reply to a comment, add the thread ID
        if( AdminUtilities::check_clearance(1) && isset($_GET['thread_id']) )
        {
            $form->entry->thread_id = (int) $_GET['thread_id'];
        }

        // If the commenter is new and no cookies exist, do a spam challenge
        if( $this->_is_verified_human()===TRUE )
        {
            $challenge = array(
                'name' => 'challenge',
                'type' => 'hidden',
                'value' => 1
            );
        }
        else
        {
            $challenge = array(
                'name'=>'challenge',
                'class'=>'input-text',
                'label'=>$this->_generate_spam_challenge()
            );
        }

        // Set up input information
        $form->input_arr = array(
            array(
                'name'=>'name',
                'class'=>'input-text',
                'label'=>'Your Name (Not Your Business Name)'
            ),
            array(
                'type'=>'email',
                'name'=>'email',
                'class'=>'input-text',
                'label'=>'Your Email (Required, Never Shared)'
            ),
            array(
                'name'=>'url',
                'class'=>'input-text',
                'label'=>'Your Website (Optional)'
            ),
            array(
                'type' => 'textarea',
                'name'=>'comment',
                'class'=>'input-textarea',
                'label'=>'Your Comment'
            ),
            $challenge,
            array(
                'type' => 'checkbox',
                'name'=>'subscribe',
                'id'=>'subscribe',
                'label'=>'Receive an email when new comments are posted',
                'value' => 1
            ),
            array(
                'type' => 'submit',
                'name' => 'comment-submit',
                'class'=>'input-submit',
                'value' => 'Post a Comment'
            ),
            array(
                'type' => 'hidden',
                'name' => 'comment_id'
            ),
            array(
                'type' => 'hidden',
                'name' => 'thread_id'
            ),
            array(
                'type' => 'hidden',
                'name' => 'return-url',
                'value' => $this->_redirect_url
            )
        );

        return $form;
    }

    public function save_comment(  )
    {
        // For debugging
        FB::group("Saving Comment");

        $comment = $this->_validate_comment_data();

        if( $comment===FALSE )
        {
            // Debugging info
            FB::send("Comment failed validation.");
            FB::groupEnd();

            $loc = array_shift(explode('?', $_SERVER['HTTP_REFERER']));
            if( !strpos($loc, '#add-comment') )
            {
                $loc .= '#add-comment';
            }

            header('Location: ' . $loc);
            exit;
        }

        // Create a new comment entry, or update one if a comment_id is supplied
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."comments`
                (
                    `comment_id`, `entry_id`, `name`, `email`, `url`,
                    `remote_address`, `comment`, `subscribed`, `thread_id`,
                    `created`
                )
                VALUES
                (
                    :comment_id, :entry_id, :name, :email, :url,
                    :remote_address, :comment, :subscribed, :thread_id, :created
                )
                ON DUPLICATE KEY UPDATE
                    `name`=:name, `email`=:email, `url`=:url,
                    `comment`=:comment;";

        try
        {
            // Create a prepared statement
            $stmt = $this->db->prepare($sql);

            // Bind the query parameters
            $stmt->bindParam(":comment_id", $comment->comment_id, PDO::PARAM_INT);
            $stmt->bindParam(":entry_id", $comment->entry_id, PDO::PARAM_INT);
            $stmt->bindParam(":name", $comment->name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $comment->email, PDO::PARAM_STR);
            $stmt->bindParam(":url", $comment->url, PDO::PARAM_STR);
            $stmt->bindParam(":remote_address", $comment->remote_address, PDO::PARAM_STR);
            $stmt->bindParam(":comment", $comment->comment, PDO::PARAM_STR);
            $stmt->bindParam(":subscribed", $comment->subscribed, PDO::PARAM_INT);
            $stmt->bindParam(":thread_id", $comment->thread_id, PDO::PARAM_INT);
            $stmt->bindParam(":created", $comment->created, PDO::PARAM_INT);

            // Execute the statement and free the resources
            $stmt->execute();

            if( $stmt->errorCode()!=='00000' )
            {
                $err = $stmt->errorInfo();

                ECMS_Error::log_exception( new Exception($err[2]) );
            }
            else
            {
                $stmt->closeCursor();

                if( (int) $comment->comment_id===0 )
                {
                    $comment->comment_id = $this->db->lastInsertId();
                }

                // Send a comment notification to all subscribers
                $this->_send_comment_notification( $comment );

                unset($this->_sdata->temp);

                $this->_suspend_commenter(15);
                $this->_sdata->verified = 1;

                $loc = array_shift(explode('?', array_shift(explode('#', $_SERVER['HTTP_REFERER']))));
                header('Location: ' . $loc . '#comment-' . $comment->comment_id);
                exit;
            }
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _send_comment_notification( $comment )
    {
        // Generate the From: field data
        $from = SITE_NAME . ' <' . SITE_CONTACT_EMAIL . '>';

        // Create a boundary string to separate the email parts
        $mime_boundary = '_x' . sha1(time()) . 'x';

        // Load entry data
        $this->get_entry_by_id($comment->entry_id);

        if( is_object($this->entries[0]) )
        {
            // Create the email subject
            $subject = '[' . SITE_NAME . '] New Comment on "'
                    . $this->entries[0]->title . '"';

            // Load the page data to get the slug
            $page = $this->get_page_data_by_id($this->entries[0]->page_id);
            $entry_link = SITE_URL . $page->page_slug . '/' . $this->entries[0]->slug;
            $comment_link = $entry_link . '#comment-' . $comment->comment_id;
        }
        else
        {
            throw new Exception( "Something went wrong loading entry data!" );
        }

        // Generate headers for the email
        $headers = "From: $from\nMIME-Version: 1.0\n"
                . "Content-Type: multipart/alternative;\n"
                . ' boundary="==PHP-alt' . $mime_boundary . '"';

        // Email location
        $filepath = CMS_PATH . 'core/resources/email/comment-notification.email';

        // Variables for the email
        $var_arr = array(
                'mime_boundary' => $mime_boundary,
                'commenter' => $comment->name,
                'comment' => $comment->comment,
                'comment_link' => $comment_link,
                'entry_link' => $entry_link,
                'entry_title' => $this->entries[0]->title
            );

        // Load the message
        $template_message = Utilities::load_file($filepath, $var_arr);

        // Load comment subscribers
        $subscribers = $this->get_comment_subscribers($comment->entry_id);

        // Loop through subscribers and send the email
        foreach( $subscribers as $subscriber )
        {
            $to = $subscriber->name . ' <' . $subscriber->email . '>';

            // Generate an unsubscribe link for each
            $unsubscribe_link = SITE_URL . 'comments/notifications/'
                    . $comment->entry_id . '/'
                    . Utilities::strtohex($subscriber->email);

            // Insert the custom unsubscribe link into the message
            $replace_pairs = array( '{unsubscribe_link}' => $unsubscribe_link );
            $message = strtr($template_message, $replace_pairs);

            mail($to, $subject, $message, $headers);
        }
    }

    private function _get_comment_error_message(  )
    {
        $error_codes = array(
                '0000' => NULL, // No errors
                '0001' => 'The "name" field is required in order to post '
                        . 'comments.',
                '0002' => 'The name you entered is not valid. Only letters and '
                        . 'numbers are allowed.',
                '0003' => 'The "email" field is required in order to post '
                        . 'comments.',
                '0004' => 'The email you entered is not valid.',
                '0005' => 'Please enter a comment before posting!',
                '0006' => 'Please answer the anti-spam question correctly '
                        . 'before posting.',
                '0007' => 'The URL you provided is not valid.',
                '0008' => 'Due to too many spam comments from this location, '
                        . 'you can no longer post comments.',
                '0009' => 'Too many failed attempts to answer the anti-spam '
                        . 'question. Try again in 2 minutes.',
                '0010' => 'You have posted 3 comments in two minutes. Slow '
                        . 'down, Turbo. Come back in 5 minutes.',
                '0011' => 'You can\'t post a comment for 2 minutes. Repeated '
                        . 'attempts will reset the timer. Please be patient.',
                '0012' => 'You can only post one comment every 15 seconds. '
                        . 'Repeated  attempts will reset the timer. Please be '
                        . 'patient.'
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

    /**
     * Validates a posted comment and sets error codes where applicable
     * 
     * @return mixed    bool FALSE on failure, object w/comment data on success
     */
    private function _validate_comment_data(  )
    {
        // Sanitize the user input
        $comment = $this->_store_comment_data();

        // Verify that all required fields were properly filled out
        //----------------------------------------------------------------------
        // Name was left blank
        if( empty($comment->name) )
        {
            $this->_sdata->error = '0001';

            FB::send("No name supplied");

            return FALSE;
        }

        // Name has disallowed characters
        else if( !SIV::validate($comment->name, SIV::STRING) )
        {
            $this->_sdata->error = '0002';

            FB::send("Invalid name supplied");

            return FALSE;
        }

        // Email was left blank
        else if( empty($comment->email))
        {
            $this->_sdata->error = '0003';

            FB::send("No email supplied");

            return FALSE;
        }

        // Email is improperly formatted
        else if( !SIV::validate($comment->email, SIV::EMAIL) )
        {
            $this->_sdata->error = '0004';

            FB::send("Invalid email supplied");

            return FALSE;
        }

        // Comment area was left blank
        else if( empty($comment->comment) )
        {
            $this->_sdata->error = '0005';

            FB::send("No comment supplied");

            return FALSE;
        }

        // Verify that the anti-spam challenge was met
        else if( !$this->_verify_spam_challenge() )
        {
            // The user gets five chances to answer the anti-spam question
            if( $this->_track_post_attempts()<5 )
            {
                $this->_sdata->error = '0006';

                FB::send("Failed spam verification");
            }

            // On the 6th try, suspend for two minutes and reset the count
            else
            {
                $this->_suspend_commenter();
                $this->_sdata->attempts = 0;
                $this->_sdata->error = '0009';

                FB::send("Too many failed spam attempts");
            }
            return FALSE;
        }

        // If suspended for too many failed attempts, add two minutes
        else if( $this->_sdata->suspend_until>time()
                && ( $this->_sdata->error==='0009'
                || $this->_sdata->error==='0011' ) )
        {
            $this->_suspend_commenter();
            $this->_sdata->error = '0011';

            FB::send("Suspended for two minutes");

            return FALSE;
        }

        // On successful post or a repost, suspend the user for 15 seconds
        else if( $this->_sdata->suspend_until>time()
                && $this->_sdata->error==='0000'
                || $this->_sdata->error==='0012' )
        {
            $this->_suspend_commenter(15);
            $this->_sdata->error = '0012';

            FB::send("Too many posts in 15 seconds. ");

            return FALSE;
        }

        // No errors occurred, the comment is cleared to save in the database
        else
        {
            // Send the success code
            $this->_sdata->error = '0000';

            // Reset the comment attempts
            $this->_sdata->attempts = 0;

            return $comment;
        }
    }

    private function _store_comment_data(  )
    {
        // Create an object containing sanitized comment data
        $comment = new stdClass();
        $comment->comment_id = (int) $_POST['comment_id'];
        $comment->entry_id = (int) $_POST['entry_id'];
        $comment->name = SIV::clean_output($_POST['name'], FALSE, FALSE);
        $comment->email = SIV::clean_output($_POST['email'], FALSE, FALSE);
        $comment->url = SIV::clean_output($_POST['url'], FALSE, FALSE);
        $comment->comment = SIV::clean_output($_POST['comment'], FALSE);
        $comment->subscribed = (int) $_POST['subscribe'];
        $comment->thread_id = (int) $_POST['thread_id'];
        $comment->remote_address = $_SERVER['REMOTE_ADDR'];
        $comment->created = time();

        // Put the comment data in the session temporarily
        $this->_sdata->temp = $comment;

        // Store user info in cookies to make posting easier in the future
        $expires = time()+2592000; // Cookies to expire in 30 days
        setcookie('ecms-comment:name', $comment->name, $expires, '/');
        setcookie('ecms-comment:email', $comment->email, $expires, '/');
        setcookie('ecms-comment:url', $comment->url, $expires, '/');

        return $comment;
    }

    private function _track_post_attempts(  )
    {
        if( isset($this->_sdata->attempts) )
        {
            return ++$this->_sdata->attempts;
        }
        else
        {
            return $this->_sdata->attempts = 1;
        }
    }

    private function _suspend_commenter( $time_in_seconds=300 )
    {
        $this->_sdata->suspend_until = time()+$time_in_seconds;
    }

    public function confirm_flag_comment(  )
    {
        $comment_id = (int) $_GET['comment_id'];

        if( empty($comment_id) )
        {
            ECMS_Error::log_exception(new Exception("No comment ID supplied."));
        }

        else
        {
            $form = new Form;

            $form->legend = 'Flag This Comment';
            $form->form_id = 'modal-form';

            $form->page = 'comments';
            $form->action = 'comment-flag-confirmed';
            $form->entry_id = $this->_entry_id;

            $form->notice = '<p>Are you sure you want to flag this comment? '
                    . 'Please only flag comments that are abusive, spam, or '
                    . 'against the the comment guidelines for this site.</p>';

            // Set up input information
            $form->input_arr = array(
                array(
                    'type'=>'submit',
                    'name'=>'confirm-flag',
                    'class'=>'input-submit inline',
                    'value'=>'Flag This Comment'
                ),
                array(
                    'type'=>'submit',
                    'name'=>'cancel',
                    'class'=>'input-submit inline',
                    'value'=>'Cancel'
                ),
                array(
                    'type'=>'hidden',
                    'name'=>'comment_id',
                    'value'=>$comment_id
                )
            );

            return $form;
        }
    }

    public function flag_comment(  )
    {
        $comment_id = (int) $_POST['comment_id'];

        if( $comment_id===0 || empty($comment_id) )
        {
            ECMS_Error::log_exception(
                        new Exception("No comment ID supplied!")
                    );
        }

        // Flag
        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."comments`
                SET `flagged`=1
                WHERE `comment_id`=:comment_id;";

        try
        {
            // Create a prepared statement
            $stmt = $this->db->prepare($sql);

            // Bind the query parameters
            $stmt->bindParam(":comment_id", $comment_id, PDO::PARAM_INT);

            // Execute the statement and free the resources
            $stmt->execute();

            // If the query fails, log the error message
            if( $stmt->errorCode()!=='00000' )
            {
                $err = $stmt->errorInfo();

                ECMS_Error::log_exception( new Exception($err[2]) );
            }

            $stmt->closeCursor();

            $loc = array_shift(explode('?', array_shift(explode('#', $_SERVER['HTTP_REFERER']))));
            header('Location: ' . $loc . '#comments');
            exit;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _track_comment_frequency(  )
    {
        // Log the comment in the session to try and stop spammers
        $_SESSION['ecms']['comments']['spam-check'] = NULL;
    }

    private function _clear_comment_data(  )
    {
        unset($this->_sdata->temp);
    }

    public function update_notification_settings(  )
    {
        // Make sure the user clicked the update button, not the cancel button
        if( array_key_exists('comment-notification-submit', $_POST) )
        {
            // Grab the entries for which the user still wants notifications
            if( array_key_exists('entries', $_POST)
                    && is_array($_POST['entries']) )
            {
                foreach( $_POST['entries'] as $entry_id )
                {
                    if( !isset($where_clause) )
                    {
                        $where_clause = ' `entry_id`<>' . (int) $entry_id;
                    }
                    else
                    {
                        $where_clause .= ' OR `entry_id`<>' . (int) $entry_id;
                    }
                }
            }
            else
            {
                $where_clause = 1;
            }

            // Extract the email and validate it
            $decoded_email = Utilities::hextostr($_POST['email']);
            if( SIV::validate($decoded_email, SIV::EMAIL) )
            {
                $email = $decoded_email;
            }
            else
            {
                ECMS_Error::log_exception(new Exception("Invalid email!"));
            }

            // Build the SQL query
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."comments`
                    SET `subscribed`=0
                    WHERE email = :email
                    AND ( $where_clause )";

            try
            {
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->execute();
                $stmt->closeCursor();

                return TRUE;
            }
            catch( Exception $e )
            {
                ECMS_Error::log_exception($e);
            }
        }
        else
        {
            header( 'Location: ' . SITE_URL );
            exit;
        }
    }

    //TODO: Update the unsubscribe function and make sure it works
    public function unsubscribe() {
        if ( $this->url1 == 'unsubscribe' ) {
            $bid = $this->url2;
            $bloginfo = $this->_getEntryTitleAndAuthor($bid);
            $blog_title = $bloginfo['title'];
            $email = $this->url3;
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                    SET subscribe=0
                    WHERE email=?
                    AND bid=?";
            if($stmt = $this->dbo->prepare($sql))
            {
                $stmt->bind_param("si", $email, $bid);
                $stmt->execute();
                $stmt->close();

                $content = <<<SUCCESS_MSG

                <h1> You Have Unsubscribed </h1>
                <p>
                    You will no longer be notified when comments are
                    posted to the entry "$blog_title".
                </p>
                <p>
                    If you have any questions or if you
                    continue to get notifications, contact
                    <a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
                    for further assistance.
                </p>
SUCCESS_MSG;
            } else {
                $content = <<<ERROR_MSG

                <h1> Uh-Oh </h1>
                <p>
                    Somewhere along the lines, something went wrong,
                    and we were unable to remove you from the mailing list.
                </p>
                <p>
                    Please try again, or contact
                    <a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
                    for further assistance.
                </p>
ERROR_MSG;
            }
        } else {
            header('Location: /');
            exit;
        }

        return $content;
    }
}
