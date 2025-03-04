<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Template.System
 * @subpackage      BBCode
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Site;

\defined('_JEXEC') or die();

$attachment = $this->attachment;
?>
<div class="kmsgvideo">
    <?php echo $this->subLayout('Attachment/Item')->set('attachment', $attachment); ?>
</div>
