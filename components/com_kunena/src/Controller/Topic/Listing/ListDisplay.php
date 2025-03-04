<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.Topic
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\Topic\Listing;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessageHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopic;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Html\KunenaParser;
use Kunena\Forum\Libraries\Pagination\KunenaPagination;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class ComponentTopicControllerListDisplay
 *
 * @since   Kunena 4.0
 */
abstract class ListDisplay extends KunenaControllerDisplay
{
    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $me;

    /**
     * @var     array|KunenaTopic[]
     * @since   Kunena 6.0
     */
    public $topics;

    /**
     * @var     KunenaPagination
     * @since   Kunena 6.0
     */
    public $pagination;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $headerText;

    public $allowed;

    public $cache;

    public $catParams;

    public $categorylist;

    public $topic;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'Topic/List';

    /**
     * Prepare topics by pre-loading needed information.
     *
     * @param   array  $userIds  List of additional user Ids to be loaded.
     * @param   array  $mesIds   List of additional message Ids to be loaded.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function prepareTopics(array $userIds = [], array $mesIds = [])
    {
        // Collect user Ids for avatar prefetch when integrated.
        $lastIds = [];

        if (!$this->topics) {
            return;
        }

        foreach ($this->topics as $topic) {
            $userIds[(int) $topic->first_post_userid] = (int) $topic->first_post_userid;
            $userIds[(int) $topic->last_post_userid]  = (int) $topic->last_post_userid;
            $lastIds[(int) $topic->last_post_id]      = (int) $topic->last_post_id;
        }

        // Prefetch all users/avatars to avoid user by user queries during template iterations.
        if (!empty($userIds)) {
            KunenaUserHelper::loadUsers($userIds);
        }

        $topicIds = array_keys((array) $this->topics);
        KunenaTopicHelper::getUserTopics($topicIds);

        $mesIds += KunenaTopicHelper::fetchNewStatus((array) $this->topics);

        // Fetch also last post positions when user can see unapproved or deleted posts.
        // TODO: Optimize? Take account of configuration option...
        if ($this->me->isAdmin() || KunenaAccess::getInstance()->getModeratorStatus()) {
            $mesIds += $lastIds;
        }

        // Load position information for all selected messages.
        if ($mesIds) {
            KunenaMessageHelper::loadLocation($mesIds);
        }

        $this->allowed = md5(serialize(KunenaAccess::getInstance()->getAllowedCategories()));
        $options = ['defaultgroup' => 'com_kunena'];
        $this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('output', $options);

        /*
        if ($cache->start("{$this->ktemplate->name}.common.jump.{$allowed}", 'com_kunena.template'))
        {
            return;
        }*/

        $options    = [];
        $options [] = HTMLHelper::_('select.option', '0', Text::_('COM_KUNENA_FORUM_TOP'));
        // Todo: fix params
        $this->catParams    = ['sections' => 1, 'catid' => 0];
        $this->categorylist = HTMLHelper::_('select.genericlist', $options, 'catid', 'class="class="form-select fbs" size="1" onchange = "this.form.submit()"', 'value', 'text');

