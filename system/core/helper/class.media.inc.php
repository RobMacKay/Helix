<?php

class Media
{
	static function latestVimeo($username)
	{
		// Load the RSS feed
		$xml = simplexml_load_file('http://vimeo.com/api/v2/'.$username.'/videos.xml');

		// Get the first video's ID
		$id = $xml->video[0]->id;

		// Output valid XHTML
		return "
				<object width=\"600\" height=\"338\" 
					type=\"application/x-shockwave-flash\" 
					data=\"http://vimeo.com/moogaloop.swf?clip_id=$id\">
					<param name=\"allowfullscreen\" value=\"true\" />
					<param name=\"allowscriptaccess\" value=\"always\" />
					<param name=\"movie\" 
						value=\"http://vimeo.com/moogaloop.swf?clip_id=$id\" />
				</object>\n";
	}

	/**
	  * Rewrite of original latestYouTube method to allow multiple videos and more flexibility.
	  *
	  * @author Jason Lengstorf (original author, kudos to the might bearded one)
	  * @author Drew Douglass (rewrite)
	  * @param array $settings - Array with keys and values for options.
	  * 	@param $settings["username"] - YouTube username - required! This is the only required array key.
	  * 	@param $settings["class"] -The div class to wrap each video in, default is "youtube".
	  * 	@param $settings["rel"] - Toggle on or off related videos when video ends, defaults to 0.
	  * 	@param $settings["count"] - Amount of videos to include, defaults to 1.
	  *		@param $settings["color1"] - Color one setting, defaults to 0x000000
	  *		@param $settings["color2"] - Color two setting, defaults to 0x0000FF
	  * 	@param $settings["border"] - The border int setting, defaults to 0.
	  *		@param $settings["showsearch"] - Show or hide search, defaults to 0.
	  *		@param $settings["showinfo"] - Show or hide info, defaults to 0.
	  *		@param $settings["fs"] - Fullscreen toggle, defaults to 0.
	  * 	@param $settings["autoplay"] - Toggles auto play on or off, defaults to 0. 
	  * 	@param $settings["start"] -Number of seconds into the video to start at, defaults to 0.
	  *		@param $settings["hd"] -Enable HD if available, defaults to 0.
	  * @return str (containing markup) | bool (on failure)
	  *
	  * NOTE: The width and height are set in the config/config.inc.php file!
	  *
	  * "Basic" Usage Example: 
	 	 <?php 
			$video_opts = array("username" => "BritneySpearsVEVO");
			echo Media::latestYouTube($video_opts);
		 ?>
	  *
	  *
	  *
	  *
	  * "Advanced" Usage Example:
	  	 <?php 
	  		$video_opts = array(
							"username" => "BritneySpearsVEVO",
							"count" => 5,
							"rel" => 1,
							"fs" => 1,
							"class" => "my-videos"
							);
			echo Media::latestYouTube($video_opts);
		 ?>
	  *
	  *
	  * 
	  */
	public static function latestYouTube($settings = array())
	{
		//Check to ensure username was passed. 
		if ( array_key_exists("username", $settings) )
		{
			//Set defaults as variables
			//We're setting them individually here as to easily overwrite them below in one loop.
			$class = "youtube";
			$rel = 0;
			$count = 1;
			$color1 = "0x000000";
			$color2 = "0x0000FF";
			$border = 0;
			$showsearch = 0;
			$showinfo = 0;
			$fs = 0;
			$autoplay = 0;
			$start = 0;
			$hd = 0;
			
			//Loop through and set any keys passed as variables, overwriting defaults only as needed.
			foreach ($settings as $key => $value)
			{
				$$key = $value;
			}
			
			//Ensure count is greater than 0. 
			$count = ((int)$count) <= 0 ? 1 : $count;
			
			// Load the RSS feed
			$xml = simplexml_load_file("http://www.youtube.com/rss/user/$username/videos.rss");
			
			//Variable holding final markup
			$videos = "";
			//Increment counter 
			$i = 0;
			
			//Pull out each link and snip off the arguments 
			//Create the markup with user options
			foreach($xml->channel->item as $key => $value)
			{
				//Check to see if we reached our count limit 
				if($i >= $count){break;}

				//Grab the video argument string and discard the rest.
				$id = str_replace("http://www.youtube.com/watch?v=", "", $value->link);
				$url_args = "?rel=$rel&count=$count&color1=$color1&color2=$color2&border=$border&showsearch=$showsearch"
				."&showinfo=$showinfo&fs=$fs&autoplay=$autoplay&start=$start&hd=$hd";
				
				//Build markup 
				$videos .= "<div class=".$class.">"
				. "<object width=".PAGE_OBJ_WIDTH." height=".PAGE_OBJ_HEIGHT." 
					type=\"application/x-shockwave-flash\" 
					data=\"http://www.youtube.com/v/$id$url_args\">
					<param name=\"allowfullscreen\" value=".($fs = 1 ? "true" : "false")." />
					<param name=\"allowscriptaccess\" value=\"always\" />
					<param name=\"movie\" 
						value=\"http://www.youtube.com/v/$id$url_args\" />
				</object>\n"
				."</div><!--End".$class."-->";
				
				//Increment the counter 
				$i++;
			}
			return $videos;
			
		}
		//No username passed, we're done here.
		return false;
	}

