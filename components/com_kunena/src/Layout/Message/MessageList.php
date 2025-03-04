<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Layout.Topic
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Layout\Message;

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Layout\KunenaLayout;

/**
 * KunenaLayoutMessageList
 *
 * @since   Kunena 4.0
 */
class MessageList extends KunenaLayout
{
    /**
     * @var     object
     * @since   Kunena 6.0
     */
    public $state;

    public $output;

    public $user;

    public $headerText;

    public $pagination;

    public $config;

    public $me;

    public $topics;

    public $allowed;

    public $cache;

    public $catParams;

    public $categorylist;

    public $topic;

    public $messages;

    public $model;

    public $moreUri;

    public $embedded;

    public $view;

    public $messagemore;

    public $actions;

    /**
     * Method to display the time filter
     *
     * @param   int|string  $id      The HTML id for the select list
     * @param   string      $attrib  The extras attributes for the select list
     *
     * @return  void
     *
     * @since   Kunena 6.0
     */
    public function displayTimeFilter($id = 'filter-time', $attrib = 'class="form-select filter" onchange="this.form.submit()"')
    {
        if (!isset($this->state)) {
            return;
        }

        // Make the select list for time selection
        $timesel[] = HTMLHelper::_('select.option', -1, Text::_('COM_KUNENA_SHOW_ALL'));
        $timesel[] = HTMLHelper::_('select.option', 0, Text::_('COM_KUNENA_SHOW_LASTVISIT'));
        $timesel[] = HTMLHelper::_('select.option', 4, Text::_('COM_KUNENA_SHOW_4_HOURS'));
        $timesel[] = HTMLHelper::_('select.option', 8, Text::_('COM_KUNENA_SHOW_8_HOURS'));
        $timesel[] = HTMLHelper::_('select.option', 12, Text::_('COM_KUNENA_SHOW_12_HOURS'));
        $timesel[] = HTMLHelper::_('select.option', 24, Text::_('COM_KUNENA_SHOW_24_HOURS'));
        $timesel[] = HTMLHelper::_('select.option', 48, Text::_('COM_KUNENA_SHOW_48_HOURS'));
        $timesel[] = HTMLHelper::_('select.option', 168, Text::_('COM_KUNENA_SHOW_WEEK'));
        $timesel[] = HTMLHelper::_('select.option', 720, Text::_('COM_KUNENA_SHOW_MONTH'));
        $timesel[] = HTMLHelper::_('select.option', 8760, Text::_('COM_KUNENA_SHOW_YEAR'));

        echo HTMLHelper::_('select.genericlist', $timesel, 'sel', $attrib, 'value', 'text', $this->state->get('list.time'), $id);
    }
}
