<?php

/**
 * Kunena Component
 *
 * @package       Kunena.Framework
 * @subpackage    Forum.Message
 *
 * @copyright     Copyright (C) 2008 - @currentyear@ Kunena Team. All rights reserved.
 * @license       https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          https://www.kunena.org
 **/

namespace Kunena\Forum\Libraries\Forum\Message;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\Exception\ExecutionFailureException;
use Kunena\Forum\Libraries\Access\KunenaAccess;
use Kunena\Forum\Libraries\Error\KunenaError;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use stdClass;

/**
 * Kunena Forum Message Helper Class
 *
 * @since   Kunena 6.0
 */
abstract class KunenaMessageHelper
{
    /**
     * @var     KunenaMessage[]
     * @since   Kunena 6.0
     */
    protected static $_instances = [];

    /**
     * @var     array
     * @since   Kunena 6.0
     */
    protected static $_location = [];

    /**
     * @param   bool|array|int  $ids        ids
     * @param   string          $authorise  authorise
     *
     * @return  KunenaMessage[]
     *
     * @since   Kunena
     *
     * @throws  Exception
     * @throws  null
     */
    public static function getMessages($ids = false, $authorise = 'read'): array
    {
        if ($ids === false) {
            return self::$_instances;
        }

        if (\is_array($ids)) {
            $ids = array_unique($ids);
        } else {
            $ids = [$ids];
        }

        self::loadMessages($ids);

        $list = [];

        foreach ($ids as $id) {
            // TODO: authorisation needs topics to be loaded, make sure that they are! (performance increase)
            if (!empty(self::$_instances [$id]) && self::$_instances [$id]->isAuthorised($authorise, null)) {
                $list [$id] = self::$_instances [$id];
            }
        }

        return $list;
    }

