<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Template.Aurelia
 * @subpackage      Pages.Topic
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site;

\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;

$content = $this->execute('Topic/Poll');

// Display breadcrumb path to the current category / topic / message / report.
$parents   = KunenaCategoryHelper::getParents($content->category->id);
$parents[] = $content->category;

foreach ($parents as $parent) {
    $this->addBreadcrumb(
        $parent->displayField('name'),
        $parent->getUri()
    );
}

$this->addBreadcrumb(
    Text::_('COM_KUNENA_MENU_TOPIC'),
    $content->topic->getUri()
);
$this->addBreadcrumb(
    Text::_('COM_KUNENA_POLL_STATS_NAME'),
    $content->uri
);

echo $content;
