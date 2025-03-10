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

use \ConvertForms\Helper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

class Recaptcha extends \ConvertForms\Field
{
	/**
	 *  Exclude all common fields
	 *
	 *  @var  mixed
	 */
	protected $excludeFields = array(
		'name',
		'required',
		'size',
		'value',
		'placeholder',
		'browserautocomplete',
		'inputcssclass'
	);

	/**
	 *  Set field object
	 *
	 *  @param  mixed  $field  Object or Array Field options
	 */
	public function setField($field)
	{
		parent::setField($field);

		$this->field->required = true;

		return $this;
	}

	/**
	 *  Get the reCAPTCHA Site Key used in Javascript code
	 *
	 *  @return  string
	 */
	public function getSiteKey()
	{
		return Helper::getComponentParams()->get('recaptcha_sitekey');
	}

	/**
	 *  Get the reCAPTCHA Secret Key used in communication between the website and the reCAPTCHA server
	 *
	 *  @return  string
	 */
	public function getSecretKey()
	{
		return Helper::getComponentParams()->get('recaptcha_secretkey');
	}

	/**
	 *  Validate field value
	 *
	 *  @param   mixed  $value           The field's value to validate
	 *
	 *  @return  mixed                   True on success, throws an exception on error
	 */
	public function validate(&$value)
	{
		if (!$this->field->get('required'))
		{
			return true;
		}

		// In case this is a submission via URL, skip the check.
		if (Factory::getApplication()->input->get('task') == 'optin')
		{
			return true;
		}

        jimport('recaptcha', JPATH_PLUGINS . '/system/nrframework/helpers/wrappers');

        $recaptcha = new \NR_ReCaptcha(
            ['secret' => $this->getSecretKey()]
        );

		$response = isset($this->data['g-recaptcha-response']) ? $this->data['g-recaptcha-response'] : null;

        $recaptcha->validate($response);

        if (!$recaptcha->success())
        {
            throw new \Exception($recaptcha->getLastError());
        }
	}

	/**
	 *  Display a text before the form options
	 *
	 * 	@param   object  $form
	 *
	 *  @return  string  The text to display
	 */
	protected function getOptionsFormHeader($form)
	{
		// Mention that this field will be deprecated in favor of the new reCAPTCHA field
		$deprecation_text = '<div class="alert alert-warning">' . Text::_('COM_CONVERTFORMS_RECAPTCHA_FIELD_DEPRECATION') . '</div>';
		
		if ($this->getSiteKey() && $this->getSecretKey())
		{
			return $deprecation_text;
		}

		$url = Uri::base() . 'index.php?option=com_config&view=component&component=com_convertforms#recaptcha';

		return
			$deprecation_text .
			'<div style="margin-top: 10px;">' .
			Text::_('COM_CONVERTFORMS_FIELD_RECAPTCHA_KEYS_NOTE') . 
			' <a onclick=\'window.open("' . $url . '", "cfrecaptcha", "width=1000, height=750");\' href="#">' 
				. Text::_("COM_CONVERTFORMS_FIELD_RECAPTCHA_CONFIGURE") . 
			'</a>.</div>';
	}
}