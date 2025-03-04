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

namespace Kunena\Forum\Site\Controller\Topic\Listing\Unread;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicFinder;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Pagination\KunenaPagination;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use Kunena\Forum\Site\Controller\Topic\Listing\ListDisplay;
use Kunena\Forum\Site\Model\TopicsModel;
use Kunena\Forum\Libraries\Controller\KunenaController;

/**
 * Class ComponentTopicControllerListDisplay
 *
 * @since   Kunena 4.0
 */
class TopicListingUnreadDisplay extends ListDisplay
{
    public $model;

    public $state;

    public $moreUri;

    public $access;

    public $params;

    public $embedded;

    public $mesIds;

    public $actions;

    /**
     * Prepare topic list for moderators.
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $this->model = new TopicsModel([]);
        $this->model->initialize($this->getOptions(), $this->getOptions()->get('embedded', false));
        $this->state    = $this->model->getState();
        $this->me       = KunenaUserHelper::getMyself();
        $this->moreUri  = null;
        $this->access   = KunenaAccess::getInstance();
        $start          = $this->state->get('list.start');
        $limit          = $this->state->get('list.limit');
        $this->params   = ComponentHelper::getParams('com_kunena');
        $Itemid         = $this->input->getInt('Itemid');
        $this->embedded = $this->getOptions()->get('embedded', true);

        // Handle &sel=x parameter.
        $time = $this->state->get('list.time');

        if ($time < 0) {
            $time = null;
        } elseif ($time == 0) {
            $time = new Date(KunenaFactory::getSession()->lasttime);
        } else {
            $time = new Date(Factory::getDate()->toUnix() - ($time * 3600));
        }

        if (!$Itemid && $this->config->sefRedirect) {
            if ($this->config->moderator_id) {
                $itemidfix = $this->config->moderator_id;
            } else {
                $menu      = $this->app->getMenu();
                $getid     = $menu->getItem(KunenaRoute::getItemID("index.php?option=com_kunena&view=topics&layout=unread"));
                $itemidfix = $getid->id;
            }

            if (!$itemidfix) {
                $itemidfix = KunenaRoute::fixMissingItemID();
            }

            $controller = new KunenaController();
            $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=topics&layout=unread&Itemid={$itemidfix}", false));
            $controller->redirect();
        }

        $finder = new KunenaTopicFinder();

        $this->topics = $finder
            ->start($start)
            ->limit($limit)
            ->filterByTime($time)
            ->order('id', 0)
            ->filterByUserAccess($this->me)
            ->find();

        $this->mesIds = [];

        $this->mesIds += KunenaTopicHelper::fetchNewStatus($this->topics, $this->me->userid);

        $list = [];

        $count = 0;

        foreach ($this->topics as $topic) {
            if ($topic->unread) {
                $list[] = $topic;
                $count++;
            }
        }

        $this->topics = $list;

        $this->pagination = new KunenaPagination($finder->count(), $start, $limit);

        if ($this->moreUri) {
            $this->pagination->setUri($this->moreUri);
        }

        if ($this->topics) {
            $this->prepareTopics();
        }

        $actions          = ['delete', 'approve', 'undelete', 'move', 'permdelete'];
        $this->actions    = $this->getTopicActions($this->topics, $actions);
        $this->headerText = Text::_('COM_KUNENA_UNREAD');
    }
}
