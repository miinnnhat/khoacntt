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

namespace Kunena\Forum\Site\Controller\User\item;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Utilities\ArrayHelper;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaBan;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;
use Kunena\Forum\Site\Model\UserModel;
use Kunena\Forum\Libraries\Controller\KunenaController;

/**
 * Class ComponentUserControllerItemDisplay
 *
 * @since   Kunena 4.0
 */
class UserItemDisplay extends KunenaControllerDisplay
{
    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $me;

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

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $headerText;

    /**
     * @var     object
     * @since   Kunena 6.0
     */
    public $tabs;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Item';

    public $state;

    public $candisplaymail;

    public $ktemplate;

    public $points;

    public $medals;

    public $private;

    public $socials;

    public $avatar;

    public $banInfo;

    /**
     * Load user profile.
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

        // If profile integration is disabled, this view doesn't exist.
        $integration = KunenaFactory::getProfile();

        if (\get_class($integration) == 'KunenaProfileNone') {
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_PROFILE_DISABLED'), 404);
        }

        $userid = $this->input->getInt('userid');

        $model = new UserModel([]);
        $model->initialize($this->getOptions(), $this->getOptions()->get('embedded', false));
        $this->state = $model->getState();

        $this->me      = KunenaUserHelper::getMyself();
        // Need to pass directly the $this->input->getInt('userid',0) to the loadUserById() to set 0 when userid is not set else it fails
        $this->user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->input->getInt('userid', 0));
        $this->profile = KunenaUserHelper::get($userid);
        $this->profile->tryAuthorise('read');
        $this->candisplaymail = $this->me->canDisplayEmail($this->profile);
        $this->ktemplate = KunenaFactory::getTemplate();

        $activityIntegration = KunenaFactory::getActivityIntegration();
        $this->points        = $activityIntegration->getUserPoints($this->profile->userid);
        $this->medals        = $activityIntegration->getUserMedals($this->profile->userid);
        $this->private       = KunenaFactory::getPrivateMessaging();
        $socials             = $this->profile->socialButtons();
        $this->socials       = ArrayHelper::toObject($socials);

        $this->avatar  = $this->profile->getAvatarImage(KunenaFactory::getTemplate()->params->get('avatarType'), 'post');
        $this->banInfo = $this->config->showBannedReason
            ? KunenaBan::getInstanceByUserid($this->profile->userid)
            : null;

        // Update profile hits.
        if (!$this->profile->exists() || !$this->profile->isMyself()) {
            $this->profile->uhits++;
            $this->profile->save();
        }

        $Itemid = $this->input->getInt('Itemid');
        $format = $this->input->getCmd('format');

        if (!$Itemid && $format != 'feed' && $this->config->sefRedirect) {
            $controller = new KunenaController();

            if ($this->config->profileId) {
                $itemidfix = $this->config->profileId;
            } else {
                $menu      = $this->app->getMenu();
                $getid     = $menu->getItem(KunenaRoute::getItemID("index.php?option=com_kunena&view=user"));
                $itemidfix = $getid->id;
            }

            if (!$itemidfix) {
                $itemidfix = KunenaRoute::fixMissingItemID();
            }

            if (!$userid) {
                $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=user&Itemid={$itemidfix}", false));
            } else {
                $controller->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=user&userid={$userid}&Itemid={$itemidfix}", false));
            }

            $controller->redirect();
        }

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
        $this->setMetaData('profile:username', $this->profile->getName(), 'property');

        if ($this->profile->getGender() == 1) {
            $this->setMetaData('profile:gender', Text::_('COM_KUNENA_MYPROFILE_GENDER_MALE'), 'property');
        } elseif ($this->profile->getGender() == 2) {
            $this->setMetaData('profile:gender', Text::_('COM_KUNENA_MYPROFILE_GENDER_FEMALE'), 'property');
        } else {
            $this->setMetaData('profile:gender', Text::_('COM_KUNENA_MYPROFILE_GENDER_UNKNOWN'), 'property');
        }

        $menu_item = $this->app->getMenu()->getActive();
        $robots    = $this->config->get('robots');
        $image     = '';

        $this->setMetaData('og:url', Uri::current(), 'property');
        $this->setMetaData('og:type', 'profile', 'property');

        if (is_file(JPATH_SITE . '/media/kunena/avatars/' . KunenaFactory::getUser($this->profile->id)->avatar)) {
            $image = Uri::root() . 'media/kunena/avatars/' . KunenaFactory::getUser($this->profile->id)->avatar;
        } elseif ($this->profile->avatar == null || $this->config->avatarType && KunenaFactory::getUser($this->profile->id)->avatar == null) {
            if (is_file(JPATH_SITE . '/' . $this->config->emailHeader)) {
                $image = Uri::base() . $this->config->emailHeader;
            }
        } else {
            $image = $this->profile->getAvatarURL('Profile', '200');
        }

        $this->setMetaData('og:image', $image, 'property');

        if ($robots == 'noindex, follow') {
            $this->setMetaData('robots', 'noindex, follow');
        } elseif ($robots == 'index, nofollow') {
            $this->setMetaData('robots', 'index, nofollow');
        } elseif ($robots == 'noindex, nofollow') {
            $this->setMetaData('robots', 'noindex, nofollow');
        } else {
            $this->setMetaData('robots', 'index, follow');
        }

        if ($menu_item) {
            $params             = $menu_item->getParams();
            $params_title       = $params->get('page_title');
            $params_description = $params->get('menu-meta_description');
            $params_robots      = $params->get('robots');

            if (!empty($params_title)) {
                $title = $params->get('page_title');
                $this->setTitle($title);
            } else {
                $title = Text::sprintf('COM_KUNENA_VIEW_USER_DEFAULT', $this->profile->getName());
                $this->setTitle($title);
            }

            $this->setMetaData('og:description', $title, 'property');
            $this->setMetaData('og:title', $this->profile->getName(), 'property');

            if (!empty($params_description)) {
                $description = $params->get('menu-meta_description');
                $this->setDescription($description);
            } else {
                $description = Text::sprintf(
                    'COM_KUNENA_META_PROFILE',
                    $this->profile->getName(),
                    $this->config->boardTitle,
                    $this->profile->getName(),
                    $this->config->boardTitle
                );
                $this->setDescription($description);
            }

            if (!empty($params_robots)) {
                $robots = $params->get('robots');
                $this->setMetaData('robots', $robots);
            }
        }
    }
}
