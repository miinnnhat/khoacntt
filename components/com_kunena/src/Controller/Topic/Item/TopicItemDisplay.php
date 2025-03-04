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

namespace Kunena\Forum\Site\Controller\Topic\Item;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Attachment\KunenaAttachmentHelper;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessageFinder;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessageHelper;
use Kunena\Forum\Libraries\Forum\Message\Thankyou\KunenaMessageThankyouHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopic;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Forum\Topic\Rate\KunenaRateHelper;
use Kunena\Forum\Libraries\Html\KunenaParser;
use Kunena\Forum\Libraries\KunenaPrivate\Message\KunenaPrivateMessageFinder;
use Kunena\Forum\Libraries\Pagination\KunenaPagination;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\Template\KunenaTemplate;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use stdClass;
use Kunena\Forum\Libraries\Controller\KunenaController;

/**
 * Class ComponentTopicControllerItemDisplay
 *
 * @since   Kunena 4.0
 */
class TopicItemDisplay extends KunenaControllerDisplay
{
    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $me;

    /**
     * @var     KunenaCategory
     * @since   Kunena 6.0
     */
    public $category;

    /**
     * @var     KunenaTopic
     * @since   Kunena 6.0
     */
    public $topic;

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

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'Topic/Item';

    public $allowed;

    public $cache;

    public $catParams;

    public $categorylist;

    public $message;

    public $messages;

    public $threaded;

    public $userTopic;

    public $quickReply;

    public $image;

    public $params;

