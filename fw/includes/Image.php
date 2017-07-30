<?php
namespace System;

// Check environment
if  (!defined('SYSTEM_PATH'))
{
	trigger_error(_('Invalid environment detected'), E_USER_ERROR);
}

// Load the MIME handler
require_once(SYSTEM_PATH . '/includes/MIME.php');

/*!
 * @brief A trait to handle images
 *
 * A trait to handle some basic image functionality
 * with both GD and ImageMagick.
 * 
 * The class constructor should call the CheckEnvironment
 * method to make sure we have an extension loaded
 * and valid constants in environment.
 *
 * $Author: mireiawen $
 * $Id: Image.php 448 2017-07-11 22:19:58Z mireiawen $
 * @copyright GNU General Public License, version 2; http://www.gnu.org/licenses/gpl-2.0.html
 */
trait Image
{
	/*!
	 * @brief Check that the environment is good
	 *
	 * Check that we have required extensions loaded
	 * and used constants defined.
	 *
	 * @throws Exception on failure
	 */
	protected function CheckEnvironment()
	{
		// Make sure GD or ImageMagick extension is available
		if ((!extension_loaded('gd')) && (!extension_loaded('imagick')))
		{
			throw new Exception(_('ImageMagick or GD extension is required!'));
		}
		
		// Check if forcing of GD use is set
		if (!defined('IMAGES_FORCE_GD'))
		{
			define('IMAGES_FORCE_GD', FALSE);
		}
	}

	/*!
	 * @brief Get the %MIME type for the file
	 *
	 * Try to get a valid %MIME type for the given file
	 * with the help from the MIME class.
	 *
	 * @param string $filename
	 * 	Filename to check
	 * @retval string
	 * 	The read %MIME type
	 * @throws Exception if file is not readable
	 */
	public function FileMimeType($filename)
	{
		if (!is_readable($filename))
		{
			throw new \Exception(sprintf(_('File "%s" was not found or was not readable'), $filename));
		}
		
		return MIME::Type($filename);
	}
	
	/*!
	 * @brief Get the image size from file
	 *
	 * Get the image size, width and height
	 * in pixels from a file
	 *
	 * @param string $filename
	 * 	File to read
	 * @retval array
	 * 	Array with width and height
	 * @throws Exception on failure
	 */
	protected function GetFileDimensions($filename)
	{
		try
		{
			// Get image object from the file
			$image = $this -> Create($filename);
			
			// And get the size info
			return $this -> GetImageDimensions($image);
		}
		catch (\Exception $e)
		{
			throw new Exception(sprintf(_('Unable to read image size from "%s": %s'), $filename, $e -> getMessage()));
		}
	}

	/*!
	 * @brief Get the image size from initialized object
	 *
	 * Get the image size, width and height
	 * in pixels from an existing object
	 *
	 * @param object $image
	 * 	Image object, either GD or ImageMagick 
	 * 	depending on settings
	 * @retval array
	 * 	Array with width and height
	 * @throws Exception on failure
	 */
	protected function GetImageDimensions($image)
	{
		// Check ImageMagick object
		if ((class_exists('\\Imagick')) && ($image instanceof \Imagick))
		{
			// Read image size
			$x = $image -> getImageWidth();
			$y = $image -> getImageHeight();
		}
		
		// Otherwise use GD
		else
		{
			// Read image size
			$x = @imagesx($image);
			$y = @imagesy($image);
		}
		
		// Make sure we were able to read the size
		if (($x === FALSE) || ($y === FALSE))
		{
			throw new \Exception(_('Unknown size'));
		}
		
		return array(
			'width' => $x,
			'height' => $y,
		);
	}
	
	/*!
	 * @brief Resize the image to a new pixel size
	 *
	 * Resize the image to a new pixel size.
	 * If one size is 0, then it is calculated from
	 * the image ratio and the other size.
	 *
	 * @param string $filename
	 * 	File to read
	 * @param int $xsize,$ysize
	 * 	New pixel size for the image
	 * @retval object
	 * 	The resized image as either GD or ImageMagick
	 * 	type depending on the availablility of the libraries
	 * @throws Exception on failure
	 */
	protected function Resize($filename, $xsize = 0, $ysize = 0)
	{
		// Make sure input is valid
		if ((!is_int($xsize)) || (!is_int($ysize)))
		{
			throw new \InvalidArgumentException(sprintf(_('Expected 2 integers, got %s and %s'), gettype($xsize), gettype($ysize)));
		}
		
		// Make sure we have at least one size is valid
		if (($xsize < 1) && ($ysize < 1))
		{
			throw new \Exception(sprintf(_('Invalid size for the image: %dx%d'), $xsize, $ysize));
		}
		
		// Get the image itself
		$src = $this -> Create($filename);
		$src_size = $this -> GetImageDimensions($src);
		$src_x = $src_size['width'];
		$src_y = $src_size['height'];
		
		// Get the scaling information
		if ($xsize < 1)
		{
			$ratio = $src_y / $dst_y;
			$dst_x = (int)(round($src_x / $ratio));
		}
		else
		{
			$dst_x = $xsize;
		}
		
		if ($ysize < 1)
		{
			$ratio = $src_x / $dst_x;
			$dst_y = (int)(round($src_y / $ratio));
		}
		else
		{
			$dst_y = $ysize;
		}
		
		// Check ImageMagick object
		if ((class_exists('\\Imagick')) && ($src instanceof \Imagick))
		{
			// Resize the image
			$src -> resizeImage($dst_x, $dst_y, \Imagick::FILTER_LANCZOS, 1);
			$src -> setImagePage($dst_x, $dst_y, 0, 0);
			
			// And return it
			return $src;
		}
		
		// Otherwise use GD
		else
		{
			// Create the destination
			$dst = imagecreatetruecolor($dst_x, $dst_y);
			if ($dst === FALSE)
			{
				throw new \Exception(sprintf(_('Unable to create the resized image: %s'), _('GD failed, see server error log')));
			}
			
			// Resize it
			if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_x, $dst_y, $src_x, $src_y))
			{
				throw new \Exception(_('Unable to scale the image'));
			}
			