    /**
     * @param   array  $ids  ids
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected static function loadMessages(array $ids): void
    {
        foreach ($ids as $i => $id) {
            $id = \intval($id);

            if (!$id || isset(self::$_instances [$id])) {
                unset($ids[$i]);
            }
        }

        if (empty($ids)) {
            return;
        }

        $idlist = implode(',', $ids);
        $db     = Factory::getContainer()->get('DatabaseDriver');
        $query  = $db->getQuery(true);
        $query->select('m.*, t.message')
            ->from($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_messages_text', 't') . ' ON m.id = t.mesid')
            ->where($db->quoteName('m.id') . ' IN (' . $idlist . ')');
        $db->setQuery($query);

        try {
            $results = (array) $db->loadAssocList('id');
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        foreach ($ids as $id) {
            if (isset($results[$id])) {
                $instance = new KunenaMessage($results[$id]);
                $instance->exists(true);
                self::$_instances [$id] = $instance;
            } else {
                self::$_instances [$id] = null;
            }
        }

        unset($results);
    }

    /**
     * @param   mixed   $topic      topic
     * @param   int     $start      start
     * @param   int     $limit      limit
     * @param   string  $ordering   ordering
     * @param   int     $hold       hold
     * @param   bool    $orderbyid  orderbyid
     *
     * @return  KunenaMessage[]
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public static function getMessagesByTopic($topic, $start = 0, $limit = 0, $ordering = 'ASC', $hold = 0, $orderbyid = false): array
    {
        $topic = KunenaTopicHelper::get($topic);

        if (!$topic->exists()) {
            return [];
        }

        $total = $topic->getTotal();

        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = KunenaFactory::getConfig()->messagesPerPage;
        }

        // If out of range, use last page
        if ($total < $start) {
            $start = \intval($total / $limit) * $limit;
        }

        $ordering = strtoupper($ordering);

        if ($ordering != 'DESC') {
            $ordering = 'ASC';
        }

        return self::loadMessagesByTopic($topic->id, $start, $limit, $ordering, $hold, $orderbyid);
    }

    /**
     * @param   int     $topic_id   topic id
     * @param   int     $start      start
     * @param   int     $limit      limit
     * @param   string  $ordering   ordering
     * @param   int     $hold       hold
     * @param   bool    $orderbyid  orderbyid
     *
     * @return  array
     *
     * @since   Kunena 6.0
     *
     * @throws Exception
     */
    protected static function loadMessagesByTopic(int $topic_id, $start = 0, $limit = 0, $ordering = 'ASC', $hold = 0, $orderbyid = false): array
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select('m.*,t.message')
            ->from($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_messages_text', 't') . ' ON '.  $db->quoteName('m.id') . ' = ' . $db->quoteName('t.mesid'))
            ->where($db->quoteName('m.thread') . ' = ' . $db->quote($topic_id))
            ->andWhere($db->quoteName('m.hold') . ' IN (' . $hold . ')')
            ->order($db->quoteName('m.time') . $ordering);
        $query->setLimit($limit, $start);
        $db->setQuery($query);

        try {
            $results = (array) $db->loadAssocList('id');
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        $location = ($orderbyid || $ordering == 'ASC') ? $start : KunenaTopicHelper::get($topic_id)->getTotal($hold) - $start - 1;
        $order    = ($ordering == 'ASC') ? 1 : -1;
        $list     = [];

        if (!empty($results)) {
            foreach ($results as $id => $result) {
                $instance = new KunenaMessage($result);
                $instance->exists(true);
                self::$_instances [$id]             = $instance;
                $list[$orderbyid ? $id : $location] = $instance;
                $location                           += $order;
            }
        }

        unset($results);

        return $list;
    }

    /**
     * @param   bool|array|int  $categories  categories
     * @param   int             $limitstart  limitstart
     * @param   int             $limit       limit
     * @param   array           $params      params
     *
     * @return  array
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     * @throws  null
     */
    public static function getLatestMessages($categories = false, $limitstart = 0, $limit = 0, $params = []): array
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');

        $reverse     = isset($params['reverse']) ? (int) $params['reverse'] : 0;
        $orderby     = isset($params['orderby']) ? (string) $params['orderby'] : $db->quoteName('m.time') . ' DESC';
        $starttime   = isset($params['starttime']) ? (int) $params['starttime'] : 0;
        $mode        = isset($params['mode']) ? $params['mode'] : 'recent';
        $user        = isset($params['user']) ? $params['user'] : false;
        $where       = isset($params['where']) ? (string) $params['where'] : '';
        $childforums = isset($params['childforums']) ? (bool) $params['childforums'] : false;
        $view        = Factory::getApplication()->input->getCmd('view');

        if ($limit < 1 && empty($params['nolimit'])) {
            if ($view == 'search') {
                $limit = KunenaFactory::getConfig()->messagesPerPageSearch;
            } elseif ($view == 'topics') {
                $limit = KunenaFactory::getConfig()->threadsPerPage;
            } else {
                $limit = KunenaFactory::getConfig()->messagesPerPage;
            }
        }

        $query = $db->getQuery(true);
        $query->select('m.*')
            ->select($db->quoteName('t.message'))
            ->from($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_messages_text', 't') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('t.mesid'))
            ->order($orderby);

        $authorise = 'read';
        $hold      = 'm.hold = 0';
        $userfield = 'm.userid';

        switch ($mode) {
            case 'unapproved':
                $authorise = 'approve';
                $hold      = $db->quoteName('m.hold') . '= 1';
                break;
            case 'deleted':
                $authorise = 'undelete';
                $hold      = $db->quoteName('m.hold') . ' >= 2';
                break;
            case 'mythanks':
                $userfield = 'th.userid';
                $query->innerJoin($db->quoteName('#__kunena_thankyou', 'th') . ' ON ' . $db->quoteName('m.id') . ' = ' .  $db->quoteName('th.postid'));
                break;
            case 'thankyou':
                $userfield = 'th.targetuserid';
                $query->innerJoin($db->quoteName('#__kunena_thankyou', 'th') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('th.postid'));
                break;
            case 'recent':
            default:
        }

        if (\is_array($categories) && \in_array(0, $categories)) {
            $categories = false;
        }

        $categories = KunenaCategoryHelper::getCategories($categories, $reverse, 'topic.' . $authorise);

        if ($childforums) {
            $categories += KunenaCategoryHelper::getChildren($categories, -1, ['action' => 'topic.' . $authorise]);
        }

        $catlist = [];

        foreach ($categories as $category) {
            if ($view == 'search') {
                if ($category->isAuthorised('read')) {
                    $catlist += $category->getChannels();
                }
            } else {
                $catlist += $category->getChannels();
            }
        }

        if (count($catlist) > 0) {
            $allowed = implode(',', array_keys($catlist));
            $query->where($db->quoteName('m.catid') . ' IN (' . $allowed . ')');
        } else {
            $catlist = '';
        }

        $query->where($hold);

        if ($user) {
            $query->where($db->quoteName($userfield) . ' = ' . $db->quote($user));
        }

        // Negative time means no time
        if ($starttime == 0) {
            $starttime = KunenaFactory::getSession()->lasttime;
        } elseif ($starttime > 0) {
            $starttime = Factory::getDate()->toUnix() - ($starttime * 3600);
        }

        if ($starttime > 0) {
            $query->where('m.time > ' . $db->quote($starttime));
        }

        if ($where) {
            $query->where($where);
        }

        $cquery = clone $query;
        $cquery->clear('select')
            ->clear('order')
            ->select('COUNT(*)');
        $db->setQuery($cquery);

        try {
            $total = (int) $db->loadResult();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);

            return [0, []];
        }

        if (!$total) {
            return [0, []];
        }

        // If out of range, use last page
        if ($limit && $total < $limitstart) {
            $limitstart = \intval($total / $limit) * $limit;
        }

        $query->setLimit($limit, $limitstart);
        $db->setQuery($query);

        try {
            $results = $db->loadAssocList();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);

            return [0, []];
        }

