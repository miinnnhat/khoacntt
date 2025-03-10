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

namespace ConvertForms\SmartTags;

defined('_JEXEC') or die('Restricted access');

use NRFramework\SmartTags\SmartTag;

class Total extends SmartTag
{
	/**
	 * Returns the total submissions.
	 * Used in Convert Forms Front End Submissions View.
	 * 
	 * @return  string
	 */
	public function getTotal()
	{
		return isset($this->data['front_end_submission']['total']) ? $this->data['front_end_submission']['total'] : '';
	}
}