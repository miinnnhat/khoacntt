<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Registry\Registry;
use CBLib\Xml\SimpleXMLElement;
use CB\Database\Table\PluginTable;
use CB\Database\Table\FieldTable;
use CB\Database\Table\TabTable;
use CB\Database\Table\ListTable;
use CBLib\Registry\GetterInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

// Ensure is being included by Joomla installer and not accessed directly:
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

// Try to set timeout limit and memory limit to ensure a complete install as could take awhile:
@set_time_limit( 240 );

$memMax				=	trim( @ini_get( 'memory_limit' ) );

if ( $memMax ) {
	$last			=	strtolower( $memMax[strlen( $memMax ) - 1] );
	$memMax			=	substr( $memMax, 0, -1 );

	switch( $last ) {
		/** @noinspection PhpMissingBreakStatementInspection */
		case 'g':
			$memMax	*=	1024;
		/** @noinspection PhpMissingBreakStatementInspection */
		case 'm':
			$memMax	*=	1024;
		case 'k':
			$memMax	*=	1024;
	}

	if ( $memMax < 16000000 ) {
		@ini_set( 'memory_limit', '16M' );
	}

	if ( $memMax < 32000000 ) {
		@ini_set( 'memory_limit', '32M' );
	}

	if ( $memMax < 48000000 ) {
		@ini_set( 'memory_limit', '48M' );
	}

	if ( $memMax < 64000000 ) {
		@ini_set( 'memory_limit', '64M' );
	}

	if ( $memMax < 80000000 ) {
		@ini_set( 'memory_limit', '80M' );
	}
}

ignore_user_abort( true );

// Define J1.6 and greater installer class; does nothing on other versions:
class Com_ComprofilerInstallerScript {

	public function install( /** @noinspection PhpUnusedParameterInspection */ $parent, $method = 'install' ) {
		global $_CB_framework, $_CB_adminpath, $ueConfig;

		// Ensure PHP version is adaquete for CB:
		if ( version_compare( phpversion(), '5.3.3', '<' ) ) {
			Factory::getApplication()->enqueueMessage( sprintf( 'As stated in README and prerequisites, PHP Version %s, which is obsolete since before 2009-11-19 and insecure, is not compatible with %s: Please upgrade to PHP %s or greater (CB is also compatible with PHP 5.4 and 5.5) before installing Community Builder.', phpversion(), 'Community Builder', sprintf( 'at least version %s, recommended version %s', '5.3.1', '5.3.10' ) ), 'error' );
			Factory::getApplication()->enqueueMessage( sprintf( 'Installation failed. In all cases, please require your hoster to upgrade your PHP version as soon as possible.' ), 'error' );
			return false;
		}

		// Initialize CB Appplication library (which has delayed config lazy loading):
		if ( is_readable( JPATH_SITE . '/libraries/CBLib/CB/Application/CBApplication.php' ) ) {
			/** @noinspection PhpIncludeInspection */
			include_once JPATH_SITE . '/libraries/CBLib/CB/Application/CBApplication.php';
			\CB\Application\CBApplication::init( Factory::getApplication()->getName() == 'ConsoleApplication' ? 'Cli' : 'Web', Factory::getApplication()->getName() );
		} else {
			Factory::getApplication()->enqueueMessage( "Mandatory Community Builder lib_CBLib not installed!", 'error');
			return false;
		}

		// Determine path to CBs backend file structure:
		$_CB_adminpath						=	JPATH_ADMINISTRATOR . '/components/com_comprofiler/';

		// Disable loading of config immediately after foundation include as the db may not exist yet:
		\CB\Application\CBConfig::setCbConfigReadyToLoad( false );

		// Check if CBLib can load:
		/** @noinspection PhpIncludeInspection */
		if ( false === include_once( $_CB_adminpath . 'plugin.foundation.php' ) ) {
			return false;
		}

		// Check if CBLib is up to date:
		if ( version_compare( constant( 'CBLIB' ), $ueConfig['version'], '<' ) ) {
			Factory::getApplication()->enqueueMessage( sprintf( 'Community Builder library lib_CBLib version %s is older than the Community Builder version %s tried to be installed.', constant( 'CBLIB' ), $ueConfig['version'] ), 'error' );
			return false;
		}

		// Set location to backend:
		$_CB_framework->cbset( '_ui', 2 );

		if ( $_CB_framework->getCfg( 'debug' ) ) {
			ini_set( 'display_errors', true );
			error_reporting( E_ALL );
		}

		// Load in CB API:
		cbimport( 'cb.tabs' );
		cbimport( 'cb.adminfilesystem' );
		cbimport( 'cb.dbchecker' );

		// Define CB backend filesystem API:
		$adminFS								=	cbAdminFileSystem::getInstance();

		// Delete removed files on upgrade:
		$foldersToDelete						=	[	JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Adapter',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Command',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Event',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Message',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Post',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Stream',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Subscriber',
														JPATH_LIBRARIES . '/CBLib/CBPHPMailer',
													];

		foreach ( $foldersToDelete as $deleteThisFolder ) {
			if ( $adminFS->file_exists( $deleteThisFolder ) ) {
				$adminFS->deldir( $deleteThisFolder . '/' );
			}
		}

		$filesToDelete							=	[	$_CB_adminpath . 'comprofileg.xml',
														$_CB_adminpath . 'comprofilej.xml',
														$_CB_adminpath . 'admin.comprofiler.php',
														$_CB_adminpath . 'ue_config_first.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Collection.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/HasDataTrait.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Mimetypes.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Query.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/QueryParser.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/ToArrayInterface.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/UriTemplate.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Url.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/cacert.pem',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Exception/AdapterException.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Exception/CouldNotRewindStreamException.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Exception/ParseException.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Psr7/functions.php',
														JPATH_LIBRARIES . '/CBLib/GuzzleHttp/Psr7/functions_include.php',
													];

		foreach ( $filesToDelete as $deleteThisFile ) {
			if ( $adminFS->file_exists( $deleteThisFile ) ) {
				$adminFS->unlink( $deleteThisFile );
			}
		}

		$liveSite								=	$_CB_framework->getCfg( 'live_site' );

		$return									=	'<div style="margin-bottom:10px;width:100%;text-align:center;"><img alt="' . htmlspecialchars( CBTxt::T( 'CB Logo' ) ) . '" src="' . $liveSite . '/components/com_comprofiler/images/smcblogo.gif" /></div>'
												.	'<div style="font-size:14px;margin-bottom:10px;">Copyright 2004-2024 Joomlapolis.com. ' . CBTxt::T( 'This component is released under the GNU/GPL version 2 License. All copyright statements must be kept. Derivate work must prominently duly acknowledge original work and include visible online links.' ) . '</div>';

		$cbDatabase								=	\CBLib\Application\Application::Database();

		// Core database fixes:
		$dbChecker								=	new \CB\Database\CBDatabaseChecker( $cbDatabase );
		$result									=	$dbChecker->checkDatabase( true );

		if ( $result == true ) {
			// All ok, Nothing to alarm user here:
			// $return							.=	'<div style="font-size:18px;color:green;margin-bottom:10px;">' . CBTxt::T( 'Automatic database upgrade applied successfully.' ) . '</div>';
		} elseif ( is_string( $result ) ) {
			$return								.=	'<div style="font-size:18px;color:red;margin-bottom:10px;">' . $result . '</div>';
		} else {
			$errors								=	$dbChecker->getErrors( false );

			if ( $errors ) {
				$return							.=	'<div style="color:red;margin-bottom:10px;">'
												.		'<div style="font-size:18px;font-weight:bold;padding-bottom:5px;margin-bottom:5px;border-bottom:1px solid red;">' . CBTxt::T( 'Database fixing errors' ) . '</div>';

				foreach ( $errors as $error ) {
					$return						.=		'<div style="margin-bottom:10px;">'
												.			'<div style="font-size:14px;">' . $error[0] . '</div>';

					if ( $error[1] ) {
						$return					.=			'<div style="font-size:12px;text-indent:15px;">' . $error[1] . '</div>';
					}

					$return						.=		'</div>';
				}

				$return							.=	'</div>';
			}
		}

		if ( $_CB_framework->getCfg( 'session_handler' ) != 'database' ) {
			$logs								=	$dbChecker->getLogs( false );

			if ( count( $logs ) > 0 ) {
				$return							.=	'<div style="margin-bottom:10px;">'
												.		'<div style="font-size:14px;margin-bottom:5px;">'
												.			'<a href="javascript: void(0);" id="cbdetailsLinkShow" onclick="this.style.display=\'none\';document.getElementById(\'cbdetailsdbcheck\').style.display=\'block\';document.getElementById(\'cbdetailsLinkHide\').style.display=\'block\';return false;">' . CBTxt::T( 'Click to Show details' ) . '</a>'
												.			'<a href="javascript: void(0);" id="cbdetailsLinkHide" onclick="this.style.display=\'none\';document.getElementById(\'cbdetailsdbcheck\').style.display=\'block\';document.getElementById(\'cbdetailsLinkShow\').style.display=\'block\';return false;" style="display:none;">' . CBTxt::T( 'Click to Hide details' ) . '</a>'
												.		'</div>'
												.		'<div id="cbdetailsdbcheck" style="dsiplay:none;color:green;">';

				foreach ( $logs as $log ) {
					$return						.=			'<div style="margin-bottom:10px;">'
												.				'<div style="font-size:14px;">' . $log[0] . '</div>';

					if ( $log[1] ) {
						$return					.=				'<div style="font-size:12px;text-indent:15px;">' . $log[1] . '</div>';
					}

					$return						.=			'</div>';
				}

				$return							.=		'</div>'
												.	'</div>';
			}
		}

		// Fix old 1.x usergroups-based permissions to 2.x access-levels in lists and in tabs:

		$this->convertUserGroupsToViewAccessLevels( new \CB\Database\Table\TabTable(), 'CB Tab access' );
		$this->convertUserGroupsToViewAccessLevels( new \CB\Database\Table\ListTable(), 'CB Users list access' );

		// Synchronize users to CB:

		$query									=	'INSERT IGNORE INTO ' . $cbDatabase->NameQuote( '#__comprofiler' )
												.	"\n ("
												.		$cbDatabase->NameQuote( 'id' )
												.		', ' . $cbDatabase->NameQuote( 'user_id' )
												.	')'
												.	"\n SELECT "
												.		$cbDatabase->NameQuote( 'id' )
												.		', ' . $cbDatabase->NameQuote( 'id' )
												.	"\n FROM " . $cbDatabase->NameQuote( '#__users' );
		$cbDatabase->setQuery( $query );
		if ( ! $cbDatabase->query() ) {
			if ( method_exists( Application::Cms(), 'getFormToken' ) ) {
				$cbSpoofField					=	Application::Session()->getFormTokenName();
				$cbSpoofString					=	Application::Session()->getFormTokenValue();
			} else {
				$cbSpoofField					=	cbSpoofField();
				$cbSpoofString					=	cbSpoofString( null, 'plugin' );
			}

			$return								.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'User synchronization failed. Please <a href="[url]" target="_blank">click here</a> to manually synchronize.', array( '[url]' => $_CB_framework->backendUrl( "index.php?option=com_comprofiler&view=syncUsers&$cbSpoofField=$cbSpoofString" ) ) ) . '</div>';
		}