        $messages = [];

        if (!empty($results)) {
            foreach ($results as $result) {
                $instance = new KunenaMessage($result);
                $instance->exists(true);
                self::$_instances [$instance->id] = $instance;
                $messages[$instance->id]          = $instance;
            }
        }

        unset($results);

        return [$total, $messages];
    }

    /**
     * @param   int          $mesid      mesid
     * @param   null|string  $direction  direction
     * @param   null|array   $hold       hold
     *
     * @return  integer
     *
     * @since   Kunena 6.0
     *
     * @throws Exception
     */
    public static function getLocation(int $mesid, $direction = null, $hold = null): int
    {
        if (\is_null($direction)) {
            $direction = KunenaUserHelper::getMyself()->getMessageOrdering();
        }

        if (!$hold) {
            $me           = KunenaUserHelper::getMyself();
            $mes_instance = self::get($mesid);

            if ($mes_instance->exists()) {
                $hold         = KunenaAccess::getInstance()->getAllowedHold($me->userid, $mes_instance->catid, false);
            }
        }

        if (!isset(self::$_location [$mesid])) {
            self::loadLocation([$mesid]);
        }

        $location = self::$_location [$mesid];
        $count    = 0;

        foreach ($location->hold as $meshold => $values) {
            if (isset($hold[$meshold])) {
                $count += $values[$direction == 'asc' ? 'before' : 'after'];

                if ($direction == 'both') {
                    $count += $values['before'];
                }
            }
        }

        return $count;
    }

    /**
     * Returns \Kunena\Forum\Libraries\Forum\Message\Message object.
     *
     * @param   null  $identifier  The message to load - Can be only an integer.
     * @param   bool  $reload      reload
     *
     * @return  KunenaMessage  The message object.
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public static function get($identifier = null, $reload = false): KunenaMessage
    {
        if ($identifier instanceof KunenaMessage) {
            return $identifier;
        }

        $id = (int) $identifier;

        if ($id < 1) {
            return new KunenaMessage();
        }

        if (empty(self::$_instances[$id])) {
            $instance = new KunenaMessage();

            // Only load messages which haven't been preloaded before (including missing ones).
            $instance->load(!\array_key_exists($id, self::$_instances) ? $id : null);
            $instance->id          = $id;
            self::$_instances[$id] = $instance;
        } elseif ($reload) {
            self::$_instances[$id]->load();
        }

        return self::$_instances[$id];
    }

    /**
     * @param   array|string  $mesids  mesid
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public static function loadLocation($mesids): void
    {
        // NOTE: if you already know the location using this code just takes resources
        if (!\is_array($mesids)) {
            $mesids = explode(',', $mesids);
        }

        $ids = [];

        foreach ($mesids as $id) {
            if ($id instanceof KunenaMessage) {
                $id = $id->id;
            } else {
                $id = (int) $id;
            }

            if (!isset(self::$_location [$id])) {
                $ids[$id]                    = $id;
                self::$_location [$id]       = new stdClass();
                self::$_location [$id]->hold = ['before' => 0, 'after' => 0];
            }
        }

        if (empty($ids)) {
            return;
        }

        $idlist = implode(',', $ids);
        $db     = Factory::getContainer()->get('DatabaseDriver');
        $query  = $db->getQuery(true);
        $query->select(
            'm.id, mm.hold, m.catid AS category_id, m.thread AS topic_id,
				SUM(mm.time<m.time) AS before_count,
				SUM(mm.time>m.time) AS after_count'
        )
            ->from($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_messages', 'mm') . ' ON ' . $db->quoteName('m.thread') . ' = ' . $db->quoteName('mm.thread'))
            ->where($db->quoteName('m.id') . ' IN (' . $idlist . ')')
            ->group([$db->quoteName('m.id'), $db->quoteName('mm.hold')]);
        $db->setQuery($query);

        try {
            $results = (array) $db->loadObjectList();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        if (!empty($results)) {
            foreach ($results as $result) {
                $instance = self::$_location [$result->id];

                if (!isset($instance->id)) {
                    $instance->id                    = $result->id;
                    $instance->category_id           = $result->category_id;
                    $instance->topic_id              = $result->topic_id;
                    self::$_location [$instance->id] = $instance;
                }

                $instance->hold[$result->hold] = ['before' => $result->before_count, 'after' => $result->after_count];
            }
        }
    }

    /**
     * Free up memory by cleaning up all cached items.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     */
    public static function cleanup(): void
    {
        self::$_instances = [];
        self::$_location  = [];
    }

    /**
     * @param   bool|array|int  $topicids  topicids
     *
     * @return  boolean|integer
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public static function recount($topicids = false)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        if (\is_array($topicids)) {
            $where = $db->quoteName('m.thread') . ' IN (' . implode(',', $topicids) . ')';
        } elseif ((int) $topicids) {
            $where = $db->quoteName('m.thread') . ' = ' . $db->quote((int) $topicids);
        } else {
            $where = '';
        }

        // Update catid in all messages
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_attachments', 'tt') . ' ON ' . $db->quoteName('tt.id') . ' = ' . $db->quoteName('m.thread'))
            ->set($db->quoteName('m.catid') . ' = ' . $db->quoteName('tt.category_id'))
            ->where($where);
        $db->setQuery($query);

        try {
            $db->execute();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);

            return false;
        }

        return $db->getAffectedRows();
    }

    /**
     * @param   array  $ids  ids
     *
     * @return  array|boolean
     *
     * @since   Kunena 5.0.3
     *
     * @throws  Exception
     */
    public static function getMessagesByTopics(array $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        $idlist = implode(',', $ids);
        $query  = $db->getQuery(true);
        $query->select(array('m.*', 't.message'))
            ->from($db->quoteName('#__kunena_messages', 'm'))
            ->innerJoin($db->quoteName('#__kunena_messages_text', 't') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('t.mesid'))
            ->where($db->quoteName('m.thread') . ' IN (' . $idlist . ')')
            ->andWhere($db->quoteName('m.hold') . ' = 0');
        $db->setQuery($query);

        try {
            $results = (array) $db->loadAssocList('id');
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        return $results;
    }

    /**
     * Get last IP address used by the user
     *
     * @param   int  $userid  userid
     *
     * @return bool
     *
     * @since   Kunena 6.0
     *
     * @throws \Exception
     */
    public static function getLastUserIP(int $userid): bool
    {
        if (!$userid) {
            return false;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true);
        $query->select($db->quoteName('ip'))
            ->from($db->quoteName('#__kunena_messages'))
            ->where($db->quoteName('userid') . ' = ' . $db->quote($userid))
            ->group($db->quoteName('ip'))
            ->order($db->quoteName('time') . ' DESC');
        $query->setLimit(1);
        $db->setQuery($query);

        try {
            $ip = $db->loadResult();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        return $ip;
    }

    /**
     * Get the messages from users given
     *
     * @param   array  $users
     *
     * @return object
     *
     * @since   Kunena 6.0
     *
     * @throws \Exception
     */
    public static function getMessagesFromUsers(array $users)
    {
        $list = [];

        foreach ($users as $user) {
            if ($user instanceof KunenaUser) {
                $list[] = (int) $user->userid;
            } elseif ($user instanceof User) {
                $list[] = (int) $user->id;
            } else {
                $list[] = (int) $user;
            }
        }

        if (empty($list)) {
            return;
        }

        $userlist = implode(',', $list);

        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true);
        $query->select($db->quoteName('thread'))
            ->from($db->quoteName('#__kunena_messages'))
            ->where($db->quoteName('userid') . ' IN (' . $userlist . ')')
            ->group($db->quoteName('thread'));

        $db->setQuery($query);

        try {
            $threads = $db->loadObjectList();
        } catch (ExecutionFailureException $e) {
            KunenaError::displayDatabaseError($e);
        }

        return $threads;
    }
}
