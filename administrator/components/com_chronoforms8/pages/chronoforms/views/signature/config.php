<?php
/**
* ChronoForms 8
* Copyright (c) 2023 ChronoEngine.com, All rights reserved.
* Author: (ChronoEngine.com Team)
* license:     GNU General Public License version 2 or later; see LICENSE.txt
* Visit http://www.ChronoEngine.com for regular updates and information.
**/
defined('_JEXEC') or die('Restricted access');
?>
<div class="equal fields">
	<?php new FormField(name: "elements[$id][label]", label: "Label", value: "Signature $id"); ?>
	<?php new FormField(name: "elements[$id][fieldname]", label: "Field Name", value: "signature_$id"); ?>
</div>
<?php
$behaviors = [
	"validation_required",
	"signature.attach", "hint", "tooltip", "events_listeners",
];
$listBehaviors($id, $behaviors);
?>