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
<?php
$element["code"] = (!empty($element["code"]) ? $element["code"] : "").' data-editor="1"';
// Chrono::addHeaderTag('<script src="'.$this->root_url.'media/vendor/tinymce/tinymce.min.js?nocache'.'"></script>');
// Chrono::loadAsset("/assets/nui.tinymce.min.js");
Chrono::loadEditor();