<?php

/**
 * Wonderchat by <https://idealextensions.com>
 *
 * @copyright	Copyright (C) 2006 - 2024 IdealExtensions.com. All rights reserved.
 * @license     GNU GPL2 or later; see LICENSE.txt
 */

namespace IdealExtensions\Plugin\System\Wonderchat\Field;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;

/**
 * Form Field class for the Template List
 */
class ComponentlistField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'ComponentlistField';

	
	/**
	 * Method to get the field options for radio buttons.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions()
	{
		$options = array();

		$folders = Folder::folders(JPATH_ROOT . '/components', '.', false, false, explode('|', $this->exclude));
		

		// Build the options list from the list of folders.
		if (is_array($folders))
		{
			$lang = Factory::getApplication()->getLanguage();

			foreach ($folders as $folder)
			{
				$lang->load($folder);
				$lang->load($folder . '.sys');
				$lang->load($folder, JPATH_ADMINISTRATOR);
				$lang->load($folder . '.sys', JPATH_ADMINISTRATOR);
				$options[] = HTMLHelper::_('select.option', $folder, Text::_($folder));
			}
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);
		
		return $options;
	}

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->filter  = (string) $this->element['filter'];
			$this->exclude = (string) $this->element['exclude'];

			$hideNone       = (string) $this->element['hide_none'];
			$this->hideNone = ($hideNone == 'true' || $hideNone == 'hideNone' || $hideNone == '1');

			$hideDefault       = (string) $this->element['hide_default'];
			$this->hideDefault = ($hideDefault == 'true' || $hideDefault == 'hideDefault' || $hideDefault == '1');

			// Get the path in which to search for file options.
			$this->directory = (string) $this->element['directory'];
		}

		return $return;
	}
}
