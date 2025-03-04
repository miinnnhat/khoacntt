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
<?php new FormField(name: "elements[$id][class]", label: "Class", value: "", hint: "Enter class(es) names to be added to this div container"); ?>
<?php
$behaviors = ["events_listeners", "area_container.subitems", "area_container.grid"];
$listBehaviors($id, $behaviors);
?>