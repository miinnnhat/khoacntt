<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Administrator
 * @subpackage      Controllers
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Controller;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;

/**
 * Component Controller
 *
 * @since   Kunena 6.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     *
     * @since  Kunena 6.0
     */
    protected $default_view = 'cpanel';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see
     *                               {@link \JFilterInput::clean()}.
     *
     * @return  BaseController
     *
     * @throws  Exception
     * @since   Kunena 6.0
     */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $document = $this->app->getDocument();
        $view = Factory::getApplication()->input->getCmd('view', '');

        if ($view !== 'ranks') {
            $wa = $document->getWebAssetManager();
            $wa->registerStyle('kunena_theme', Uri::base() . '/components/com_kunena/media/css/theme.min.css', [], [], []);
            $wa->useStyle('kunena_theme');
        }

        return parent::display($cachable, $urlparams);
    }
}
