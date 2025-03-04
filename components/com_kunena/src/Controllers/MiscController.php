<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Controllers
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Controllers;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Uri\Uri;
use Kunena\Forum\Libraries\Controller\KunenaController;
use Kunena\Forum\Libraries\Path\KunenaPath;
use Kunena\Forum\Libraries\Route\KunenaRoute;

/**
 * Kunena Misc Controller
 *
 * @since   Kunena 2.0
 */
class MiscController extends KunenaController
{
    /**
     * @param   array  $config  config
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return  void
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     * @throws  null
     */
    public function template()
    {
        $name = $this->input->getString(
            'name',
            $this->input->cookie->getString('kunena_template', '')
        );

        if ($name) {
            $name = KunenaPath::clean($name);

            if (!is_readable(KPATH_SITE . "/template/{$name}/config/template.xml")) {
                $name = 'aurelia';
            }

            setcookie('kunena_template', $name, 0, Uri::root(true) . '/', '', true);
        } else {
            setcookie('kunena_template', null, time() - 3600, Uri::root(true) . '/', '', true);
        }

        $this->setRedirect(KunenaRoute::_('index.php?option=com_kunena', false));
    }
}
