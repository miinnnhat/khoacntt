<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace NRFramework\Widgets;

defined('_JEXEC') or die;

/**
 *  The Range Slider widget
 */
class RangeSlider extends Widget
{
	/**
	 * Widget default options
	 *
	 * @var array
	 */
	protected $widget_options = [
		// The default value of the widget. 
		'value' => 0,

		// The minimum value of the slider
		'min' => 0,

		// The maximum value of the slider
		'max' => 100,

		// The step of the slider
		'step' => 1,

		// The main slider color
		'color' => '#1976d2',

		// The input border color of the slider inputs
		'input_border_color' => '#bdbdbd',

		// The input background color of the slider inputs
		'input_bg_color' => 'transparent'
	];

	/**
	 * Class constructor
	 *
	 * @param array $options
	 */
	public function __construct($options = [])
	{
		parent::__construct($options);

		// Base color is 20% of given color
		$this->options['base_color'] = $this->options['color'] . '33';

		// Calculate value
		$this->options['value'] = (float) $this->options['value'] < $this->options['min'] ? $this->options['min'] : ((float) $this->options['value'] > $this->options['max'] ? $this->options['max'] : (float) $this->options['value']);

		// Calculate bar percentage
		$this->options['bar_percentage'] = $this->options['max'] ? floor(100 * ($this->options['value'] - $this->options['min']) / ($this->options['max'] - $this->options['min'])) : $this->options['value'];
	}
}