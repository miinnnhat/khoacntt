<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Models
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Model;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\Exception\ExecutionFailureException;
use Kunena\Forum\Libraries\Error\KunenaError;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Model\KunenaModel;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * User Model for Kunena
 *
 * @since   Kunena 6.0
 */
class UserModel extends KunenaModel
{
    /**
     * @return  integer
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function getTotal()
    {
        static $total = false;

        if ($total === false) {
            $db    = $this->getDatabase();
            $where = $this->getQueryWhere();
            $query = $db->getQuery(true);
            $query->select('COUNT(*)')->from($db->quoteName('#__users', 'u')->where("{$where}"));

            try {
                $total = $db->loadResult();
            } catch (ExecutionFailureException $e) {
                KunenaError::displayDatabaseError($e);
            }
        }

        return $total;
    }

    /**
     * @return  string
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function getQueryWhere()
    {
        $where = '';

        // Hide super admins from the list
        if (KunenaFactory::getConfig()->superAdminUserlist) {
            $db    = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select($db->quoteName('user_id'))->from($db->quoteName('#__user_usergroup_map'))->where($db->quoteName('group_id') . ' = 8');
            $db->setQuery($query);
            $superadmins = (array) $db->loadColumn();

            if (!$superadmins) {
                $superadmins = [0];
            }

            $this->setState('list.exclude', implode(',', $superadmins));

            $where = ' u.id NOT IN (' . $this->getState('list.exclude') . ') AND ';
        }

        if ($this->config->userlistCountUsers == '1') {
            $where .= '(u.block=0 OR u.activation="")';
        } elseif ($this->config->userlistCountUsers == '2') {
            $where .= '(u.block=0 AND u.activation="")';
        } elseif ($this->config->userlistCountUsers == '3') {
            $where .= 'u.block=0';
        } else {
            $where .= '1';
        }

        return $where;
    }

    /**
     * @return  array|false
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function getItems()
    {
        // FIXME: use pagination object and redirect on illegal page (maybe in the view)
        // TODO: should we reset to page 1 when user makes a new search?
        static $items = false;

        if ($items === false) {
            $limitstart = $this->getState('list.start');
            $limit      = $this->getState('list.limit');
            $count      = $this->getCount();

            if ($count < $limitstart) {
                $limitstart = $count - ($count % $limit);
                $this->setState('list.start', $limitstart);
            }

            $direction = $this->getState('list.direction');

            switch ($this->getState('list.ordering')) {
                case 'posts':
                    $orderby = 'ku.posts ';
                    break;
                case 'karma':
                    $orderby = 'ku.karma ';
                    break;
                case 'registerDate':
                    $orderby = 'u.registerDate ';
                    break;
                case 'lastvisitDate':
                    $orderby = 'u.lastvisitDate ';
                    break;
                case 'uhits':
                    $orderby = 'ku.uhits ';
                    break;
                case 'username':
                default:
                    $orderby = 'u.username ';
            }

            $db     = $this->getDatabase();
            $where  = $this->getQueryWhere();
            $search = $this->getQuerySearch();
            $query  = $db->getQuery(true);

            $query->select($db->quoteName('u.id'))
                ->from($db->quoteName('#__users', 'u'))
                ->join('left', $db->quoteName('#__kunena_users', 'ku') . ' ON (' . $db->quoteName('ku.userid') . ' = ' . $db->quoteName('u.id') . ')')
                ->where("{$where} {$search}")
                ->order("{$orderby} {$direction}");
            $query->setLimit($limit, $limitstart);
            $db->setQuery($query);

            try {
                $items = $db->loadColumn();
            } catch (ExecutionFailureException $e) {
                KunenaError::displayDatabaseError($e);
            }

            // Prefetch all users/avatars to avoid user by user queries during template iterations
            $items = KunenaUserHelper::loadUsers($items);
        }

        return $items;
    }

    /**
     * @return  integer
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function getCount()
    {
        static $total = false;

        if ($total === false) {
            $db     = $this->getDatabase();
            $where  = $this->getQueryWhere();
            $search = $this->getQuerySearch();

            $query = $db->getQuery(true);
            $query->select('COUNT(*)')
                ->from($db->quoteName('#__users', 'u'))
                ->join('left', $db->quoteName('#__kunena_users', 'ku') . ' ON (' . $db->quoteName('ku.userid') . ' = ' . $db->quoteName('u.id') . ')')
                ->where("{$where} {$search}");
            $db->setQuery($query);

            try {
                $total = $db->loadResult();
            } catch (ExecutionFailureException $e) {
                KunenaError::displayDatabaseError($e);
            }
        }

        return $total;
    }

    /**
     * @return  string
     *
     * @since   Kunena 6.0
     */
    public function getQuerySearch()
    {
        // TODO: add strict search from the beginning of the name
        $search = $this->getState('list.search');
        $where  = [];

        if ($search) {
            $db = $this->getDatabase();

            if ($this->config->username) {
                $where[] = "u.username LIKE '%{$db->escape($search)}%'";
            } else {
                $where[] = "u.name LIKE '%{$db->escape($search)}%'";
            }

            $where = 'AND (' . implode(' OR ', $where) . ')';
        } else {
            $where = '';
        }

        return $where;
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   null  $ordering
     * @param   null  $direction
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws Exception
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $active = $this->app->getMenu()->getActive();
        $active = $active ? (int) $active->id : 0;

        $layout = $this->getCmd('layout', 'default');
        $this->setState('layout', $layout);

        $display = $this->getUserStateFromRequest('com_kunena.users_display', 'display', 'topics');
        $this->setState('display', $display);

        $config = KunenaFactory::getConfig();

        // List state information
        $limit = $this->getUserStateFromRequest("com_kunena.users_{$active}_list_limit", 'limit', $config->userlistRows, 'int');

        if ($limit < 1 || $limit > 100) {
            $limit = $config->userlistRows;
        }

        if ($limit < 1) {
            $limit = 30;
        }

        $this->setState('list.limit', $limit);

        $value = $this->getUserStateFromRequest("com_kunena.users_{$active}_list_start", 'limitstart', 0, 'int');
        $value -= $value % $limit;
        $this->setState('list.start', $value);

        $value = $this->getUserStateFromRequest("com_kunena.users_{$active}_list_ordering", 'filter_order', 'id', 'cmd');
        $this->setState('list.ordering', $value);

        $value = $this->getUserStateFromRequest("com_kunena.users_{$active}_list_direction", 'filter_order_Dir', 'asc', 'word');

        if ($value != 'asc') {
            $value = 'desc';
        }

        $this->setState('list.direction', $value);

        $value = $this->app->input->get('search', null, 'string');

        if (!empty($value) && $value != Text::_('COM_KUNENA_USRL_SEARCH')) {
            $this->setState('list.search', rtrim($value));
        }
    }
}
