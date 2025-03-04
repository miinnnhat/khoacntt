<?php
/**
 * Community Builder Package installer
 * @version $Id: 10/31/13 11:29 PM $
 * @package pkg_communitybuilder
 * @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

defined ( '_JEXEC' ) or die ();

/**
 * Community Builder package installer script.
 */
class pkg_communitybuilderInstallerScript {
	/**
	 * List of supported versions:
	 * Newest version first!
	 * @var array
	 */
	private $versions = array(
		'php' => array (
			'8.3' => '8.3.0',
			'8.2' => '8.2.0',
			'8.1' => '8.1.0',
			'8.0' => '8.0.0',
			'7.4' => '7.4.0',
			'0' => '8.3.10' // Preferred version
		),
		'mysql' => array (
			'8.0' => '8.0',
			'5.7' => '5.7',
			'5.6' => '5.6',
			'0' => '8.0.39' // Preferred version
		),
		'mariaDB' => array (
			'11.5' => '11.5',
			'11.4' => '11.4',
			'11.3' => '11.3',
			'11.2' => '11.2',
			'11.1' => '11.1',
			'11.0' => '11.0',
			'10.11' => '10.11',
			'10.10' => '10.10',
			'10.9' => '10.9',
			'10.8' => '10.8',
			'10.7' => '10.7',
			'10.6' => '10.6',
			'10.5' => '10.5',
			'10.4' => '10.4',
			'10.3' => '10.3',
			'10.2' => '10.2',
			'10.1' => '10.1',
			'10.0' => '10.0',
			'0' => '10.11.9' // Preferred version
		),
		'joomla' => array (
			'6.0.0-alpha1' => '999.9',		// 999.9 = incompatible
			'5.1' => '5.1.0',
			'5.0' => '5.0.0',
			'4.3' => '4.3.0',
			'4.2' => '4.2.0',
			'4.1' => '4.1.0',
			'4.0' => '4.0.0',
			'3.10' => '3.10.0',
			'0' => '5.1.4' // Preferred version
		)
	);

	/**
	 * List of required PHP extensions.
	 * @var array
	 */
	private $phpExtensions = array ( 'json', 'simplexml' );

