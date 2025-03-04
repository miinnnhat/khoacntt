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
	$id = "repeater-".$element["id"];
	$key = !empty($element["key"]) ? $element["key"] : "n";

?>
<?php foreach([$key => ""] + (!empty($element["params"]["locales"]) ? $element["params"]["locales"]["lang"] : []) as $k => $lang): ?>
	<div class="nui form clonable <?php echo $id; ?>" data-selector=".clonable.<?php echo $id; ?>" data-cloner=".<?php echo $id; ?>-cloner" data-key="<?php echo $k; ?>">
		<?php $DisplayElements($elements_by_parent, $element["id"], "views"); ?>
		<button type="button" class="nui button red rounded iconed block remove-clone"><?php echo Chrono::ShowIcon("xmark"); ?><?php echo (!empty($element["remove_text"]) ? $element["remove_text"] : "Remove"); ?></button>
		<!-- <div class="nui divider block"></div> -->
	</div>
<?php endforeach; ?>
<button type="button" class="nui button blue rounded iconed <?php echo $id; ?>-cloner"><?php echo Chrono::ShowIcon("plus"); ?><?php echo (!empty($element["add_text"]) ? $element["add_text"] : "Add"); ?></button>