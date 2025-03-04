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
if(!empty($action["element_name"])){
	foreach($elements_by_parent as $page_id => $elements){
		foreach($elements as $element){
			if(CF8::getname($element) == $action["element_name"]){
				require(dirname(dirname(dirname(__DIR__))) . "/display_element.php");
			}
		}
	}
}