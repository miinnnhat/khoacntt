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
 * Kunena Backend Email Controller
 *
 * @since   Kunena 5.1
 * 
 * @deprecated Kunena 6.3 will be removed in Kunena 7.0 without replacement
 */
class EmailController extends FormController
{
    /**
     * @var     string
     * @since   Kunena 5.1
     */
    protected $baseurl = null;

    /**
     * Construct
     *
     * @param   array  $config  config
     *
     * @throws  Exception
     * @since   Kunena 5.1
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->baseurl = 'administrator/index.php?option=com_kunena&view=email';
    }
}
