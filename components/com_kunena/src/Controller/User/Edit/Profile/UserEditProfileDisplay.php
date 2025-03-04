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

namespace Kunena\Forum\Site\Controller\User\Edit\Profile;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Kunena\Forum\Libraries\Config\KunenaConfig;
use Kunena\Forum\Site\Controller\User\Edit\UserEditDisplay;

/**
 * Class ComponentUserControllerEditProfileDisplay
 *
 * @since   Kunena 4.0
 */
class UserEditProfileDisplay extends UserEditDisplay
{
    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Edit/Profile';

    public $genders;

    public $social;

    public $birthdate;

    /**
     * Prepare profile form items.
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

        $bd = $this->profile->birthdate ? explode("-", $this->profile->birthdate) : [];

        if (\count($bd) == 3) {
            $this->birthdate["year"]  = $bd[0];
            $this->birthdate["month"] = $bd[1];
            $this->birthdate["day"]   = $bd[2];
        }

        $this->genders[] = HTMLHelper::_('select.option', '0', Text::_('COM_KUNENA_MYPROFILE_GENDER_UNKNOWN'));
        $this->genders[] = HTMLHelper::_('select.option', '1', Text::_('COM_KUNENA_MYPROFILE_GENDER_MALE'));
        $this->genders[] = HTMLHelper::_('select.option', '2', Text::_('COM_KUNENA_MYPROFILE_GENDER_FEMALE'));

        $config = KunenaConfig::getInstance();

        if ($config->social) {
            $this->social = $this->profile->socialButtons();
            $this->social = ArrayHelper::toObject($this->social);
        } else {
            $this->social = null;
        }

        $this->headerText = Text::_('COM_KUNENA_PROFILE_EDIT_PROFILE_TITLE');
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
