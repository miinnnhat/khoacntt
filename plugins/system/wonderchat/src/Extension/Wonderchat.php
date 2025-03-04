<?php

/**
 * Contact Enhanced Component by <https://idealextensions.com>
 *
 * @copyright	Copyright (C) 2006 - 2024 IdealExtensions.com. All rights reserved.
 * @license     GNU GPL2 or later; see LICENSE.txt
 */

namespace IdealExtensions\Plugin\System\Wonderchat\Extension;

use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Shortcut plugin to add accessible keyboard shortcuts to the administrator templates.
 *
 * @since  4.2.0
 */
final class Wonderchat extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  4.2.0
     */
    protected $autoloadLanguage = true;

    /**
     * An internal flag whether plugin should listen any event.
     *
     * @var bool
     *
     * @since   4.3.0
     */
    protected static $enabled = false;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $subject  The object to observe
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   boolean              $enabled  An internal flag whether plugin should listen any event.
     *
     * @since   4.3.0
     */
    public function __construct($subject, array $config = [], bool $enabled = false)
    {
        $this->autoloadLanguage = $enabled;
        self::$enabled          = $enabled;

        parent::__construct($subject, $config);
    }

    /**
     * On after render
     * @return void
     */
    public function onAfterRender()
    {
        $app = $this->getApplication();

        if (!$this->checkLoadPermissions() || !$this->params->get('widget_id')) {
            return;
        }

        // Add link before the </body> tag
        $body = $app->getBody();
        $script = '<script
    src="https://app.wonderchat.io/scripts/wonderchat.js"
    data-name="wonderchat"
    data-address="app.wonderchat.io"
    data-id="' . trim(trim($this->params->get('widget_id'),'"')) . '"
    data-widget-size="' . $this->params->get('widget_size', 'normal') . '"
    data-widget-button-size="' . $this->params->get('widget_btn_size', 'normal') . '"
    data-widget-offset-bottom="' . $this->params->get('widget_offset_bottom', 0) . 'px"
    data-widget-offset-right="' . $this->params->get('widget_offset_right', 0) . 'px"
    defer
  ></script>';
        $body = str_replace('</body>', $script . '</body>', $body);
        $app->setBody($body);
    }

    /**
     * Can we load the plugin?
     * @return boolean
     */
    private function checkLoadPermissions()
    {
        $app = $this->getApplication();

        if (!self::$enabled)
        {
            return false;
        }

        if (!$this->params->get('loadonmobile', 1)) {
            if (Browser::getInstance()->isMobile()) {
                return false;
            }
        }

        // Check if menu items have been excluded.
        $excludedMenuItems = $this->params->get('exclude_menu_items', []);

        if ($excludedMenuItems) {
            // Get the current menu item.
            $active = $app->getMenu()->getActive();

            if ($active && $active->id && in_array((int) $active->id, (array) $excludedMenuItems)) {
                return false;
            }
        }

        $input = $app->input;

        // Check if menu items have been excluded.
        $excludedComponents = $this->params->get('exclude_components', '');

        if ($excludedComponents && in_array($input->getCmd('option'), $excludedComponents))
        {
            return false;
        }

        return true;
    }
}
