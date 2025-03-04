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
<?php new FormField(name: "elements[$id][cc]", type:"select", label: "CC List", multiple:true, code:" data-additions='1' data-separators=','"); ?>
<?php new FormField(name: "elements[$id][bcc]", type:"select", label: "BCC List", multiple:true, code:" data-additions='1' data-separators=','"); ?>