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

namespace ConvertForms\Field;

defined('_JEXEC') or die('Restricted access');

use ConvertForms\Validate;
use Joomla\CMS\Language\Text;

class Email extends \ConvertForms\Field
{
    protected $inheritInputLayout = 'text';

	/**
	 *  Validate field value
	 *
	 *  @param   mixed  $value           The field's value to validate
	 *
	 *  @return  mixed                   True on success, throws an exception on error
	 */
	public function validate(&$value)
	{
		parent::validate($value);

		if ($this->isEmpty($value))
		{
			return true;
		}

		if (!Validate::email($value) || $this->field->get('dnscheck') && !Validate::emaildns($value))
		{
			$this->throwError(Text::sprintf('COM_CONVERTFORMS_FIELD_EMAIL_INVALID'));
		}
	}

	/**
	 * Prepare value to be displayed to the user as HTML/text
	 *
	 * @param  mixed $value
	 *
	 * @return string
	 */
	public function prepareValueHTML($value)
	{
		if (!$value)
		{
			return;
		}

		return '<a target="_blank" href="mailto:' . $value . '">' . $value . '</a>';
	}
}