		// Fix images:
		$imagesPath								=	$_CB_framework->getCfg( 'absolute_path' ) . '/images';
		$cbImages								=	$imagesPath . '/comprofiler';
		$cbImagesGallery						=	$cbImages . '/gallery';
		$cbImagesCanvasGallery					=	$cbImages . '/gallery/canvas';

		if ( $adminFS->isUsingStandardPHP() && ( ! $adminFS->file_exists( $cbImages ) ) && ( ! $adminFS->is_writable( $_CB_framework->getCfg( 'absolute_path' ) . '/images/' ) ) ) {
			$return								.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ is not writable.', array( '[path]' => $imagesPath ) ) . '</div>';
		} else {
			if ( ! $adminFS->file_exists( $cbImages ) ) {
				if ( $adminFS->mkdir( $cbImages ) ) {
					// All ok, Nothing to alarm user here.
					// $return						.=	'<div style="font-size:14px;color:green;margin-bottom:10px;">' . CBTxt::P( '[path]/ successfully added.', array( '[path]' => $cbImages ) ) . '</div>';
				} else {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to create, please do so manually.', array( '[path]' => $cbImages ) ) . '</div>';
				}
			}

			if ( ! $adminFS->file_exists( $cbImagesGallery ) ) {
				if ( $adminFS->mkdir( $cbImagesGallery ) ) {
					// All ok, Nothing to alarm user here:
					// $return					.=	'<div style="font-size:14px;color:green;margin-bottom:10px;">' . CBTxt::P( '[path]/ successfully added.', array( '[path]' => $cbImagesGallery ) ) . '</div>';
				} else {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to create, please do so manually.', array( '[path]' => $cbImagesGallery ) ) . '</div>';
				}
			}

			if ( ! $adminFS->file_exists( $cbImagesCanvasGallery ) ) {
				if ( $adminFS->mkdir( $cbImagesCanvasGallery ) ) {
					// All ok, Nothing to alarm user here:
					// $return					.=	'<div style="font-size:14px;color:green;margin-bottom:10px;">' . CBTxt::P( '[path]/ successfully added.', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>';
				} else {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to create, please do so manually.', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>';
				}
			}

			if ( $adminFS->file_exists( $cbImages ) ) {
				if ( ! is_writable( $cbImages ) ) {
					if ( ! $adminFS->chmod( $cbImages, 0775 ) ) {
						if ( ! @chmod( $cbImages, 0775 ) ) {
							$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to chmod to 775 please do so manually.', array( '[path]' => $cbImages ) ) . '</div>';
						}
					}
				}

				if ( ! is_writable( $cbImages ) ) {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ is not writable and failed to chmod to 775 please do so manually.', array( '[path]' => $cbImages ) ) . '</div>';
				}

				if ( ! $adminFS->file_exists( $cbImages . '/index.html' ) ) {
					$result						=	@copy( $imagesPath . '/index.html', $cbImages . '/index.html' );

					if ( ! $result ) {
						$result					=	$adminFS->copy( $imagesPath . '/index.html', $cbImages . '/index.html' );
					}

					if ( ! $result ) {
						$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'index.html failed to be added to [path] please do so manually.', array( '[path]' => $cbImages ) ) . '</div>';
					}
				}
			}

