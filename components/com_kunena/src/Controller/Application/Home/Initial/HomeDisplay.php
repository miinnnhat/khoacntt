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

namespace Kunena\Forum\Site\Controller\Application\Home\Initial;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\BaseLayout;
use Joomla\CMS\Menu\AbstractMenu;
use Kunena\Forum\Libraries\Controller\KunenaControllerApplication;
use Kunena\Forum\Libraries\Controller\KunenaControllerDisplay;
use Kunena\Forum\Libraries\Error\KunenaError;
use Kunena\Forum\Libraries\Exception\KunenaExceptionAuthorise;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Layout\KunenaLayout;
use Kunena\Forum\Libraries\Route\KunenaRoute;

/**
 * Class ComponentKunenaControllerApplicationHomeDefaultDisplay
 *
 * @since   Kunena 4.0
 */
class HomeDisplay extends KunenaControllerDisplay
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
        return KunenaFactory::getTemplate()->isHmvc();
    }

    /**
     * Redirect to home page.
     *
     * @return  BaseLayout|KunenaLayout
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     * @throws  null
     */
    public function execute()
    {
        $menu = $this->app->getMenu();
        $home = $menu->getActive();

        if (!$home) {
            $this->input->set('view', 'category');
            $this->input->set('layout', 'list');
        } else {
            // Find default menu item.
            $default = $this->getDefaultMenuItem($menu, $home);

            if (!$default || $default->id == $home->id) {
                // There is no default menu item, use category view instead.
                $default = $menu->getItem(KunenaRoute::getItemID('index.php?option=com_kunena&view=category&layout=list'));

                if ($default) {
                    $default = clone $default;
                    $defhome = KunenaRoute::getHome($default);

                    if (!$defhome || $defhome->id != $home->id) {
                        $default = clone $home;
                    }

                    $default->query['view']   = 'category';
                    $default->query['layout'] = 'list';
                }
            }

            if (!$default) {
                throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 500);
            }

            // Add query variables from shown menu item.
            foreach ($default->query as $var => $value) {
                $this->input->set($var, $value);
            }

            // Remove query variables coming from the home menu item.
            $this->input->set('defaultmenu', null);

            // Set active menu item to point the real page.
            $this->input->set('Itemid', $default->id);
            $menu->setActive($default->id);
        }

        // Reset our router.
        KunenaRoute::initialize();

        // Get HMVC controller for the current page.
        $controller = KunenaControllerApplication::getInstance(
            $this->input->getCmd('view'),
            $this->input->getCmd('layout', 'default'),
            $this->input->getCmd('task', 'display'),
            $this->input,
            $this->app
        );

        if (!$controller) {
            throw new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 404);
        }

        return $controller->execute();
    }

    /**
     * Get default menu item to be shown up.
     *
     * @param   AbstractMenu  $menu     Joomla menu.
     * @param   object        $active   Active menu item.
     * @param   array         $visited  Already visited menu items.
     *
     * @return  object|boolean
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    protected function getDefaultMenuItem(AbstractMenu $menu, $active, $visited = [])
    {
        KunenaFactory::loadLanguage('com_kunena.controllers');

        if (empty($active->query['defaultmenu']) || $active->id == $active->query['defaultmenu']) {
            // There is no highlighted menu item!
            return false;
        }

        $item = $menu->getItem($active->query['defaultmenu']);

        if (!$item) {
            // Menu item points to nowhere, abort!
            KunenaError::warning(Text::sprintf('COM_KUNENA_WARNING_MENU_NOT_EXISTS'), 'menu');

            return false;
        }

        if (isset($visited[$item->id])) {
            // Menu loop detected, abort!
            KunenaError::warning(Text::sprintf('COM_KUNENA_WARNING_MENU_LOOP'), 'menu');

            return false;
        }

        if (empty($item->component) || $item->component != 'com_kunena' || !isset($item->query['view'])) {
            // Menu item doesn't point to Kunena, abort!
            KunenaError::warning(Text::sprintf('COM_KUNENA_WARNING_MENU_NOT_KUNENA'), 'menu');

            return false;
        }

        if ($item->query['view'] == 'home') {
            // Menu item is pointing to another Home Page, try to find default menu item from there.
            $visited[$item->id] = 1;
            $item               = $this->getDefaultMenuItem($menu, $item->query['defaultmenu'], $visited);
        }

        return $item;
    }
}
