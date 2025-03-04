<?php

/**
 * Kunena Plugin
 *
 * @package          Kunena.Plugins
 * @subpackage       Community
 *
 * @copyright   (C)  2008 - 2024 Kunena Team. All rights reserved.
 * @copyright   (C)  2013 - 2014 iJoomla, Inc. All rights reserved.
 * @license          https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link             https://www.kunena.org
 **/

defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;
use Kunena\Forum\Libraries\Forum\KunenaForum;
use Kunena\Forum\Plugin\Kunena\Community\KunenaAccessCommunity;
use Kunena\Forum\Plugin\Kunena\Community\KunenaActivityCommunity;
use Kunena\Forum\Plugin\Kunena\Community\KunenaAvatarCommunity;
use Kunena\Forum\Plugin\Kunena\Community\KunenaLoginCommunity;
use Kunena\Forum\Plugin\Kunena\Community\KunenaPrivateCommunity;
use Kunena\Forum\Plugin\Kunena\Community\KunenaProfileCommunity;

/**
 * Class PlgKunenaCommunity
 *
 * @since   Kunena 6.0
 */
class PlgKunenaCommunity extends CMSPlugin
{
    /**
     * plgKunenaCommunity constructor.
     *
     * @param   DispatcherInterface  &$subject  The object to observe
     * @param   array                 $config   An optional associative array of configuration settings.
     *                                          Recognized key values include 'name', 'group', 'params', 'language'
     *                                          (this list is not meant to be comprehensive).
     *
     * @throws Exception
     * @since   Kunena 6.0
     */
    public function __construct(&$subject, $config)
    {
        // Do not load if Kunena version is not supported or Kunena is offline
        if (!(class_exists('Kunena\Forum\Libraries\Forum\KunenaForum') && KunenaForum::isCompatible('6.3') && KunenaForum::enabled())) {
            return;
        }

        // Do not load if JomSocial is not installed
        $path = JPATH_ROOT . '/components/com_community/libraries/core.php';

        if (!is_file($path)) {
            return;
        }

        include_once $path;

        parent::__construct($subject, $config);

        $this->loadLanguage('plg_kunena_community.sys', JPATH_ADMINISTRATOR) || $this->loadLanguage('plg_kunena_community.sys', JPATH_ADMINISTRATOR . '/components/com_kunena');
    }

    /**
     * Get Kunena access control object.
     *
     * @return  KunenaAccessCommunity|void
     *
     * @todo    Should we remove category ACL integration?
     * @since   Kunena
     */
    public function onKunenaGetAccessControl()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('access', 1)) {
            return;
        }

        return new KunenaAccessCommunity($this->params);
    }

    /**
     * Get Kunena login integration object.
     *
     * @return  KunenaLoginCommunity|null|void
     * @since   Kunena 6.0
     */
    public function onKunenaGetLogin()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('login', 1)) {
            return;
        }

        return new KunenaLoginCommunity($this->params);
    }

    /**
     * Get Kunena avatar integration object.
     *
     * @return  KunenaAvatarCommunity|void
     * @since   Kunena 6.0
     */
    public function onKunenaGetAvatar()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('avatar', 1)) {
            return;
        }

        return new KunenaAvatarCommunity($this->params);
    }

    /**
     * Get Kunena profile integration object.
     *
     * @return  KunenaProfileCommunity|null|void
     * @since   Kunena 6.0
     */
    public function onKunenaGetProfile()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('profile', 1)) {
            return;
        }

        return new KunenaProfileCommunity($this->params);
    }

    /**
     * Get Kunena private message integration object.
     *
     * @return  KunenaPrivateCommunity|null|void
     * @since   Kunena 6.0
     */
    public function onKunenaGetPrivate()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('private', 1)) {
            return;
        }

        return new KunenaPrivateCommunity($this->params);
    }

    /**
     * Get Kunena activity stream integration object.
     *
     * @return  KunenaActivityCommunity|null|void
     * @throws Exception
     * @since   Kunena 6.0
     */
    public function onKunenaGetActivity()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('activity', 1)) {
            return;
        }

        return new KunenaActivityCommunity($this->params);
    }
}
