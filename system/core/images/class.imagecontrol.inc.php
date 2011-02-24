<?php
/**
 *  Class ImageControl
 *
 * Description:
 *         A set of controls to easily upload, move, and resize images. Works with
 *         JPG, GIF, and PNG files (preserves alpha transparency with PNG files).
 *
 * Source:
 *         http://ennuidesign.com/projects/ImageControl/
 *
 * Usage:
 *         <code>
 *         $myClass = new ImageControl(400, 325);
 *         </code>
 *
 * @author        Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright    2009 Ennui Design
 * @license        http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version        Release: 1.0.0
 * @link        http://ennuidesign.com/projects/ImageControl/
 */
class ImageControl
{

    /**
     * Directory to save/find files
     *
     * NOTE: The trailing slash is necessary for proper execution
     *
     * @var string
     */
    public $dir = IMG_SAVE_DIR;

    /**
     * Dimensions to which image will be resized
     *
     * Format: array(
     *             0=>500, // Maximum allowed width
     *             1=>375, // Maximum allowed height
     *            )
     *
     * @var array
     */
    public $max_dims = array(500, 375);

    /**
     * Filter to be applied to an image via image_filter()
     *
     * @var string The filter name (i.e. IMG_FILTER_GRAYSCALE)
     */
    public $filter;

    /**
     * If applicable, arguments to accompany the selected filter
     *
     * @var array The filter arguments
     */
    public $filter_args = array();

    /**
     * The suffix to be added to filtered image file names
     *
     * If empty, the original image will be overwritten.
     *
     * @var string The suffix to be appended to the file name
     */
    public $filter_suffix = "-filtered";

    /**
     * If TRUE, creates a square thumbnail of selected images
     *
     * @var bool
     */
    public $thumb = FALSE;

    /**
     * If TRUE, places resized images in a folder called preview
     *
     * @var bool
     */
    public $preview = FALSE;

