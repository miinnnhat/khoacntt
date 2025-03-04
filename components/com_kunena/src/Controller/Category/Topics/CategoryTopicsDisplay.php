<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.Category
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\Category\Topics;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Forum\Message\KunenaMessageHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopic;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Pagination\KunenaPagination;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use Kunena\Forum\Site\Model\CategoryModel;
use Kunena\Forum\Libraries\Controller\KunenaController;

/**
 * Class ComponentKunenaControllerApplicationMiscDisplay
 *
 * @since   Kunena 4.0
 */
class CategoryTopicsDisplay extends KunenaControllerDisplay
{
    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $headerText;

    /**
     * @var     KunenaCategory
     * @since   Kunena 6.0
     */
    public $category;

    /**
     * @var     integer
     * @since   Kunena 6.0
     */
    public $total;

    /**
     * @var     KunenaTopic
     * @since   Kunena 6.0
     */
    public $topics;

    /**
     * @var     KunenaPagination
     * @since   Kunena 6.0
     */
    public $pagination;

    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $me;

    public $topicActions;

    public $actionMove;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'Category/Item';

    /**
     * Prepare category display.
     *
     * @return  void
     *
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $model = new CategoryModel();

        $this->me = KunenaUserHelper::getMyself();

        $catid      = $this->input->getInt('catid');
        $limitstart = $this->input->getInt('limitstart', 0);
        $limit      = $this->input->getInt('limit', 0);
        $Itemid     = $this->input->getInt('Itemid');
        $format     = $this->input->getCmd('format');

        if (!$Itemid && $format != 'feed' && $this->config->sefRedirect) {
            $itemid     = KunenaRoute::fixMissingItemID();
            $controller = new KunenaController();
            $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=category&catid={$catid}&Itemid={$itemid}", false));
            $controller->redirect();
        }

        if ($limit < 1 || $limit > 100) {
            $limit = $this->config->threadsPerPage;
        }

        // TODO:
        $direction = 'DESC';

        $this->category = KunenaCategoryHelper::get($catid);
        $this->category->tryAuthorise();

        $this->headerText = $this->category->name;

        $topicOrdering = $this->category->topicOrdering;

        $access = KunenaAccess::getInstance();
        $hold   = $access->getAllowedHold($this->me, $catid);
        $moved  = 1;
        $params = [
            'hold'  => $hold,
            'moved' => $moved,
        ];

        switch ($topicOrdering) {
            case 'alpha':
                $params['orderby'] = 'tt.ordering DESC, tt.subject ASC ';
                break;
            case 'creation':
                $params['orderby'] = 'tt.ordering DESC, tt.first_post_time ' . $direction;
                break;
            case 'views':
                $params['orderby'] = 'tt.ordering DESC, tt.hits ' . $direction;
                break;
            case 'posts':
                $params['orderby'] = 'tt.ordering DESC, tt.posts ' . $direction;
                break;
            case 'lastpost':
            default:
                $params['orderby'] = 'tt.ordering DESC, tt.last_post_time ' . $direction;
        }

        list($this->total, $this->topics) = KunenaTopicHelper::getLatestTopics($catid, $limitstart, $limit, $params);

        if ($limitstart > 1 && !$this->topics) {
            $itemid     = KunenaRoute::fixMissingItemID();
            $controller = new KunenaController();
            $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=category&catid={$catid}&Itemid={$itemid}", false));
            $controller->redirect();
        }

        if ($this->total > 0) {
            // Collect user ids for avatar prefetch when integrated.
            $userlist     = [];
            $lastpostlist = [];

            foreach ($this->topics as $topic) {
                $userlist[\intval($topic->first_post_userid)] = \intval($topic->first_post_userid);
                $userlist[\intval($topic->last_post_userid)]  = \intval($topic->last_post_userid);
                $lastpostlist[\intval($topic->last_post_id)]  = \intval($topic->last_post_id);
            }

            // Prefetch all users/avatars to avoid user by user queries during template iterations.
            if (!empty($userlist)) {
                KunenaUserHelper::loadUsers($userlist);
            }

            KunenaTopicHelper::getUserTopics(array_keys($this->topics));
            $lastreadlist = KunenaTopicHelper::fetchNewStatus($this->topics);

            // Fetch last / new post positions when user can see unapproved or deleted posts.
            if ($lastreadlist || $this->me->isAdmin() || KunenaAccess::getInstance()->getModeratorStatus()) {
                KunenaMessageHelper::loadLocation($lastpostlist + $lastreadlist);
            }
        }

        if (!$this->config->readOnly) {
            $this->topicActions = $model->getTopicActions();
        }

        $this->actionMove = $model->getActionMove();

        $this->pagination = new KunenaPagination($this->total, $limitstart, $limit);
        $this->pagination->setDisplayedPages(5);
        $doc  = $this->app->getDocument();
        $page = $this->pagination->pagesCurrent;

        if ($page > 1) {
            foreach ($doc->_links as $key => $value) {
                if (\is_array($value)) {
                    if (\array_key_exists('relation', $value)) {
                        if ($value['relation'] == 'canonical') {
                            $canonicalUrl               = $this->category->getUrl();
                            $doc->_links[$canonicalUrl] = $value;
                            unset($doc->_links[$key]);
                            break;
                        }
                    }
                }
            }
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
        $page  = $this->pagination->pagesCurrent;
        $pages = $this->pagination->pagesTotal;
        $image = null;

        $pagesText    = ($pages > 1 && $page > 1 ? " - " . Text::_('COM_KUNENA_PAGES') . " {$page}" : '');
        $parentText   = $this->category->getParent()->name;
        $categoryText = $this->category->name;
        $categorydesc = $this->category->description;

        $menu_item = $this->app->getMenu()->getActive();
        $doc       = $this->app->getDocument();
        $robots    = $this->app->get('robots');

        if (is_file(JPATH_SITE . '/' . $this->config->emailHeader)) {
            $image = Uri::base() . $this->config->emailHeader;
            $this->setMetaData('og:image', $image, 'property');
        }

        $this->setMetaData('og:url', Uri::current(), 'property');
        $this->setMetaData('og:type', 'article', 'property');
        $this->setMetaData('og:title', $this->category->name, 'property');
        $this->setMetaData('og:description', $this->category->description, 'property');
        $this->setMetaData('og:image', $image, 'property');
        $this->setMetaData('twitter:card', 'summary', 'name');
        $this->setMetaData('twitter:title', $this->category->name, 'name');
        $this->setMetaData('twitter:image', $image, 'property');
        $this->setMetaData('twitter:description', $this->category->description);

        if ($robots == 'noindex, follow') {
            $this->setMetaData('robots', 'noindex, follow');
        } elseif ($robots == 'index, nofollow') {
            $this->setMetaData('robots', 'index, nofollow');
        } elseif ($robots == 'noindex, nofollow') {
            $this->setMetaData('robots', 'noindex, nofollow');
        } else {
            $this->setMetaData('robots', 'index, follow');
        }

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

        if ($menu_item) {
            $params             = $menu_item->getParams();
            $params_description = $params->get('menu-meta_description');
            $params_robots      = $params->get('robots');

            if (!empty($params_title)) {
                $title = $params->get('page_title') . $pagesText;
                $this->setTitle($title);
            } else {
                $title = Text::sprintf("{$categoryText}{$pagesText}");
                $this->setTitle($title);
            }

            if (!empty($params_description)) {
                $description = $params->get('menu-meta_description');
                $description = substr($description, 0, 140) . '... ' . $pagesText;
                $this->setDescription($description);
            } elseif (!empty($categorydesc)) {
                $categorydesc = substr($categorydesc, 0, 140) . '... ' . $pagesText;
                $this->setDescription($categorydesc);
            } else {
                $description = "{$parentText} - {$categoryText}{$pagesText} - {$this->config->boardTitle}";
                $description = substr($description, 0, 140) . '...';
                $this->setDescription($description);
            }

            if (!empty($params_robots)) {
                $robots = $params->get('robots');
                $this->setMetaData('robots', $robots);
            }
        }
    }
}