        // Run events.
        $params = new Registry();
        $params->set('ksource', 'kunena');
        $params->set('kunena_view', 'topic');
        $params->set('kunena_layout', 'list');
        PluginHelper::importPlugin('kunena');
        KunenaParser::prepareContent($content, 'topicList_default');
        $this->app->triggerEvent('onKunenaPrepare', ['kunena.topic.list', &$this->topic, &$params, 0]);
    }

    /**
     * Prepare document.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function prepareDocument()
    {
        $page       = $this->pagination->pagesCurrent;
        $total      = $this->pagination->pagesTotal;
        $headerText = $this->headerText . ($total > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');

        $menu_item = $this->app->getMenu()->getActive();

        $robots = $this->app->get('robots');

        if ($robots == 'noindex, follow') {
            $this->setMetaData('robots', 'noindex, follow');
        } elseif ($robots == 'index, nofollow') {
            $this->setMetaData('robots', 'index, nofollow');
        } elseif ($robots == 'noindex, nofollow') {
            $this->setMetaData('robots', 'noindex, nofollow');
        } else {
            $this->setMetaData('robots', 'index, follow');
        }

        if ($menu_item) {
            $params             = $menu_item->getParams();
            $params_title       = $params->get('page_title');
            $params_description = $params->get('menu-meta_description');
            $params_robots      = $params->get('robots');

            if (!empty($params_title)) {
                $title = $params->get('page_title') . ($total > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');
                $this->setTitle($title);
            } else {
                $this->setTitle($headerText);
            }

            if (!empty($params_description)) {
                $description = $params->get('menu-meta_description') . ($total > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');
                $this->setDescription($description);
            } else {
                $description = Text::_('COM_KUNENA_THREADS_IN_FORUM') . ': ' . $this->config->boardTitle . ($total > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');
                $this->setDescription($description);
            }

            if (!empty($params_robots)) {
                $robots = $params->get('robots');
                $this->setMetaData('robots', $robots);
            }
        }
    }

    /**
     * Get Topic Actions.
     *
     * @param   array  $topics   topics
     * @param   array  $actions  actions
     *
     * @return  array|void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function getTopicActions(array $topics, $actions = ['delete', 'approve', 'undelete', 'move', 'permdelete'])
    {
        if (!$actions) {
            return;
        }

        $options                = [];
        $options['none']        = HTMLHelper::_('select.option', 'none', Text::_('COM_KUNENA_BULK_CHOOSE_ACTION'));
        $options['unsubscribe'] = HTMLHelper::_('select.option', 'unsubscribe', Text::_('COM_KUNENA_UNSUBSCRIBE_SELECTED'));
        $options['unfavorite']  = HTMLHelper::_('select.option', 'unfavorite', Text::_('COM_KUNENA_UNFAVORITE_SELECTED'));
        $options['move']        = HTMLHelper::_('select.option', 'move', Text::_('COM_KUNENA_MOVE_SELECTED'));
        $options['approve']     = HTMLHelper::_('select.option', 'approve', Text::_('COM_KUNENA_APPROVE_SELECTED'));
        $options['delete']      = HTMLHelper::_('select.option', 'delete', Text::_('COM_KUNENA_DELETE_SELECTED'));
        $options['permdelete']  = HTMLHelper::_('select.option', 'permdel', Text::_('COM_KUNENA_BUTTON_PERMDELETE_LONG'));
        $options['undelete']    = HTMLHelper::_('select.option', 'restore', Text::_('COM_KUNENA_BUTTON_UNDELETE_LONG'));

        // Only display actions that are available to user.
        $actions = array_combine($actions, array_fill(0, \count($actions), false));
        array_unshift($actions, $options['none']);

        foreach ($topics as $topic) {
            foreach ($actions as $action => $value) {
                if ($value !== false) {
                    continue;
                }

                switch ($action) {
                    case 'unsubscribe':
                    case 'unfavorite':
                        $actions[$action] = isset($options[$action]) ? $options[$action] : false;
                        break;
                    default:
                        $actions[$action] = isset($options[$action]) && $topic->isAuthorised($action) ? $options[$action] : false;
                }
            }
        }

        $actions = array_filter($actions, function ($item) {
            return !empty($item);
        });

        if (\count($actions) == 1) {
            return;
        }

        return $actions;
    }

    /**
     * Get Message Actions.
     *
     * @param   array  $messages  messages
     * @param   array  $actions   actions
     *
     * @return  array|void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function getMessageActions(array $messages, $actions = ['approve', 'undelete', 'delete', 'move', 'permdelete'])
    {
        if (!$actions) {
            return;
        }

        $options               = [];
        $options['none']       = HTMLHelper::_('select.option', 'none', Text::_('COM_KUNENA_BULK_CHOOSE_ACTION'));
        $options['approve']    = HTMLHelper::_('select.option', 'approve_posts', Text::_('COM_KUNENA_APPROVE_SELECTED'));
        $options['delete']     = HTMLHelper::_('select.option', 'delete_posts', Text::_('COM_KUNENA_DELETE_SELECTED'));
        $options['move']       = HTMLHelper::_('select.option', 'move', Text::_('COM_KUNENA_MOVE_SELECTED'));
        $options['permdelete'] = HTMLHelper::_('select.option', 'permdel_posts', Text::_('COM_KUNENA_BUTTON_PERMDELETE_LONG'));
        $options['undelete']   = HTMLHelper::_('select.option', 'restore_posts', Text::_('COM_KUNENA_BUTTON_UNDELETE_LONG'));

        // Only display actions that are available to user.
        $actions = array_combine($actions, array_fill(0, \count($actions), false));
        array_unshift($actions, $options['none']);

        foreach ($messages as $message) {
            foreach ($actions as $action => $value) {
                if ($value !== false) {
                    continue;
                }

                $actions[$action] = isset($options[$action]) && $message->isAuthorised($action) ? $options[$action] : false;
            }
        }

        $actions = array_filter($actions, function ($item) {
            return !empty($item);
        });

        if (\count($actions) == 1) {
            return;
        }

        return $actions;
    }
}
