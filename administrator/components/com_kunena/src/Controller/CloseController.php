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

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Kunena Cpanel Controller
 *
 * @since   Kunena 2.0
 * 
 * @deprecated Kunena 6.3 will be removed in Kunena 7.0 without replacement
 */
class CloseController extends FormController
{
    /**
     * @var     null|string
     * @since   Kunena 2.0.0-BETA2
     */
    protected $baseurl = null;

    /**
     * Construct
     *
     * @param   array  $config  config
     *
     * @throws  Exception
     * @since   Kunena 2.0.0-BETA2
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->baseurl = 'index.php?option=com_kunena';
    }
}
