<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.User
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\User\Edit\User;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Kunena\Forum\Site\Controller\User\Edit\UserEditDisplay;
use StdClass;

/**
 * Class ComponentUserControllerEditUserDisplay
 *
 * @since   Kunena 4.0
 */
class UserEditUserDisplay extends UserEditDisplay
{
    /**
     * @var     boolean
     * @since   Kunena 6.0
     */
    public $changeUsername;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $frontendForm;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Edit/User';

    /**
     * Load user form.
     *
     * @return  void
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $userParams = ComponentHelper::getParams('com_users');

        // Check if user is allowed to change his name.
        $this->changeUsername = $userParams->get('change_login_name', 1);

        // Check to see if Frontend User Params have been enabled.
        if ($userParams->get('frontend_userparams', 0)) {
            $this->app->getLanguage()->load('com_users', JPATH_ADMINISTRATOR);

            Form::addFormPath(JPATH_ROOT . '/components/com_users/forms');
            Form::addFieldPath(JPATH_ROOT . '/components/com_users/models/fields');

            PluginHelper::importPlugin('user');

            $registry     = new Registry($this->user->params);
            $form         = Form::getInstance('com_users.profile', 'frontend');
            $data         = new StdClass();
            $data->params = $registry->toArray();
            $this->app->triggerEvent('onContentPrepareForm', [$form, $data]);

            $form->bind($data);
            $this->frontendForm = $form->getFieldset('params');
        }

        $this->headerText = Text::_('COM_KUNENA_PROFILE_EDIT_USER_TITLE');
    }

    /**
     * Prepare document.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    protected function prepareDocument()
    {
        $menu_item = $this->app->getMenu()->getActive();

        if ($menu_item) {
            $params             = $menu_item->getParams();
            $params_title       = $params->get('page_title');
            $params_description = $params->get('menu-meta_description');

            if (!empty($params_title)) {
                $title = $params->get('page_title');
                $this->setTitle($title);
            } else {
                $this->setTitle($this->headerText);
            }

            if (!empty($params_description)) {
                $description = $params->get('menu-meta_description');
                $this->setDescription($description);
            } else {
                $this->setDescription($this->headerText);
            }
        }
    }
}
