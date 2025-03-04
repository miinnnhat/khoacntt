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
<?php new FormField(name: "elements[$id][text]", type:"textarea", label: "Text", value: "", rows:5, hint:"The text to appear in your message box.", editor:0); ?>
<?php
$behaviors = ["icon", "color", "size"];
$listBehaviors($id, $behaviors);
?>