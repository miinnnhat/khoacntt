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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class JFormFieldLeadColumns extends JFormFieldCheckboxes
{
    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of options.
     */
    protected function getOptions()
    {
        $formID = $this->getFormID();

        $form_fields = ConvertForms\Helper::getColumns($formID);

        $optionsForm = [];

        foreach ($form_fields as $key => $field)
        {
            $label = ucfirst(str_replace('param_', '', $field));

            if (strpos($field, 'param_') === false)
            {
                $label = Text::_('COM_CONVERTFORMS_' . $label);
            }

            // Temporary workaround to translate the Submission Notes option
            if ($field == 'param_leadnotes')
            {
                $label = Text::_('COM_CONVERTFORMS_NOTES');
            }

            $optionsForm[] = (object) [
                'value' => $field,
                'text'  => $label
            ];
        }

        return $optionsForm;
    }

    protected function getInput()
    {
		Factory::getDocument()->addScriptDeclaration('
			function cfSubmissionColumnsApply(that) {
                // Reset task in case it was previously set and would trigger the task on submit such as submissions export
                let task = document.querySelector("input[type=\"hidden\"][name=\"task\"]");
                if (task) {
                    task.value = "";
                }
                
                that.form.submit();
            }
		');

        $html = '
            <div class="chooseColumns">
                <a class="btn btn-secondary" data-bs-toggle="collapse" data-toggle="collapse" href="#" data-target=".chooseColumnsOptions" data-bs-target=".chooseColumnsOptions">'
                    . Text::_('COM_CONVERTFORMS_CHOOSE_COLUMNS') . '
                </a>
                <div class="collapse chooseColumnsOptions">
                    <div>
                        ' . parent::getInput() . '
                        <button class="btn btn-sm btn-success" onclick="cfSubmissionColumnsApply(this);">'
                            . Text::_('JAPPLY') . 
                        '</button>
                    </div>
                </div>
            </div>
        ';

        return $html;
    }

    private function getFormID()
    {
        return $this->form->getData()->get('filter.form_id');
    }
}