    /**
     * Prepare topic display.
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $catid  = $this->input->getInt('catid');
        $id     = $this->input->getInt('id');
        $mesid  = $this->input->getInt('mesid');
        $start  = $this->input->getInt('limitstart', 0);
        $limit  = $this->input->getInt('limit', 0);
        $Itemid = $this->input->getInt('Itemid');
        $format = $this->input->getInt('format');

        if (!$Itemid && $format != 'feed' && $this->config->sefRedirect) {
            $itemid     = KunenaRoute::fixMissingItemID();
            $controller = new KunenaController();
            $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=topic&catid={$catid}&id={$id}&Itemid={$itemid}", false));
            $controller->redirect();
        }

        if ($limit < 1 || $limit > 100) {
            $limit = $this->config->messagesPerPage;
        }

        $this->me = KunenaUserHelper::getMyself();

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
        $this->categorylist = HTMLHelper::_(
            'kunenaforum.categorylist',
            'catid',
            0,
            $options,
            $this->catParams,
            'class="form-select" data-bs-toggle="tooltip" title="' . Text::_('COM_KUNENA_FORUM_TOP') . '" size="1" onchange = "this.form.submit()"',
            'value',
            'text'
        );

        // Load topic and message.
        if ($mesid) {
            // If message was set, use it to find the current topic.
            $this->message = KunenaMessageHelper::get($mesid);
            $this->topic   = $this->message->getTopic();
        } else {
            // Note that redirect loops throw RuntimeException because of we added \Kunena\Forum\Libraries\Forum\Topic\Topic::getTopic() call!
            $this->topic   = KunenaTopicHelper::get($id)->getTopic();
            $this->message = KunenaMessageHelper::get($this->topic->first_post_id);
        }

        // Load also category (prefer the URI variable if available).
        if ($catid && $catid != $this->topic->category_id) {
            $this->category = KunenaCategoryHelper::get($catid);
            $this->category->tryAuthorise();
        } else {
            $this->category = $this->topic->getCategory();
        }

        // Access check.
        $this->message->tryAuthorise();

        // Check if we need to redirect (category or topic mismatch, or resolve permanent URL).
        if ($this->primary) {
            $channels = $this->category->getChannels();

            if (
                $this->message->thread != $this->topic->id
                || ($this->topic->category_id != $this->category->id && !isset($channels[$this->topic->category_id]))
            ) {
                $this->app->redirect($this->message->getUrl(null, false));
            }
        }

        // Load messages from the current page and set the pagination.
        $hold   = KunenaAccess::getInstance()->getAllowedHold($this->me, $this->category->id, false);
        $finder = new KunenaMessageFinder();
        $finder
            ->where('thread', '=', $this->topic->id)
            ->filterByHold($hold);

        $start            = $mesid ? $this->topic->getPostLocation($mesid) : $start;
        $this->pagination = new KunenaPagination($finder->count(), $start, $limit);

        $this->messages = $finder
            ->order('time', $this->me->getMessageOrdering() == 'asc' ? 1 : -1)
            ->start($this->pagination->limitstart)
            ->limit($this->pagination->limit)
            ->find();

        $this->prepareMessages($mesid);
        $doc = $this->app->getDocument();

        if ($this->me->exists()) {
            $pmFinder = new KunenaPrivateMessageFinder();
            $pmFinder->filterByMessageIds(array_keys($this->messages))->order('id');

            if (!$this->me->isModerator($this->category)) {
                $pmFinder->filterByUser($this->me);
            }

            $pms = $pmFinder->find();

            foreach ($pms as $pm) {
                $registry = new Registry($pm->params);
                $posts    = $registry->get('receivers.posts');

                foreach ($posts as $post) {
                    if (!isset($this->messages[$post]->pm)) {
                        $this->messages[$post]->pm = [];
                    }
                }

                $this->messages[$post]->pm[$pm->id] = $pm;
            }
        }

        if ($this->topic->unread) {
            $doc->setMetaData('robots', 'noindex, follow');
        }

        if (!$start) {
            foreach ($doc->_links as $key => $value) {
                if (\is_array($value)) {
                    if (\array_key_exists('relation', $value)) {
                        if ($value['relation'] == 'canonical') {
                            $canonicalUrl               = $this->topic->getUrl();
                            $doc->_links[$canonicalUrl] = $value;
                            unset($doc->_links[$key]);
                            break;
                        }
                    }
                }
            }
        }

        // Run events.
        $params = new Registry();
        $params->set('ksource', 'kunena');
        $params->set('kunena_view', 'topic');
        $params->set('kunena_layout', 'default');

        PluginHelper::importPlugin('kunena');
        KunenaParser::prepareContent($content, 'topic_top');
        $this->app->triggerEvent('onKunenaPrepare', ['kunena.topic', &$this->topic, &$params, 0]);
        $this->app->triggerEvent('onKunenaPrepare', ['kunena.messages', &$this->messages, &$params, 0]);

        // Get user data, captcha & quick reply.
        $this->userTopic  = $this->topic->getUserTopic();
        $this->quickReply = $this->topic->isAuthorised('reply') && $this->me->exists() && $this->config->quickReply;

        $this->headerText = KunenaParser::parseText($this->topic->displayField('subject'));

        $data                           = new \stdClass();
        $data->{'@context'}             = "https://schema.org";
        $data->{'@type'}                = "DiscussionForumPosting";
        $data->{'url'}                  = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $this->topic->getPermaUrl();
        $data->{'discussionUrl'}        = $this->topic->getPermaUrl();
        $data->{'headline'}             = $this->headerText;
        $data->{'image'}                = $this->docImage();
        $data->{'datePublished'}        = $this->topic->getFirstPostTime()->toISO8601();

        if ($this->message->modified_time !== null) {
            $data->{'dateModified'}         = Factory::getDate($this->message->modified_time)->toISO8601();
        }

        $data->{'author'}               = [];
        $tmp                            = new \stdClass();
        $tmp->{'@type'}                 = "Person";
        $tmp->{'name'}                  = $this->topic->getLastPostAuthor()->username;
        $tmp->{'url'}                   = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $this->topic->getLastPostAuthor()->getURL();
        $data->{'author'}               = $tmp;
        $data->interactionStatistic     = [];
        $tmp2                           = new \stdClass();
        $tmp2->{'@type'}                = "InteractionCounter";
        $tmp2->{'interactionType'}      = "InteractionCounter";
        $tmp2->{'userInteractionCount'} = $this->topic->getReplies();
        $data->interactionStatistic     = $tmp2;
        $tmp3                           = new \stdClass();
        $tmp3->{'@type'}                = "ImageObject";
        $tmp3->{'url'}                  = $this->docImage();
        $tmp4                           = new \stdClass();
        $tmp4->{'@type'}                = "Organization";
        $tmp4->{'name'}                 = $this->config->boardTitle;
        $tmp4->{'logo'}                 = $tmp3;
        $data->publisher                = (array) $tmp4;
        $data->mainEntityOfPage         = [];
        $tmp5                           = new \stdClass();
        $tmp5->{'@type'}                = "WebPage";
        $tmp5->{'name'}                 = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $this->topic->getPermaUrl();
        $data->mainEntityOfPage         = $tmp5;

        if ($this->category->allowRatings && $this->config->ratingEnabled && KunenaRateHelper::getCount($this->topic->id) > 0) {
            $data->aggregateRating  = [];
            $tmp3                   = new \stdClass();
            $tmp3->{'@type'}        = "AggregateRating";
            $tmp3->{'itemReviewed'} = $this->headerText;
            $tmp3->{'ratingValue'}  = KunenaRateHelper::getSelected($this->topic->id) > 0 ? KunenaRateHelper::getSelected($this->topic->id) : 5;
            $tmp3->{'reviewCount'}  = KunenaRateHelper::getCount($this->topic->id);
            $data->aggregateRating  = $tmp3;
        }

        KunenaTemplate::getInstance()->addScriptDeclaration(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'application/ld+json');
    }

    /**
     * Prepare messages for display.
     *
     * @param   int  $mesid  Selected message Id.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function prepareMessages($mesid)
    {
        // Get thank yous for all messages in the page
        $thankyous = KunenaMessageThankyouHelper::getByMessage($this->messages);

        // First collect ids and users.
        $threaded       = ($this->layout == 'indented' || $this->layout == 'threaded');
        $userlist       = [];
        $this->threaded = [];
        $location       = $this->pagination->limitstart;

        foreach ($this->messages as $message) {
            $message->replynum = ++$location;

            if ($threaded) {
                // Threaded ordering
                if (isset($this->messages[$message->parent])) {
                    $this->threaded[$message->parent][] = $message->id;
                } else {
                    $this->threaded[0][] = $message->id;
                }
            }

            $userlist[(int) $message->userid]      = (int) $message->userid;
            $userlist[(int) $message->modified_by] = (int) $message->modified_by;

            $thankyou_list     = $thankyous[$message->id]->getList();
            $message->thankyou = [];

            if (!empty($thankyou_list)) {
                $message->thankyou = $thankyou_list;
            }
        }

        if (!isset($this->messages[$mesid]) && !empty($this->messages)) {
            $this->message = reset($this->messages);
        }

        if ($threaded) {
            if (!isset($this->messages[$this->topic->first_post_id])) {
                $this->messages = $this->getThreadedOrdering(0, ['edge']);
            } else {
                $this->messages = $this->getThreadedOrdering();
            }
        }

        // Prefetch all users/avatars to avoid user by user queries during template iterations
        KunenaUserHelper::loadUsers($userlist);

        // Prefetch attachments.
        KunenaAttachmentHelper::getByMessage($this->messages);
    }

    /**
     * Change ordering of the displayed messages and apply threading.
     *
     * @param   int    $parent  Parent Id.
     * @param   array  $indent  Indent for the current object.
     *
     * @return  array
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function getThreadedOrdering($parent = 0, $indent = [])
    {
        $list = [];

        if (\count($indent) == 1 && $this->topic->getTotal() > $this->pagination->limitstart + $this->pagination->limit) {
            $last = -1;
        } else {
            $last = end($this->threaded[$parent]);
        }

        foreach ($this->threaded[$parent] as $mesid) {
            $message = $this->messages[$mesid];
            $skip    = $message->id != $this->topic->first_post_id
                && $message->parent != $this->topic->first_post_id && !isset($this->messages[$message->parent]);

            if ($mesid != $last) {
                // Default sibling edge
                $indent[] = 'crossedge';
            } else {
                // Last sibling edge
                $indent[] = 'lastedge';
            }

            end($indent);
            $key = key($indent);

            if ($skip) {
                $indent[] = 'gap';
            }

            $list[$mesid]         = $this->messages[$mesid];
            $list[$mesid]->indent = $indent;

            if (empty($this->threaded[$mesid])) {
                // No children node
                // FIXME: $mesid == $message->thread
                $list[$mesid]->indent[] = ($mesid == $message->thread) ? 'single' : 'leaf';
            } else {
                // Has children node
                // FIXME: $mesid == $message->thread
                $list[$mesid]->indent[] = ($mesid == $message->thread) ? 'root' : 'node';
            }

            if (!empty($this->threaded[$mesid])) {
                // Fix edges
                if ($mesid != $last) {
                    $indent[$key] = 'edge';
                } else {
                    $indent[$key] = 'empty';
                }

                if ($skip) {
                    $indent[$key + 1] = 'empty';
                }

                $list += $this->getThreadedOrdering($mesid, $indent);
            }

            if ($skip) {
                array_pop($indent);
            }

            array_pop($indent);
        }

        return $list;
    }

    /**
     * Prepare document.
     *
     * @return  string
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function docImage()
    {
        if (is_file(JPATH_SITE . '/media/kunena/avatars/' . KunenaFactory::getUser($this->topic->getAuthor()->id)->avatar)) {
            $image = Uri::root() . 'media/kunena/avatars/' . KunenaFactory::getUser($this->topic->getAuthor()->id)->avatar;
        } elseif ($this->topic->getAuthor()->avatar == null) {
            if (is_file(JPATH_SITE . '/' . $this->config->emailHeader)) {
                $image = Uri::base() . $this->config->emailHeader;
            } else {
                $image = Uri::base() . '/media/kunena/email/hero-wide.png';
            }
        } else {
            $image = $this->topic->getAuthor()->getAvatarURL('Profile', '200');
        }

        return $image;
    }

    /**
     * After render update topic data for the user.
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function after()
    {
        parent::after();

        $this->topic->hit();

        $this->topic->markRead();

        // Check if subscriptions have been sent and reset the value.
        if ($this->topic->isAuthorised('subscribe') && $this->userTopic->subscribed == 2) {
            $this->userTopic->subscribed = 1;
            $this->userTopic->save();
        }
    }

    /**
     * Prepare document.
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function prepareDocument()
    {
        $this->image = '';
        $doc         = $this->app->getDocument();
        $this->setMetaData('og:url', Uri::current(), 'property');
        $this->setMetaData('og:type', 'article', 'property');
        $this->setMetaData('og:title', $this->topic->displayField('subject'), 'property');
        $this->setMetaData('profile:username', $this->topic->getAuthor()->username, 'property');

        $image = $this->docImage();

        $message = KunenaParser::parseText($this->topic->first_post_message);
        $matches = preg_match("/\[img]http(s?):\/\/.*\/img]/iu", $message, $title);

        if ($matches) {
            $image = substr($title[0], 5, -6);
        }

        if ($this->topic->attachments > 0) {
            $attachments = KunenaAttachmentHelper::getByMessage($this->topic->first_post_id);
            $item        = [];

            foreach ($attachments as $attach) {
                $object           = new stdClass();
                $object->path     = $attach->getUrl();
                $object->image    = $attach->isImage();
                $object->filename = $attach->filename;
                $object->folder   = $attach->folder;
                $item             = $object;
            }

            $attach = $item;

            if ($attach) {
                if (is_file(JPATH_SITE . '/' . $attach->folder . '/' . $attach->filename)) {
                    if ($attach->image) {
                        if ($this->config->attachmentProtection) {
                            $url      = $attach->path;
                            $protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
                            $image    = $protocol . $_SERVER['SERVER_NAME'] . $url;
                        } else {
                            $image = $attach->path;
                        }
                    }
                }
            }
        }

        $multispaces_replaced = '';
        if (!empty($this->topic->first_post_message)) {
            $firstPostMessage = KunenaParser::stripBBCode($this->topic->first_post_message, 160);
            $firstPostMessage = $this->topic->subject;
            $multispaces_replaced = preg_replace('/\s+/', ' ', $firstPostMessage);
        }

        $this->setMetaData('og:description', $multispaces_replaced, 'property');
        $this->setMetaData('og:image', $image, 'property');
        $this->setMetaData('article:published_time', $this->topic->getFirstPostTime()->toISO8601(), 'property');
        $this->setMetaData('article:section', $this->topic->getCategory()->name, 'property');
        $this->setMetaData('twitter:card', 'summary', 'name');
        $this->setMetaData('twitter:title', $this->topic->displayField('subject'), 'name');
        $this->setMetaData('twitter:image', $image, 'property');
        $this->setMetaData('twitter:description', $multispaces_replaced);

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

        $page       = (int) $this->pagination->pagesCurrent;
        $total      = (int) $this->pagination->pagesTotal;
        $headerText = $this->headerText . ($total > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');

        $pagdata = $this->pagination->getData();

        if ($pagdata->previous->link) {
            $pagdata->previous->link = str_replace('?limitstart=0', '', $pagdata->previous->link);
            $doc->addHeadLink($pagdata->previous->link, 'prev');
        }

        if ($pagdata->next->link) {
            $doc->addHeadLink($pagdata->next->link, 'next');
        }

        if ($page > 1) {
            foreach ($doc->_links as $key => $value) {
                if (\is_array($value)) {
                    if (\array_key_exists('relation', $value)) {
                        if ($value['relation'] == 'canonical') {
                            $canonicalUrl               = KunenaRoute::_();
                            $doc->_links[$canonicalUrl] = $value;
                            unset($doc->_links[$key]);
                            break;
                        }
                    }
                }
            }
        }

        $menu_item = $this->app->getMenu()->getActive();

        if ($menu_item) {
            $this->params = $menu_item->getParams();
            $subject      = KunenaParser::parseText($this->topic->displayField('subject'));

            $this->setTitle($subject);

            $multispaces_replaced_desc = preg_replace('/\s+/', ' ', $this->topic->first_post_message);

            if ($total > 1 && $page > 1) {
                $small = KunenaParser::stripBBCode($multispaces_replaced_desc, 130);

                if (empty($small)) {
                    $small = $headerText;
                }

                $this->setDescription($small . " - " . Text::_('COM_KUNENA_PAGES') . " {$page}");
            } else {
                $small = KunenaParser::stripBBCode($multispaces_replaced_desc, 160);

                if (empty($small)) {
                    $small = $headerText;
                }

                $this->setDescription($small);
            }
        }
    }
}
