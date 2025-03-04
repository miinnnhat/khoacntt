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
<?php foreach($field->options as $option): ?>
<div class="field">
    <?php if($option->header): ?>
	<label><strong><?php echo $option->text; ?></strong></label>
	<?php else: ?>
	<div class="nui checkbox">
		<input type="checkbox" name="<?php echo $field->name; ?>" tabindex="0" class="hidden" value="<?php echo $option->value; ?>" <?php if($option->selected): ?>checked="checked"<?php endif; ?> <?php echo $field->code; ?>>
		<label tabindex="0"><?php echo $option->text; ?></label>
	</div>
	<?php endif; ?>
</div>
<?php endforeach; ?>