<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use IdealExtensions\Plugin\System\Wonderchat\Extension\Wonderchat;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class() implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $app        = Factory::getApplication();
                $plugin     = new Wonderchat(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('system', 'wonderchat'),
                    $app->isClient('site')
                );
                $plugin->setApplication($app);

                return $plugin;
            }
        );
    }
};