			// And return it
			return $dst;
		}
	}
	
	/*!
	 * @brief Scale the image
	 *
	 * Scale the image to new ratio, where 1.0
	 * is 100% ratio. If $yscale is 0.0, then
	 * $xscale is used for both.
	 *
	 * @param string $filename
	 * 	File to read
	 * @param float $xscale,$yscale
	 * 	New scale for the image
	 * @retval object
	 * 	The resized image as either GD or ImageMagick
	 * 	type depending on the availablility of the libraries
	 * @throws Exception on failure
	 */
	protected function Scale($filename, $xscale, $yscale = 0.0)
	{
		// Make sure we have x-scale
		if (!$xscale)
		{
			throw new \Exception(sprintf(_('Invalid scale for the image: %f'), $xscale));
		}
		
		// Get the image itself
		$src = $this -> Create($filename);
		$src_size = $this -> GetImageDimensions($src);
		$src_x = $src_size['width'];
		$src_y = $src_size['height'];
		
		// Get the scaling information
		if (!$yscale)
		{
			$yscale = $xscale;
		}
		$dst_x = round($src_x * $xscale);
		$dst_y = round($src_y * $yscale);
		
		// Check ImageMagick object
		if ((class_exists('\\Imagick')) && ($src instanceof \Imagick))
		{
			// Resize the image
			$src -> resizeImage($dst_x, $dst_y, \Imagick::FILTER_LANCZOS, 1);
			$src -> setImagePage($dst_x, $dst_y);
			
			// And return it
			return $src;
		}
		
		// Otherwise use GD
		else
		{
			// Create the destination
			$dst = imagecreatetruecolor($dst_x, $dst_y);
			if ($dst === FALSE)
			{
				throw new \Exception(sprintf(_('Unable to create the resized image: %s'), _('GD failed, see server error log')));
			}
			
			// Resize it
			if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_x, $dst_y, $src_x, $src_y))
			{
				throw new \Exception(_('Unable to scale the image'));
			}
			
			// And return it
			return $dst;
		}
	}
	
	/*!
	 * @brief Create image handle from file
	 *
	 * Create image handle from file, possibly 
	 * using %MIME type to guess the correct image 
	 * format with GD
	 *
	 * @param string $filename
	 * 	The filename to read into image
	 * @retval object
	 * 	Image resource identifier
	 * @throws Exception if image cannot be created
	 */
	protected function Create($filename)
	{
		// Default to ImageMagick
		if ((!IMAGES_FORCE_GD) && (extension_loaded('imagick')))
		{
			$image = new \Imagick();
			$image -> readImage($filename);
			return $image;
		}
		
		// Otherwise use GD
		else
		{
			// Get the MIME type for the file
			$mime = $this -> GetMIME();
			
			// Create the image based on the MIME type
			switch ($mime)
			{
			case 'image/png':
				if (!function_exists('createimagefrompng'))
				{
					throw new \Exception(sprintf(_('Support for file type %s is missing'), $mime));
				}
				$image = createimagefrompng($filename);
				break;
			case 'image/jpeg':
			case 'image/jpg':
				if (!function_exists('createimagefromjpeg'))
				{
					throw new \Exception(sprintf(_('Support for file type %s is missing'), $mime));
				}
				$image = createimagefromjpeg($filename);
				break;
			case 'image/gif':
				if (!function_exists('createimagefromgif'))
				{
					throw new \Exception(sprintf(_('Support for file type %s is missing'), $mime));
				}
				$image = createimagefromgif($filename);
				break;
			default:
				throw new \Exception(sprintf(_('Invalid image type "%s"'), $mime));
			}
			
			// Check that creation was success
			if ($image === FALSE)
			{
				throw new \Exception(sprintf(_('Invalid image file "%s" for MIME type "%s"'), $this -> GetFilename(), $mime));
			}
			
			return $image;
		}
	}
	
	/*!
	 * @brief Output the image data
	 *
	 * Output the actual image data to the 
	 * output stream. This tries to select 
	 * the correct methods to do the actual
	 * output.
	 *
	 * @param object $image
	 * 	Image object, either GD or ImageMagick 
	 * 	depending on settings
	 */
	public function WriteImage($image)
	{
		// Check ImageMagick object
		if ((class_exists('\\Imagick')) && ($image instanceof \Imagick))
		{
			echo $this -> image -> getImageBlob();
		}
		
		// Otherwise use GD
		else
		{
			// Get the MIME type for the file
			$mime = $this -> GetMIME();
			
			// Create the image based on the MIME type
			switch ($mime)
			{
			case 'image/png':
				return imagepng($this -> image, NULL, 9);
			case 'image/jpeg':
			case 'image/jpg':
				return imagejpeg($this -> image, NULL, 100);
			case 'image/gif':
				return imagegif($this -> image, NULL);
			default:
				throw new \Exception(sprintf(_('Invalid image type "%s"'), $mime));
			}
		}
	}
}
