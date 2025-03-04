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
    <?php new FormField(name: "elements[$id][list_search][read_data]", type:"text", label: "Read Data name", hint:"The name of the Read Data action to be used for searching"); ?>
    <?php new FormField(name: "elements[$id][list_search][columns]", type:"select", label: "Search Columns", multiple: true, code: "data-additions='1' data-separators=','", hint:"Comma separated list of table columns to be searched"); ?>
</div>