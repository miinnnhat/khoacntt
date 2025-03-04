<?php
/**
* ChronoForms 8
* Copyright (c) 2023 ChronoEngine.com, All rights reserved.
* Author: (ChronoEngine.com Team)
* license:     GNU General Public License version 2 or later; see LICENSE.txt
* Visit http://www.ChronoEngine.com for regular updates and information.
**/
defined('_JEXEC') or die('Restricted access');

class com_chronoforms8InstallerScript {
	function postflight($type, $parent){
		$mainframe = Joomla\CMS\Factory::getApplication();
		$parent->getParent()->setRedirectUrl('index.php?option=com_chronoforms8&action=install');
	}
}
?>