	public function install( /** @noinspection PhpUnusedParameterInspection */ $parent ) {
		$session = Factory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry))
		{
			echo $registry->get('com_comprofiler_install', null);
			$registry->set('com_comprofiler_install', '');
		}
	}

	public function discover_install( $parent ) {
		$this->install( $parent );
	}

	public function update( $parent ) {
		$this->install( $parent );
	}

	/**
	 * Pre-flight checks
	 * @param  string             $type    Type of install/uninstall
	 * @param  JInstallerPackage  $parent  The parent
	 * @return bool                          true: Can insatll, false: Can't install
	 */
	public function preflight( $type, $parent )
	{
		if ( $type == 'uninstall' )
		{
			return true;
		}

		@set_time_limit( 300 );
		@ini_set( 'memory_limit', '128M' );
		@ini_set( 'post_max_size', '128M' );
		@ini_set( 'upload_max_filesize', '128M' );
		@ini_set( 'error_reporting', 0 );
		@ignore_user_abort( true );

		$installer = $parent->getParent();
		/** @var JInstaller $installer */
		$manifest = $installer->getManifest();

		// Prevent installation if requirements are not met.
		return $this->checkRequirements( $manifest->version );
	}


	public function postflight($type, /** @noinspection PhpUnusedParameterInspection */ $parent) {
		$this->fixUpdateSite();

		// Clear Joomla system cache.
		/** @var JCache|JCacheController $cache */
		$cache = Factory::getCache();
		$cache->clean('_system');

		// Remove all compiled files from APC cache and from PHP 5.5 OpCache:
		if ( function_exists( 'apc_clear_cache' ) )
		{
			@apc_clear_cache();
		}
		if ( function_exists( 'opcache_reset' ) )
		{
			@opcache_reset();
		}

		if ( $type == 'uninstall' )
		{
			return true;
		}

		// nothing more to do here, rest is done in postflight scripts of plugin and modules.
		// $this->enablePlugin('system', 'communitybuilder');

		return true;
	}

	public function enablePlugin($group, $element) {
		$plugin = Table::getInstance('extension');
		if (!$plugin->load(array('type'=>'plugin', 'folder'=>$group, 'element'=>$element))) {
			return false;
		}
		$plugin->enabled = 1;
		return $plugin->store();
	}

	public function checkRequirements( /** @noinspection PhpUnusedParameterInspection */ $version ) {
		$db		=	Factory::getDbo();
		$pass	=	$this->checkVersion('php', phpversion())
				&&	$this->checkVersion('joomla', JVERSION)
				&&	$this->checkDbVersion($db->getVersion())
				&&	$this->checkDbo($db->name, array('mysql', 'mysqli', 'pdomysql'))
				&&	$this->checkPHPExtensions($this->phpExtensions)
				&&	$this->checkGit();
//				&&	$this->checkCBVersion( $version );
		return $pass;
	}

	// Internal functions

	protected function checkDbVersion( $version )
	{
		if ( preg_match( '/(?:.*-)?(\d+\.\d+\.\d+)-MariaDB.*/', $version, $matches ) )
		{
			return $this->checkVersion( 'mariaDB', $matches[1] );
		}

		return $this->checkVersion( 'mysql', $version );
	}

	protected function checkVersion($name, $version)
	{
		$app = Factory::getApplication();

		$major = 0;
		$minor = 0;

		foreach ($this->versions[$name] as $major=>$minor)
		{
			if (!$major || version_compare($version, $major, '<'))
			{
				continue;
			}
			if (version_compare($version, $minor, '>='))
			{
				return true;
			}
			break;
		}

		if (!$major)
		{
			// Get minimum version, which is the second to last array value:
			end($this->versions[$name]);
			$minor = prev($this->versions[$name]);
		}
		$recommended = end($this->versions[$name]);

		if ($minor === '999.9')
		{
			$app->enqueueMessage(sprintf("%s %s is not yet supported by Community Builder in this version. It is recommended to use %s %s or later. Installation is cancelled.", $name, $version, $name, $recommended), 'error');
			return false;
		}

		$app->enqueueMessage(sprintf("%s %s is not supported. Minimum required version is %s %s, but it is recommended to use %s %s or later. Installation is cancelled.", $name, $version, $name, $minor, $name, $recommended), 'notice');
		return false;
	}

	protected function checkDbo($name, $types) {
		$app = Factory::getApplication();

		if (in_array($name, $types)) {
			return true;
		}
		$app->enqueueMessage(sprintf("Database driver '%s' is not supported. Please use MySQLi instead.", $name), 'notice');
		return false;
	}

	protected function checkPHPExtensions($extensions)
	{
		$app = Factory::getApplication();

		$pass = true;
		foreach ($extensions as $name)
		{
			if (!extension_loaded($name))
			{
				$pass = false;
				$app->enqueueMessage(sprintf("Required PHP extension '%s' is missing. Please install it into your system.", $name), 'notice');
			}
		}
		return $pass;
	}

	protected function checkGit()
	{
		$app = Factory::getApplication();

		if ( realpath( JPATH_ADMINISTRATOR . '/components/com_comprofiler/../../../' ) == realpath( JPATH_ADMINISTRATOR ) ) {
			// Do not check for Joomla being git-versioned (only for CB and only if it is in a different, aliased folder):
			return true;
		}

		$gitCb		=	JPATH_ADMINISTRATOR . '/components/com_comprofiler/../../../.git';
		$gitJoomla	=	JPATH_ADMINISTRATOR . '/../.git';
		if ( file_exists ( $gitCb ) && ! file_exists( $gitJoomla ) )
		{
			$app->enqueueMessage('Oops! You tried to install Community Builder over your Git repository! Fortunately we checked and did not allow this', 'error');
			return false;
		}

		return true;
	}

	protected function checkCBVersion( $version )
	{
		if ( defined( 'CBLIB' ) && version_compare( $version, CBLIB, '<' ) )
		{
			$app = Factory::getApplication();
			$app->enqueueMessage('You are trying to install Community Builder over a newer version!', 'error');
			return false;
		}
		return true;
	}

	protected function fixUpdateSite()
	{
		$db = Factory::getDbo();

		// Get list of ids of all obsolete update sites:
		$query = $db->getQuery(true)
			->select($db->quoteName('update_site_id'))->from($db->quoteName('#__update_sites'))
			->where( '(' . $db->quoteName('location') . ' LIKE '. $db->quote('http://update.joomlapolis.___/%') . ' AND ' . $db->quoteName('type') . ' <> '. $db->quote('collection') . ')'
					. ' OR ' . $db->quoteName('location') . ' LIKE '. $db->quote('http://update.joomlapolis.net/%') )
			->order($db->quoteName('update_site_id') . ' ASC');
		$db->setQuery($query);
		$list = (array) $db->loadColumn();

		if ($list)
		{
			$ids = implode(',', $list);

			// Remove old update sites (not collection):
			$query = $db->getQuery(true)->delete($db->quoteName('#__update_sites'))->where($db->quoteName('update_site_id') . 'IN ('.$ids.')');
			$db->setQuery($query);
			$db->execute();

			// Remove old updates.
			$query = $db->getQuery(true)->delete($db->quoteName('#__updates'))->where($db->quoteName('update_site_id') . 'IN ('.$ids.')');
			$db->setQuery($query);
			$db->execute();

			// Remove old update extension bindings.
			$query = $db->getQuery(true)->delete($db->quoteName('#__update_sites_extensions'))->where($db->quoteName('update_site_id') . 'IN ('.$ids.')');
			$db->setQuery($query);
			$db->execute();
		}
	}
}
