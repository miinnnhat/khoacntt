<?php

/**
 * @package         Convert Forms
 * @version         4.4.8 Free
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

extract($displayData);
?>
<textarea name="<?php echo $field->input_name ?>" id="<?php echo $field->input_id; ?>"
	
	<?php if (isset($field->hidelabel) && !empty($field->label)) { ?>
		aria-label="<?php echo htmlspecialchars($field->label, ENT_COMPAT, 'UTF-8'); ?>"
	<?php } ?>

	<?php if (isset($field->required) && $field->required) { ?>
		required
		aria-required="true"
	<?php } ?>

	<?php if (isset($field->placeholder)) { ?>
		placeholder="<?php echo htmlspecialchars($field->placeholder, ENT_COMPAT, 'UTF-8'); ?>"
	<?php } ?>

	<?php if (isset($field->readonly) && $field->readonly == '1') { ?>
		readonly
	<?php } ?>

	<?php if (isset($field->minchars) && $field->minchars > 0) { ?>
		minlength="<?php echo $field->minchars; ?>"
	<?php } ?>

	<?php if (isset($field->maxchars) && $field->maxchars > 0) { ?>
		maxlength="<?php echo $field->maxchars; ?>"
	<?php } ?>

	class="<?php echo $field->class ?>"
	rows="<?php echo $field->textareaheight ?>"><?php 
	if (isset($field->value))
	{ 
		echo htmlspecialchars($field->value, ENT_COMPAT, 'UTF-8');
	}
?></textarea>