<?php
/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

require_once dirname(__DIR__) . '/helpers/field.php';

class JFormFieldNR_Rate extends NRFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	public $type = 'nr_rate';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	public function getInput()
	{
		// Setup properties
		$starwidth = $this->get('starwidth', '25px');
		$numstarts = $this->get('numstars', 5);
		$maxvalue  = $this->get('maxvalue', 5);
		$halfstar  = $this->get('halfstar', 0) ? "true" : "false";
		$spacing   = $this->get('spacing', "3px");
		$ratedfill = $this->get('ratedfill', "#e7711b");
		$this->value     = empty($this->value) ? 0 : $this->value;
		
		static $run;
		if (!$run)
		{
			// Add styles and scripts to DOM
			HTMLHelper::_('jquery.framework');
			HTMLHelper::script('plg_system_nrframework/vendor/jquery.rateyo.min.js', ['relative' => true, 'version' => true]);
			HTMLHelper::stylesheet('plg_system_nrframework/vendor/jquery.rateyo.min.css', ['relative' => true, 'version' => true]);

			$this->doc->addStyleDeclaration('
				.nr_rate {
				    display: flex;
				    align-items: center;
				}
				.nr_rate_preview {
				    background-color: #393939;
				    color: #fff;
				    padding: 7px;
				    font-size: 12px;
				    line-height: 1;
				    min-width: 20px;
				    text-align: center;
				    border-radius: 2px;
				    position:relative;
				    top:2px;
				}
				.nr_rate .jq-ry-container {
				    padding: 0 10px 0 0;
				}
				.nr_rate svg {
				    max-width: unset;
				}
			');

			$run = true;
		}

		$this->doc->addScriptDeclaration('
			jQuery(function($) {
				$("#nr_rate_'.$this->id.'").rateYo({
					rating:    ' . $this->value . ',
					starWidth: "'. $starwidth .'",
					numStars:  ' . $numstarts . ',
					maxValue:  ' . $maxvalue . ',
					halfStar:  ' . $halfstar . ',
					spacing:   "' . $spacing . '",
					ratedFill: "' . $ratedfill . '",
					onInit: function (rating) {
						$(this).parent().find(".nr_rate_preview").html(rating);
					},
					onSet: function(rating) {
						$(this).next("input").val(rating);
					},
					onChange: function(rating) {
						$(this).parent().find(".nr_rate_preview").html(rating);
					}
				});
			});
    	');

		$html[] = '<div class="nr_rate">';
		$html[] = '<div id="nr_rate_'.$this->id.'"></div>';
		$html[] = '<input value="' . $this->value . '" name="' . $this->name . '" type="hidden"/>';
		$html[] = '<span class="nr_rate_preview"></span>';
		$html[] = '</div>';

		return implode(" ", $html);
	}
}