	static function loadFlickr($username, $class=NULL)
	{
		$feed = "http://api.flickr.com/services/feeds/photos_public.gne?id="
			. $username . "&lang=en-us&format=rss_200";
		$link = "http://flickr.com/$username";
		$rss = simplexml_load_file($feed);
		$photodisp = "\n\t<ul class=\"$class\">\n";
		foreach ($rss->channel->item as $item) {
		    $title = $item->title;
		    $media  = $item->children('http://search.yahoo.com/mrss/');
		    $image  = $media->thumbnail->attributes();
		    $url    = str_replace('_s', '', $image['url']);

		    $photodisp .= <<<________________EOD

	    <li>
			<img src="$url" 
				title="$title"
				alt="$title"
				style="border:0;" />
		</li>
________________EOD;
			}
			
			return $photodisp . "\n\t</ul>\n<a href=\"$link\">View on Flickr</a>";
	}
	
	/**
	  * Returns a shortened bit.ly link given a "long" anchor. 
	  * Attempts to use cURL before file_get_contents for performance reasons, see results here:
	  * http://stackoverflow.com/questions/555523/file-get-contents-vs-curl-what-has-better-performance
	  *
	  * @author - Drew Douglass
	  * @param str $username - The bitly username to use.
	  * @param str $key - The api key attached to the username. 
	  * @param str $link - The link to shorten, no HTML (i.e. http://google.com)
	  * @param [optional] int $timeout - The time in seconds until timeout, if performance is a major concern stick with 2 or less.
	  * @return str The shortened link
	  * 
	  * Example usage: <?php echo Media::bitlyShorten("yourBitlyUsername","YOURAPIKEY","http://google.com"); ?>
	  */
	public static function bitlyShorten($username, $key, $link, $timeout = 2)
	{
		// Generate the URL to send to the bit.ly API
		$url = "http://api.bit.ly/shorten?version=2.0.1&login=$username"
			. "&apiKey=$key&longUrl=$link";

			//Attempt to use cURL first as it is very fast.
	  		if ( in_array("curl", get_loaded_extensions()) )
	  		{
	  			$ch = curl_init($url);
	  			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  			$short_link = json_decode(curl_exec($ch),true);
	  
	  			if ($short_link["errorCode"] === 0) {
	  				return $short_link["results"][$link]["shortUrl"];
	  			}
	  			//Else an error was present when using the API 
	  			return false;
	  		}
	  		
	  		//cURL not available, try file_get_contents (slight performance hit)
	  		elseif ( function_exists("file_get_contents") )
	  		{
	  			$short_link = file_get_contents($url);
	  			$short_link = json_decode($short_link,true);
	  			if ($short_link["errorCode"] === 0) {
	  				return $short_link["results"][$link]["shortUrl"];
	  			}
	  			//Else an error was present when using the API 
	  			return false;
	  		}
	  		
	  		else 
	  		{
	  			return false;
	  		}
	  }
}

?>