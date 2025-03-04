<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Administrator
 * @subpackage      Models
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Model;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Model\KunenaModel;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use RuntimeException;

/**
 * User Model for Kunena
 *
 * @since  3.0
 */
class UserModel extends KunenaModel
{
    /**
     * @param   array    $data      data
     * @param   boolean  $loadData  load data
     *
     * @return void
     *
     * @since  Kunena 6.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // TODO: Implement getForm() method.
    }

    /**
     * @return array
     *
     * @since   Kunena 6.0
     * @throws \Exception
     */
    public function getSubscriptions(): array
    {
        $db     = $this->getDatabase();
        $userid = $this->getState($this->getName() . '.id');

        $query = $db->getQuery(true);
        $query->select($db->quoteName('topic_id') . ' AS thread')
            ->from($db->quoteName('#__kunena_user_topics'))
            ->where($db->quoteName('user_id') . ' = ' . $userid . ' AND ' . $db->quoteName('subscribed') . '=1');
        $db->setQuery($query);

        try {
            $subsList = (array) $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            return false;
        }

        $topicList = [];

        if (!empty($subsList)) {
            foreach ($subsList as $sub) {
                $topicList[] = $sub->thread;
            }

            $topicList = KunenaTopicHelper::getTopics($topicList);
        }

        return $topicList;
    }

    /**
     * @return  KunenaCategory[]
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function getCatSubscriptions(): array
    {
        $userid = $this->getState($this->getName() . '.id');

        return KunenaCategoryHelper::getSubscriptions($userid);
    }

    /**
     * @return array
     *
     * @throws \Exception
     * @since   Kunena 6.0
     */
    public function getIPlist(): array
    {
        $db     = $this->getDatabase();
        $userid = $this->getState($this->getName() . '.id');

        $query = $db->getQuery(true);
        $query->select('ip')
            ->from($db->quoteName('#__kunena_messages'))
            ->where($db->quoteName('userid') . ' = ' . $userid)
            ->group('ip');
        $db->setQuery($query);

        try {
            $ipList = implode("','", (array) $db->loadColumn());
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            return false;
        }

        $list = [];

        if ($ipList) {
            $ipList = "'{$ipList}'";
            $query  = $db->getQuery(true);
            $query->select('m.ip,m.userid,u.username,COUNT(*) as mescnt')
                ->from($db->quoteName('#__kunena_messages', 'm'))
                ->innerJoin($db->quoteName('#__users', 'u') . ' ON m.userid = u.id')
                ->where('m.ip IN (' . $ipList . ')')
                ->group('m.userid,m.ip');
            $db->setQuery($query);

            try {
                $list = (array) $db->loadObjectlist();
            } catch (RuntimeException $e) {
                Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

                return false;
            }
        }

        $userIpsList = [];

        foreach ($list as $item) {
            $userIpsList[$item->ip][] = $item;
        }

        return $userIpsList;
    }

    /**
     * @return  mixed
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function getListModCats()
    {
        $user = $this->getUser();

        $modCatList = array_keys(KunenaAccess::getInstance()->getModeratorStatus($user));

        if (empty($modCatList)) {
            $modCatList[] = 0;
        }

        $categoryList = [];

        if ($this->me->isAdmin()) {
            $categoryList[] = HTMLHelper::_('select.option', 0, Text::_('COM_KUNENA_GLOBAL_MODERATOR'));
        }

        // Todo: fix params
        $params = [
            'sections' => false,
            'action'   => 'read', ];

        return HTMLHelper::_('kunenaforum.categorylist', 'catid[]', 0, $categoryList, $params, 'class="form-select" multiple="multiple" size="15"', 'value', 'text', $modCatList, 'kforums');
    }

    /**
     * @return  KunenaUser
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function getUser(): KunenaUser
    {
        $userid = $this->getState($this->getName() . '.id');

        return KunenaUserHelper::get($userid);
    }

    /**
     * @return string
     *
     * @throws \Exception
     * @since   Kunena 6.0
     */
    public function getListUserRanks(): string
    {
        $db   = $this->getDatabase();
        $user = $this->getUser();

        // Grab all special ranks
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName('#__kunena_ranks'))
            ->where('rankSpecial = \'1\'');
        $db->setQuery($query);

        try {
            $specialRanks = (array) $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            return false;
        }

        $yesnoRank [] = HTMLHelper::_('select.option', '0', Text::_('COM_KUNENA_RANK_NO_ASSIGNED'));

        foreach ($specialRanks as $ranks) {
            $yesnoRank [] = HTMLHelper::_('select.option', $ranks->rankId, Text::_($ranks->rankTitle));
        }

        // Build special ranks select list
        return HTMLHelper::_('select.genericlist', $yesnoRank, 'newRank', 'class="form-select" size="1"', 'value', 'text', $user->rank);
    }

    /**
     * @return  mixed
     *
     * @since   Kunena 6.0
     */
    public function getMoveCatsList()
    {
        return HTMLHelper::_('kunenaforum.categorylist', 'catid', 0, array(), array(), 'class="inputbox form-control"', 'value', 'text');
    }

    /**
     * @return  array|void|null
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function getMoveUser()
    {
        $db = $this->getDatabase();

        $userids = (array) $this->app->getUserState('kunena.usermove.userids');

        if (!$userids) {
            return $userids;
        }

        $userids = implode(',', $userids);
        $query   = $db->getQuery(true);
        $query->select('id,username')
            ->from($db->quoteName('#__users'))
            ->where('id IN(' . $userids . ')');
        $db->setQuery($query);

        try {
            $userids = (array) $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

            return;
        }

        return $userids;
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   null  $ordering   ordering
     * @param   null  $direction  direction
     *
     * @return  void
     *
     * @throws Exception
     * @since   Kunena 6.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $context = 'com_kunena.admin.user';

        $app = Factory::getApplication();

        // Adjust the context to support modal layouts.
        $layout  = $app->input->get('layout');
        $context = 'com_kunena.admin.user';

        if ($layout) {
            $context .= '.' . $layout;
        }

        $value = Factory::getApplication()->input->getInt('userid');
        $this->setState($this->getName() . '.id', $value);
    }
}
