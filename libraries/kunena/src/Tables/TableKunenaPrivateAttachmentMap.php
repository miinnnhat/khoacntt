<?php

/**
 * Kunena Component
 *
 * @package       Kunena.Framework
 * @subpackage    Tables
 *
 * @Copyright (C) 2008 - @currentyear@ Kunena Team. All rights reserved.
 * @license       http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          http://www.kunena.org
 **/

namespace Kunena\Forum\Libraries\Tables;

\defined('_JEXEC') or die();

use Joomla\Database\DatabaseDriver;

/**
 * Kunena Private Message map to attachments.
 * Provides access to the #__kunena_private_attachment_map table
 *
 * @since   Kunena 6.0
 */
class TableKunenaPrivateAttachmentMap extends KunenaTable
{
    /**
     * @var     null
     * @since   Kunena 6.0
     */
    public $private_id = null;

    /**
     * @var     null
     * @since   Kunena 6.0
     */
    public $attachment_id = null;

    /**
     * @var     boolean
     * @since   Kunena 6.0
     */
    protected $_autoincrement = false;

    /**
     * TableKunenaPrivateAttachmentMap constructor.
     *
     * @param   DatabaseDriver  $db  database driver
     *
     * @since   Kunena 6.0
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__kunena_private_attachment_map', ['private_id', 'attachment_id'], $db);
    }
}
