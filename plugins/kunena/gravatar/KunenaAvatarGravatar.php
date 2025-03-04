<?php

/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Gravatar
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Plugin\Kunena\Gravatar;

defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Integration\KunenaAvatar;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaUser;
use Pedrollo\GravatarLib\Gravatar;

/**
 * Class KunenaAvatarGravatar
 *
 * @since   Kunena 6.0
 */
class KunenaAvatarGravatar extends KunenaAvatar
{
    /**
     * @var     null
     * @since   Kunena 6.0
     */
    protected $params = null;

    /**
     * KunenaAvatarGravatar constructor.
     *
     * @param   object  $params  params
     *
     * @since   Kunena 6.0
     */
    public function __construct(object $params)
    {
        $this->params = $params;
        require_once __DIR__ . '/gravatar.php';
    }

    /**
     * @return string
     *
     * @since   Kunena 6.0
     */
    public function getEditURL(): string
    {
        return KunenaRoute::_('index.php?option=com_kunena&view=user&layout=edit');
    }

    /**
     * @param   KunenaUser  $user   user
     * @param   int         $sizex  sizex
     * @param   int         $sizey  sizey
     *
     * @return  string
     *
     * @since   Kunena 6.0
     */
    protected function _getURL(KunenaUser $user, int $sizex, int $sizey): string
    {
        $user     = KunenaFactory::getUser($user);
        $gravatar = new Gravatar();
        $gravatar->setAvatarSize(min($sizex, $sizey));
        $gravatar->setDefaultImage($this->params->get("default_image", false));
        $gravatar->setMaxRating('g');

        $uri = Uri::getInstance();

        if ($uri->isSSL()) {
            $gravatar->enableSecureImages();
        } else {
            $gravatar->disableSecureImages();
        }

        return $gravatar->buildGravatarURL($user->email);
    }
}
