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

namespace Kunena\Forum\Site\Layout\Topic\Edit;

\defined('_JEXEC') or die;

use Kunena\Forum\Libraries\Config\KunenaConfig;
use Kunena\Forum\Libraries\Layout\KunenaLayout;

/**
 * KunenaLayoutTopicEditHistory
 *
 * @since   Kunena 4.0
 */
class TopicEditHistory extends KunenaLayout
{
    /**
     * @var     KunenaConfig
     * @since   Kunena 6.0
     */
    public $config;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $numLink;

    public $output;

    public $user;

    public $headerText;

    public $pagination;

    public $topic;

    public $history;

    public $replycount;

    public $historycount;

    public $attachments;

    public $inline_attachments;

    public $message;

    /**
     * Method to get the anchor link with number
     *
     * @param   int  $mesid     The Id of the message
     * @param   int  $replycnt  The number of replies
     *
     * @return  string
     *
     * @since   Kunena 6.0
     */
    public function getNumLink($mesid, $replycnt)
    {
        if ($this->config->orderingSystem == 'replyid') {
            $this->numLink = $this->getSamePageAnchorLink($mesid, '#' . $replycnt);
        } else {
            $this->numLink = $this->getSamePageAnchorLink($mesid, '#' . $mesid);
        }

        return $this->numLink;
    }

    /**
     * Method to get anchor link on the same page
     *
     * @param   int     $anchor  The anchor number
     * @param   string  $name    The name for the link
     * @param   string  $rel     The rel attribute for the link
     * @param   string  $class   The class attribute for the link
     *
     * @return  string
     *
     * @since   Kunena 6.0
     */
    public function getSamePageAnchorLink($anchor, $name, $rel = 'nofollow', $class = '')
    {
        return '<a ' . ($class ? 'class="' . $class . '"' : '') . ' href="#' . $anchor . '"' . ($rel ? ' rel="' . $rel . '"' : '') . '>' . $name . '</a>';
    }
}
