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
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;
use Kunena\Forum\Libraries\Controller\KunenaController;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\Tables\TableKunenaCategories;
use Kunena\Forum\Administrator\Model\CategoriesModel;
use RuntimeException;

/**
 * Kunena Categories Controller
 *
 * @property  $me  KunenaUser
 * @since   Kunena 2.0
 */
class CategoriesController extends KunenaController
{
    /**
     * @var     string
     * @since   Kunena 2.0.0-BETA2
     */
    protected $baseurl = null;

    /**
     * @var     string
     * @since   Kunena 2.0.0-BETA2
     */
    protected $baseurl2 = null;

    /**
     * Construct
     *
     * @param   array  $config  config
     *
     * @throws  Exception
     * @since   Kunena 2.0
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->baseurl = 'administrator/index.php?option=com_kunena&view=categories';
    }

    /**
     * Lock
     *
     * @return  void
     * @throws  Exception
     * @throws  null
     * @since   Kunena 2.0.0-BETA2
     */
    public function lock(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'locked', 1);
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
                    if (!empty($e->getMessage())) {
                        $this->app->enqueueMessage(
                            Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED', $category->id, $this->escape($e->getMessage())),
                            'error'
                        );
                    } else {
                        $this->app->enqueueMessage(
                            Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED_WITH_NO_ERROR_REPORTED', $category->id),
                            'error'
                        );
                    }
                }

                // At this point the category should be saved without errors
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
     * Unlock
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function unlock(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'locked', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Review
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function review(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'review', 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Unreview
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function unreview(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'review', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Allow Anonymous
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function allowanonymous(): void
    {
        // Function renamed from allowAnonymous in Kunena 6.3.0-BETA3 as this name collides with table
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'allowAnonymous', 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Deny Anonymous
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function denyanonymous(): void
    {
        // Function renamed from denyAnonymous in Kunena 6.3.0-BETA3 as this name collides with table
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'allowAnonymous', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Allow Polls
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function allowpolls(): void
    {
        // Function renamed from allowPolls in Kunena 6.3.0-BETA3 as this name collides with table
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'allowPolls', 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Deny Polls
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function denypolls(): void
    {
        // Function renamed from denyPolls in Kunena 6.3.0-BETA3 as this name collides with table
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'allowPolls', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Publish
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function publish(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'published', 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Unpublish
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function unpublish(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->setVariable($cid, 'published', 0);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Add
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function add()
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $id = array_shift($cid);
        $this->setRedirect(KunenaRoute::_("administrator/index.php?option=com_kunena&view=category&layout=create&catid={$id}", false));
    }

    /**
     * Edit
     *
     * @param   null  $key     key
     * @param   null  $urlVar  url var
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function edit($key = null, $urlVar = null)
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $id = array_shift($cid);

        if (!$id) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_A_NO_CATEGORIES_SELECTED'), 'notice');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $this->setRedirect(KunenaRoute::_("administrator/index.php?option=com_kunena&view=category&layout=edit&catid={$id}", false));
    }

    /**
     * Remove
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 3.0
     */
    public function delete()
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        if (empty($cid)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_A_NO_CATEGORIES_SELECTED'), 'notice');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $count = 0;
        $name  = null;

        $categories = KunenaCategoryHelper::getCategories($cid);

        foreach ($categories as $category) {
            if (!$category->isAuthorised('admin')) {
                $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->name)), 'error');
            } elseif (!$category->isCheckedOut($this->me->userid)) {
                try {
                    $category->delete();
                } catch (Exception $e) {
                    $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_DELETE_FAILED', $this->escape($e->getMessage())), 'error');
                }

                // At this point the category should be deleted without errors
                $count++;
                $name = $category->name;
            } else {
                $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)), 'notice');
            }
        }

        if ($count == 1 && $name) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_DELETED', $this->escape($name)), 'success');
        }

        if ($count > 1) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORIES_DELETED', $count), 'success');
        }

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Cancel
     *
     * @param   null  $key  key
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 3.0
     */
    public function cancel($key = null)
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $id = $this->input->getInt('catid', 0);

        $category = KunenaCategoryHelper::get($id);

        if (!$category->isAuthorised('admin')) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->name)), 'error');
        } elseif (!$category->isCheckedOut($this->me->userid)) {
            $category->checkIn();
        } else {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)), 'notice');
        }

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Save order
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 3.0
     */
    public function saveOrder()
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $cid   = $this->input->get('cid', [], 'array');
        $cid   = ArrayHelper::toInteger($cid);
        $order = $this->input->get('order', [], 'array');
        $order = ArrayHelper::toInteger($order);

        if (empty($cid)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_A_NO_CATEGORIES_SELECTED'), 'notice');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }

        $success = false;

        $categories = KunenaCategoryHelper::getCategories($cid);

        foreach ($categories as $category) {
            if (!isset($order[$category->id]) || $category->get('ordering') == $order[$category->id]) {
                continue;
            }

            if (!$category->getParent()->tryAuthorise('admin')) {
                $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->getParent()->name)), 'error');
            } elseif (!$category->isCheckedOut($this->me->userid)) {
                $category->set('ordering', $order[$category->id]);

                try {
                    $category->save();
                } catch (Exception $e) {
                    if (!empty($e->getMessage())) {
                        $this->app->enqueueMessage(
                            Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED', $category->id, $this->escape($e->getMessage())),
                            'error'
                        );
                    } else {
                        $this->app->enqueueMessage(
                            Text::sprintf('COM_KUNENA_A_CATEGORY_SAVE_FAILED_WITH_NO_ERROR_REPORTED', $category->id),
                            'error'
                        );
                    }
                }
            } else {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)),
                    'notice'
                );
            }
        }

        if ($success) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_NEW_ORDERING_SAVED'), 'success');
        }

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Method to save the submitted ordering values for records via AJAX.
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 3.0
     */
    public function saveorderajax(): void
    {
        /*if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return;
        }*/

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tableObject = new TableKunenaCategories($db);

        // Get the arrays from the Request
        $pks   = $this->input->post->get('cid', null, 'array');
        $order = $this->input->post->get('order', null, 'array');

        // Get the model
        $model = new CategoriesModel();

        // Save the ordering
        $return = $model->saveOrder($tableObject, $pks, $order);

        if ($return) {
            echo "1";
        }

        // Close the application
        $this->app->close();
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  object  The model.
     *
     * @since   1.6
     */
    public function getModel($name = 'Categories', $prefix = 'Administrator', $config = ['ignore_request' => true]): object
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Order Up
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 3.0
     */
    public function orderup(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->orderUpDown(array_shift($cid), -1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Order Up Down
     *
     * @param   integer  $id         id
     * @param   integer  $direction  direction
     *
     * @return  void
     *
     * @throws null
     * @since   Kunena 3.0
     */
    protected function orderUpDown(int $id, int $direction): void
    {
        KunenaFactory::loadLanguage('com_kunena', 'admin');

        if (!$id) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');

            return;
        }

        $category = KunenaCategoryHelper::get($id);

        if (!$category->getParent()->tryAuthorise('admin')) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_NO_ADMIN', $this->escape($category->getParent()->name)), 'error');

            return;
        }

        if ($category->isCheckedOut($this->me->userid)) {
            $this->app->enqueueMessage(Text::sprintf('COM_KUNENA_A_CATEGORY_X_CHECKED_OUT', $this->escape($category->name)), 'notice');

            return;
        }

        $row = new TableKunenaCategories($this->db);
        $row->load($id);

        // Ensure that we have the right ordering
        $where = 'parentid=' . $this->db->quote($row->parentid);
        $row->reOrder();
        $row->move($direction, $where);
    }

    /**
     * Order Down
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 3.0
     */
    public function orderdown(): void
    {
        $cid = $this->input->get('cid', [], 'array');
        $cid = ArrayHelper::toInteger($cid);

        $this->orderUpDown(array_shift($cid), 1);
        $this->setRedirect(KunenaRoute::_($this->baseurl, false));
    }

    /**
     * Method to archive one or multiples categories
     *
     * @return  void
     *
     * @throws  Exception
     * @throws  null
     * @since   Kunena 2.0
     */
    public function archive(): void
    {
        $cid = $this->input->get('cid', [], 'array');

        if (!empty($cid)) {
            $this->setVariable((int) $cid, 'published', 2);
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));
        }
    }

    /**
     * Method to put in trash one or multiple categories
     *
     * @return  void
     *
     * @throws  null
     * @throws  Exception
     * @since   Kunena 4.0
     */
    public function trash(): void
    {
        $cid = $this->input->get('cid', [], 'array');

        if (!empty($cid)) {
            $this->setVariable((int) $cid, 'published', -2);
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));
        }
    }

    /**
     * Method to do batch process on selected categories, to move or copy them.
     *
     * @return bool
     *
     * @since   Kunena 5.1.0
     * @throws \Exception
     */
    public function batchcategories(): bool
    {
        // Function renamed from batchCategories in Kunena 6.3.0-BETA3 as this name collides with view / model
        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_ERROR_TOKEN'), 'error');

            return false;
        }

        $cid       = $this->input->get('cid', '', 'array');
        $catParent = $this->input->getInt('batch_catid_target', 0);
        $task      = $this->input->getString('move_copy');

        if ($catParent == 0 || empty($cid)) {
            $this->app->enqueueMessage(Text::_('COM_KUNENA_CATEGORIES_LABEL_BATCH_NOT_SELECTED'), 'notice');
            $this->setRedirect(KunenaRoute::_($this->baseurl, false));

            return false;
        }

        if ($task == 'move') {
            foreach ($cid as $cat) {
                if ($catParent != $cat) {
                    $query = $this->db->getQuery(true);
                    $query->update($this->db->quoteName('#__kunena_categories'))
                        ->set($this->db->quoteName('parentid') . " = " . $this->db->quote(\intval($catParent)))
                        ->where($this->db->quoteName('id') . " = " . $this->db->quote($cat));
                    $this->db->setQuery($query);

                    try {
                        $this->db->execute();
                    } catch (RuntimeException $e) {
                        $this->app->enqueueMessage($e->getMessage(), 'error');

                        return false;
                    }
                }
            }

            $this->app->enqueueMessage(Text::_('COM_KUNENA_CATEGORIES_LABEL_BATCH_MOVE_SUCCESS'), 'success');
        }

        $this->setRedirect(KunenaRoute::_($this->baseurl, false));

        return true;
    }
}
