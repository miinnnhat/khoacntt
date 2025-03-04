<?php
/**
* ChronoForms 8
* Copyright (c) 2023 ChronoEngine.com, All rights reserved.
* Author: (ChronoEngine.com Team)
* license:     GNU General Public License version 2 or later; see LICENSE.txt
* Visit http://www.ChronoEngine.com for regular updates and information.
**/
defined('_JEXEC') or die('Restricted access');
?>
<?php 
$name = $element["fieldname"];
$data = Chrono::getVal($this->data, $name);

if(!empty($element["listeners"])){
	foreach($element["listeners"] as $k => $listener){
		if(!empty($listener["actions"]) && in_array("disable_validation", $listener["actions"])){
			return;
		}
	}
}

if ($element["name"] == "field_file") {
	$file = Chrono::getVal($_FILES, $name);
	if (is_null($file)) {
		$this->errors[$name] = $element["validation_required"]["prompt"];
	}
} else {
	if (
		is_null($data) ||
		(is_string($data) && strlen($data) == 0) ||
		(is_array($data) && count($data) == 0)
	) {
		$this->errors[$name] = $element["validation_required"]["prompt"];
	}
}