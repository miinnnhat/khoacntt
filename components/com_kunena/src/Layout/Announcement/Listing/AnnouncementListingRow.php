<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Site
 * @subpackage      Layout.Announcement.List
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Layout\Announcement\Listing;

\defined('_JEXEC') or die;

use Exception;
use Kunena\Forum\Libraries\Forum\Announcement\KunenaAnnouncement;
use Kunena\Forum\Libraries\Layout\KunenaLayout;

/**
 * KunenaLayoutAnnouncementListRow
 *
 * @since   Kunena 4.0
 */
class AnnouncementListingRow extends KunenaLayout
{
    /**
     * @var     KunenaAnnouncement
     * @since   Kunena 6.0
     */
    public $announcement;

    public $row;

    public $checkbox;

    public $config;

    /**
     * Method to check if the user can publish an announcement
     *
     * @return  boolean
     *
     * @since   Kunena 6.0
     */
    public function canPublish()
    {
        return $this->announcement->isAuthorised('edit');
    }

    /**
     * Method to check if the user can edit an announcement
     *
     * @return  boolean
     *
     * @since   Kunena 6.0
     */
    public function canEdit()
    {
        return $this->announcement->isAuthorised('edit');
    }

    /**
     * Method to check if the user can delete an announcement
     *
     * @return  boolean
     *
     * @since   Kunena 6.0
     */
    public function canDelete()
    {
        return $this->announcement->isAuthorised('delete');
    }

    /**
     * Method to display an announcement field
     *
     * @param   string  $name  The name of the field
     * @param   string  $mode  Define the way to display the date on the field
     *
     * @return  integer|string
     *
     * @since   Kunena 6.0
     *
     * @throws  Exception
     */
    public function displayField($name, $mode = null)
    {
        return $this->announcement->displayField($name, $mode);
    }
}
