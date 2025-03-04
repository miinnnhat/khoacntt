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
	<?php new FormField(name: "elements[$id][data-startmode]", type:"select", label: "Start Mode", options:[
		new Option(value:"day", text:"Day"),
		new Option(value:"month-year", text:"Month/Year"),
		new Option(value:"time", text:"Time"),
	]); ?>
	<?php new FormField(name: "elements[$id][data-type]", type:"select", label: "Selection Type", options:[
		new Option(value:"datetime", text:"DateTime"),
		new Option(value:"date", text:"Date"),
		new Option(value:"time", text:"Time"),
	]); ?>
</div>