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

namespace Kunena\Forum\Site\Controller\User\Ban\History;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\User\KunenaBan;
use Kunena\Forum\Libraries\User\KunenaUser;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class ComponentUserControllerBanHistoryDisplay
 *
 * @since   Kunena 4.0
 */
class UserBanHistoryDisplay extends KunenaControllerDisplay
{
    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $me;

    /**
     * @var     KunenaUser
     * @since   Kunena 6.0
     */
    public $profile;

    /**
     * @var     array|KunenaBan[]
     * @since   Kunena 6.0
     */
    public $banHistory;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    public $headerText;

    /**
     * @var     string
     * @since   Kunena 6.0
     */
    protected $name = 'User/Ban/History';

    /**
     * Prepare ban history.
     *
     * @return  void
     *
     * @throws  null
     * @since   Kunena 6.0
     */
    protected function before()
    {
        parent::before();

        $userid = $this->input->getInt('userid');

        $this->me      = KunenaUserHelper::getMyself();
        $this->profile = KunenaUserHelper::get($userid);
        $this->profile->tryAuthorise('ban');

        $this->banHistory = KunenaBan::getUserHistory($this->profile->userid);

        $this->headerText = Text::sprintf('COM_KUNENA_BAN_BANHISTORYFOR', $this->profile->getName());
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
