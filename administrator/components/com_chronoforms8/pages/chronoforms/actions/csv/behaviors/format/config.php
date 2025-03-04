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
	<?php new FormField(name: "elements[$id][delimiter]", label: "Delimiter", value: ",", hint: ""); ?>
	<?php new FormField(name: "elements[$id][enclosure]", label: "Enclosure", value: '"', hint: ""); ?>
	<?php new FormField(name: "elements[$id][escape_char]", label: "Escape Character", value: "\\", hint: ""); ?>
</div>