    /**
     * Establishes dimensions for the image, then sends for processing
     *
     * @param array $file
     * @return string    Image path on success or FALSE on failure
     */
    public function check_image( $file, $dir=NULL, $rename=TRUE )
    {
        $img_ctrl = new ImageControl;

        if( isset($dir) && strlen($dir)>0 )
        {
            $img_ctrl->dir = $dir;
            $img_ctrl->checkDir();
        }

        // If no errors occurred in the upload, process the image
        if( $file['error']==0 )
        {
            // Make sure it's processed as the right type of image
            if( $file['type']!==IMG_JPG
                    || $file['type']!==IMG_GIF
                    || $file['type']!==IMG_PNG )
            {
                $extension = array_shift(array_reverse(explode('.', $file['name'])));

                if( $extension==='jpg' )
                {
                    $file['type'] = IMG_JPG;
                }
                else if( $extension==='gif' )
                {
                    $file['type'] = IMG_GIF;
                }
                else if( $extension==='png' )
                {
                    $file['type'] = IMG_PNG;
                }
                else
                {
                    ECMS_Error::log_exception( new Exception("Unrecognized file type") );
                }
            }

            $img_ctrl->max_dims = array(IMG_MAX_WIDTH, IMG_MAX_HEIGHT);
            try
            {
                // Store the image
                $stored = $img_ctrl->processUploadedImage($file, $rename);
                if( !$stored )
                {
                    return FALSE;
                }
                else
                {
                    // Create a preview of the image
                    $img_ctrl->preview = TRUE;
                    $img_ctrl->max_dims = array(IMG_PREV_WIDTH, IMG_PREV_HEIGHT);
                    if( !$img_ctrl->processStoredImage($stored) )
                    {
                        throw new Exception("Couldn't create image preview!");
                    }

                    // Create a square thumbnail of the image
                    $img_ctrl->preview = FALSE;
                    $img_ctrl->max_dims = array(IMG_THUMB_SIZE, IMG_THUMB_SIZE);
                    if( $img_ctrl->processStoredImage($stored, TRUE) )
                    {
                        return substr($stored, 0, 1)==='/' ? $stored : '/' . $stored;
                    }
                    else
                    {
                        return FALSE;
                    }
                }
            }
            catch( Exception $e )
            {
                ECMS_Error::log_exception($e);
            }
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Resizes/resamples an image uploaded via a web form
     *
     * @param array $upload    the array contained in the $_FILES superglobal
     * @param bool    $rename    flag that determines whether or not to rename the file
     *
     * @return string        the path to the resized uploaded file
     * @access public
     */
    public function processUploadedImage($upload, $rename=TRUE)
    {
        // Simplify the uploaded file data
        list($name, $type, $tmp, $err, $size) = array_values($upload);

        // Collect file type information and get new dimensions
        $imgInfo = $this->getImageInfo($type);

        $filename = $this->renameFile($name, $imgInfo[0], $rename);
        $fullpath = $this->dir . $filename;

        /*
         * Verify that the upload directory has been created
         */
        $this->checkDir();

        if(!move_uploaded_file($tmp,$fullpath)) {
            throw new Exception('Could not move file ('
                . $name
                . ') to the specified directory ('
                . $this->dir
                . ')');
        }

        return $this->doProcessing($fullpath, $imgInfo);
    }

    /**
     * Resizes/resamples an image already stored on the server
     *
     * @param string $image    path to the image on the server
     * @param bool $thumb    whether or not a thumbnail should be made
     * @param bool $rename    whether or not the file should be renamed
     *
     * @return string        the path to the new, processed image
     */
    public function processStoredImage($image, $thumb=FALSE, $rename=FALSE)
    {
        /*
         * exif_imagetype() not available on all platforms but is much quicker.
         * This check prevents possible errors.
         */
        if(function_exists('exif_imagetype'))
        {
            $type = exif_imagetype($image);
        }
        else
        {
            $info = getimagesize($image);
            $type = $info[2];
        }

        $imgInfo = $this->getImageInfo($type);

        /*
         * Verify that the thumb directory has been created
         */
        $this->thumb = $thumb;
        $this->checkDir();

        if(($fullpath=$this->doProcessing($image, $imgInfo)) !== false) {
            return $fullpath;
        } else {
            throw new Exception('Error processing the file.<br />\n');
        }
    }

    /**
     * Accesses methods to find dimensions and resize the image
     *
     * @param string $fullpath    the path to the image on the server
     * @param array  $imgInfo    array of information about the image
     *
     * @return string            the path to the processed image
     */
    private function doProcessing($fullpath, $imgInfo)
    {
        $dims = $this->getNewDimensions($fullpath);
        if($loc = $this->doImageResize($fullpath, $dims, $imgInfo)) {
            return $loc;
        } else {
            return false;
        }
    }

    /**
     * Determines the file type and associated processing functions
     *
     * @param string $type  the image file type
     *
     * @return array    an array with 3 elements:
     *                     0 - file extension,
     *                     1 - imagecreatefrom___ function name,
     *                     2 - image___ function name
     * @access private
     */
    private function getImageInfo($type)
    {
        // Make sure the quality setting is within range
        $quality = ( IMG_QUALITY >=0 && IMG_QUALITY<=9) ? IMG_QUALITY : 7;

        switch( $type )
        {
            case IMG_JPG:
            case IMAGETYPE_JPEG:
            case 'image/jpeg':
            case 'image/pjpeg':
                return array(
                            '.jpg',
                            'imagecreatefromjpeg',
                            'imagejpeg',
                            $quality*10
                        );
                break;
            case IMG_GIF:
            case IMAGETYPE_GIF:
            case 'image/gif':
                return array(
                            '.gif',
                            'imagecreatefromgif',
                            'imagegif',
                            $quality
                        );
                break;
            case IMG_PNG:
            case IMAGETYPE_PNG:
            case 'image/png':
                return array(
                            '.png',
                            'imagecreatefrompng',
                            'imagepng',
                            $quality
                        );
                break;
            default:
                ECMS_Error::log_exception(
                        new Exception("Uploaded file must be GIF, JPG, or PNG.\n"
                                . "Supplied file type: " . $type . "\n"
                                . "IMG_PNG: " . IMG_PNG . "\n"
                                . "IMG_WBMP: " . IMG_WBMP . "\n"
                                . "IMG_GIF: " . IMG_GIF . "\n"
                                . "IMG_JPG: " . IMG_JPG . "\n"
                                . "IMG_XPM: " . IMG_XPM . "\n"
                                . "Type is XPM: " . print_r(IMG_XPM===$type, TRUE) . "\n")
                    );
                break;
        }
    }

    /**
     * Checks image dimensions and scales down if image exceeds maximum specs
     *
     * @param string $img    the path to the image to be checked
     *
     * @return array     an array containing 4 elements:
     *                    0 - new width,
     *                    1 - new height,
     *                    2 - original width,
     *                    3 - original height,
     *                    4 - X offset (for thumbs),
     *                    5 - Y offset (for thumbs)
     * @access private
     */
    private function getNewDimensions($img)
    {
        if(!$img) {
            throw new Exception('No image supplied');
        } else {

            /*
             * Get the dimensions of the original image
             */
            list($src_w,$src_h) = getimagesize($img);

            /*
             * If the image is bigger than our max values, calculate
             * new dimensions that keep the aspect ratio intact
             */
            if($src_w>$this->max_dims[0] || $src_h>$this->max_dims[1]) {

                // Squares off the image if set to TRUE
                if ( $this->thumb===TRUE )
                {
                    $new[0] = $this->max_dims[0];
                    $new[1] = $this->max_dims[1];

                    // Determine the scale
                    $scale = min(
                            $this->max_dims[0]/$src_w,
                            $this->max_dims[1]/$src_h
                        );

					if ( $src_w>$src_h )
                    {
                        $to_x = round(($src_w-$src_h)/2);
						$to_y = 0;
						$src_w = $src_h;
					}
                    else
                    {
						$to_x = 0;
						$to_y = round(($src_h-$src_w)/2);
						$src_h = $src_w;
					}
                }

                // Non-thumbnail resizing
                else
                {
                    // Determine the scale
                    $scale = min(
                            $this->max_dims[0]/$src_w,
                            $this->max_dims[1]/$src_h
                        );

                    // Determines the short side for a later double-check
                    $dblchk = $src_w>$src_h ? 1 : 0;
                    $to_x = 0;
                    $to_y = 0;

                    // Gets the new scaled dimensions
                    $new = array(
                            round($scale*$src_w),
                            round($scale*$src_h)
                        );

                    /*
                     * Double-checks to make sure image fits within the
                     * boundaries and processes it again if not
                     */
                    if ( $new[$dblchk]>$this->max_dims[$dblchk] )
                    {
                        $scale = $this->max_dims[$dblchk]/$new[$dblchk];
                        $new = array(
                                round($scale*$new[0]),
                                round($scale*$new[1])
                            );
                    }
                }

                // Sets the array to return
                return array($new[0], $new[1], $src_w, $src_h, $to_x, $to_y);
            }
            else
            {
                return array($src_w, $src_h, $src_w, $src_h, 0, 0);
            }
        }
    }

    /**
     * Creates a resized version of the supplied image
     *
     * @param string $loc    the path to the image
     * @param array  $dims    array of image size information
     * @param array  $funcs    array of image type-specific functions supplied by getImageInfo()
     *
     * @return bool        true on success
     * @access private
     */
    private function doImageResize($loc, $dims, $funcs)
    {
        /*
         * Sets the final location for the file depending upon whether or not
         * the script is creating a thumbnail or not
         */
        if($this->thumb===TRUE) {
            $finalloc = str_replace($this->dir, $this->dir.'thumbs/', $loc);
        } elseif($this->preview===TRUE) {
            $finalloc = str_replace($this->dir, $this->dir.'preview/', $loc);
        } else {
            $finalloc = $loc;
        }

        /*
         * If a filter has been specified, add the filter suffix to the file
         * name so the filtered image doesn't override the default image.
         */
        if(isset($this->filter)) {
            $pattern = '/([\w]+)(\.[a-z]{3,4})/';
            $replacement = "$1$this->filter_suffix$2";
            $finalloc = preg_replace($pattern, $replacement, $finalloc);
        }

        /*
         * Use the stored functions from getImageInfo() to create an image
         * resource and a resource to copy the resampled image into
         */
        $src_img = $funcs[1]($loc);
        $new_img = imagecreatetruecolor($dims[0], $dims[1]);

        /*
         * Because PNG images support alpha transparency, they are handled
         * differently here.
         */
        if($funcs[0]=='.png') {
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
        }

        /*
         * Resamples the image, then free the resources used for the original
         */
        if(imagecopyresampled($new_img, $src_img, 0, 0, $dims[4], $dims[5], $dims[0], $dims[1], $dims[2], $dims[3])) {
            imagedestroy($src_img);

            /*
             * Runs the filtering function if a filter was specified
             */
            if(isset($this->filter)
                && $filtered=$this->applyFilter($new_img)) {
                $new_img = $filtered;
            }

            /*
             * Saves the newly resized and resampled image in the
             * destination specified above, then frees the resources used for
             * the temporary image
             */
            if($new_img && $funcs[2]($new_img, $finalloc, $funcs[3])) {
                imagedestroy($new_img);
                return $finalloc;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Applies a filter to an image using image_filter()
     *
     * @param resource $im An image resource to be filtered
     *
     * @return resource The filtered image
     */
    private function applyFilter($im)
    {
        if(function_exists('imagefilter')) {
            list($arg1, $arg2, $arg3, $arg4) = $this->filter_args;
            if($im && imagefilter($im, $this->filter, $arg1, $arg2, $arg3, $arg4)) {
                return $im;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * If set to TRUE, generates a new name for a file
     *
     * @param string $name        the name of the file
     * @param string $ext        the file extension
     * @param bool   $rename    flag to trigger rename
     *
     * @return string    the new file name
     * @access private
     */
    private function renameFile($name, $ext, $rename)
    {
        return ($rename===TRUE) ? time().'_'.mt_rand(1000,9999).$ext : $name;
    }

    /**
     * Checks if a directory exists, then creates it if it doesn't
     *
     * @return void
     */
    private function checkDir()
    {
        $dir = ($this->thumb===TRUE) ? $this->dir.'thumbs/' : $this->dir;
        $dir = ($this->preview===TRUE) ? $this->dir.'preview/' : $dir;

        if( !is_dir($dir) && strlen($dir)>0 )
        {
             if( !mkdir($dir,0755,TRUE) )
             {
                ECMS_Error::log_exception(
                        new Exception("'$dir' could not be created.<br />")
                    );
             }
             else
             {
                 return TRUE;
             }
        }
        else
        {
            return TRUE;
        }
    }

}
