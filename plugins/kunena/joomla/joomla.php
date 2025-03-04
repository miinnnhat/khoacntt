<?php

/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Joomla
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;
use Kunena\Forum\Plugin\Kunena\Joomla\KunenaAccessJoomla;
use Kunena\Forum\Plugin\Kunena\Joomla\KunenaLoginJoomla;

/**
 * Class plgKunenaJoomla
 *
 * @since   Kunena 6.0
 */
class PlgKunenaJoomla extends CMSPlugin
{
    /**
     * plgKunenaJoomla constructor.
     *
     * @param   DispatcherInterface   &$subject  The object to observe
     * @param   array                 $config    An optional associative array of configuration settings.
     *                                           Recognized key values include 'name', 'group', 'params', 'language'
     *                                           (this list is not meant to be comprehensive).
     *
     * @since   Kunena 6.0
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->loadLanguage('plg_kunena_joomla.sys');
    }

    /**
     * @return  false|KunenaAccessJoomla
     *
     * @since   Kunena 6.0
     */
    public function onKunenaGetAccessControl()
    {
        if (!$this->params->get('access', 1)) {
            return false;
        }

        return new KunenaAccessJoomla($this->params);
    }

    /**
     * @return  KunenaLoginJoomla
     *
     * @since   Kunena 6.0
     */
    public function onKunenaGetLogin()
    {
        return new KunenaLoginJoomla($this->params);
    }
}
