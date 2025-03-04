<?php

/**
 * Kunena Component
 *
 * @package         Kunena.Administrator.Template
 * @subpackage      Layouts.Pagination
 *
 * @copyright       Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

defined('_JEXEC') or die();

use Kunena\Forum\Libraries\Layout\KunenaLayout;

?>

<?php echo KunenaLayout::factory('pagination/list')->set('pagination', $this->pagination); ?>
<input type="hidden" name="<?php echo $this->pagination->prefix ?>limitstart"
       value="<?php echo $this->pagination->limitstart; ?>"/>
