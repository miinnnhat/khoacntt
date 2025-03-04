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
<?php new FormField(name: "elements[$id][key]", label: "Key name", value: "n", hint: "The default key to be replaced in fields names placed inside the repeater, your fields names should be like this: text_field[n]"); ?>
<div class="equal fields">
	<?php new FormField(name: "elements[$id][remove_text]", label: "Remove Text", value: "Remove", hint:"Text for the remove button"); ?>
	<?php new FormField(name: "elements[$id][add_text]", label: "Add Text", value: "Add Item", hint:"Text for the add button"); ?>
</div>
<?php
$behaviors = [];
$listBehaviors($id, $behaviors);
?>