			if ( $adminFS->file_exists( $cbImagesGallery ) ) {
				if ( ! is_writable( $cbImagesGallery ) ) {
					if ( ! $adminFS->chmod( $cbImagesGallery, 0775 ) ) {
						if ( ! @chmod( $cbImagesGallery, 0775 ) ) {
							$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to chmod to 775 please do so manually.', array( '[path]' => $cbImagesGallery ) ) . '</div>';
						}
					}
				}

				if ( ! is_writable( $cbImagesGallery ) ) {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ is not writable and failed to chmod to 775 please do so manually.', array( '[path]' => $cbImagesGallery ) ) . '</div>';
				}

				$galleryPath					=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/images/gallery';
				$galleryDir						=	@opendir( $galleryPath );
				$galleryFiles					=	array();

				while ( true == ( $file = @readdir( $galleryDir ) ) ) {
					if ( ( $file != '.' ) && ( $file != '..' ) ) {
						$galleryFiles[]			=	$file;
					}
				}

				@closedir( $galleryDir );

				foreach ( $galleryFiles as $galleryFile ) {
					if ( ! ( file_exists( $cbImagesGallery . '/' . $galleryFile ) && is_readable( $cbImagesGallery . '/' . $galleryFile ) ) ) {
						$result					=	@copy( $galleryPath . '/' . $galleryFile, $cbImagesGallery . '/' . $galleryFile );

						if ( ! $result ) {
							$result				=	$adminFS->copy( $galleryPath . '/' . $galleryFile, $cbImagesGallery . '/' . $galleryFile );
						}

						if ( ! $result ) {
							$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[file] failed to be added to the gallery please do so manually.', array( '[file]' => $galleryFile ) ) . '</div>';
						}
					}
				}

				if ( ! $adminFS->file_exists( $cbImagesGallery . '/index.html' ) ) {
					$result						=	@copy( $imagesPath . '/index.html', $cbImagesGallery . '/index.html' );

					if ( ! $result ) {
						$result					=	$adminFS->copy( $imagesPath . '/index.html', $cbImagesGallery . '/index.html' );
					}

					if ( ! $result ) {
						$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'index.html failed to be added to [path] please do so manually.', array( '[path]' => $cbImagesGallery ) ) . '</div>';
					}
				}
			}

			if ( $adminFS->file_exists( $cbImagesCanvasGallery ) ) {
				if ( ! is_writable( $cbImagesCanvasGallery ) ) {
					if ( ! $adminFS->chmod( $cbImagesCanvasGallery, 0775 ) ) {
						if ( ! @chmod( $cbImagesCanvasGallery, 0775 ) ) {
							$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ failed to chmod to 775 please do so manually.', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>';
						}
					}
				}

				if ( ! is_writable( $cbImagesCanvasGallery ) ) {
					$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[path]/ is not writable and failed to chmod to 775 please do so manually.', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>';
				}

				$galleryPath					=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/images/gallery/canvas';
				$galleryDir						=	@opendir( $galleryPath );
				$galleryFiles					=	array();

				while ( true == ( $file = @readdir( $galleryDir ) ) ) {
					if ( ( $file != '.' ) && ( $file != '..' ) ) {
						$galleryFiles[]			=	$file;
					}
				}

				@closedir( $galleryDir );

				foreach ( $galleryFiles as $galleryFile ) {
					if ( ! ( file_exists( $cbImagesCanvasGallery . '/' . $galleryFile ) && is_readable( $cbImagesCanvasGallery . '/' . $galleryFile ) ) ) {
						$result					=	@copy( $galleryPath . '/' . $galleryFile, $cbImagesCanvasGallery . '/' . $galleryFile );

						if ( ! $result ) {
							$result				=	$adminFS->copy( $galleryPath . '/' . $galleryFile, $cbImagesCanvasGallery . '/' . $galleryFile );
						}

						if ( ! $result ) {
							$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( '[file] failed to be added to the gallery please do so manually.', array( '[file]' => $galleryFile ) ) . '</div>';
						}
					}
				}

				if ( ! $adminFS->file_exists( $cbImagesCanvasGallery . '/index.html' ) ) {
					$result						=	@copy( $imagesPath . '/index.html', $cbImagesCanvasGallery . '/index.html' );

					if ( ! $result ) {
						$result					=	$adminFS->copy( $imagesPath . '/index.html', $cbImagesCanvasGallery . '/index.html' );
					}

					if ( ! $result ) {
						$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'index.html failed to be added to [path] please do so manually.', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>';
					}
				}
			}
		}

		if ( ! ( $adminFS->file_exists( $cbImages ) && is_writable( $cbImages ) && $adminFS->file_exists( $cbImagesGallery ) && $adminFS->file_exists( $cbImagesCanvasGallery ) ) ) {
			$return								.=	'<div style="margin-bottom:10px;">'
												.		'<div style="font-size:14px;">' . CBTxt::T( 'Manually do the following:' ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::P( '1. create [path]/ directory', array( '[path]' => $cbImages ) ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::T( '2. chmod it to 755 or if needed to 775' ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::P( '3. create [path]/ directory', array( '[path]' => $cbImagesGallery ) ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::T( '4. chmod it to 755 or if needed to 775' ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::P( '5. copy [from_path]/ and its contents to [to_path]/', array( '[from_path]' => $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/images/gallery', '[to_path]' => $cbImagesGallery ) ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::P( '6. create [path]/ directory', array( '[path]' => $cbImagesCanvasGallery ) ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::T( '7. chmod it to 755 or if needed to 775' ) . '</div>'
												.		'<div style="font-size:12px;text-indent:15px;">' . CBTxt::P( '8. copy [from_path]/ and its contents to [to_path]/', array( '[from_path]' => $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/images/gallery/canvas', '[to_path]' => $cbImagesCanvasGallery ) ) . '</div>'
												.	'</div>';
		}

		$pluginMessages							=	null;

		if ( cbInstaller_install_plugins( $this, $pluginMessages, $method ) ) {
			// All ok, Nothing to alarm user here-
			// $return							.=	'<div style="font-size:18px;color:green;margin-bottom:10px;">' . CBTxt::T( 'Core plugins installed successfully.' ) . '</div>';
		} else {
			$return								.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Core plugins installation failed. Please <a href="[url]" target="_blank">click here</a> to manually install.', array( '[url]' => $_CB_framework->backendUrl( 'index.php?option=com_comprofiler&view=finishinstallation' ) ) ) . '</div>';
		}

		$installFromWeb							=	( isset( $ueConfig['installFromWeb'] ) ? $ueConfig['installFromWeb'] : 1 );

		$return									.=	$pluginMessages
												.	'<div style="color:green;font-size:18px;font-weight:bold;margin-top:15px;margin-bottom:15px;">' . CBTxt::P( 'Installation done.' ) . '</div>'
												.	'<div style="color:green;font-size:18px;font-weight:bold;margin-top:15px;margin-bottom:15px;">' . CBTxt::P( 'Now is a great time to checkout the <a href="[help_url]" target="_blank">Getting Started</a> resources.', array( '[help_url]' => 'https://www.joomlapolis.com/documentation/community-builder/getting-started?pk_campaign=in-cb&amp;pk_kwd=installedwelcomescreen' ) ) . '</div>'
												.	'<div style="margin-bottom:10px;">'
												.		'<div style="font-size:12px;"><a href="https://www.joomlapolis.com/cb-solutions?pk_campaign=in-cb&amp;pk_kwd=installedwelcomescreen" target="_blank">' . CBTxt::T( 'Click here to see more CB Plugins (Languages, Fields, Tabs, Signup-Connect, Paid Memberships and over 30 more) by CB Team at joomlapolis.com' ) . '</a></div>'
												.		'<div style="font-size:12px;"><a href="https://extensions.joomla.org/extensions/clients-a-communities/communities/210" target="_blank">' . CBTxt::T( 'Click here to see our CB listing on the Joomla! Extensions Directory (JED) and find third-party add-ons for your website.' ) . '</a></div>'
												.		'<div style="font-size:12px;margin:10px 0 25px;">'
												.			( ! $installFromWeb ? 'or ' : null ) . '<a href="index.php?option=com_comprofiler&view=showconfig" class="btn btn-primary">' . CBTxt::Th( 'Start to Configure Community Builder' ) . '</a>'
												.			( $installFromWeb ? ' or <a href="index.php?option=com_comprofiler&view=installcbplugin&tab=installfrom2" class="btn btn-primary">' . CBTxt::Th( 'Install available Languages and Add-Ons…' ) . '</a>' : null )
												.		'</div>'
												.	'</div>';

		echo $return;

		// For display in packager:
		$_CB_framework->setUserState( 'com_comprofiler_install', $return );

		return true;
	}

	public function discover_install( $parent ) {
		return $this->install( $parent, 'discover_install' );
	}

	public function update( $parent ) {
		return $this->install( $parent, 'update' );
	}

	public function preflight( $type, /** @noinspection PhpUnusedParameterInspection */ $parent ) {
		// Fix "Can not build admin menus" error on upgrades:
		if ( ! in_array($type, array( 'install', 'discover_install' ) ) ) {
			$db			=	Factory::getDbo();
//			$db			=	\Joomla\CMS\Factory::getContainer()->get(DatabaseFactoryInterface::class);

			$query		=	$db->getQuery( true );
			$query->select( 'id' );
			$query->from( '#__menu' );
			$query->where( $db->qn( 'type' ) . ' = ' . $db->q( 'component' ) );
			$query->where( $db->qn( 'menutype' ) . ' = ' . $db->q( 'main' ) );
			$query->where( $db->qn( 'client_id' ) . ' = ' . $db->q( '1' ) );
			$query->where( $db->qn( 'link' ) .' LIKE ' . $db->q( 'index.php?option=com_comprofiler%' ) );
			$db->setQuery( $query );
			$ids		=	$db->loadColumn();

			if ( $ids ) foreach( $ids as $id ) {
				$query	=	$db->getQuery( true );
				$query->delete( '#__menu' );
				$query->where( $db->qn( 'id' ) . ' = ' . $db->q( $id ) );
				$db->setQuery( $query );
				if ( method_exists( $db, 'execute' ) ) {
					// Joomla 3.0+:
					$db->execute();
				} else {
					// Joomla 2.5-:
					$db->query();
				}
			}
		}
	}

	public function postflight( $type, /** @noinspection PhpUnusedParameterInspection */ $parent ) {
		if ( in_array( $type, array( 'update', 'install', 'discover_install' ) ) ) {
			$cbDatabase		=	\CBLib\Application\Application::Database();

			$query			=	'SELECT ' . $cbDatabase->NameQuote( 'extension_id' )
							.	"\n FROM " . $cbDatabase->NameQuote( '#__extensions' )
							.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'component' )
							.	"\n AND " . $cbDatabase->NameQuote( 'element' ) . " = " . $cbDatabase->Quote( 'com_comprofiler' )
							.	"\n ORDER BY " . $cbDatabase->NameQuote( 'extension_id' ) . " DESC";
			$cbDatabase->setQuery( $query, 0, 1 );
			$componentId	=	$cbDatabase->loadResult();

			if ( $componentId ) {
				$query		=	'UPDATE ' . $cbDatabase->NameQuote( '#__menu' )
							.	"\n SET " . $cbDatabase->NameQuote( 'component_id' ) . " = " . (int) $componentId
							.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'component' )
							.	"\n AND " . $cbDatabase->NameQuote( 'link' ) . " LIKE " . $cbDatabase->Quote( '%option=com_comprofiler%' );
				$cbDatabase->setQuery( $query );
				$cbDatabase->query();

				if ( cbInstaller_create_menutype( 'communitybuilder', CBTxt::T( 'Community Builder' ) ) === true ) {
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_PROFILE', 'CB Profile' ), 'index.php?option=com_comprofiler&view=userprofile', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_PROFILE_EDIT', 'CB Profile Edit' ), 'index.php?option=com_comprofiler&view=userdetails', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_REGISTRATION', 'CB Registration' ), 'index.php?option=com_comprofiler&view=registers', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_LOGIN', 'CB Login' ), 'index.php?option=com_comprofiler&view=login', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_LOGOUT', 'CB Logout' ), 'index.php?option=com_comprofiler&view=logout', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_FORGOT_LOGIN', 'CB Forgot Login' ), 'index.php?option=com_comprofiler&view=lostpassword', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_USERLIST', 'CB Userlist' ), 'index.php?option=com_comprofiler&view=userslist', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_MANAGE_CONNECTIONS', 'CB Manage Connections' ), 'index.php?option=com_comprofiler&view=manageconnections', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_MODERATE_BANS', 'CB Moderate Bans' ), 'index.php?option=com_comprofiler&view=moderatebans', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_MODERATE_IMAGES', 'CB Moderate Images' ), 'index.php?option=com_comprofiler&view=moderateimages', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_MODERATE_REPORTS', 'CB Moderate Reports' ), 'index.php?option=com_comprofiler&view=moderatereports', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_MODERATE_USER_APPROVALS', 'CB Moderate User Approvals' ), 'index.php?option=com_comprofiler&view=pendingapprovaluser', array( 'component_id' => (int) $componentId ) );
					cbInstaller_create_menuitem( 'communitybuilder', CBTxt::T( 'CB_PRIVATE_MESSAGES', 'CB Private Messages' ), 'index.php?option=com_comprofiler&view=pluginclass&plugin=pms.mypmspro&action=messages', array( 'component_id' => (int) $componentId ) );
				}

				// Migrate legacy CB 1.x menu items from task to view:
				$menuItems	=	array( 'userprofile', 'login', 'logout', 'registers', 'userdetails', 'lostpassword', 'userslist' );

				foreach ( $menuItems as $view ) {
					$query	=	'UPDATE ' . $cbDatabase->NameQuote( '#__menu' )
							.	"\n SET " . $cbDatabase->NameQuote( 'link' ) . " = " . ( $view != 'userprofile' ? "REPLACE( " . $cbDatabase->NameQuote( 'link' ) . ", " . $cbDatabase->Quote( 'index.php?option=com_comprofiler&task=' . $view ) . ", " . $cbDatabase->Quote( 'index.php?option=com_comprofiler&view=' . $view ) . " )" : $cbDatabase->Quote( 'index.php?option=com_comprofiler&view=userprofile' ) )
							.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'component' )
							.	"\n AND " . $cbDatabase->NameQuote( 'component_id' ) . " = " . (int) $componentId
							.	"\n AND " . $cbDatabase->NameQuote( 'client_id' ) . " = 0"
							.	"\n AND " . $cbDatabase->NameQuote( 'link' ) . ( $view != 'userprofile' ? " LIKE " : " = " ) . $cbDatabase->Quote( 'index.php?option=com_comprofiler' . ( $view != 'userprofile' ? '&task=' . $view . '%' : '' ) );
					$cbDatabase->setQuery( $query );
					$cbDatabase->query();
				}
			}
		}
	}

	/**
	 * Fix old 1.x usergroups-based permissions to 2.x access-levels in lists and in tabs
	 *
	 * @param  \CB\Database\Table\TabTable|\CB\Database\Table\ListTable  $loaderTabOrList
	 * @param  string                                                    $titleIfCreate    Title for newly created access levels if needed (e.g. 'CB Tab access')
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	private function convertUserGroupsToViewAccessLevels( $loaderTabOrList, $titleIfCreate )
	{
		$loaderTabOrList->getDbo()->setQuery( 'SELECT * FROM ' . $loaderTabOrList->getDbo()->NameQuote( $loaderTabOrList->getTableName() ) );

		$allTabsOrLists								=	$loaderTabOrList->loadTrueObjects();

		foreach ( $allTabsOrLists as $tabOrList ) {
			if ( isset( $tabOrList->useraccessgroupid ) ) {
				if ( (int) $tabOrList->useraccessgroupid == 0 ) {
					// Already converted or new in 2.x+:
					continue;
				}

				$alreadyConvertedButNotZeroed		=	( (int) $tabOrList->useraccessgroupid == -2 ) && $tabOrList->viewaccesslevel;

				if ( ( (int) $tabOrList->viewaccesslevel <= 1 ) && ! $alreadyConvertedButNotZeroed ) {
					// Still database default: Convert:
					$tabOrList->viewaccesslevel		=	Application::CmsPermissions()->convertOldGroupToViewAccessLevel( $tabOrList->useraccessgroupid, $titleIfCreate );
				}

				// Always set to 0 after conversion:
				$tabOrList->useraccessgroupid		=	0;

				$tabOrList->store();
			}
		}
	}

	/**
	 * Converts a percentage width to a bootstrap grid size
	 *
	 * @param int $width
	 * @return int
	 */
	public function convertWidthToBootstrap( $width )
	{
		if ( ! $width ) {
			$size	=	0;
		} elseif ( $width <= 8.33333 ) {
			$size	=	1;
		} elseif ( $width <= 16.66667 ) {
			$size	=	2;
		} elseif ( $width <= 25 ) {
			$size	=	3;
		} elseif ( $width <= 33.33333 ) {
			$size	=	4;
		} elseif ( $width <= 41.66667 ) {
			$size	=	5;
		} elseif ( $width <= 50 ) {
			$size	=	6;
		} elseif ( $width <= 58.33333 ) {
			$size	=	7;
		} elseif ( $width <= 66.66667 ) {
			$size	=	8;
		} elseif ( $width <= 75 ) {
			$size	=	9;
		} elseif ( $width <= 83.33333 ) {
			$size	=	10;
		} elseif ( $width <= 91.66667 ) {
			$size	=	11;
		} else {
			$size	=	12;
		}

		return $size;
	}
}

