<?php
/**
 *  Class ImageGallery
 * 
 * Description:
 * 		Extends the functionality of the ImageControl class to allow for easy
 * 		reading and display of images stored in a folder.
 * 
 * Requirements:
 * 		PHP 5+
 * 		ImageControl.inc.php
 * 		
 * 
 * Source:
 * 		http://ennuidesign.com/projects/ImageGallery/
 * 
 * Usage:
 * 	<code>
 * 		require_once 'path/to/ImageControl.inc.php';
 * 		require_once 'path/to/ImageGallery.inc.php';
 * 
 * 		try {
 * 			$gal = new ImageGallery();
 * 			$gal->max_dims = array(550, 400);	// Maximum dimensions of the images (width, height, thumbnail)
 * 			$gal->dir = 'gallery/';				// Folder you want to read images from
 * 			$gal->altAttr = 'Class Test';		// Alt attribute (to produce valid markup)
 * 			$gal->getImages();					// Reads all images out of a folder
 * 			$gal->checkSize();					// Makes sure the images are the right size
 * 			$gal->makeThumb(120);				// Creates thumbs, stored in 'thumbs/' in directory defined above
 * 		} catch(Exception $e) {
 * 			echo $e->getMessage();
 * 		}
 * 	</code>
 *
 * @author		Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright	2009 Ennui Design
 * @license		http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version		Release: 1.0.0
 * @link		http://ennuidesign.com/projects/ImageGallery/
 */
class ImageGallery extends ImageControl
{
	/**
	 * Location of ImageControl class
	 * 
	 * @var string
	 */
	public $relAttr;
	public $altAttr = "Created by Ennui Design's ImageGallery Tool";
	public $imgCap_album = NULL;

	/**
	 * Array of files found in the specified folder
	 * 
	 * @var array
	 */
	private $_imageArray = array();

	/**
	 * Loads the class variable with
	 * 
	 * @return void
	 * @access public
	 */
	public function getImages()
	{
        FB::group("Image Loading Data");
        FB::send($this->dir, "Directory");

        if( ($cache=Utilities::check_cache($this->dir))!==FALSE )
        {
            $this->_imageArray = $cache;
        }

        // Make sure the directory exists
		else if( is_dir($this->dir) )
		{
            // Open the directory for reading
			if( $folder = opendir($this->dir) )
			{
                // Loop through the files in the directory
				while( ($file = readdir($folder))!==FALSE )
				{
					/*
					 * Verifies that the current value of $file
					 * refers to an existing file and that the 
					 * file is big enough not to throw an error.
					 */
					if( is_file($this->dir.$file)
                            && is_readable($this->dir.$file)
                            && filesize($this->dir.$file)>11 )
					{
						// Verify that the file is an image
                        //XXX Add EXIF check and fallback
						if( exif_imagetype($this->dir.$file)!==FALSE )
						{
                            FB::send("The file is an image. Added to array.");

                            // Adds the image to the array
							$this->_imageArray[] = $file;
						}
					}
				}

                // Sort the images according using natural sorting
				natsort($this->_imageArray);

                FB::send($this->_imageArray, "Image Array");

                Utilities::save_cache($this->dir, $this->_imageArray);
			}
		}

        FB::groupEnd();
	}

	/**
	 * Create thumbnails for each photo in the folder
	 */
	public function makeThumb($size=140)
	{
		foreach($this->_imageArray as $img) {

			/*
			 * Checks if the file already has a thumbnail
			 */
			if(!file_exists($this->dir.'thumbs/'.$img)) {

				/*
				 * Verifies that the 'thumbs/' directory exists within the main
				 * directory
				 */
				if (!is_dir($this->dir.'thumbs/')) {

					/*
					 * If the directory doesn't exist, creates the folder
					 */
					if(!mkdir($this->dir.'thumbs/',0777,true))
						throw new Exception("Couldn't create the thumbnail 
							directory.");
				}

				/*
				 * Sets the thumbnail size and sends the image for processing
				 */
				$this->max_dims = array($size, $size);
				$this->processStoredImage($this->dir.$img, TRUE);
			}
		}
	}

	/**
	 * Checks if an image is within the defined size constraints
	 * 
	 * @return void
	 */
	public function checkSize()
	{
		$preview = TRUE;
		foreach($this->_imageArray as $img) {
			list($w, $h) = getimagesize($this->dir.$img);

			if(!is_file($this->dir."preview/".$img))
			{
				$preview = FALSE;
			}

			/*
			 * If the image is larger than the defined maximum width and 
			 * height, it's sent to be processed
			 */
			if($w > $this->max_dims[0] || $h > $this->max_dims[1]) {
				$this->processStoredImage($this->dir.$img);
			}
		}
		return $preview;
	}

	/**
	 * Displays the images
	 * 
	 * @return string The HTML to display gallery images.
	 */
	public function displayGallery()
	{
		$image_array = array();
		foreach ( $this->_imageArray as $img )
        {
			if ( isset($this->imgCap_album) )
			{
				$e['caption'] = $this->getImageCaption($img);
			}
			if ( !isset($e['caption']) )
			{
				$e['caption'] = isset($this->imgTitle) ? $this->imgTitle : NULL;
			}
			$e['thumb'] = '/'.$this->dir."thumbs/".$img;
            $e['preview'] = '/'.$this->dir."preview/".$img;
            $e['image'] = '/'.$this->dir.$img;
            $image_array[] = $e;
		}

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate('imagegallery.inc');

        return UTILITIES::parseTemplate($image_array, $template);
	}

	public function getImagesAsArray()
	{
		return $this->_imageArray;
	}

	public function getFirstImage()
	{
		if(isset($this->_imageArray[0]))
		{
			return $this->dir.array_shift($this->_imageArray);
		}
		else
		{
			return NULL;
		}
	}

	public function getNumImages()
	{
		return count($this->_imageArray);
	}

	public function getImageCaption($img)
	{
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
		$sql = "SELECT photo_cap
				FROM `".DB_NAME."`.`".DB_PREFIX."imgCap`
				WHERE album_id=:album
				AND photo_id=:photo
				LIMIT 1";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":album", $this->imgCap_album, PDO::PARAM_INT);
		$stmt->bindParam(":photo", substr($img, 3), PDO::PARAM_STR);
		$stmt->execute();
		$r = $stmt->fetch();
		$stmt->closeCursor();
		return isset($r['photo_cap']) ? stripslashes($r['photo_cap']) : NULL;
	}

	/**
	 * ToString method
	 * 
	 * @return string The result of the displayGallery method
	 */
	public function __toString()
	{
		return $this->displayGallery();
	}
}
