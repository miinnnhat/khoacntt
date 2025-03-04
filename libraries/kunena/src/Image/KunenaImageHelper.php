<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Framework
 * @subpackage      Image
 *
 * @copyright       Copyright (C) 2008 - @currentyear@ Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Libraries\Image;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Kunena\Forum\Libraries\Folder\KunenaFolder;
use Kunena\Forum\Libraries\Path\KunenaPath;

/**
 * Helper class for image manipulation.
 *
 * @since   Kunena 6.0
 */
class KunenaImageHelper
{
    /**
     * Create new re-sized version of the original image.
     *
     * @param   string  $file       Incoming file
     * @param   string  $folder     Folder for the new image.
     * @param   string  $filename   Filename for the new image.
     * @param   int     $maxWidth   Maximum width for the image.
     * @param   int     $maxHeight  Maximum height for the image.
     * @param   int     $quality    Quality for the file (1-100).
     * @param   int     $scale      See available KunenaImage constants.
     * @param   int     $crop       Define if you want crop the image.
     *
     * @return  boolean  True on success.
     *
     * @throws Exception
     * @since   Kunena 6.0
     */
    public static function version($file, $folder, $filename, $maxWidth = 800, $maxHeight = 800, $quality = 70, $scale = KunenaImage::SCALE_INSIDE, $crop = 0)
    {
        // Create target directory if it does not exist.
        if (!is_dir($folder) && !Folder::create($folder)) {
            return false;
        }

        // Make sure that index.html exists in the folder.
        KunenaFolder::createIndex($folder);

        try {
            $info = KunenaImage::getImageFileProperties($file);
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());

            return false;
        }

        if ($info->width > $maxWidth || $info->height > $maxHeight) {
            // Make sure that quality is in allowed range.
            if ($quality < 1 || $quality > 100) {
                $quality = 70;
            }

            // Calculate quality for PNG.
            if ($info->type == IMAGETYPE_PNG) {
                $quality = \intval(($quality - 1) / 10);
            }

            $options = ['quality' => $quality];

            try {
                // Resize image and copy it to temporary file.
                $image = new KunenaImage($file);

                if ($crop && $info->width > $info->height) {
                    $image = $image->resize($info->width * $maxHeight / $info->height, $maxHeight, false, $scale);
                    $image = $image->crop($maxWidth, $maxHeight);
                } elseif ($crop && $info->width < $info->height) {
                    $image = $image->resize($maxWidth, $info->height * $maxWidth / $info->width, false, $scale);
                    $image = $image->crop($maxWidth, $maxHeight);
                } else {
                    $image = $image->resize($maxWidth, $maxHeight, false, $scale);
                }

                $temp = KunenaPath::tmpdir() . '/kunena_' . md5(rand());
                $image->toFile($temp, $info->type, $options);
                unset($image);
            } catch (Exception $e) {
                throw new \Exception($e->getMessage());

                return false;
            }

            // Move new file to its proper location.
            if (!File::move($temp, "{$folder}/{$filename}")) {
                unlink($temp);

                return false;
            }
        } else {
            // Copy original file to the new location.
            if (!File::copy($file, "{$folder}/{$filename}")) {
                return false;
            }
        }

        return true;
    }
}
