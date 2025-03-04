<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Template.Aurelia
 * @subpackage      Pages.Topics
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site;

\defined('_JEXEC') or die();

$content = $this->execute('Topic/Listing/Moderator')
    ->setLayout('moderator');

$this->addBreadcrumb(
    $content->headerText,
    'index.php?option=com_kunena&view=topics&layout=moderator'
);

echo $content;
