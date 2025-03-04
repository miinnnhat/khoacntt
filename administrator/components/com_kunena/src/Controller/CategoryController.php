<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Administrator
 * @subpackage      Controllers
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Controller;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Kunena\Forum\Libraries\Controller\KunenaController;
use Kunena\Forum\Libraries\Exception\KunenaException;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Kunena Category Controller
 *
 * @since   Kunena 6.0
 */
class CategoryController extends KunenaController
{
    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $baseurl = null;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $basecategoryurl = null;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws Exception
     * @since   Kunena 2.0
     *
     * @see     FormController
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->baseurl         = 'administrator/index.php?option=com_kunena&view=categories';
        $this->basecategoryurl = 'administrator/index.php?option=com_kunena&view=category';
    }

    /**
     * Save changes on the category
     *
     * @param   null  $key     key
     * @param   null  $urlVar  url var
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function save($key = null, $urlVar = null)
    {
        $this->internalSave();
        $postCatid = $this->input->post->get('catid', '', 'raw');

        if ($this->app->isClient('administrator')) {
            if ($this->task === 'apply') {
                $this->setRedirect(KunenaRoute::_($this->basecategoryurl . "&layout=edit&catid={$postCatid}", false));
            } else {
                $this->setRedirect(KunenaRoute::_($this->baseurl, false));
            }
        } else {
            $this->setRedirect(KunenaRoute::_($this->basecategoryurl . '&catid=' . $postCatid));
        }
    }

    /**
     * Internal method to save category
     *
     * @return false|KunenaCategory
     *
     * @since   Kunena 2.0.0-BETA2
     *@throws  Exception
     * @throws  null
     */
    protected function internalSave()
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');
        KunenaFactory::loadLanguage('com_kunena.controllers', 'admin');
        $me = KunenaUserHelper::getMyself();

        if ($this->app->isClient('site')) {
            KunenaFactory::loadLanguage('com_kunena.controllers', 'admin');
        }

        if (!Session::checkToken()) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return false;
        }

        $post       = $this->input->post->getArray();
        $accesstype = strtr($this->input->getCmd('accesstype', 'joomla.level'), '.', '-');

        if (empty($post['name'])) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_CATEGORY_MANAGER_PLEASE_SET_A_TITLE'), 'error');

            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return false;
        }

        if ($post['task'] === 'category.save2copycategory') {
            $post['name'] = $this->app->getUserState('com_kunena.category_title');
            $post['alias'] = $this->app->getUserState('com_kunena.category_alias');
            $post['catid'] = $this->app->getUserState('com_kunena.category_catid');
        }

        $post['access'] = $this->input->getInt("access-{$accesstype}", $this->input->getInt('access'));
        $post['params'] = $this->input->get("params-{$accesstype}", [], 'array');
        $post['params'] += $this->input->get("params", [], 'array');
        $success        = false;

        $category = KunenaCategoryHelper::get(\intval($post ['catid']));
        $parent   = KunenaCategoryHelper::get(\intval($post ['parentid']));

        if ($category->exists() && !$category->isAuthorised('admin')) {
            // Category exists and user is not admin in category
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->name)), 'error');
        } elseif (!$category->exists() && !$me->isAdmin($parent)) {
            // Category doesn't exist and user is not admin in parent, parentid=0 needs global admin rights
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($parent->name)), 'error');
        } elseif (!$category->isCheckedOut($me->userid)) {
            // Nobody can change id or statistics
            $ignore = ['option', 'view', 'task', 'catid', 'id', 'id_last_msg', 'numTopics', 'numPosts', 'time_last_msg', 'aliases', 'aliasesAll'];

            // User needs to be admin in parent (both new and old) in order to move category, parentid=0 needs global admin rights
            if (!$me->isAdmin($parent) || ($category->exists() && !$me->isAdmin($category->getParent()))) {
                $ignore            = array_merge($ignore, ['parentid', 'ordering']);
                $post ['parentid'] = $category->parentid;
            }

            // Only global admin can change access control and class_sfx (others are inherited from parent)
            if (!$me->isAdmin()) {
                $access = ['accesstype', 'access', 'pubAccess', 'pubRecurse', 'adminAccess', 'adminRecurse', 'channels', 'class_sfx', 'params'];

                if (!$category->exists() || $parent->id != $category->parentid) {
                    // If category didn't exist or is moved, copy access and class_sfx from parent
                    $category->bind($parent->getProperties(), $access, true);
                }

                $ignore = array_merge($ignore, $access);
            }

            $category->bind($post, $ignore);

            if (!$category->exists()) {
                $category->ordering = 99999;
            }

            try {
                $success = $category->save();
            } catch (KunenaException $e) {
                if (!empty($e->getMessage())) {
                    $this->app->enqueueMessage(
                        Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED', $category->id, $e->getMessage()),
                        'error'
                    );
                } else {
                    $this->app->enqueueMessage(
                        Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED_WITH_NO_ERROR_REPORTED', $category->id),
                        'error'
                    );
                }
            }

            $aliasesInput = $this->app->input->getString('aliases_all');

            if (!empty($aliasesInput)) {
                $aliases_all = explode(',', $aliasesInput);

                $aliases = $this->app->input->post->getArray(['aliases' => []]);

                if ($aliases_all && count($aliases['aliases']) > 1) {
                    $aliases = array_diff($aliases_all, $aliases['aliases']);

                    foreach ($aliases_all as $alias) {
                        $category->deleteAlias($alias);
                    }
                }
            }

            // Update read access
            $read                = $this->app->getUserState("com_kunena.user{$me->userid}_read");
            $read[$category->id] = $category->id;
            $this->app->setUserState("com_kunena.user{$me->userid}_read", null);

            $category->checkIn();
        } else {
            // Category was checked out by someone else.
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)), 'notice');
        }

        if ($success) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_SAVED', $this->escape($category->name)), 'success');
        }

        if (!empty($post['rmmod'])) {
            foreach ((array) $post['rmmod'] as $userid => $value) {
                $user = KunenaFactory::getUser($userid);

                if ($category->tryAuthorise('admin', null, false) && $category->removeModerator($user)) {
                    $this->app->enqueueMessage(
                        Text::sprintf(
                            'COM_KUNENA_VIEW_CATEGORY_EDIT_MODERATOR_REMOVED',
                            $this->escape($user->getName()),
                            $this->escape($category->name)
                        ),
                        'success'
                    );
                }
            }
        }

        return $category;
    }

    /**
     * Apply
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function apply(): void
    {
        $category = $this->internalSave();

        if ($category->exists()) {
            $this->setRedirect(KunenaRoute::_($this->basecategoryurl . "&layout=edit&catid={$category->id}", false));
        } else {
            $this->setRedirect(KunenaRoute::_($this->basecategoryurl . "&layout=create", false));
        }
    }

    /**
     * Cancel
     *
     * @param   null  $key     key
     * @param   null  $urlVar  url var
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function cancel($key = null, $urlVar = null)
    {
        $postCatid = $this->input->post->get('catid', '', 'raw');
        $category  = KunenaCategoryHelper::get($postCatid);
        $category->checkIn();

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Method to save a category like a copy of existing one.
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function save2copycategory(): void
    {
        $postCatid = $this->input->post->get('catid', '', 'raw');
        $postAlias = $this->input->post->get('alias', '', 'raw');
        $postName  = $this->input->post->get('name', '', 'raw');

        list($title, $alias) = $this->internalGenerateNewTitle($postCatid, $postAlias, $postName);

        $this->app->setUserState('com_kunena.category_title', $title);
        $this->app->setUserState('com_kunena.category_alias', $alias);
        $this->app->setUserState('com_kunena.category_catid', 0);

        $this->internalSave();
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Method to change the title & alias.
     *
     * @param   integer  $categoryId  The id of the category.
     * @param   string   $alias       The alias.
     * @param   string   $name        The name.
     *
     * @return  array  Contains the modified title and alias.
     *
     * @throws Exception
     * @since   Kunena 2.0.0-BETA2
     */
    protected function internalGenerateNewTitle(int $categoryId, string $alias, string $name): array
    {
        while (KunenaCategoryHelper::getAlias($categoryId, $alias)) {
            $name  = StringHelper::increment($name);
            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$name, $alias];
    }

    /**
     * Save2new
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function save2newcategory(): void
    {
        $this->internalSave();
        $this->setRedirect(KunenaRoute::_($this->basecategoryurl . "&layout=create", false));
    }

    /**
     * Method to checkin
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 6.0.0
     */
    public function checkin()
    {
        // TODO : need to implement the logic to checkin the category

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Publish category item
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function publish(): void
    {
        $cid = $this->app->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'published', 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Unpublish category item
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function unpublish(): void
    {
        $cid = $this->app->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'published', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Set variable
     *
     * @param   array   $cid       id
     * @param   string  $variable  variable
     * @param   string  $value     value
     *
     * @return  void
     *
     * @throws null
     * @throws Exception
     * @since   Kunena 3.0
     */
    protected function setVariable(array $cid, string $variable, string $value): void
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');

            return;
        }

        if (empty($cid)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_A_NO_CATEGORIES_SELECTED'), 'notice');

            return;
        }

        $count = 0;
        $name  = null;

        $categories = KunenaCategoryHelper::getCategories($cid);

        foreach ($categories as $category) {
            if ($category->get($variable) == $value) {
                continue;
            }

            if (!$category->isAuthorised('admin')) {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->name)),
                    'notice'
                );
            } elseif (!$category->isCheckedOut($this->me->userid)) {
                $category->set($variable, $value);
                
                try {
                    $category->save();
                } catch (Exception $e) {
                    $this->app->enqueueMessage(
                        Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED', $category->id, $this->escape($e->getMessage())),
                        'error'
                        );
                }

                $count++;
                $name = $category->name;
            } else {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)),
                    'notice'
                );
            }
        }

        if ($count == 1 && $name) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_SAVED', $this->escape($name)), 'success');
        }

        if ($count > 1) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORIES_SAVED', $count), 'success');
        }
    }

    /**
     * Check if the alias given is already in use in the categories
     * 
     * @return  void
     *
     * @since   Kunena 6.0
     */
    public function ChkAliases(): void
    {
        $alias = $this->app->input->get('alias', null, 'string');

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select('id')
            ->from($db->quoteName('#__kunena_categories'))
            ->where('alias = ' . $db->quote($alias));
        $db->setQuery($query);
        $result = (int) $db->loadResult();

        if ($result) {
            $response['msg'] = 0;
        } else {
            $response['msg'] = 1;
        }

        echo json_encode($response);

        jexit();
    }
}
