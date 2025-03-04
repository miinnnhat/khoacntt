<?php

/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Easysocial
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @copyright       Copyright (C) 2010 - 2016 Stack Ideas Sdn Bhd. All rights reserved.
 * @license         GNU/GPL, see LICENSE.php
 * EasySocial is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die('Unauthorized Access');

use Kunena\Forum\Libraries\Forum\KunenaForum;
use Kunena\Forum\Plugin\Kunena\Easysocial\KunenaAvatarEasySocial;
use Kunena\Forum\Plugin\Kunena\Easysocial\KunenaProfileEasySocial;
use Kunena\Forum\Plugin\Kunena\Easysocial\KunenaLoginEasySocial;
use Kunena\Forum\Plugin\Kunena\Easysocial\KunenaPrivateEasySocial;
use Kunena\Forum\Plugin\Kunena\Easysocial\KunenaActivityEasySocial;

$file = JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/plugins.php';

if (!is_file($file)) {
    return;
}

require_once $file;

/**
 * @package     Kunena
 *
 * @since       Kunena 5.0
 */
class PlgKunenaEasySocial extends EasySocialPlugins
{
    public $params;

    /**
     * plgKunenaEasySocial constructor.
     *
     * @param   object  $subject                The object to observe
     * @param   array   $config                 An optional associative array of configuration settings.
     *                                          Recognized key values include 'name', 'group', 'params', 'language'
     *                                          (this list is not meant to be comprehensive).
     *
     * @throws Exception
     * @since   Kunena 5.0
     */
    public function __construct(object $subject, $config = [])
    {
        // Do not load if Kunena version is not supported or Kunena is offline
        if (!(class_exists('Kunena\Forum\Libraries\Forum\KunenaForum') && KunenaForum::isCompatible('6.3') && KunenaForum::enabled())) {
            return true;
        }

        parent::__construct($subject, $config);

        $this->loadLanguage('plg_kunena_easysocial.sys', JPATH_ADMINISTRATOR) || $this->loadLanguage('plg_kunena_easysocial.sys', JPATH_ADMINISTRATOR . '/components/com_kunena');

        return true;
    }

    /**
     * Get Kunena login integration object.
     *
     * @return  KunenaLoginEasySocial
     *
     * @since   Kunena 5.0
     */
    public function onKunenaGetLogin()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('login', 1)) {
            return;
        }

        return new KunenaLoginEasySocial($this->params);
    }

    /**
     * Get Kunena avatar integration object.
     *
     * @return  KunenaAvatarEasySocial|void
     * @since   Kunena 5.0
     */
    public function onKunenaGetAvatar()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('avatar', 1)) {
            return;
        }

        return new KunenaAvatarEasySocial($this->params);
    }

    /**
     * Get Kunena profile integration object.
     *
     * @return  KunenaProfileEasySocial
     *
     * @since   Kunena 5.0
     */
    public function onKunenaGetProfile()
    {
        if (!isset($this->params)) {
            return;
        }

        if (!$this->params->get('profile', 1)) {
            return;
        }

        return new KunenaProfileEasySocial($this->params);
    }

    /**
     * Get Kunena private message integration object.
     *
     * @return  KunenaPrivateEasySocial
     *
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

        return new KunenaPrivateEasySocial($this->params);
    }

    /**
     * Get Kunena activity stream integration object.
     *
     * @return  KunenaActivityEasySocial
     *
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

        return new KunenaActivityEasySocial($this->params);
    }
}