/**
 * @param Com_ComprofilerInstallerScript $parent
 * @param string                         $return
 * @param string                         $method
 * @return bool
 */
function cbInstaller_install_plugins( $parent, &$return, $method ) {
	global $_CB_framework, $_CB_adminpath, $ueConfig;

	cbimport( 'cb.adminfilesystem' );
	cbimport( 'cb.installer' );

	$cbDatabase							=	\CBLib\Application\Application::Database();

	// List of core plugins that are no longer core, but we just want to disable core status and not remove as they don't conflict:
	$deprecated					=	array( 'bootstrap', 'winclassic', 'webfx', 'osx', 'luna', 'dark', 'yanc', 'cb.mamblogtab', 'cb.simpleboardtab', 'cb.authortab' );

	foreach ( $deprecated as $pluginElement ) {
		$plugin					=	new PluginTable();

		if ( $plugin->load( array( 'element' => $pluginElement ) ) ) {
			$query				=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_plugin' )
								.	"\n SET " . $cbDatabase->NameQuote( 'iscore' ) . " = 0"
								.	"\n WHERE " . $cbDatabase->NameQuote( 'id' ) . " = " . (int) $plugin->id;
			$cbDatabase->setQuery( $query );
			if ( ! $cbDatabase->query() ) {
				$return			.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Plugin [element] failed to deprecate. Please run Tools > Check Community Builder Database to reattempt.', array( '[element]' => $pluginElement ) ) . '</div>';
			}

			$query				=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_tabs' )
								.	"\n SET " . $cbDatabase->NameQuote( 'sys' ) . " = 0"
								.	"\n WHERE " . $cbDatabase->NameQuote( 'pluginid' ) . " = " . (int) $plugin->id;
			$cbDatabase->setQuery( $query );
			if ( ! $cbDatabase->query() ) {
				$return			.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Plugin [element] tabs failed to deprecate. Please run Tools > Check Community Builder Database to reattempt.', array( '[element]' => $pluginElement ) ) . '</div>';
			}

			$query				=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
								.	"\n SET " . $cbDatabase->NameQuote( 'sys' ) . " = 0"
								.	"\n WHERE " . $cbDatabase->NameQuote( 'pluginid' ) . " = " . (int) $plugin->id;
			$cbDatabase->setQuery( $query );
			if ( ! $cbDatabase->query() ) {
				$return			.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Plugin [element] fields failed to deprecate. Please run Tools > Check Community Builder Database to reattempt.', array( '[element]' => $pluginElement ) ) . '</div>';
			}
		}
	}

	// List of plugins that conflict with the core that need to be removed (normally due to being merged into core):
	$conflicted					=	array( 'bootstrap', 'winclassic', 'webfx', 'osx', 'luna', 'dark', 'yanc', 'cb.mamblogtab', 'cb.authortab', 'cbvideofield', 'cb.filefield' );

	foreach ( $conflicted as $pluginElement ) {
		$plugin					=	new PluginTable();

		if ( $plugin->load( array( 'element' => $pluginElement ) ) ) {
			if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
				return false;
			}
		}
	}

	// Ensure Default template, CB Core, and language plugins are published as they are not allowed to be unpublished:
	$query						=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_plugin' )
								.	"\n SET " . $cbDatabase->NameQuote( 'published' ) . " = 1"
								.	"\n WHERE ( " . $cbDatabase->NameQuote( 'id' ) . " IN " . $cbDatabase->safeArrayOfIntegers( array( 1, 7 ) )
								.	' OR ' . $cbDatabase->NameQuote( 'type' ) . ' = ' . $cbDatabase->quote( 'language' ) . ' )';
	$cbDatabase->setQuery( $query );
	$cbDatabase->query();

	$pluginsFile				=	$_CB_adminpath . 'pluginsfiles.tgz';

	// We need to ensure the core plugins archive actually exists before doing anything with it:
	if ( ! file_exists( $pluginsFile ) ) {
		$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Core plugins archive [path] missing.', array( '[path]' => $pluginsFile ) ) . '</div>';

		return false;
	}

	// We need zlib to unzip packages so lets check that it exists:
	if ( ! extension_loaded( 'zlib' ) ) {
		$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::T( 'Core plugins can not be installed as zlib is not installed.' ) . '</div>';

		return false;
	}

	$installer					=	new cbInstallerPlugin();

	// Uncompress the core plugins so we can install them:
	if ( ! $installer->upload( $pluginsFile, true, false ) ) {
		$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Core plugins can not be installed as uncompressing [path] failed.', array( '[path]' => $pluginsFile ) ) . '</div>';

		return false;
	}

	$adminFS					=	cbAdminFileSystem::getInstance();
	$baseDir					=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler';

	// Create the base plugin directory:
	if ( ! $adminFS->is_dir( $baseDir . '/plugin' ) ) {
		if ( ! $adminFS->mkdir( $baseDir . '/plugin' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create directory [path].', array( '[path]' => $baseDir . '/plugin' ) ) . '</div>';

			return false;
		}

		if ( ! $adminFS->copy( $baseDir . '/index.html', $baseDir . '/plugin/index.html' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create index [path].', array( '[path]' => $baseDir . '/plugin/index.html' ) ) . '</div>';

			return false;
		}
	}

	// Create the language template directory:
	if ( ! $adminFS->is_dir( $baseDir . '/plugin/language' ) ) {
		if ( ! $adminFS->mkdir( $baseDir . '/plugin/language' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create directory [path].', array( '[path]' => $baseDir . '/plugin/language' ) ) . '</div>';

			return false;
		}

		if ( ! $adminFS->copy( $baseDir . '/index.html', $baseDir . '/plugin/language/index.html' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create index [path].', array( '[path]' => $baseDir . '/plugin/language/index.html' ) ) . '</div>';

			return false;
		}
	}

	// Create the template plugin directory:
	if ( ! $adminFS->is_dir( $baseDir . '/plugin/templates' ) ) {
		if ( ! $adminFS->mkdir( $baseDir . '/plugin/templates' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create directory [path].', array( '[path]' => $baseDir . '/plugin/templates' ) ) . '</div>';

			return false;
		}

		if ( ! $adminFS->copy( $baseDir . '/index.html', $baseDir . '/plugin/templates/index.html' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create index [path].', array( '[path]' => $baseDir . '/plugin/templates/index.html' ) ) . '</div>';

			return false;
		}
	}

	// Create the user plugin directory:
	if ( ! $adminFS->is_dir( $baseDir . '/plugin/user' ) ) {
		if ( ! $adminFS->mkdir( $baseDir . '/plugin/user' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create directory [path].', array( '[path]' => $baseDir . '/plugin/user' ) ) . '</div>';

			return false;
		}

		if ( ! $adminFS->copy( $baseDir . '/index.html', $baseDir . '/plugin/user/index.html' ) ) {
			$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Failed to create index [path].', array( '[path]' => $baseDir . '/plugin/user/index.html' ) ) . '</div>';

			return false;
		}
	}

	// Install core plugins 1 by 1 silently:
	$installFrom				=	$installer->installDir();
	$filesList					=	cbReadDirectory( $installFrom, '.', true );

	foreach ( $filesList as $file ) {
		if ( preg_match( '/^.+\.xml$/i', $file ) ) {
			$plgPath			=	$installFrom . ( substr( $installFrom, -1, 1 ) == '/' ? '' : '/' ) . $file;
			$plgXml				=	new SimpleXMLElement( trim( file_get_contents( $plgPath ) ) );

			if ( $plgXml->getName() == 'cbinstall' ) {
				$plgDir			=	dirname( $plgPath ) . '/';

				ob_start();
				$plgInstaller	=	new cbInstallerPlugin();
				$installed		=	$plgInstaller->install( $plgDir );
				ob_end_clean();

				if ( ! $installed ) {
					$return		.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Installing core plugin [plugin] failed with error [error].', array( '[plugin]' => ( $plgInstaller->i_elementname ? $plgInstaller->i_elementname : $file ), '[error]' => $plgInstaller->getError() ) ) . '</div>';

					return false;
				}
			}
		}
	}

	// Delete the expanded core plugins archive:
	$result						=	$adminFS->deldir( _cbPathName( $installFrom . '/' ) );

	if ( $result === false ) {
		$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::T( 'Deleting expanded core plugins archive failed.' ) . '</div>';
	}

	// Delete the core plugins archive:
	$result						=	$adminFS->unlink( _cbPathName( $pluginsFile, false ) );

	if ( $result === false ) {
		$return					.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Deleting core plugins archive [path] failed.', array( '[path]' => $pluginsFile ) ) . '</div>';
	}

	// Sets the as ready so config can actually load this time:
	\CB\Application\CBConfig::setCbConfigReadyToLoad( true );

	// Load the config now that the tables exist encase they didn't during install:
	\CB\Application\CBConfig::loadLegacyCBueConfig();

	// Migrate old file based configuration to database based configuration:
	$newConfig								=	null;

	if ( $adminFS->file_exists( $_CB_adminpath . 'ue_config.php' ) ) {
		/** @noinspection PhpIncludeInspection */
		include_once( $_CB_adminpath . 'ue_config.php' );

		// Reset the template back to default if upgrading from a 1.x install:
		$ueConfig['templatedir']			=	'default';

		$newConfig							=	json_encode( $ueConfig );
	}

	// Convert CB 1.x nesttabs into new nested tab display mode if needed:
	if ( isset( $ueConfig['nesttabs'] ) ) {
		// Update all the tabs that would have normally auto-nested and make them nested displays
		$query								=	'UPDATE ' . $cbDatabase->NameQuote( '#__comprofiler_tabs' )
											.	"\n SET " . $cbDatabase->NameQuote( 'displaytype' ) . " = " . $cbDatabase->Quote( 'nested' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'displaytype' ) . " = " . $cbDatabase->Quote( 'tab' )
											.	"\n AND " . $cbDatabase->NameQuote( 'fields' ) . " = 1"
											.	"\n AND ( ( " . $cbDatabase->NameQuote( 'pluginclass' ) . " IS NULL )"
											.	' OR ( ' . $cbDatabase->NameQuote( 'sys' ) . ' = 2 ) )';
		$cbDatabase->setQuery( $query );
		$cbDatabase->query();

		unset( $ueConfig['nesttabs'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Migrate global avatar params to field params:
	if ( isset( $ueConfig['allowAvatar'] ) || isset( $ueConfig['defaultAvatar'] ) || isset( $ueConfig['defaultPendingAvatar'] ) || isset( $ueConfig['allowAvatarGallery'] ) ) {
		$field								=	new FieldTable();

		if ( $field->load( array( 'name' => 'avatar' ) ) ) {
			$fieldParams					=	new Registry( $field->params );

			if ( isset( $ueConfig['allowAvatar'] ) ) {
				$fieldParams->set( 'image_allow_uploads', (int) $ueConfig['allowAvatar'] );

				unset( $ueConfig['allowAvatar'] );
			}

			if ( isset( $ueConfig['defaultAvatar'] ) ) {
				$fieldParams->set( 'defaultAvatar', $ueConfig['defaultAvatar'] );

				unset( $ueConfig['defaultAvatar'] );
			}

			if ( isset( $ueConfig['defaultPendingAvatar'] ) ) {
				$fieldParams->set( 'defaultPendingAvatar', $ueConfig['defaultPendingAvatar'] );

				unset( $ueConfig['defaultPendingAvatar'] );
			}

			if ( isset( $ueConfig['allowAvatarGallery'] ) ) {
				$fieldParams->set( 'image_allow_gallery', (int) $ueConfig['allowAvatarGallery'] );

				unset( $ueConfig['allowAvatarGallery'] );
			}

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		$newConfig							=	json_encode( $ueConfig );
	}

	// Migrate global email ajax checker to field specific param:
	if ( isset( $ueConfig['reg_email_checker'] ) ) {
		$field								=	new FieldTable();

		if ( $field->load( array( 'name' => 'email' ) ) ) {
			$fieldParams					=	new Registry( $field->params );

			$fieldParams->set( 'field_check_email', (string) $ueConfig['reg_email_checker'] );

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		unset( $ueConfig['reg_email_checker'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Migrate global image params to field params:
	if ( isset( $ueConfig['allowAvatarUpload'] ) ) {
		$query								=	'SELECT *'
											.	"\n FROM " .  $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'name' ) . " != " . $cbDatabase->Quote( 'avatar' )
											.	"\n AND " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'image' );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$fieldParams					=	new Registry( $field->params );

			$fieldParams->set( 'image_allow_uploads', (int) $ueConfig['allowAvatarUpload'] );

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		unset( $ueConfig['allowAvatarUpload'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Convert CB 1.x allow_profileviewbyGID into new profile_viewaccesslevel if needed:
	if ( isset( $ueConfig['allow_profileviewbyGID'] ) && ( ! isset( $ueConfig['profile_viewaccesslevel'] ) ) ) {
		$ueConfig['profile_viewaccesslevel']	=	\CBLib\Application\Application::CmsPermissions()->convertOldGroupToViewAccessLevel( $ueConfig['allow_profileviewbyGID'], 'CB Profiles access' );

		unset( $ueConfig['allow_profileviewbyGID'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Convert CB 1.x allow_profileviewbyGID into new profile_viewaccesslevel if needed:
	if ( isset( $ueConfig['imageApproverGid'] ) && ( ! isset( $ueConfig['moderator_viewaccesslevel'] ) ) ) {
		$ueConfig['moderator_viewaccesslevel']	=	\CBLib\Application\Application::CmsPermissions()->convertOldGroupToViewAccessLevel( $ueConfig['imageApproverGid'], 'CB Moderators access' );

		unset( $ueConfig['imageApproverGid'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// If old configuration for terms and conditions exists we need to pass it to the terms and conditions field:
	if ( isset( $ueConfig['reg_enable_toc'] ) && isset( $ueConfig['reg_toc_url'] ) ) {
		if ( ( $ueConfig['reg_enable_toc'] == 1 ) && ( $ueConfig['reg_toc_url'] != '' ) ) {
			$field							=	new FieldTable();

			if ( $field->load( array( 'name' => 'acceptedterms' ) ) ) {
				$fieldParams				=	new Registry( $field->params );

				if ( $fieldParams->get( 'terms_url' ) == '' ) {
					$fieldParams->set( 'terms_url', $ueConfig['reg_toc_url'] );

					$field->set( 'required', 1 );
					$field->set( 'registration', 1 );
					$field->set( 'edit', 1 );
					$field->set( 'params', $fieldParams->asJson() );

					if ( ! $field->store() ) {
						$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
					}
				}
			}
		}

		unset( $ueConfig['reg_enable_toc'] );
		unset( $ueConfig['reg_toc_url'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// If old configuration for userlists exists we need to pass it to the userlist it self:
	if ( isset( $ueConfig['num_per_page'] ) && isset( $ueConfig['allow_profilelink'] ) ) {
		if ( ( $ueConfig['num_per_page'] != '' ) || ( $ueConfig['allow_profilelink'] != 1 ) ) {
			$query							=	'SELECT *'
											.	"\n FROM " .  $cbDatabase->NameQuote( '#__comprofiler_lists' );
			$cbDatabase->setQuery( $query );
			$lists							=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\ListTable', array( $cbDatabase ) );

			/** @var $lists ListTable[] */
			foreach ( $lists as $list ) {
				$listParams					=	new Registry( $list->params );
				$changed					=	false;

				if ( ( $ueConfig['num_per_page'] != '' ) && ( $listParams->get( 'list_limit' ) == '' ) ) {
					$listParams->set( 'list_limit', $ueConfig['num_per_page'] );

					$changed				=	true;
				}

				if ( ( $ueConfig['allow_profilelink'] != 1 ) && ( $listParams->get( 'allow_profilelink' ) == '' ) ) {
					$listParams->set( 'allow_profilelink', $ueConfig['allow_profilelink'] );

					$changed				=	true;
				}

				if ( $changed ) {
					$list->set( 'params', $listParams->asJson() );

					if ( ! $list->store() ) {
						$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Userlist [title] failed to migrate. Error: [error]', array( '[name]' => $list->title, '[error]' => $list->getError() ) ) . '</div>';
					}
				}
			}
		}

		unset( $ueConfig['num_per_page'] );
		unset( $ueConfig['allow_profilelink'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Convert CB 2.x tab layout sizes from % width to Bootstrap grid size:
	if ( isset( $ueConfig['left2colsWidth'] ) || isset( $ueConfig['left3colsWidth'] ) ) {
		// Since there's 2 old left sizes lets just use the largest of the 2:
		$leftSize							=	0;

		if ( isset( $ueConfig['left2colsWidth'] ) ) {
			$leftSize						=	$parent->convertWidthToBootstrap( $ueConfig['left2colsWidth'] );

			unset( $ueConfig['left2colsWidth'] );
		}

		if ( isset( $ueConfig['left3colsWidth'] ) ) {
			$left3Size						=	$parent->convertWidthToBootstrap( $ueConfig['left3colsWidth'] );

			unset( $ueConfig['left3colsWidth'] );

			if ( $left3Size > $leftSize ) {
				$leftSize					=	$left3Size;
			}
		}

		$ueConfig['mainLayoutLeftSize']		=	$leftSize;

		$newConfig							=	json_encode( $ueConfig );
	}

	if ( isset( $ueConfig['right3colsWidth'] ) ) {
		$ueConfig['mainLayoutRightSize']	=	$parent->convertWidthToBootstrap( $ueConfig['right3colsWidth'] );

		unset( $ueConfig['right3colsWidth'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	if ( isset( $ueConfig['noVersionCheck'] ) && ( ! isset( $ueConfig['versionCheckInterval'] ) ) ) {
		$ueConfig['versionCheckInterval']	=	( $ueConfig['noVersionCheck'] == '1' ? '0' : '+12 HOURS' );

		unset( $ueConfig['noVersionCheck'] );

		$newConfig							=	json_encode( $ueConfig );
	}

	// Migrate old layout parameters to template
	if ( isset( $ueConfig['mainLayoutLeftSize'] ) || isset( $ueConfig['mainLayoutMiddleSize'] ) || isset( $ueConfig['mainLayoutRightSize'] )
		 || isset( $ueConfig['profile_edit_layout'] ) || isset( $ueConfig['profile_edit_show_icons_explain'] ) || isset( $ueConfig['reg_layout'] )
		 || isset( $ueConfig['reg_show_login_on_page'] ) || isset( $ueConfig['reg_title_img'] ) || isset( $ueConfig['reg_show_icons_explain'] )
		 || isset( $ueConfig['reg_intro_msg'] ) || isset( $ueConfig['reg_conclusion_msg'] )
	) {
		$tmpl									=	new PluginTable();

		if ( $tmpl->load( [ 'folder' => ( ( $ueConfig['templatedir'] ?? '' ) ?: 'default' ), 'type' => 'templates' ] ) ) {
			$tmplParams							=	new Registry( $tmpl->params );

			$tmplParams->set( 'profile_view_layout_other_main_left', (int) ( $ueConfig['mainLayoutLeftSize'] ?? 3 ) );
			$tmplParams->set( 'profile_view_layout_other_main_middle', (int) ( $ueConfig['mainLayoutMiddleSize'] ?? 0 ) );
			$tmplParams->set( 'profile_view_layout_other_main_right', (int) ( $ueConfig['mainLayoutRightSize'] ?? 3 ) );
			$tmplParams->set( 'profile_edit_layout', ( $ueConfig['profile_edit_layout'] ?? 'tabbed' ) );
			$tmplParams->set( 'profile_edit_layout_legend', (int) ( $ueConfig['profile_edit_show_icons_explain'] ?? 0 ) );
			$tmplParams->set( 'registration_layout', ( $ueConfig['reg_layout'] ?? 'flat' ) );
			$tmplParams->set( 'registration_layout_login', (int) ( $ueConfig['reg_show_login_on_page'] ?? 0 ) );
			$tmplParams->set( 'registration_layout_canvas', ( $ueConfig['reg_title_img'] ?? 'none' ) );
			$tmplParams->set( 'registration_layout_legend', (int) ( $ueConfig['reg_show_icons_explain'] ?? 0 ) );
			$tmplParams->set( 'registration_layout_header', ( $ueConfig['reg_intro_msg'] ?? '' ) );
			$tmplParams->set( 'registration_layout_footer', ( $ueConfig['reg_conclusion_msg'] ?? '' ) );

			$tmpl->set( 'params', $tmplParams->asJson() );
			$tmpl->store();

			unset( $ueConfig['mainLayoutLeftSize'], $ueConfig['mainLayoutMiddleSize'], $ueConfig['mainLayoutRightSize'],
					$ueConfig['profile_edit_layout'], $ueConfig['profile_edit_show_icons_explain'], $ueConfig['reg_layout'],
					$ueConfig['reg_show_login_on_page'], $ueConfig['reg_title_img'], $ueConfig['reg_show_icons_explain'],
					$ueConfig['reg_intro_msg'], $ueConfig['reg_conclusion_msg'] );

			$newConfig							=	json_encode( $ueConfig );
		}
	}

	// Establish default for any missing config params:
	$configXml								=	new SimpleXMLElement( trim( file_get_contents( $_CB_adminpath . 'xmlcb/views/view.com_comprofiler.editconfig.xml' ) ) );

	if ( $configXml ) {
		$configXmlParams					=	$configXml->xpath( '//param' );

		if ( $configXmlParams ) {
			$configXmlSet					=	false;

			foreach ( $configXmlParams as $configXmlParam ) {
				$k							=	(string) $configXmlParam->attributes( 'name' );

				if ( ! isset( $ueConfig[$k] ) ) {
					$v						=	(string) $configXmlParam->attributes( 'default' );

					if ( $k ) {
						$ueConfig[$k]		=	$v;
						$configXmlSet		=	true;
					}
				}
			}

			if ( $configXmlSet ) {
				$newConfig					=	json_encode( $ueConfig );
			}
		}
	}

	// Update cb.core with the new cb config:
	if ( $newConfig ) {
		$query								=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_plugin' )
											.	"\n SET " . $cbDatabase->NameQuote( 'params' ) . " = " . $cbDatabase->Quote( $newConfig )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'id' ) . " = 1";
		$cbDatabase->setQuery( $query );
		if ( ! $cbDatabase->query() ) {
			$_CB_framework->enqueueMessage( CBTxt::P( 'Failed to update configuration params in database. Error: [error]', array( '[error]' => $cbDatabase->getErrorMsg() ) ), 'error' );
			return false;
		}
	}

	// Remove the old config file if it exists as we migrated above already:
	if ( $adminFS->file_exists( $_CB_adminpath . 'ue_config.php' ) ) {
		$adminFS->unlink( $_CB_adminpath . 'ue_config.php' );
	}

	// Migrate old userlist columns to new usage:
	$tableFields							=	$cbDatabase->getTableFields( '#__comprofiler_lists' );

	if ( isset( $tableFields['#__comprofiler_lists'] ) ) {
		$userListFields						=	array_keys( $tableFields['#__comprofiler_lists'] );
		$userListOldFields					=	array(	'useraccessgroupid', 'sortfields', 'filterfields',
														'col1title', 'col1enabled', 'col1fields', 'col1captions',
														'col2title', 'col2enabled', 'col2fields', 'col2captions',
														'col3title', 'col3enabled', 'col3fields', 'col3captions',
														'col4title', 'col4enabled', 'col4fields', 'col4captions'
													);

		// At least 1 legacy column still exists so lets begin migration of userlists:
		if ( array_intersect( $userListOldFields, $userListFields ) ) {
			$query							=	'SELECT *'
											.	"\n FROM " .  $cbDatabase->NameQuote( '#__comprofiler_lists' );
			$cbDatabase->setQuery( $query );
			$lists							=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\ListTable', array( $cbDatabase ) );

			/** @var $lists ListTable[] */
			foreach ( $lists as $list ) {
				$listParams					=	new Registry( $list->params );
				$listSorting				=	array();
				$listSortFields				=	( $list->get( 'sortfields' ) ? explode( ', ', str_replace( '`', '', $list->get( 'sortfields' ) ) ) : array() );
				$paramsChanged				=	false;

				foreach ( $listSortFields as $listSortField ) {
					$sortParts				=	explode( ' ', $listSortField );
					$sortField				=	( isset( $sortParts[0] ) ? trim( $sortParts[0] ) : null );

					if ( $sortField ) {
						$sortDirection		=	( isset( $sortParts[1] ) ? trim( $sortParts[1] ) : 'ASC' );

						$listSorting[]		=	array( 'column' => $sortField, 'direction' => $sortDirection );
					}
				}

				if ( $listSorting ) {
					$paramsChanged			=	true;

					$listParams->set( 'sort_mode', '0' );
					$listParams->set( 'basic_sort', $listSorting );
				}

				$listFilterFields							=	$list->get( 'filterfields' );

				if ( $listFilterFields ) {
					$filterType								=	substr( $listFilterFields, 0, 1 );
					$listFilterFields						=	rawurldecode( substr( $listFilterFields, 2, -1 ) );

					if ( $filterType == 'a' ) {
						$paramsChanged						=	true;

						$listParams->set( 'filter_mode', '1' );
						$listParams->set( 'filter_advanced', $listFilterFields );
					} else {
						$listFilters						=	array();
						$basicFilters						=	explode( ' AND ', $listFilterFields );

						foreach ( $basicFilters as $basicFilter ) {
							if ( preg_match( '/`(.+)`\s*(.+)\s*\'(.*)\'|`(.+)`\s*(.+)/i', $basicFilter, $matches ) ) {
								$filterField				=	( isset( $matches[1] ) ? $matches[1] : ( isset( $matches[4] ) ? $matches[4] : null ) );
								$filterOperator				=	( isset( $matches[2] ) ? $matches[2] : ( isset( $matches[5] ) ? $matches[5] : null ) );
								$filterVal					=	( isset( $matches[3] ) ? $matches[3] : '' );

								switch ( $filterOperator ) {
									case '!=':
										$filterOperator		=	'<>||ISNULL';
										break;
									case 'IS NULL':
									case "= ''":
										$filterOperator		=	'=';
										$filterVal			=	'';
										break;
									case 'IS NOT NULL':
									case "!= ''":
										$filterOperator		=	'!=';
										$filterVal			=	'';
										break;
								}

								if ( $filterField && $filterOperator ) {
									$listFilters[]			=	array( 'column' => $filterField, 'operator' => $filterOperator, 'value' => $filterVal );
								}
							}
						}

						if ( $listFilters ) {
							$paramsChanged					=	true;

							$listParams->set( 'filter_mode', '0' );
							$listParams->set( 'filter_basic', $listFilters );
						}
					}
				}

				$listColumns				=	array();

				for ( $i = 1, $n = 4; $i <= $n ; $i++ ) {
					if ( $list->get( 'col' . $i . 'enabled' ) ) {
						$columnTitle		=	$list->get( 'col' . $i . 'title', '' );
						$columnCaptions		=	(int) $list->get( 'col' . $i . 'captions', 0 );
						$columnFields		=	( $list->get( 'col' . $i . 'fields' ) ? explode( '|*|', $list->get( 'col' . $i . 'fields' ) ) : array() );
						$listFields			=	array();

						foreach ( $columnFields as $columnField ) {
							$listFields[]	=	array( 'field' => (string) $columnField, 'display' => ( $columnCaptions ? '1' : '4' ) );
						}

						if ( $listFields ) {
							$listColumns[]	=	array( 'title' => $columnTitle, 'size' => '3', 'cssclass' => '', 'fields' => $listFields );
						}
					}
				}

				if ( $listColumns ) {
					$paramsChanged			=	true;

					$listParams->set( 'columns', $listColumns );
				}

				if ( $paramsChanged || $list->get( 'usergroupids' ) ) {
					$list->set( 'usergroupids', implode( '|*|', explode( ', ', $list->get( 'usergroupids' ) ) ) );
					$list->set( 'params', $listParams->asJson() );

					if ( ! $list->store() ) {
						$return				.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Userlist [title] failed to migrate. Error: [error]', array( '[name]' => $list->title, '[error]' => $list->getError() ) ) . '</div>';
					}
				}
			}

			$userListDrop					=	array();

			foreach ( $userListOldFields as $userListOldField ) {
				if ( in_array( $userListOldField, $userListFields ) ) {
					$userListDrop[]			=	$cbDatabase->NameQuote( $userListOldField );
				}
			}

			if ( $userListDrop ) {
				$query						=	'ALTER TABLE ' . $cbDatabase->NameQuote( '#__comprofiler_lists' )
											.	"\n DROP " . implode( ', DROP ', $userListDrop );
				$cbDatabase->setQuery( $query );
				$cbDatabase->query();
			}
		}
	}

	// Migrates password strength parameters:
	$plugin									=	new PluginTable();

	if ( $plugin->load( array( 'element' => 'cbpasswordstrength' ) ) ) {
		$query								=	"SELECT *"
											.	"\n FROM " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'password' );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$fieldParams					=	new Registry( $field->params );

			$fieldParams->set( 'passTestSrength', (string) $fieldParams->get( 'pswstr_display', 1 ) );

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
			return false;
		}
	}

	// Migrates ajax points field parameters:
	$plugin									=	new PluginTable();

	if ( $plugin->load( array( 'element' => 'cbajaxpointsfield' ) ) ) {
		$query								=	"SELECT *"
											.	"\n FROM " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " = " . $cbDatabase->Quote( 'ajaxpoints' );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$fieldParams					=	new Registry( $field->params );

			switch ( (int) $fieldParams->get( 'ajax_layout', 1 ) ) {
				case 1:
					$fieldParams->set( 'points_layout', '[minus] [value] [plus]' );
					break;
				case 2:
					$fieldParams->set( 'points_layout', '[plus] [value] [minus]' );
					break;
				case 3:
					$fieldParams->set( 'points_layout', '[value] [minus][plus]' );
					break;
				case 4:
					$fieldParams->set( 'points_layout', '[value] [plus][minus]' );
					break;
				case 5:
					$fieldParams->set( 'points_layout', '[minus][plus] [value]' );
					break;
				case 6:
					$fieldParams->set( 'points_layout', '[plus][minus] [value]' );
					break;
			}

			$fieldParams->set( 'points_inc_plus', (string) $fieldParams->get( 'ajax_increment_up', 1 ) );
			$fieldParams->set( 'points_inc_minus', (string) $fieldParams->get( 'ajax_increment_down', 1 ) );
			$fieldParams->set( 'points_access', '8' );
			$fieldParams->set( 'points_access_custom', (string) $fieldParams->get( 'ajax_access', 0 ) );

			$field->set( 'type', 'points' );
			$field->set( 'pluginid', 1 );
			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
			return false;
		}
	}

	// Migrates rating field parameters:
	$plugin									=	new PluginTable();

	if ( $plugin->load( array( 'element' => 'ratingfield' ) ) ) {
		$query								=	"SELECT *"
											.	"\n FROM " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " IN " . $cbDatabase->safeArrayOfStrings( array( 'myrating', 'yourrating' ) );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$fieldParams					=	new Registry( $field->params );

			if ( $field->type == 'myrating' ) {
				$fieldParams->set( 'rating_access', '2' );
			} else {
				if ( $fieldParams->get( 'AllowAnnonymous', 1 ) ) {
					$fieldParams->set( 'rating_access', '3' );
				} else {
					$fieldParams->set( 'rating_access', '4' );
					$fieldParams->set( 'rating_access_exclude', '1' );
				}
			}

			$fieldParams->set( 'rating_number', (string) $fieldParams->get( 'NumStars', 5 ) );

			switch ( (int) $fieldParams->get( 'RatingFraction', 1 ) ) {
				case 1:
					$fieldParams->set( 'rating_step', '1' );
					break;
				case 2:
					$fieldParams->set( 'rating_step', '0.5' );
					break;
				case 3:
					$fieldParams->set( 'rating_step', '0.33' );
					break;
				case 4:
					$fieldParams->set( 'rating_step', '0.25' );
					break;
			}

			$field->set( 'type', 'rating' );
			$field->set( 'pluginid', 1 );
			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
			return false;
		}
	}

	// Migrates verify email field parameters:
	$plugin									=	new PluginTable();

	if ( $plugin->load( array( 'element' => 'cbverifyemail' ) ) ) {
		$query								=	"SELECT *"
											.	"\n FROM " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'type' ) . " IN " . $cbDatabase->safeArrayOfStrings( array( 'emailaddress', 'primaryemailaddress' ) );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$fieldParams					=	new Registry( $field->params );

			$fieldParams->set( 'fieldVerifyInput', ( $fieldParams->get( 'verifyemail_display_reg', 1 ) || $fieldParams->get( 'verifyemail_display_edit', 0 ) ? '1' : '0' ) );
			$fieldParams->set( 'verifyEmailTitle', $fieldParams->get( 'verifyemail_title', '_UE_VERIFY_SOMETHING' ) );

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
			return false;
		}
	}

	// Migrates forum integration parameters:
	$plugin									=	new PluginTable();

	if ( $plugin->load( array( 'element' => 'cb.simpleboardtab' ) ) ) {
		$pluginParams						=	new Registry( $plugin->params );
		$cbForums							=	new PluginTable();

		if ( $cbForums->load( array( 'element' => 'cbforums' ) ) ) {
			$cbForumsParams					=	new Registry( $cbForums->params );

			$cbForumsParams->set( 'k20_ordering', 'cb_forumorder' );
			$cbForumsParams->set( 'k20_viewtype', 'cb_forumview' );
			$cbForumsParams->set( 'k20_signature', 'cb_forumsignature' );

			switch ( (int) $pluginParams->get( 'sidebarMode', 0 ) ) {
				case 1:
					$cbForumsParams->set( 'k20_personaltext', $pluginParams->get( 'sidebarBeginner1' ) );
					$cbForumsParams->set( 'k20_gender', $pluginParams->get( 'sidebarBeginner4' ) );
					$cbForumsParams->set( 'k20_birthdate', $pluginParams->get( 'sidebarBeginner2' ) );
					$cbForumsParams->set( 'k20_location', $pluginParams->get( 'sidebarBeginner3' ) );
					$cbForumsParams->set( 'k20_icq', $pluginParams->get( 'sidebarBeginner5' ) );
					$cbForumsParams->set( 'k20_aim', $pluginParams->get( 'sidebarBeginner6' ) );
					$cbForumsParams->set( 'k20_yim', $pluginParams->get( 'sidebarBeginner7' ) );
					$cbForumsParams->set( 'k20_msn', $pluginParams->get( 'sidebarBeginner8' ) );
					$cbForumsParams->set( 'k20_skype', $pluginParams->get( 'sidebarBeginner9' ) );
					$cbForumsParams->set( 'k20_twitter', $pluginParams->get( 'sidebarBeginner12' ) );
					$cbForumsParams->set( 'k20_facebook', $pluginParams->get( 'sidebarBeginner13' ) );
					$cbForumsParams->set( 'k20_gtalk', $pluginParams->get( 'sidebarBeginner10' ) );
					$cbForumsParams->set( 'k20_myspace', $pluginParams->get( 'sidebarBeginner14' ) );
					$cbForumsParams->set( 'k20_linkedin', $pluginParams->get( 'sidebarBeginner15' ) );
					$cbForumsParams->set( 'k20_delicious', $pluginParams->get( 'sidebarBeginner16' ) );
					$cbForumsParams->set( 'k20_digg', $pluginParams->get( 'sidebarBeginner18' ) );
					$cbForumsParams->set( 'k20_blogspot', $pluginParams->get( 'sidebarBeginner19' ) );
					$cbForumsParams->set( 'k20_flickr', $pluginParams->get( 'sidebarBeginner20' ) );
					$cbForumsParams->set( 'k20_bebo', $pluginParams->get( 'sidebarBeginner21' ) );
					$cbForumsParams->set( 'k20_website', $pluginParams->get( 'sidebarBeginner11' ) );
					break;
				case 2:
					$cbForumsParams->set( 'k20_sidebar_reg', $pluginParams->get( 'sidebarAdvancedExists' ) );
					$cbForumsParams->set( 'k20_sidebar_anon', $pluginParams->get( 'sidebarAdvancedPublic' ) );
					$cbForumsParams->set( 'k20_sidebar_del', $pluginParams->get( 'sidebarAdvancedDeleted' ) );
					break;
			}

			$cbForums->set( 'params', $cbForumsParams->asJson() );

			if ( ! $cbForums->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Plugin [element] failed to migrate. Error: [error]', array( '[element]' => $plugin->element, '[error]' => $cbForums->getError() ) ) . '</div>';
			}
		}

		// Migrate the forum fields to ensure their display mode is set:
		$query								=	"SELECT *"
											.	"\n FROM " . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n WHERE " . $cbDatabase->NameQuote( 'name' ) . " IN " . $cbDatabase->safeArrayOfStrings( array( 'forumsignature', 'forumorder', 'forumview', 'forumrank', 'forumposts', 'forumkarma' ) );
		$cbDatabase->setQuery( $query );
		$fields								=	$cbDatabase->loadObjectList( null, '\CB\Database\Table\FieldTable', array( $cbDatabase ) );

		$forumSignature						=	false;
		$forumOrder							=	false;
		$forumView							=	false;

		/** @var $fields FieldTable[] */
		foreach ( $fields as $field ) {
			$field->set( 'sys', 0 );
			$field->set( 'calculated', 0 );

			$fieldParams					=	new Registry( $field->params );

			switch ( $field->name ) {
				case 'forumsignature':
					$forumSignature			=	true;

					$field->set( 'pluginid', 1 );
					$field->set( 'type', 'textarea' );
					$field->set( 'name', 'cb_forumsignature' );
					$field->set( 'cols', (int) $fieldParams->get( 'fs_signature_cols' ) );
					$field->set( 'rows', (int) $fieldParams->get( 'fs_signature_rows' ) );
					break;
				case 'forumorder':
					$forumOrder				=	true;

					$field->set( 'pluginid', 1 );
					$field->set( 'type', 'select' );
					$field->set( 'name', 'cb_forumorder' );

					$fieldParams->set( '_fieldvalues', array( array( 'fieldtitle' => '0', 'fieldlabel' => 'Oldest' ), array( 'fieldtitle' => '1', 'fieldlabel' => 'Latest' ) ) );
					break;
				case 'forumview':
					$forumView				=	true;

					$field->set( 'pluginid', 1 );
					$field->set( 'type', 'select' );
					$field->set( 'name', 'cb_forumview' );

					$fieldParams->set( '_fieldvalues', array( array( 'fieldtitle' => 'flat', 'fieldlabel' => 'Flat' ), array( 'fieldtitle' => 'threaded', 'fieldlabel' => 'Threaded' ) ) );
					break;
				case 'forumposts':
					$field->set( 'pluginid', (int) $cbForums->get( 'id' ) );
					$field->set( 'type', 'forumstats' );
					$field->set( 'name', 'cb_forumposts' );

					$fieldParams->set( 'forumStatus', 'posts' );
					break;
				case 'forumkarma':
					$field->set( 'pluginid', (int) $cbForums->get( 'id' ) );
					$field->set( 'type', 'forumstats' );
					$field->set( 'name', 'cb_forumkarma' );

					$fieldParams->set( 'forumStatus', 'karma' );
					break;
				case 'forumrank':
					$field->set( 'pluginid', (int) $cbForums->get( 'id' ) );
					$field->set( 'type', 'forumstats' );
					$field->set( 'name', 'cb_forumrank' );

					$fieldParams->set( 'forumStatus', 'rank' );
					break;
			}

			$field->set( 'params', $fieldParams->asJson() );

			if ( ! $field->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Field [name] failed to migrate. Error: [error]', array( '[name]' => $field->name, '[error]' => $field->getError() ) ) . '</div>';
			}
		}

		if ( $cbDatabase->getTableStatus( '#__kunena_users' ) && ( $forumSignature || $forumOrder || $forumView ) ) {
			// If forum profile fields are available to migrate then push their data to CB from Kunena as previous usage did not store in CB directly:
			$forumFieldSet					=	array();

			if ( $forumSignature ) {
				$forumFieldSet[]			=	'c.' . $cbDatabase->NameQuote( 'cb_forumsignature' ) . ' = k.' . $cbDatabase->NameQuote( 'signature' );
			}

			if ( $forumOrder ) {
				$forumFieldSet[]			=	'c.' . $cbDatabase->NameQuote( 'cb_forumorder' ) . ' = k.' . $cbDatabase->NameQuote( 'ordering' );
			}

			if ( $forumView ) {
				$forumFieldSet[]			=	'c.' . $cbDatabase->NameQuote( 'cb_forumview' ) . ' = k.' . $cbDatabase->NameQuote( 'view' );
			}

			$query							=	'UPDATE ' . $cbDatabase->NameQuote( '#__comprofiler' ) . ' AS c'
											.	"\n INNER JOIN " . $cbDatabase->NameQuote( '#__kunena_users' ) . " AS k"
											.	' ON k.' . $cbDatabase->NameQuote( 'userid' ) . ' = c.' . $cbDatabase->NameQuote( 'id' )
											.	"\n SET " . implode( ', ', $forumFieldSet );
			$cbDatabase->setQuery( $query );
			$cbDatabase->query();
		}

		if ( ! cbInstaller_uninstall_plugin( $plugin, $return ) ) {
			return false;
		}
	}

	$pms									=	new PluginTable();

	$pms->load( array( 'element' => 'pms.mypmspro', 'published' => 1 ) );

	if ( $pms->get( 'id', 0, GetterInterface::INT ) ) {
		$pmsParams							=	new Registry( $pms->params );

		if ( $method == 'update' ) {
			// Check if no model has been selected and UddeIM doesn't exist as on an update install we need to force unpublish to prevent PMS suddenly being available:
			if ( ( ! $pmsParams->get( 'general_model', null, GetterInterface::STRING ) ) && ( ! file_exists( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_uddeim/uddeim.php' ) ) ) {
				$pms->set( 'published', 0 );

				// We'll also go ahead and toggle the model to internal as default since UddeIM doesn't exist encase the admin just toggles the published state to start using PMS:
				$pmsParams->set( 'general_model', 'internal' );

				$pms->set( 'params', $pmsParams->asJson() );

				$pms->store();
			}
		} else {
			// If this is a new install then just set the model to internal by default:
			$pmsParams->set( 'general_model', 'internal' );

			$pms->set( 'params', $pmsParams->asJson() );

			$pms->store();
		}
	}

	// Removes legacy about cb menu items from CB Menu tab params
	$tab									=	new TabTable();

	if ( $tab->load( 17 ) ) {
		$tabParams							=	new Registry( $tab->params );

		if ( $tabParams->get( 'firstSubMenuName' ) == '_UE_MENU_ABOUT_CB' ) {
			$tabParams->set( 'firstSubMenuName', '' );
			$tabParams->set( 'firstSubMenuHref', '' );

			if ( ( $tabParams->get( 'firstMenuName' ) == '_UE_MENU_CB' ) && ( ! $tabParams->get( 'secondSubMenuName' ) ) ) {
				$tabParams->set( 'firstMenuName', '' );
			}

			$tab->set( 'params', $tabParams->asJson() );

			if ( ! $tab->store() ) {
				$return						.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Tab [title] failed to migrate. Error: [error]', array( '[title]' => $tab->title, '[error]' => $tab->getError() ) ) . '</div>';
			}
		}
	}

	// Migrate old canvas positions to new positions
	$query						=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_tabs' )
								.	"\n SET " . $cbDatabase->NameQuote( 'position' ) . " = " . $cbDatabase->Quote( 'canvas_stats' )
								.	"\n WHERE " . $cbDatabase->NameQuote( 'position' ) . " IN " . $cbDatabase->safeArrayOfStrings( array( 'canvas_stats_top', 'canvas_stats_middle', 'canvas_stats_bottom' ) );
	$cbDatabase->setQuery( $query );
	$cbDatabase->query();

	$query						=	"UPDATE " . $cbDatabase->NameQuote( '#__comprofiler_tabs' )
								.	"\n SET " . $cbDatabase->NameQuote( 'position' ) . " = " . $cbDatabase->Quote( 'canvas_title' )
								.	"\n WHERE " . $cbDatabase->NameQuote( 'position' ) . " IN " . $cbDatabase->safeArrayOfStrings( array( 'canvas_title_top', 'canvas_title_middle', 'canvas_title_bottom' ) );
	$cbDatabase->setQuery( $query );
	$cbDatabase->query();

	// We need to fix the name fields publish state:
	switch ( $ueConfig['name_style'] ) {
		case 2:
			$nameArray						=	array( 'name' => 0, 'firstname' => 1, 'middlename' => 0, 'lastname' => 1 );
			break;
		case 3:
			$nameArray						=	array( 'name' => 0, 'firstname' => 1, 'middlename' => 1, 'lastname' => 1 );
			break;
		case 1:
		default:
			$nameArray						=	array( 'name' => 1, 'firstname' => 0, 'middlename' => 0, 'lastname' => 0 );
			break;
	}

	foreach ( $nameArray as $name => $published ) {
		$query								=	'UPDATE ' . $cbDatabase->NameQuote( '#__comprofiler_fields' )
											.	"\n SET " . $cbDatabase->NameQuote( 'published' ) . " = " . (int) $published
											.	"\n WHERE " . $cbDatabase->NameQuote( 'name' ) . " = " . $cbDatabase->Quote( $name );
		$cbDatabase->setQuery( $query );
		$cbDatabase->query();
	}

	return true;
}

function cbInstaller_uninstall_plugin( $plugin, &$return ) {
	if ( $plugin->id ) {
		ob_start();
		$plgInstaller	=	new cbInstallerPlugin();
		$installed		=	$plgInstaller->uninstall( $plugin->id, 'com_comprofiler' );
		ob_end_clean();

		if ( ! $installed ) {
			$return		.=	'<div style="font-size:14px;color:red;margin-bottom:10px;">' . CBTxt::P( 'Conflicting plugin [element] failed to uninstall. Error: [error]', array( '[element]' => $plugin->element, '[error]' => $plgInstaller->getError() ) ) . '</div>';

			return false;
		}
	}

	return true;
}

function cbInstaller_create_menutype( $type, $title, $description = null, $properties = array() ) {
	$menuType					=	Table::getInstance( 'MenuType' );

	if ( ! $menuType->load( array( 'menutype' => $type ) ) ) {
		$menuType->menutype		=	$type;
		$menuType->title		=	$title;
		$menuType->description	=	$description;

		if ( $properties ) {
			$menuType->bind( $properties );
		}

		$menuType->check();

		if ( ! $menuType->store() ) {
			return false;
		}
	} else {
		return null;
	}

	return true;
}

function cbInstaller_create_menuitem( $menuType, $title, $url, $properties = array() ) {
	$alias						=	trim( preg_replace( '/_+/', '-', preg_replace( '/\W+/', '', str_replace( ' ', '_', str_replace( '_', '', trim( strtolower( $title ) ) ) ) ) ) );

	$table						=	Table::getInstance( 'Menu' );

	while ( $table->load( array( 'alias' => $alias ) ) ) {
		$matches				=	null;

		if ( preg_match( '#-(\d+)$#', $alias, $matches ) ) {
			$alias				=	preg_replace( '#-(\d+)$#', '-' . ( $matches[1] + 1 ) . '', $alias );
		} else {
			$alias				.=	'-2';
		}
	}

	/** @var \Joomla\CMS\Table\Menu $menu */
	$menu						=	Table::getInstance( 'menu' );
	$menu->menutype				=	$menuType;
	$menu->title				=	$title;
	$menu->alias				=	$alias;
	$menu->link					=	$url;
	$menu->type					=	'component';
	$menu->language				=	'*';
	$menu->published			=	1;

	if ( $properties ) {
		$menu->bind( $properties );
	}

	$menu->check();

	if ( checkJversion( '4.0+' ) ) {
		$menu->client_id		=	0;
		$menu->home				=	0;
		$menu->parent_id		=	1;

		$menu->setLocation( $menu->parent_id, 'last-child' );

		if ( ! $menu->store() ) {
			return false;
		}
	} else {
		if ( ! $menu->store() ) {
			return false;
		} else {
			if ( ! $menu->parent_id ) {
				$menu->parent_id	=	1;
			}

			if ( ! $menu->level ) {
				$menu->level		=	1;
			}

			if ( ! $menu->store() ) {
				return false;
			}
		}
	}

	return true;
}
