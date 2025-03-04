<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controller.Application
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controller\Application\Topic\Indented;

\defined('_JEXEC') or die();

use Exception;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Layout\KunenaPage;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class ComponentKunenaControllerApplicationTopicIndentedDisplay
 *
 * @since   Kunena 4.0
 */
class ApplicationTopicIndentedDisplay extends KunenaControllerDisplay
{
    /**
     * Return true if layout exists.
     *
     * @return  boolean
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function exists()
    {
        $page = KunenaPage::factory("{$this->input->getCmd('view')}/default");

        return (bool) $page->getPath();
    }

    /**
     * Change topic layout to indented.
     *
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     * @throws  null
     */
    protected function before()
    {
        $layout = $this->input->getWord('layout');
        KunenaUserHelper::getMyself()->setTopicLayout($layout);

        parent::before();
    }
}
