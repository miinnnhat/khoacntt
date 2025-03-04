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

namespace Kunena\Forum\Site\Controller\User\Edit;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class ComponentUserControllerEditDisplay
 *
 * @since   Kunena 4.0
 */
class UserEditDisplay extends KunenaControllerDisplay
{
    /**
     * @var     User
     * @since   Kunena 6.0
     */
    public $user;

    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $profile;

    public $headerText;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Edit';

    /**
     * Prepare user for editing.
     *
     * @return  void
     *
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        // If profile integration is disabled, this view doesn't exist.
        $integration = KunenaFactory::getProfile();

        if (\get_class($integration) == 'KunenaProfileNone') {
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_PROFILE_DISABLED'), 404);
        }

        $userid = $this->input->getInt('userid');

        if ($userid > 0) {
            $this->user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userid);
        } else {
            $this->user = KunenaUserHelper::getMyself();
        }

        $this->profile = KunenaUserHelper::get($userid);
        $this->profile->tryAuthorise('edit');

        $this->headerText = Text::sprintf('COM_KUNENA_VIEW_USER_DEFAULT', $this->profile->getName());
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
