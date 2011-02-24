<?php

/*
 * ***************************************************
 * RSS Feed Class 
 * @author Jason Lengstorf 
 * @author Drew Douglass
 * ***************************************************
 */
  // DB Info
  	include_once '../ennui-cms/config/config.inc.php';
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

  // Set the site name
  	$siteFull = SITE_NAME;
    $siteName = SITE_URL;
    $siteDesc = SITE_DESCRIPTION;

    $xml=<<<________EOD
<?xml version="1.0"?>
<rss version="2.0">
  <channel>
 
    <title>$siteFull</title>
    <link>$siteName</link>
    <description>
      $siteDesc
    </description>
    <language>en-us</language>

________EOD;

    $sql = "SELECT title, img, imgcap, body
    		FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
    		WHERE page='blog'
    		ORDER BY created DESC
    		LIMIT 15";
    foreach($db->query($sql) as $a) {
      $title = str_replace('&','&amp;',stripslashes($a['title']));
      $urltitle = urlencode($a['title']);

      $para = stripslashes($a['body']);
      if (!empty($a['img']))
        $desc = "<img src=\"{$siteName}/{$a['img']}\" alt=\"{$a['imgcap']}\" /><br />";
      else
        $desc = "";
      $desc .= $para . '<br /><br />';
      $desc .= "(To read and post comments for this entry, visit ";
      $desc .="<a href=\"{$siteName}/blog/{$urltitle}\">";
      $desc .="$siteName</a>)<hr />";
      $desc = nl2br($desc);
      $desc = str_replace('&','&amp;',$desc);
      $desc = str_replace('<','&lt;',$desc);
      $desc = str_replace('>','&gt;',$desc);
      $xml.=<<<____________EOD

    <item>
      <title>{$title}</title>
      <description>{$desc}</description>
      <link>{$siteName}/blog/{$urltitle}</link>
      <guid>{$siteName}/blog/{$urltitle}</guid>
    </item>
____________EOD;
    }

    $xml.=<<<________EOD

  </channel>

</rss>
________EOD;

    print_r(stripslashes($xml));
?>