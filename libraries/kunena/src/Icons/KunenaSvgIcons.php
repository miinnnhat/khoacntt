<?php

/**
 * Kunena Component
 *
 * @package    Kunena.Framework
 * @subpackage Icons
 *
 * @copyright  Copyright (C) 2008 - @currentyear@ Kunena Team. All rights reserved.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       https://www.kunena.org
 **/

namespace Kunena\Forum\Libraries\Icons;

\defined('_JEXEC') or die();

use DOMDocument;

/**
 * Class KunenaSvgIcons
 *
 * @since   Kunena 6.0
 */
class KunenaSvgIcons
{
    /**
     * Class load svg icon.
     *
     * @param   string  $svgname  Name of SVG file to load
     * @param   string  $group    Load the svg location
     * @param   string  $iconset  Load iconset when topicIcons
     *
     * @return string
     *
     * @since   Kunena 6.0
     */
    public static function loadsvg(string $svgname, string $group = 'default', string $iconset = 'default'): string
    {
        if (empty($svgname)) {
            return false;
        }

        if ($group == 'default') {
            $file = JPATH_SITE . '/media/kunena/core/svg/' . $svgname;
        } elseif ($group == 'social') {
            $file = JPATH_SITE . '/media/kunena/core/images/social/' . $svgname;
        } elseif ($group == 'systemtopicIcons') {
            $file = JPATH_SITE . '/media/kunena/topic_icons/' . $iconset . '/system/svg/' . $svgname;

            if (!\file_exists($file . '.svg')) {
                $file = JPATH_SITE . '/media/kunena/core/svg/' . $svgname;
            }
        } elseif ($group == 'usertopicIcons') {
            $file = JPATH_SITE . '/media/kunena/topic_icons/' . $iconset . '/user/svg/' . $svgname;

            if (!\file_exists($file . '.svg')) {
                $file = JPATH_SITE . '/media/kunena/core/svg/' . $svgname;
            }
        } else {
            $file = JPATH_SITE . '/' . $group . $svgname;
        }

        $iconfile = new DOMDocument();

        if ($iconfile->load($file . '.svg')) {
            return $iconfile->saveHTML($iconfile->getElementsByTagName('svg')[0]);
        } else {
            return '';
        }
    }
}
