<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Conveythis
 *
 * @copyright   Copyright (C) 2018 www.conveythis.com, All rights reserved.
 * @license     ConveyThis Translate is licensed under GPLv2 license.
 */
defined( '_JEXEC' ) or die;

class PlgSystemConveythisInstallerScript
{
	public function install($parent)
	{
		$db  = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update('#__extensions');
		$query->set($db->quoteName('enabled') . ' = 1');
		$query->where($db->quoteName('element') . ' = ' . $db->quote('conveythis'));
		$query->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
		$db->setQuery($query);
		$db->execute();
	}
}
