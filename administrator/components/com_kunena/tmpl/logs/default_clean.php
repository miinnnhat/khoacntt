<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Administrator.Template
 * @subpackage      Logs
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

?>
<div class="p-3">
    <div class="row">
        <fieldset>
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="cleandays" class="col-form-label"><?php echo Text::_('COM_KUNENA_LOG_CLEAN_FROM') ?></label>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control" id="cleandays" name="clean_days" value="30">
                </div>
                <div class="col-auto">
                    <span class="input-group-text"><?php echo Text::_('COM_KUNENA_LOG_CLEAN_FROM_DAYS') ?></span>
                </div>
                <div class="form-text"><?php echo Text::_('COM_KUNENA_LOG_CLEAN_DESC'); ?></div>
            </div>
        </fieldset>
    </div>
</div>
<div class="btn-toolbar p-3">
    <button type="button" class="btn btn-danger ms-auto" data-bs-dismiss="modal">
        <?php echo Text::_('JCANCEL'); ?>
    </button>
    <button type="submit" id='batch-submit-button-id' class="btn btn-success" onclick="Joomla.submitbutton('logs.clean');return false;">
        <?php echo Text::_('JSUBMIT'); ?>
    </button>
</div>