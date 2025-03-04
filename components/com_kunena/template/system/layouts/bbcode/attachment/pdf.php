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
$location   = $attachment->getUrl();

if (!$attachment->isPdf()) {
    return;
}
?>
<div class="clearfix"></div>

<object class="pdf" data="<?php echo $location; ?>" type="application/pdf" width="100%" height="auto"
        style="min-height: 300px;">
    <p>
        This browser does not support PDFs. Please download the PDF to view it: <a href="<?php echo $location; ?>">Download
            PDF</a>
    </p>
</object>
