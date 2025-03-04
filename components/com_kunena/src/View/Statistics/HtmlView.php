<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Views
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\View\Statistics;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Kunena\Forum\Libraries\Date\KunenaDate;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\View\KunenaView;

/**
 * Topics View
 *
 * @since   Kunena 6.0
 */
class HtmlView extends KunenaView
{
    public $me;

    public $state;

    public $embedded;

    public $config;

    public $topic;

    public $position;

    /**
     * @var false|float
     * @since version
     */
    public $pages;

    /**
     * @var integer
     * @since version
     */
    public $message_position;

    public $module;

    public $firstUserName;

    public $firstPostTime;

    public $firstPostAuthor;

    /**
     * @var string
     * @since version
     */
    public $categoryLink;

    /**
     * @var boolean
     * @since version
     */
    public $cache;

    public $category;

    public $topics;

    public $messages;

    public $layout;

    /**
     * @var string
     * @since version
     */
    public $moreUri;

    /**
     * @var boolean|string
     * @since version
     */
    public $URL;

    public $message_ordering;

    /**
     * @var false
     * @since version
     */
    public $actionMove;

    /**
     * @var mixed
     * @since version
     */
    public $total;

    public $params;

    /**
     * @var mixed
     * @since version
     */
    public $topicActions;

    /**
     * @param   null  $tpl  tpl
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    public function displayDefault($tpl = null)
    {
        $this->layout           = 'default';
        $this->params           = $this->state->get('params');
        $this->Itemid           = $this->get('Itemid');
        $this->topics           = $this->get('Topics');
        $this->total            = $this->get('Total');
        $this->topicActions     = $this->get('TopicActions');
        $this->actionMove       = $this->get('ActionMove');
        $this->message_ordering = $this->me->getMessageOrdering();

        $this->URL = KunenaRoute::_();

        if ($this->embedded) {
            $this->moreUri = 'index.php?option=com_kunena&view=topics&layout=default&mode=' . $this->state->get('list.mode');
            $userid        = $this->state->get('user');

            if ($userid) {
                $this->moreUri .= "&userid={$userid}";
            }
        }

        $this->_prepareDocument();

        $this->render('Topic/List', $tpl);
    }

    /**
     * @return  void
     *
     * @since   Kunena 6.0
     */
    protected function _prepareDocument()
    {
    }

