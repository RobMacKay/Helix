<?php

/**
 * A class to perform all common database actions
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
class DB_Actions extends DB_Connect
{

    /**
     * An array to store any loaded entries
     * @var array
     */
    public $entries = array();

    /**
     * Maximum number of entries allowed per page
     * @var int
     */
    protected $entry_limit = MAX_ENTRIES_PER_PAGE;

    /**
     * A string containing all the fields available in the entry database
     *
     * NOTE: If this changes, the write function may need to be updated!
     *
     * @var string  The fields available in the database
     */
    const ENTRY_FIELDS = "
                    `entry_id`,`page_id`,`title`,`entry`,`excerpt`,`slug`,
                    `tags`,`order`,`extra`,`author`,`created`";

    /**
     * Calls the parent constructor to create a PDO object
     *
     * @return void
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Writes data to the database; either updates or creates an entry
     *
     * @return bool        Returns true on success or false on error
     */
    public function save_entry()
    {
        // Initialize all variables to prevent any notices
        $entry_id = '';
        $page_id = '';
        $title = NULL;
        $entry = NULL;
        $excerpt = NULL;
        $slug = "";
        $tags = NULL;
        $extra = array();

        $var_names = array('entry_id', 'page_id', 'title', 'entry', 'excerpt',
                'slug', 'tags', 'author', 'created');

        // Loop through the POST array and define all variables
        foreach( $_POST as $key => $val )
        {
            if( !in_array($key, array('page', 'action', 'token', 'form-submit'))
                    && !in_array($key, $var_names) )
            {
                $extra[$key] = $val;
            }
            else if( $key==="entry" || $key==="excerpt" )
            {
                $$key = $val;
            }
            else
            {
                // If it's not the body of the entry, escape all entities
                $$key = htmlentities($val, ENT_QUOTES, 'UTF-8', FALSE);
            }
        }

        foreach( $_FILES as $key=>$val )
        {
            // If a file was uploaded, handle it here
            if( is_array($_FILES[$key]) && $_FILES[$key]['error']===0 )
            {
                // First, see if the file is an image
                $$key = ImageControl::check_image($_FILES[$key]);

                // If not, just save the file
                if( !$$key )
                {
                    $$key = Utilities::store_uploaded_file($_FILES[$key]);
                }

                $extra[$key] = $$key;
            }
            else if( !empty($_POST[$key.'-value']) )
            {
                $extra[$key] = SIV::clean_output($_POST[$key.'-value'], FALSE, FALSE);
            }
        }

        // If a slug wasn't set, save a URL version of the title
        $slug = empty($slug) ? Utilities::make_url($title) : $slug;

        // Make sure an order value exists
        $order = !empty($order) ? $order : 0;

        // If an excerpt wasn't set, create a text preview
        $excerpt = empty($excerpt) ? strip_tags(Utilities::text_preview($entry)) : $excerpt;

        // Store the author's name and a timestamp
        $author = $_SESSION['user']['name'];
        $created = time();

        // Set up the query to insert or update the entry
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entries`
                (" . self::ENTRY_FIELDS . "
                )
                VALUES
                (
                    :entry_id,
                    (
                        SELECT `page_id`
                        FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                        WHERE `page_slug`=:page_slug
                        LIMIT 1
                    ), :title, :entry, :excerpt, :slug, :tags,
                    :order, :extra, :author, :created
                )
                ON DUPLICATE KEY UPDATE
                    `title`=:title,
                    `entry`=:entry,
                    `excerpt`=:excerpt,
                    `slug`=:slug,
                    `tags`=:tags,
                    `order`=:order,
                    `extra`=:extra;";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->bindParam(":page_slug", $page, PDO::PARAM_INT);
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":entry", $entry, PDO::PARAM_STR);
            $stmt->bindParam(":excerpt", $excerpt, PDO::PARAM_STR);
            $stmt->bindParam(":slug", $slug, PDO::PARAM_STR);
            $stmt->bindParam(":order", $order, PDO::PARAM_INT);
            $stmt->bindParam(":tags", $tags, PDO::PARAM_STR);
            $stmt->bindParam(":extra", serialize($extra), PDO::PARAM_STR);
            $stmt->bindParam(":author", $author, PDO::PARAM_STR);
            $stmt->bindParam(":created", $created, PDO::PARAM_STR);
            $stmt->execute();

            if( $stmt->errorCode()!=='00000' )
            {
                $err = $stmt->errorInfo();

                ECMS_Error::log_exception( new Exception($err[2]) );
            }

            $stmt->closeCursor();

            return TRUE;
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    /**
     * Removes an entry from the database
     *
     * @param int $id       The ID of the entry to delete
     * @return bool         Returns TRUE on success, FALSE on failure
     */
    public function delete($id)
    {
        //TODO: Add a confirmation step here
        $sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE id=:id
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return FALSE;
        }
    }

    /**
     * Returns an entry by its ID
     *
     * @param int $id
     * @return object    An Entry object
     */
    protected function get_entry_by_id($id)
    {
        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT " . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `entry_id`=:id
                LIMIT 1";

        // Check for a cached file
        $cache_id = $sql.$id;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE && strlen($cache)>0 )
        {
            return $cache;
        }

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    /**
     * Retrieves an entry using its URL
     * @param string $url
     * @return array
     */
    protected function get_entry_by_url( $url=NULL )
    {
        // Fails if no URL is supplied
        if( empty($url) )
        {
            ECMS_Error::log_exception( new Exception("No URL supplied.") );
        }

        // Prepare the query and execute it
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `title` LIKE :title
                OR `slug`=:url
                LIMIT 1";

        $cache_id = $sql.$url;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE && count($cache)>0 )
        {
            $this->entries = $cache;
            return;
        }

        // Just in case the entry doesn't have a slug
        $title = '%' . urldecode($url) . '%';

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":url", $url, PDO::PARAM_STR);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    /**
     * Loads entries from the database by category
     *
     * @param string $category  The category by which to filter entries
     * @param int $offset       The query offset
     *
     * @return void
     */
    protected function get_entries_by_tag($category, $offset=0)
    {
        // Prepare the query and execute it
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE LOWER(`tags`) LIKE :category
                ORDER BY `created` DESC
                LIMIT $offset, $this->entry_limit";

        // Check for a cached file
        $cache_id = $sql . $category . $offset . $this->entry_limit;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE && count($cache)>0 )
        {
            $this->entries = $cache;
            return;
        }

        // Prepare the category for the query
        $cat = '%'.str_replace('-', ' ', $category).'%';

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":category", $cat, PDO::PARAM_STR);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    protected function get_recent_entries( $page_id, $num_entries_to_display=8 )
    {
        try
        {
            $sql = "SELECT " . self::ENTRY_FIELDS
                 . "FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                    WHERE `page_id`=:page_id
                    ORDER BY `created` DESC
                    LIMIT 0, $num_entries_to_display";

            $cache_id = $sql.$page_id;
            $cache = Utilities::check_cache($cache_id);
            if( $cache!==FALSE )
            {
                $this->entries = $cache;
                return;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':page_id', $page_id, PDO::PARAM_INT);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e, FALSE);
        }
    }

    protected function get_featured_entries( $num_entries_to_display=8 )
    {
        try
        {
            $sql = "SELECT " . self::ENTRY_FIELDS
                 . "FROM `".DB_NAME."`.`".DB_PREFIX."featured`
                    LEFT JOIN `".DB_NAME."`.`".DB_PREFIX."entries`
                        USING( `entry_id` )
                    LIMIT 0, $num_entries_to_display";

            $cache_id = $sql;
            $cache = Utilities::check_cache($cache_id);
            if( $cache!==FALSE )
            {
                $this->entries = $cache;
                return;
            }

            $stmt = $this->db->prepare($sql);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e, FALSE);
        }
    }

    protected function get_related_entries($tags, $limit=30)
    {
        $tag_arr = array_map('trim', explode(',', $tags));
        $union_sql = NULL;
        $db_params = array();
        $count = 0;

        $sql = "SELECT
                    COUNT(*) AS `common_term_count`," . self::ENTRY_FIELDS
            .  "FROM
                (";

        foreach( $tag_arr as $tag )
        {
            $union_all = $union_sql ? "UNION ALL" : NULL;
            $union_sql .= "
                    $union_all
                    (
                        SELECT
                            `entry_id`, `page_id`, `title`, `entry`, `slug`,
                            `tags`, `extra`, `author`, `created`
                        FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                        WHERE MATCH( `title`, `entry`, `tags` )
                                AGAINST( ? IN BOOLEAN MODE )
                    )";

            $db_params[++$i] = '+' . $tag;
        }

        $sql .= $union_sql . "
                ) AS `combined_results`
                WHERE page = ?
                GROUP BY `slug`
                ORDER BY `common_term_count` DESC
                LIMIT 0, $limit";

        $db_params[++$i] = $this->url0;

        $cache_id = $sql.$tags;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE )
        {
            $this->entries = $cache;
            return;
        }

        try
        {
            $stmt = $this->db->prepare($sql);

            foreach( $db_params as $placeholder_num=>$bound_var )
            {
                $stmt->bindParam($placeholder_num, $bound_var, PDO::PARAM_STR);
            }

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            FB::log($this->mysqli->error, "MySQLi Error");
            die ( "Search Error: " . $e->getMessage() );
        }
    }

    /**
     * Retrieves entries based on a search string
     *
     * @param string $search    The search term(s)
     * @param int $offset       Entry offset for paginations
     *
     * @return void
     */
    protected function get_entries_by_search( $search, $offset=0 )
    {
        // Prepare the statement and execute it
        $sql = "SELECT
                MATCH (`title`,`entry`,`excerpt`,`tags`)
                    AGAINST (:search) AS `relevance`,"
                . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `title` LIKE :title
                OR MATCH (`title`,`entry`,`excerpt`,`tags`)
                    AGAINST (:key_search IN BOOLEAN MODE)
                AND `page_id`=:to_be_searched
                ORDER  BY `relevance` DESC
                LIMIT $offset, $this->entry_limit";

        $cache_id = $sql.$search;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE )
        {
            $this->entries = $cache;
            return;
        }

        try
        {
            $query = htmlentities(urldecode($search), ENT_QUOTES);
            $like = "%$query%";

            $keys = explode(' ', $query);
            $key_search = NULL;
            foreach( $keys as $key )
            {
                $key_search .= empty($key_search) ? "+$key" : " +$key";
            }

            $blog_id = $this->get_page_data_by_slug('blog')->page_id;

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":search", $search, PDO::PARAM_STR);
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":key_search", $key_search, PDO::PARAM_STR);
            $stmt->bindParam(":to_be_searched", $blog_id, PDO::PARAM_INT);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            FB::log($this->mysqli->error, "MySQLi Error");
            die ( "Search Error: " . $e->getMessage() );
        }
    }

    /**
     * Retrieves all values for the given page from the database
     *
     * @param int $offset
     * @param int $limit
     * @return array    A multi-dimensional array of entries
     */
    protected function get_all_entries( $offset=0, $ord="`created` DESC" )
    {
        // Prepare the statement and execute it
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `page_id`= (
                        SELECT `page_id`
                        FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                        WHERE `page_slug`=:page_slug
                    )
                ORDER BY $ord
                LIMIT $offset, $this->entry_limit";

        $cache_id = $sql.$this->url0;
        $cache = Utilities::check_cache($cache_id);
        if( $cache!==FALSE )
        {
            $this->entries = $cache;
            return;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page_slug", $this->url0, PDO::PARAM_STR);

            $this->_load_entry_array($stmt);

            $file = Utilities::save_cache($cache_id, $this->entries);
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    protected function get_entry_count_by_tag()
    {
        $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
        $url1 = empty($this->url1) ? 'tag' : $this->url1;
        $param2 = empty($this->url2) ? 'recent' : $this->url2;
        $param2 = $param2!='recent' ? '%'.str_replace('-',' ',$param2).'%' : '%';
        $num = empty($this->url3) ? 1 : $this->url3;
        $sql = "SELECT
                    COUNT(`title`) AS `the_count`
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `page_id`=(
                        SELECT `page_id`
                        FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                        WHERE `page_slug`=:page_slug
                    )
                AND `tags` LIKE :tag";

        // Check for a cached file
        $cache = Utilities::check_cache($sql.$param1.$param2);
        if( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page_slug", $param1, PDO::PARAM_STR);
            $stmt->bindParam(":tag", $param2, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $data = $result[0]['the_count'];
        }
        catch( Exception $e )
        {
            $this->_log_exception($e);
        }

        // Cache the data
        $file = Utilities::save_cache($sql.$param1.$param2, $data);

        return $data;
    }

    /**
     * Counts the number of entries matching a search term
     *
     * @param string $search    The search term
     * @return int              The entry count
     */
    protected function get_entry_count_by_search($search)
    {
        // Get rid of any non-word or space characters
        $query = htmlentities(urldecode($search), ENT_QUOTES);
        $keys = explode(' ', $query);
        $param2 = NULL;
        foreach ( $keys as $key )
        {
            $param2 .= empty($param2) ? "+$key" : " +$key";
        }
        $param1 = "%$query%";

        $sql = "SELECT
                    COUNT(`title`) AS `the_count`
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `title` LIKE :title
                OR MATCH (`title`,`entry`,`excerpt`,`tags`)
                    AGAINST (:keys IN BOOLEAN MODE)
                AND `page_id`=:to_be_searched";

        $cache = Utilities::check_cache($sql.$param1.$param2);
        if ( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            $blog_id = $this->get_page_data_by_slug('blog')->page_id;

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":title", $param1, PDO::PARAM_STR);
            $stmt->bindParam(":keys", $param2, PDO::PARAM_STR);
            $stmt->bindParam(":to_be_searched", $blog_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $data = $result[0]['the_count'];
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }

        $file = Utilities::save_cache($sql.$param1.$param2, $data);

        return $data;
    }

    protected function getEntryOrder( $entry_id )
    {
        $sql = "SELECT `order`
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `entry_id`=:entry_id
                LIMIT 1";

        $cache = Utilities::check_cache($sql.$param1.$param2);
        if ( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }

        $file = Utilities::save_cache($sql.$param1.$param2, $data);

        return $result['order'];
    }

    protected function get_user_data( $username )
    {
        $sql = "SELECT
                    `email`, `username`, `password`, `display`, `clearance`
                FROM `".DB_NAME."`.`".DB_PREFIX."users`
                WHERE `username`=:username
                LIMIT 1";

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
FB::log($data, "User Data");

        return $data;
    }

    protected function insert_new_user( $email, $vcode )
    {
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."users`
                (
                    `email`, `vcode`, `clearance`
                )
                VALUES
                (
                    :email, :vcode, :clearance
                )";

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":vcode", $vcode, PDO::PARAM_STR);
            $stmt->bindParam(":clearance", $clearance, PDO::PARAM_INT);
            $success = $stmt->execute();
            $stmt->closeCursor();

            return $success;
        }
        catch ( Exception $e )
        {
            $this->_log_exception($e);
        }
    }

    /**
     * Creates pagination for pages with a lot of entries
     *
     * @param int $preview  The number of entries per page
     * @return string       The HTML markup for the pagination
     */
    protected function paginate_entries($preview=BLOG_PREVIEW_NUM)
    {
        if ( $this->url0=="search" )
        {
            $page = 'search';
            $link = $this->url1;
            $num = empty($this->url2) ? 1 : $this->url2;
            $c = array_shift(array_shift($this->get_entry_count_by_tag()));
        }
        else
        {
            $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
            $url1 = empty($this->url1) ? 'category' : $this->url1;
            $url2 = empty($this->url2) ? 'recent' : $this->url2;
            $link = "$url1/$url2";
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = array_shift(array_shift($this->get_entry_count_by_tag()));
        }

        // How many pages shown adjacent to current page
        $span = ENTRY_PAGINATION_SPAN;

        $pagination = "<ul id=\"pagination\">";

        /*
         * Determine minimum and maximum page numbers
         */
        $pages = ceil($c/$preview);

        $prev_page = $num-1;
        if($num==1)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/1/\">&#171;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$prev_page/\">&#139;</a>
                </li>";
        }

        // Determine the page boundaries
        $mod = ($span>$num) ? $span-$num : 0;
        $max_mod = ($num+$span>$pages) ? $span-($pages-$num) : 0;
        $max = ($num+$span<=$pages) ? $num+$span+$mod : $pages;
        $max_num = ($max>$pages) ? $pages : $max;
        $min = ($max_num>$span*2) ? $num-$span-$max_mod : 1;
        $min_num = ($min<1) ? 1 : $min;

        for($i=$min_num; $i<=$max_num; ++$i)
        {
            $sel = ($i==$num) ? ' class="selected"' : NULL;
            $pagination .= "
                <li$sel>
                    <a href=\"/$page/$link/$i/\">$i</a>
                </li>";
        }

        $next_page = $num+1;
        if($next_page>$pages)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/$next_page/\">&#155;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$pages/\">&#187;</a>
                </li>";
        }

        return $pagination . "
            </ul>\n";
    }

    private function _load_entry_array($stmt)
    {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if( $stmt->errorCode()!=='00000' )
        {
            $err = $stmt->errorInfo();

            ECMS_Error::log_exception( new Exception($err[2]) );
        }

        $stmt->closeCursor();

        // Empty the current entry array just in case
        $this->entries = array();

        // Load the entries into a usable array
        foreach( $result as $entry )
        {
            // For legacy compatibility to make sure serialization doesn't break
            $serialized = preg_replace_callback(
                '!(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|i:|o:|N;))!s',
                array('DB_Actions','serialize_fix_callback'),
                $entry['extra']
            );

            // Unserialize any extra data
            $extra = unserialize($serialized);

            // Delete the "extra" array value because we don't need it anymore
            unset($entry['extra']);

            // Loop through and add it back to the entry array
            foreach( $extra as $attr=>$val )
            {
                $entry[$attr] = $val;
            }

            $this->entries[] = new Entry(array_map('stripslashes', $entry));
        }
    }

    protected function serialize_fix_callback( $match )
    {
        return 's:' . strlen($match[2]);
    }

    protected function load_page_data()
    {
        $sql = "SELECT
                    `page_id`, `page_name`, `page_slug`, `type`, `menu_order`,
                    `show_full`, `hide_in_menu`, `parent_id`, `extra`
                FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                ORDER BY `menu_order`";

        try
        {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    protected function get_page_data_by_id( $page_id )
    {
        $sql = "SELECT
                    `page_name`, `page_slug`, `type`, `menu_order`, `show_full`,
                    `hide_in_menu`, `parent_id`, `extra`
                FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                WHERE `page_id`=:page_id
                LIMIT 1";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page_id", $page_id, PDO::PARAM_INT);
            $stmt->execute();
            $entry = array_shift($stmt->fetchAll(PDO::FETCH_ASSOC));

            $result = new stdClass();
            $result->page_name = $entry['page_name'];
            $result->page_slug = $entry['page_slug'];
            $result->type = $entry['type'];
            $result->menu_order = $entry['menu_order'];
            $result->show_full = $entry['show_full'];
            $result->hide_in_menu = $entry['hide_in_menu'];
            $result->parent_id = $entry['parent_id'];
            $result->extra = $entry['extra'];

            $stmt->closeCursor();

            return $result;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    public static function get_page_data_by_slug( $page_slug )
    {
        $sql = "SELECT
                    `page_id`, `page_name`, `page_slug`, `type`, `menu_order`,
                    `show_full`, `hide_in_menu`, `parent_id`, `extra`
                FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                WHERE `page_slug`=:page_slug
                LIMIT 1";

        try
        {
            $dbconn = new DB_Connect();
            $stmt = $dbconn->db->prepare($sql);
            $stmt->bindParam(":page_slug", $page_slug, PDO::PARAM_STR);
            $stmt->execute();
            $entry = $stmt->fetch(PDO::FETCH_OBJ);
            $stmt->closeCursor();

            if( is_object($entry) && isset($entry->type) )
            {
                return $entry;
            }
            else
            {
                // Define a lookup array for special case classes
                $special_cases = array(
                        'admin' => (object) array(
                                'file' => CMS_PATH
                                        . 'core/helper/class.admin.inc.php',
                                'page_name' => 'Admin',
                                'type' => 'Admin'
                            ),
                        'menu' => (object) array(
                                'page_name' => 'Menu',
                                'type' => 'Menu'
                            ),
                        'comments' => (object) array(
                                'file' => CMS_PATH
                                        . 'core/helper/class.comments.inc.php',
                                'page_name' => 'Comment Notification Settings',
                                'type' => 'Comments'
                            ),
                        'search' => (object) array(
                                'file' => CMS_PATH
                                        . 'core/helper/class.search.inc.php',
                                'page_name' => 'Search',
                                'type' => 'Search'
                            )
                    );

                // Certain classes require special lovin' care, provided here
                if( array_key_exists($page_slug, $special_cases) )
                {
                    // Load the class if it won't __autoload()
                    if( isset($special_cases[$page_slug]->file) )
                    {
                        require_once $special_cases[$page_slug]->file;
                    }

                    return $special_cases[$page_slug];
                }

                // If no cases are met, the page doesn't exist
                else
                {
                    echo $page_slug, "\n<br />";
                    return FALSE;
                }
            }
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    public static function get_default_page(  )
    {
        $sql = "SELECT `page_slug`
                FROM `" . DB_NAME . "`.`" . DB_PREFIX . "pages`
                WHERE
                    CASE (
                            SELECT COUNT(`page_slug`)
                            FROM `" . DB_PREFIX . "pages`
                            WHERE `is_default`=1
                        )
                        WHEN 1 THEN `is_default`=1
                        ELSE `menu_order`=1
                    END
                LIMIT 0, 1";

        try
        {
            $dbconn = new DB_Connect();
            $stmt = $dbconn->db->prepare($sql);
            $stmt->execute();
            $entry = array_shift($stmt->fetchAll(PDO::FETCH_ASSOC));

            $result = $entry['page_slug'];

            $stmt->closeCursor();

            return $result;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    protected function get_comments_by_entry_id( $entry_id, $thread_id=0 )
    {
        $sql = "SELECT
                    `comment_id`, `entry_id`, `name`, `email`, `url`,
                    `remote_address`, `comment`, `thread_id`, `created`
                FROM `".DB_NAME."`.`".DB_PREFIX."comments`
                WHERE `entry_id`=:entry_id
                AND `flagged`=0
                AND `thread_id`=:thread_id
                ORDER BY `created`";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->bindParam(":thread_id", $thread_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $stmt->closeCursor();

            return $result;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    protected function get_comment_count( $entry_id )
    {
        $sql = "SELECT
                    COUNT(`entry_id`) AS `the_count`
                FROM `".DB_NAME."`.`".DB_PREFIX."comments`
                WHERE `entry_id`=:entry_id
                    AND `flagged`=0;";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result['0']['the_count'];
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    protected function get_comment_subscriptions_by_email( $email )
    {
        $sql = "SELECT
                    `".DB_PREFIX."comments`.`entry_id`,
                    `".DB_PREFIX."entries`.`title`
                FROM `".DB_NAME."`.`".DB_PREFIX."comments`
                LEFT JOIN `".DB_NAME."`.`".DB_PREFIX."entries`
                    USING( `entry_id` )
                WHERE `".DB_PREFIX."comments`.`email`=:email
                AND `subscribed`=1
                ORDER BY `title`";

        try
        {
            // Validate the email
            if( SIV::validate($email, SIV::EMAIL) )
            {
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->execute();
                $entries = $stmt->fetchAll(PDO::FETCH_OBJ);
                $stmt->closeCursor();

                return $entries;
            }
            else
            {
                ECMS_Error::log_exception(new Exception( 'Invalid email!' ));
            }
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    protected function get_comment_subscribers( $entry_id )
    {
        $sql = "SELECT
                    `name`, `email`
                FROM `".DB_NAME."`.`".DB_PREFIX."comments`
                WHERE `entry_id`=:entry_id
                AND `subscribed`=1
                GROUP BY `email`";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->execute();
            $emails = $stmt->fetchAll(PDO::FETCH_OBJ);
            $stmt->closeCursor();

            return $emails;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    /**
     * Logs an exception, outputs an error message to the console (if debugging)
     *
     * @param object $e The error object
     */
    private function _log_exception($e)
    {
        ECMS_Error::log_exception($e);
    }

    /**
     * Creates the database tables necessary for the CMS to function
     *
     * @param array $menuPages  The menu configuration array
     * @return void
     */
    public static function build_database(  )
    {
        // Loads necessary MySQL to build and populate the database
        $file_array = array();
        $var_arr = array();

        $file_array[] = CMS_PATH.'core/resources/sql/build_database.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_pages.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_entries.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_categories.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_entry_categories.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_featured.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_users.sql';
        $file_array[] = CMS_PATH.'core/resources/sql/build_table_comments.sql';

        // If an admin is initializing the ECMS, create his or her account
        if( DEV_PASS!=='' )
        {
            $filepath = CMS_PATH.'core/resources/sql/insert_users_entry.sql';

            // Create a salted hash of the password
            $password_hash = AdminUtilities::createSaltedHash(DEV_PASS);

            // Assign variables needed to properly parse the file
            $var_arr = array(
                    $filepath => array(
                            'display' => DEV_DISPLAY_NAME,
                            'username' => DEV_USER_NAME,
                            'email' => DEV_EMAIL,
                            'vcode' => sha1(uniqid(time(), TRUE)),
                            'clearance' => DEV_CLEARANCE,
                            'password' => $password_hash
                        )
                );

            // Add the file to the array
            $file_array[] = $filepath;
        }

        // Load the files
        $sql = Utilities::load_file($file_array, $var_arr);

        // Execute the loaded queries
        try
        {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->query($sql);
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

}
