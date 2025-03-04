<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Layout.widget
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Layout\Widget;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Kunena\Forum\Libraries\Layout\KunenaLayout;

/**
 * KunenaLayoutWidgetModule
 *
 * @since   Kunena 4.0
 */
class WidgetModule extends KunenaLayout
{
    /**
     * @var     null
     * @since   Kunena 6.0
     */
    public $position = null;

    public $cols;

    /**
     * Renders module position.
     *
     * @return  string
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function renderPosition()
    {
        $document = Factory::getApplication()->getDocument();

        if ($this->position && $document->countModules($this->position)) {
            $renderer = $document->loadRenderer('modules');
            $options  = ['style' => 'xhtml'];

            return (string) $renderer->render($this->position, $options, null);
        }

        return '';
    }
}