    /**
     * @param   null  $tpl  tpl
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    public function displayUser($tpl = null)
    {
        $this->layout           = 'user';
        $this->params           = $this->state->get('params');
        $this->topics           = $this->get('Topics');
        $this->total            = $this->get('Total');
        $this->topicActions     = $this->get('TopicActions');
        $this->actionMove       = $this->get('ActionMove');
        $this->message_ordering = $this->me->getMessageOrdering();

        $this->URL = KunenaRoute::_();

        if ($this->embedded) {
            $this->moreUri = 'index.php?option=com_kunena&view=topics&layout=user&mode=' . $this->state->get('list.mode');
            $userid        = $this->state->get('user');

            if ($userid) {
                $this->moreUri .= "&userid={$userid}";
            }
        }

        $this->_prepareDocument();

        $this->render('Topic/List', $tpl);
    }

    /**
     * @param   null  $tpl  tpl
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    public function displayPosts($tpl = null)
    {
        $this->layout           = 'posts';
        $this->params           = $this->state->get('params');
        $this->messages         = $this->get('Messages');
        $this->topics           = $this->get('Topics');
        $this->total            = $this->get('Total');
        $this->postActions      = $this->get('PostActions');
        $this->actionMove       = false;
        $this->message_ordering = $this->me->getMessageOrdering();

        $this->URL = KunenaRoute::_();

        if ($this->embedded) {
            $this->moreUri = 'index.php?option=com_kunena&view=topics&layout=posts&mode=' . $this->state->get('list.mode');
            $userid        = $this->state->get('user');

            if ($userid) {
                $this->moreUri .= "&userid={$userid}";
            }
        }

        $this->_prepareDocument();

        $this->render('Message/List', $tpl);
    }

    /**
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function displayRows()
    {
        if ($this->layout == 'posts') {
            $this->displayPostRows();
        } else {
            $this->displayTopicRows();
        }
    }

    /**
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function displayPostRows()
    {
        $this->lasttopic = null;
        $this->position  = 0;

        // Run events
        $params = new Registry();
        $params->set('ksource', 'kunena');
        $params->set('kunena_view', 'user');
        $params->set('kunena_layout', 'posts');

        PluginHelper::importPlugin('kunena');

        Factory::getApplication()->triggerEvent('onKunenaPrepare', ['kunena.messages', &$this->messages, &$params, 0]);

        foreach ($this->messages as $message) {
            $this->position++;
            $this->topic    = $message->getTopic();
            $this->category = $this->topic->getCategory();
            $usertype       = $this->me->getType($this->category->id, true);

            // TODO: add context (options, template) to caching
            $this->cache      = true;
            $options = ['defaultgroup' => 'com_kunena'];
            $this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('output', $options);
            $this->cachekey   = "{$this->getTemplateMD5()}.{$usertype}.t{$this->topic->id}.p{$message->id}";
            $this->cachegroup = 'com_kunena.posts';

            // FIXME: enable caching after fixing the issues
            $contents = false; // $cache->get($cachekey, $cachegroup);

            if (!$contents) {
                $this->categoryLink     = $this->getCategoryLink($this->category->getParent()) . ' / ' . $this->getCategoryLink($this->category);
                $this->postAuthor       = KunenaFactory::getUser($message->userid);
                $this->firstPostAuthor  = $this->topic->getfirstPostAuthor();
                $this->firstPostTime    = $this->topic->first_post_time;
                $this->firstUserName    = $this->topic->first_post_guest_name;
                $this->module           = $this->getModulePosition('kunena_topic_' . $this->position);
                $this->message_position = $this->topic->posts - ($this->topic->unread ? $this->topic->unread - 1 : 0);
                $this->pages            = ceil($this->topic->getTotal() / $this->config->messagesPerPage);

                if ($this->config->avatarOnCategory) {
                    $this->topic->avatar = KunenaFactory::getUser($this->topic->last_post_userid)->getAvatarImage('klist-avatar', 'list');
                }

                $contents = $this->loadTemplateFile('row');

                if ($usertype == 'guest') {
                    $contents = preg_replace_callback('|\[K=(\w+)(?:\:([\w_-]+))?\]|', [$this, 'fillTopicInfo'], $contents);
                }

                // FIXME: enable caching after fixing the issues
                // if ($this->cache) $cache->store($contents, $cachekey, $cachegroup);
            }

            if ($usertype != 'guest') {
                $contents = preg_replace_callback('|\[K=(\w+)(?:\:([\w_-]+))?\]|', [$this, 'fillTopicInfo'], $contents);
            }

            echo $contents;
            $this->lasttopic = $this->topic;
        }
    }

    /**
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function displayTopicRows()
    {
        $this->lasttopic = null;
        $this->position  = 0;

        // Run events
        $params = new Registry();
        $params->set('ksource', 'kunena');
        $params->set('kunena_view', 'user');
        $params->set('kunena_layout', 'topics');

        PluginHelper::importPlugin('kunena');

        Factory::getApplication()->triggerEvent('onKunenaPrepare', ['kunena.topics', &$this->topics, &$params, 0]);

        foreach ($this->topics as $this->topic) {
            $this->position++;
            $this->category = $this->topic->getCategory();
            $usertype       = $this->me->getType($this->category->id, true);

            // TODO: add context (options, template) to caching
            $this->cache      = true;
            $options = ['defaultgroup' => 'com_kunena'];
            $this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('output', $options);
            $this->cachekey   = "{$this->getTemplateMD5()}.{$usertype}.t{$this->topic->id}.p{$this->topic->last_post_id}";
            $this->cachegroup = 'com_kunena.topics';

            // FIXME: enable caching after fixing the issues
            $contents = false; // $cache->get($cachekey, $cachegroup);

            if (!$contents) {
                $this->categoryLink     = $this->getCategoryLink($this->category->getParent()) . ' / ' . $this->getCategoryLink($this->category);
                $this->firstPostAuthor  = $this->topic->getfirstPostAuthor();
                $this->firstPostTime    = $this->topic->first_post_time;
                $this->firstUserName    = $this->topic->first_post_guest_name;
                $this->lastPostAuthor   = $this->topic->getLastPostAuthor();
                $this->lastPostTime     = $this->topic->last_post_time;
                $this->lastUserName     = $this->topic->last_post_guest_name;
                $this->module           = $this->getModulePosition('kunena_topic_' . $this->position);
                $this->message_position = $this->topic->posts - ($this->topic->unread ? $this->topic->unread - 1 : 0);
                $this->pages            = ceil($this->topic->getTotal() / $this->config->messagesPerPage);

                if ($this->config->avatarOnCategory) {
                    $this->topic->avatar = KunenaFactory::getUser($this->topic->last_post_userid)->getAvatarImage('klist-avatar', 'list');
                }

                if (\is_object($this->lasttopic) && $this->lasttopic->ordering != $this->topic->ordering) {
                    $this->spacing = 1;
                } else {
                    $this->spacing = 0;
                }

                $contents = $this->loadTemplateFile('row');

                if ($usertype == 'guest') {
                    $contents = preg_replace_callback('|\[K=(\w+)(?:\:([\w_-]+))?\]|', [$this, 'fillTopicInfo'], $contents);
                }

                // FIXME: enable caching after fixing the issues
                // if ($this->cache) $cache->store($contents, $cachekey, $cachegroup);
            }

            if ($usertype != 'guest') {
                $contents = preg_replace_callback('|\[K=(\w+)(?:\:([\w_-]+))?\]|', [$this, 'fillTopicInfo'], $contents);
            }

            echo $contents;
            $this->lasttopic = $this->topic;
        }
    }

    /**
     * @param   array  $matches  matches
     *
     * @return  mixed|string|void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    public function fillTopicInfo($matches)
    {
        switch ($matches[1]) {
            case 'ROW':
                return $matches[2] . ($this->position && 1 ? 'odd' : 'even') . ($this->topic->ordering ? " {$matches[2]}sticky" : '');
            case 'TOPIC_ICON':
                return $this->topic->getIcon();
            case 'TOPIC_NEW_COUNT':
                return $this->topic->unread ? $this->getTopicLink($this->topic, 'unread', '<sup class="kindicator-new">(' . $this->topic->unread . ' ' . Text::_('COM_KUNENA_A_GEN_NEWCHAR') . ')</sup>') : '';
            case 'DATE':
                $date = new KunenaDate($matches[2]);

                return $date->toSpan('config_postDateFormat', 'config_postDateFormatHover');
        }
    }
}
