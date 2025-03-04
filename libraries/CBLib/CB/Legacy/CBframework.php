<?php
/**
* CBLib, Community Builder Library(TM)
* @version $Id: 6/16/14 9:50 PM $
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CB\Database\Table\UserTable;
use \CBLib\Language\CBTxt;
use CBLib\Registry\GetterInterface;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\User\AfterLoginEvent;
use Joomla\CMS\Event\User\LoginEvent;
use Joomla\CMS\Event\User\LoginFailureEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

defined('CBLIB') or die();

/**
 * CBframework Class implementation
 * CB Framework class
 *
 */
class CBframework
{
	/**
	 * Base framework class
	 * @var JApplication
	 */
	var $_baseFramework;

	/**
	 * php gacl compatible instance
	 * @var CBACL $acl
	 */
	var $acl;
	/**
	 * Legacy ACL Actions bindings to CMS
	 * @var array
	 */
	var $_aclParams					=	array();
	/**
	 * SEF function of the framework
	 * @see JRoute::_
	 * @var callable
	 */
	var $_cmsSefFunction;
	/**
	 * CB's URL Routing = array( 'option' => 'com_comprofiler' )
	 * @var array
	 */
	var $_cbUrlRouting;
	/**
	 * Redirection URL memory
	 * @var string|null
	 */
	var $_redirectUrl				=	null;
	/**
	 * Redirection message memory
	 * @var string|null
	 */
	var $_redirectMessage			=	null;
	/**
	 * Redirection message type memory
	 * @var string
	 */
	var $_redirectMessageType		=	'message';
	/**
	 * Outputing CB document instance:
	 * @var CBdocumentHtml
	 */
	var $document;
	/** @var null|int  */
	protected static $displayedUser	=	null;

	/**
	 * Constructor
	 *
	 * @param $baseFramework
	 * @param $aclParams
	 * @param $cmsSefFunction
	 * @param $cbUrlRouting
	 * @param $getVarFunction
	 * @param $getDocFunction
	 */
	public function __construct( &$baseFramework, &$aclParams, $cmsSefFunction, $cbUrlRouting, $getVarFunction, &$getDocFunction )
	{
		$this->_baseFramework		=&	$baseFramework;
		$this->_aclParams			=&	$aclParams;
		$this->_cmsSefFunction		=	$cmsSefFunction;
		$this->_cbUrlRouting		=	$cbUrlRouting;
		$this->document				=	new CBdocumentHtml( $getDocFunction );
	}

	/**
	 * Returns the global $_CB_framework object
	 * @since 1.7
	 * @deprecated 2.0 use Application::CBFramework()
	 *
	 * @return CBframework
	 */
	public static function & framework( )
	{
		global $_CB_framework;
		return $_CB_framework;
	}

	/**
	 * Returns the global $_CB_database object
	 * @since 1.7
	 * @deprecated 2.0 use Application::Database()
	 *
	 * @return CBdatabase
	 */
	public static function & database( )
	{
		global $_CB_database;
		return $_CB_database;
	}

	/**
	 * Returns a config from gloabal CB Configuration
	 * @deprecated 2.0 use Application::Config()
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function cbConfig( $name )
	{
		global $ueConfig;
		return $ueConfig[$name];
	}

	/**
	 * User login into CMS framework
	 *
	 * @param  string          $username    The username
	 * @param  string|boolean  $password    if boolean FALSE: login without password if possible
	 * @param  int             $rememberMe  1 for "remember-me" cookie method
	 * @param  int             $userId      used for "remember-me" login function only
	 * @param  string          $secretKey   used for "two step authentication" login function only
	 * @return boolean                      Login success
	 */
	public function login( $username, $password, $rememberMe = 0, /** @noinspection PhpUnusedParameterInspection */ $userId = null, $secretKey = null )
	{
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');              // needed for IE6 to accept this anti-spam cookie in higher security setting.

		if ( $password !== false ) {
			return $this->_baseFramework->login( array( 'username' => $username, 'password' => $password, 'secretkey' => $secretKey ), array( 'remember' => $rememberMe ) );
		} else {
			// login without password:
			jimport( 'joomla.user.authentication' );
			// load user plugins:
			PluginHelper::importPlugin( 'user' );
			// get JAuthentication object:
			Authentication::getInstance();

			$response						=	new AuthenticationResponse();

			// prepare our SUCCESS login response including user data:
			$row							=	new UserTable();

			$row->loadByUsername( stripslashes( $username ) );

			$response->type					=	'Joomla';

			if ( $row->get( 'id', 0, GetterInterface::INT ) ) {
				$response->email			=	$row->get( 'email', null, GetterInterface::STRING );
				$response->username			=	$username;
				$response->password			=	null;
				$response->fullname			=	$row->get( 'name', null, GetterInterface::STRING );
				$response->language			=	$row->getUserLanguage();

				$response->status			=	Authentication::STATUS_SUCCESS;
				$response->error_message	=	'';
			} else {
				$response->status			=	Authentication::STATUS_FAILURE;
				$response->error_message	=	'';
			}

			$options						=	array( 'action' => 'core.login.site', 'silent' => true );

			if ( $rememberMe ) {
				$options['remember']		=	$rememberMe;
			}

			// now we attempt user login and check results:
			if ( $response->status === Authentication::STATUS_SUCCESS ) {
				if ( checkJversion( '<5.0' ) ) {
					$results					=	Application::Cms()->triggerEvent( 'onUserLogin', array( (array) $response, $options ) );

					if ( in_array( false, $results, true ) == false ) {
						$options['user']		=	Application::Cms()->getCmsUser()->asCmsUser();
						$options['responseType']=	$response->type;

						// Login was a success so trigger the after login event so cookies can be handled properly:
						Application::Cms()->triggerEvent( 'onUserAfterLogin', array( $options ) );

						return true;
					}
				} else {
					$loginEvent					=	new LoginEvent( 'onUserLogin', [ 'subject' => (array) $response, 'options' => $options ] );

					Application::Cms()->triggerEvent( 'onUserLogin', $loginEvent );

					$results					=	( $loginEvent['result'] ?? [] );

					if ( \in_array( false, $results, true ) == false ) {
						$options['user']			=	Application::Cms()->getCmsUser()->asCmsUser();
						$options['responseType']	=	$response->type;

						// The user is successfully logged in. Run the after login events
						Application::Cms()->triggerEvent( 'onUserAfterLogin', new AfterLoginEvent( 'onUserAfterLogin', [
							'options'	=>	$options,
							'subject'	=>	(array) $response,
						]));

						return true;
					}
				}
			}

			if ( checkJversion( '<5.0' ) ) {
				// Trigger onUserLoginFailure Event.
				Application::Cms()->triggerEvent( 'onUserLoginFailure', array( (array) $response ) );
			} else {
				// Trigger onUserLoginFailure Event.
				Application::Cms()->triggerEvent( 'onUserLoginFailure', new LoginFailureEvent( 'onUserLoginFailure', [
					'subject'	=>	(array) $response,
					'options'	=>	$options,
				]));
			}

			return false;
		}
	}

	/**
	 * Logs-out the user
	 *
	 * @return void
	 */
	public function logout( )
	{
		// needed for IE6 to accept this anti-spam cookie in higher security setting:
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

		// Perform CMS logout, which will destroy the session:
		$this->_baseFramework->logout();

		// Save the new session to storage:
		$this->_baseFramework->checkSession();
	}

	/**
	 * Gets a global CMS config
	 *
	 * @param  string  $config  Config to get
	 * @return string           The config if exists
	 */
	public function getCfg( $config )
	{
		switch ( $config ) {
			case 'absolute_path':
				return JPATH_SITE;

			case 'live_site':
				if ( $this->getUi() == 1 ) {
					$liveSite	=	Uri::base();
				} else {
					$liveSite	=	preg_replace( '%administrator/$%', '', Uri::base() );
				}

				if ( substr( $liveSite, -1, 1 ) == '/' ) {
					return substr( $liveSite, 0, -1 );
				} else {
					return $liveSite;
				}

			case 'lang':
				return Application::Cms()->getLanguageName();

			case 'lang_tag':
				return Application::Cms()->getLanguageTag();

			case 'uniquemail':
				return '1';

			case 'frontend_userparams':
			case 'allowUserRegistration':
			case 'useractivation':
			case 'new_usertype':
			case 'guest_usergroup':
			case 'reset_time':
			case 'reset_count':
				$setting			=	(int) ComponentHelper::getParams( 'com_users' )->get( $config );
				if ( ( $config == 'new_usertype' ) && ! $setting ) {
					$setting		=	2;	// 'Registered' default UserGroupId as last recourse
				} elseif ( ( $config == 'guest_usergroup' ) && ! $setting ) {
					$setting		=	1;	// 'Guest' default UserGroupId as last recourse
				}

				return $setting;

			case 'hits':
			case 'vote':
				return ComponentHelper::getParams( 'com_content' )->get( 'show_' . $config );

			case 'dirperms':
			case 'fileperms':
				return '';		//TODO: these two missing configs should one day go to CB

			// CB-Specific config params:
			case 'tmp_path':
				$abs_path			=	$this->getCfg('absolute_path');
				$tmpDir				=	$abs_path . '/tmp';
				if ( @is_dir( $tmpDir ) && @is_writable( $tmpDir ) ) {
					return $tmpDir;
				}
				$tmpDir				=	$abs_path . '/media';
				if ( @is_dir( $tmpDir ) && @is_writable( $tmpDir ) ) {
					return $tmpDir;
				}
				// First try the new PHP 5.2.1+ function:
				if ( function_exists( 'sys_get_temp_dir' ) ) {
					$tmpDir			=	@sys_get_temp_dir();
					if ( @is_dir( $tmpDir ) && @is_writable( $tmpDir ) ) {
						return $tmpDir;
					}
				}
				// Based on http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/
				$varsToTry			=	array( 'TMP', 'TMPDIR', 'TEMP' );
				foreach ( $varsToTry as $v ) {
					if ( ! empty( $_ENV[$v] ) ) {
						$tmpDir		=	realpath( $v );
						if ( @is_dir( $tmpDir ) && @is_writable( $tmpDir ) ) {
							return $tmpDir;
						}
					}
				}
				// Try the CMS cache directory and other directories desperately:
				$tmpDirToTry		=	array( $this->getCfg( 'cachepath' ), realpath( '/tmp' ), $abs_path.'/tmp', $abs_path.'/images', $abs_path.'/images/stories', $abs_path.'/images/comprofiler' );
				foreach ( $tmpDirToTry as $tmpDir ) {
					if ( @is_dir( $tmpDir ) && @is_writable( $tmpDir ) ) {
						return $tmpDir;
					}
				}

				return null;

			case 'offset':
				static $jOffset				=	null;
				if ( $jOffset === null ) {
					$dateTimeZoneUTC		=	new DateTimeZone( 'UTC' );
					$dateTimeZoneCurrent	=	new DateTimeZone( Application::Cms()->getConfig()->get( 'offset' ) );
					$dateTimeUTC			=	new DateTime( 'now', $dateTimeZoneUTC );
					$timeOffset				=	$dateTimeZoneCurrent->getOffset( $dateTimeUTC );
					$jOffset				=	$timeOffset / 3600;
				}

				return $jOffset;

			/** @noinspection PhpMissingBreakStatementInspection */
			case 'user_timezone':
				$timeZone = Application::Cms()->getCmsUser()->getTimezone();

				if ($timeZone ) {
					return $timeZone;
				}
			// Fall-through on purpose here: When user has no configured timezone, return system default one:

			case 'system_timezone':
				return Application::Cms()->getConfig()->get( 'offset' );

			case 'mailonline':
				// Default needed as if first install config is not set so it stops emails in CB, but not in Joomla:
				return Application::Cms()->getConfig()->get( 'mailonline', 1 );

			default:
				break;
		}

		return Application::Cms()->getConfig()->get( $config );

	}

	/**
	 * Returns the UI (admin: 2, front-end: 1)
	 * @deprecated 2.0  Use Application::Cms()->getClientId() which returns (0 = front, 1 = admin)
	 *
	 * @return int
	 */
	public function getUi( )
	{
		return ( Application::Cms()->getClientId() ? 2 : 1 );
	}

	/**
	 * Returns user id (0: guest)
	 * @deprecated 2.0  Use Application::MyUser()->getUserId()
	 *
	 * @return int
	 */
	public function myId( )
	{
		return Application::MyUser()->getUserId();
	}

	/**
	 * Returns the username
	 * @deprecated 2.0  Use Application::MyUser()->get( 'username' )
	 *
	 * @return string
	 */
	public function myUsername( )
	{
		return Application::MyUser()->get( 'username' );
	}

	/**
	 * Returns the usertype
	 * @deprecated 2.0  Use Application::MyUser()->getAuthorisedGroups( false )
	 *
	 * @return string
	 */
	public function myUserType( )
	{
		$user			=	Application::MyUser();

		if ( $user->isSuperAdmin() ) {
			$usertype	=	'Super Users';
		} elseif ( $user->isGlobalModerator() ) {
			$usertype	=	'Manager';
		} elseif ( $user->getUserId() ) {
			$usertype	=	'Registered';
		} else {
			$usertype	=	'Public';
		}

		return $usertype;
	}

	/**
	 * Returns the view access level
	 * @deprecated 2.0  Use Application::MyUser()->getAuthorisedViewLevels()
	 *
	 * @return string
	 */
	public function myCmsGid( )
	{
		$levels		=	Application::MyUser()->getAuthorisedViewLevels();

		return array_pop( $levels );
	}

	/**
	 * @deprecated 2.0  Use Application::MyUser()->isAuthorizedToPerformActionOnAsset( $action, $asset ) or Application::User( (int) $userId )->isAuthorizedToPerformActionOnAsset( $action, $asset )
	 *             Unused in CB 2.0
	 *
	 * @return array
	 */
	public function _cms_all_acl( )
	{
		return $this->_aclParams;
	}

	/**
	 * @deprecated 2.0  Use Application::MyUser()->isAuthorizedToPerformActionOnAsset( $action, $asset ) or Application::User( (int) $userId )->isAuthorizedToPerformActionOnAsset( $action, $asset )
	 *             Unused in CB 2.0
	 *
	 * @param  string  $action
	 * @return mixed
	 */
	private function _cms_acl( $action )
	{
		if ( isset( $this->_aclParams[$action] ) ) {
			return $this->_aclParams[$action];
		}
		trigger_error( 'acl_check undefined', E_USER_ERROR );
		exit;
	}

	/**
	 * Checks rights of user $userType to perform a $action.
	 *
	 * @deprecated 2.0  Use Application::MyUser()->isAuthorizedToPerformActionOnAsset( $action, $asset ) or Application::User( (int) $userId )->isAuthorizedToPerformActionOnAsset( $action, $asset )
	 *             Unused in CB 2.0
	 *
	 * @param  string  $action  'canEditUsers', 'canBlockUsers', 'canManageUsers', 'canReceiveAdminEmails','canInstallPlugins'
	 *                          'canEditOwnContent', 'canAddAllContent', 'canEditAllContent', 'canPublishContent'
	 * @param  string  $userTye
	 * @return boolean           TRUE: Yes, user can do that, FALSE: forbidden.
	 */
	public function check_acl( $action, $userTye )
	{
		$aclParams						=	$this->_cms_acl( $action );
		$aclParams[3]					=	$userTye;
		return ( true == call_user_func_array( array( $this->acl, 'acl_check' ), $aclParams ) );
	}

	/**
	 * Gets the output character set (UTF-8)
	 *
	 * @return string
	 */
	public function outputCharset( )
	{
		return 'UTF-8';
	}

	/**
	 * Gets the routing of CB, like:  array( 'option' => 'com_comprofiler' )
	 * @deprecated 2.0 use \CBLib\Controller\RouterInterface
	 * @see \CBLib\Controller\RouterInterface::getMainRoutingArgs()
	 *
	 * @return array  array( 'option' => 'com_comprofiler' )
	 */
	public function getUrlRoutingOfCb( )
	{
		return $this->_cbUrlRouting;
	}

	/**
	 * Gets the request variable $name
	 *
	 * @param  string        $name     Name of variable
	 * @param  string        $default  Default value
	 * @return string|array
	 * @deprecated 2.0 use Application::Input()->get( $name, $default = null )
	 */
	public function getRequestVar( $name, $default = null )
	{
		return Application::Input()->get( $name, $default );
	}

	/**
	 * Sets the redirect url, and optionally the message (with message-type)
	 *
	 * @param  string  $url          URL to redirect later
	 * @param  string  $message      HTML message to display
	 * @param  string  $messageType  Message type ('message' or 'error')
	 * @return void
	 */
	public function setRedirect( $url, $message = null, $messageType = 'message' )
	{
		$this->_redirectUrl				=	$url;
		$this->_redirectMessage			=	$message;
		$this->_redirectMessageType		=	$messageType;
	}

	/**
	 * Redirects immedately (no return)
	 *
	 * @param  string  $url          URL to redirect later
	 * @param  string  $message      HTML message to display
	 * @param  string  $messageType  Message type ('message' or 'error')
	 */
	public function redirect( $url = null, $message = null, $messageType = null )
	{
		if ( $url ) {
			$this->_redirectUrl			=	$url;
		}
		if ( $message !== null ) {
			$this->_redirectMessage		=	$message;
		}
		if ( $messageType !== null ) {
			$this->_redirectMessageType	=	$messageType;
		}
		$this->enqueueMessage( $this->_redirectMessage, $this->_redirectMessageType );
		$this->_baseFramework->redirect( $this->_redirectUrl );
	}

	/**
	 * Enqueues into the session a message to display on next displayed page
	 *
	 * @param  string  $message      HTML message to display
	 * @param  string  $messageType  Message type ('message' or 'error')
	 */
	public function enqueueMessage( $message = null, $messageType = 'message' )
	{
		if ( trim( $message ) != '' ) {
			if ( method_exists( $this->_baseFramework, 'enqueueMessage' ) ) {
				$this->_baseFramework->enqueueMessage( $message, $messageType );
			} else {
				echo '<div class="' . htmlspecialchars( $messageType ) . '">' . $message . '</div>';
			}
		}
	}

	/**
	 * Returns the messages queue (optionally for messsage-type $type)
	 *
	 * @param  string|null  $type  [optional] Message type ('message' or 'error')
	 * @return array
	 */
	public function getMessageQueue( $type = null )
	{
		$queue						=	array();

		if ( method_exists( $this->_baseFramework, 'getMessageQueue' ) ) {
			$messages				=	$this->_baseFramework->getMessageQueue();

			if ( $messages ) {
				if ( $type ) foreach ( $messages as $message ) {
					if ( $message['type'] == $type ) {
						$queue[]	=	$message;
					}
				} else {
					$queue			=	$messages;
				}
			}
		}

		return $queue;
	}

	/**
	 * changes "index.php?....." into what's needed for the CMS
	 * @since CB 1.2.3
	 *
	 * @param  string   $link       This URL should be htmlspecialchared already IF $htmlSpecials = TRUE, but NOT if = FALSE
	 * @param  boolean  $htmlSpecials
	 * @param  string   $format         'html', 'component', 'raw', 'rawrel' (same as 'raw' in backend for now)
	 * @return string
	 */
	public function backendUrl( $link, $htmlSpecials = true, $format = 'html' )
	{
		if ( $format == 'component' ) {
			$link	.=	( $htmlSpecials ? '&amp;' : '&' ) . 'tmpl=' . $format;
		}

		if ( $format == 'rawrel' ) {
			$format	=	'raw';
		}

		if ( $format == 'raw' ) {
			$link	.=	( $htmlSpecials ? '&amp;' : '&' ) . 'format=' . $format;
		}

		return $link;
	}

	/**
	 * Converts an URL to an absolute URI with or without SEF format
	 *
	 * @param  string  $string        The relative URL
	 * @param  bool    $htmlSpecials  TRUE (default): apply htmlspecialchars to sefed URL, FALSE: don't.
	 * @param  string  $format        'html', 'component', 'raw', 'rawrel'		(added in CB 1.2.3)
	 * @param  int     $ssl           1 force HTTPS, 0 leave as is, -1 for HTTP		(added in CB 1.10.0)
	 * @param  bool    $sef           TRUE (default): apply SEF if possible, FALSE: don't SEF		(added in CB 1.10.0)
	 * @return string                 The absolute URL (relative if rawrel)
	 */
	public function cbSef( $string, $htmlSpecials = true, $format = 'html', $ssl = 0, $sef = true )
	{
		if ( $format == 'html' ) {
			if ( ( $string == 'index.php' ) || ( $string == '' ) ) {
				$uri				=	$this->getCfg( 'live_site' ) . '/';
			} else {
				if ( $sef ) {
					if ( ( $this->getUi() == 1 ) && ( ( substr( $string, 0, 9 ) == 'index.php' ) || ( $string[0] == '?' ) ) && is_callable( $this->_cmsSefFunction ) && ( ! ( ( checkJversion() == 0 ) && ( strpos( $string, '[' ) !== false ) ) ) ) {
						if ( $string == 'index.php?option=com_comprofiler' ) {
							$string	.=	'&view=userprofile';
						}
						$uri		=	call_user_func_array( $this->_cmsSefFunction, array( cbUnHtmlspecialchars( $string ) ) );
					} else {
						$uri		=	$string;
					}
				} else {
					$uri			=	$string;
				}

				if ( ! in_array( substr( $uri, 0, 4 ), array( 'http', 'java' ) ) ) {
					if ( ( strlen( $uri ) > 1 ) && ( $uri[0] == '/' ) ) {
						// we got special case of an absolute link without live_site, but an eventual subdirectory of live_site is included...need to strip live_site:
						$matches	=	array();

						if ( ( preg_match( '!^([^:]+://)([^/]+)(/.*)$!', $this->getCfg( 'live_site' ), $matches ) ) && ( $matches[3] == substr( $uri, 0, strlen( $matches[3] ) ) ) ) {
							$uri	=	$matches[1] . $matches[2] . $uri;		// 'http://' . 'site.com' . '/......
						} else {
							$uri	=	$this->getCfg( 'live_site' ) . $uri;
						}
					} else {
						$uri		=	$this->getCfg( 'live_site' ) . '/' . $uri;
					}
				}
			}
		} else /* if ( $format == 'raw' || $format == 'rawrel' || $format == 'component' ) */ {
			if ( substr( $string, 0, 9 ) == 'index.php' ) {
				if ( $format == 'rawrel' ) {
					$format			=	'raw';
					$uri			=	'';
				} else {
					$uri			=	$this->getCfg( 'live_site' ) . '/';
				}

				if ( $format == 'component' ) {
					$uri			.=	$string . '&amp;tmpl=' . $format;
				} else {
					$uri			.=	$string . '&amp;format=' . $format;
				}
			} else {
				$uri				=	$string;
			}
		}

		if ( ! $htmlSpecials ) {
			$uri					=	cbUnHtmlspecialchars( $uri );
		} else {
			$uri					=	htmlspecialchars( cbUnHtmlspecialchars( $uri ) );	// quite a few sefs, including Mambo and Joomla's non-sef are buggy.
		}

		if ( (int) $ssl === 1 ) {
			$uri					=	str_replace( 'http://', 'https://', $uri );
		} elseif ( (int) $ssl === -1 ) {
			$uri					=	str_replace( 'https://', 'http://', $uri );
		}

		return $uri;
	}

	/**
	 * Returns the current page Itemid and optionally only if the query string matches the menu items query
	 *
	 * @param string $query
	 * @return int
	 * @since 1.7
	 */
	public function itemid( $query = null )
	{
		static $cache							=	array();

		if ( ! isset( $cache[$query] ) ) {
			$currentMenu						=	Factory::getApplication()->getMenu()->getActive();
			$itemId								=	0;
			$parts								=	array();

			if ( $query ) {
				parse_str( $query, $parts );
			}

			// Check if the current active menu item has an itemid:
			if ( $currentMenu && isset( $currentMenu->id ) ) {
				// If there's a query string lets be sure the menu item contains it exactly:
				if ( $parts ) {
					if ( isset( $currentMenu->query ) ) {
						$mismatch				=	false;

						foreach ( $parts as $k => $v ) {
							// For B/C task since Joomla does not store task menu items, but stores view:
							if ( $k == 'task' ) {
								$k				=	'view';
							}

							if ( ( ! isset( $currentMenu->query[$k] ) ) || ( $currentMenu->query[$k] != $v ) ) {
								$mismatch		=	true;
								break;
							}
						}

						if ( ! $mismatch ) {
							$itemId				=	(int) $currentMenu->id;
						}
					}
				} else {
					$itemId						=	(int) $currentMenu->id;
				}
			}

			// Current menu item doesn't have an itemid or the query mismatched so lets test the current uri instead:
			if ( ! $itemId ) {
				$currentUri						=	Uri::getInstance();

				if ( $currentUri && $currentUri->hasVar( 'Itemid' ) ) {
					// If there's a query string lets be sure the current uri contains it exactly:
					if ( $parts ) {
						if ( $currentUri->getQuery( true ) ) {
							$mismatch			=	false;

							foreach ( $parts as $k => $v ) {
								if ( ( ! $currentUri->hasVar( $k ) ) || ( $currentUri->getVar( $k ) != $v ) ) {
									$mismatch	=	true;
									break;
								}
							}

							if ( ! $mismatch ) {
								$itemId			=	(int) $currentUri->getVar( 'Itemid' );
							}
						}
					} else {
						$itemId					=	(int) $currentUri->getVar( 'Itemid' );
					}
				}
			}

			$cache[$query]						=	$itemId;
		}

		return $cache[$query];
	}

	/**
	 * Returns a CB profile view specific url from user id
	 *
	 * @param null $userId
	 * @param bool $htmlspecialchars
	 * @param null $tab
	 * @param string $format 'html', 'component', 'raw', 'rawrel'
	 * @param int $ssl '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param array $variables $variables array of variables to append to the url
	 * @return string
	 */
	public function userProfileUrl( $userId = null, $htmlspecialchars = true, $tab = null, $format = 'html', $ssl = 0, $variables = array() )
	{
		if ( $userId && ( $userId == $this->myId() ) ) {
			$userId					=	null;
		}

		$urlVariables				=	array();

		if ( $userId ) {
			$urlVariables['user']	=	(int) $userId;
		}

		if ( $tab ) {
			$urlVariables['tab']	=	$tab;
		}

		if ( $variables && is_array( $variables ) ) {
			$variables				=	array_merge( $urlVariables, $variables );
		} else {
			$variables				=	$urlVariables;
		}

		return $this->viewUrl( 'userprofile', $htmlspecialchars, $variables, $format, $ssl );
	}

	/**
	 * Returns a CB profile edit specific url from user id
	 *
	 * @param null $userId
	 * @param bool $htmlspecialchars
	 * @param null $tab
	 * @param string $format 'html', 'component', 'raw', 'rawrel'
	 * @param int $ssl '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param array $variables $variables array of variables to append to the url
	 * @return string
	 */
	public function userProfileEditUrl( $userId = null, $htmlspecialchars = true, $tab = null, $format = 'html', $ssl = 0, $variables = array() )
	{
		$urlVariables				=	array();

		if ( $userId ) {
			$urlVariables['uid']	=	(int) $userId;
		}

		if ( $tab ) {
			$urlVariables['tab']	=	$tab;
		}

		if ( $variables && is_array( $variables ) ) {
			$variables				=	array_merge( $urlVariables, $variables );
		} else {
			$variables				=	$urlVariables;
		}

		return $this->viewUrl( 'userdetails', $htmlspecialchars, $variables, $format, $ssl );
	}

	/**
	 * Returns a uselrist specific url from list id
	 *
	 * @param null $listId
	 * @param bool $htmlspecialchars
	 * @param null $searchMode '0' for list only, '1' for search only, '2' for search and list
	 * @param string $format 'html', 'component', 'raw', 'rawrel'
	 * @param int $ssl '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param array $variables $variables array of variables to append to the url
	 * @return string
	 */
	public function userProfilesListUrl( $listId = null, $htmlspecialchars = true, $searchMode = null, $format = 'html', $ssl = 0, $variables = array() )
	{
		$urlVariables						=	array();

		if ( $listId ) {
			$urlVariables['listid']		=	(int) $listId;
		}

		if ( $searchMode ) {
			$urlVariables['searchmode']	=	(int) $searchMode;
		}

		if ( $variables && is_array( $variables ) ) {
			$variables					=	array_merge( $urlVariables, $variables );
		} else {
			$variables					=	$urlVariables;
		}

		return $this->viewUrl( 'userslist', $htmlspecialchars, $variables, $format, $ssl );
	}

	/**
	 * Returns a CB task specific url
	 *
	 * @param string|array $task             string: view, array: full url params for string itemid parsing (e.g. array( 'view' => 'view', 'func' => 'test' ) or array( 'view', 'func' => 'test' ))
	 *                                       'manageconnections', 'registers', 'lostpassword', 'login', 'logout', 'moderateimages', 'moderatereports', 'moderatebans', 'viewreports', 'processreports', 'pendingapprovaluser'
	 * @param bool         $htmlspecialchars
	 * @param array        $variables        array of variables to append to the url; if $task is an array they will also be prepended as if $variables
	 * @param string       $format           'html', 'component', 'raw', 'rawrel'
	 * @param int          $ssl              '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param bool         $raw              TRUE: without sef, FALSE: with sef
	 * @return string
	 */
	public function viewUrl( $task, $htmlspecialchars = true, $variables = array(), $format = 'html', $ssl = 0, $raw = false )
	{
		global $_CB_database;

		if ( ! $task ) {
			$task					=	'userprofile';
		}

		$additional					=	null;

		if ( is_array( $task ) ) {
			$view					=	array();

			// If view => view isn't supplied treat first value as view:
			if ( ! isset( $task['view'] ) ) {
				$view['view']		=	array_shift( $task );
			}

			$view					=	array_merge( $view, $task );

			if ( $variables && is_array( $variables ) ) {
				$urlVariables		=	array_merge( $view, $variables );
			} else {
				$urlVariables		=	$view;
			}

			// Lets build the string of additional URL parameters to check for when parsing Itemid:
			foreach ( $view as $name => $value ) {
				if ( ( ! $name ) || ( $name == 'view' ) || ( $value === null ) ) {
					continue;
				}

				if ( is_array( $value ) ) {
					$value			=	implode( '|*|', $value );
				}

				$additional			.=	'&' . $_CB_database->getEscaped( $name, true ) . '=' . $_CB_database->getEscaped( $value, true );
			}
		} else {
			$view					=	array( 'view' => $task );

			if ( $variables && is_array( $variables ) ) {
				$urlVariables		=	array_merge( $view, $variables );
			} else {
				$urlVariables		=	$view;
			}
		}

		$extra						=	null;

		if ( $urlVariables && is_array( $urlVariables ) ) foreach ( $urlVariables as $name => $value ) {
				if ( ( ! $name ) || ( $value === null ) ) {
					continue;
				}

			if ( is_array( $value ) ) {
				$value				=	implode( '|*|', $value );
			}

			$extra					.=	'&' . urlencode( $name ) . '=' . urlencode( $value );
		}

		return $this->cbSef( 'index.php?option=com_comprofiler' . $extra . getCBprofileItemid( false, $urlVariables['view'], $additional ), $htmlspecialchars, $format, $ssl, ( ! $raw ) );
	}

	/**
	 * Returns a raw CB task specific url (without SEF)
	 *
	 * @param string|array $task             string: view, array: full url params for string itemid parsing (e.g. array( 'view' => 'view', 'func' => 'test' ) or array( 'view', 'func' => 'test' ))
	 *                                       'manageconnections', 'registers', 'lostpassword', 'login', 'logout', 'moderateimages', 'moderatereports', 'moderatebans', 'viewreports', 'processreports', 'pendingapprovaluser'
	 * @param bool         $htmlspecialchars
	 * @param array        $variables        array of variables to append to the url; if $task is an array they will also be prepended as if $variables
	 * @param string       $format           'html', 'component', 'raw', 'rawrel'
	 * @param int          $ssl              '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @return string
	 */
	public function rawViewUrl( $task, $htmlspecialchars = true, $variables = array(), $format = 'html', $ssl = 0 )
	{
		// Reduces duplicate coding, but makes it easier to generate raw URLs without providing full stack of params:
		return $this->viewUrl( $task, $htmlspecialchars, $variables, $format, $ssl, true );
	}

	/**
	 * Returns a CB task specific backend url
	 *
	 * @param string $task 'showusers', 'showTab', 'showField', 'showLists', 'showPlugins', 'tools', 'showconfig', 'pluginmenu', 'editPlugin'
	 * @param bool $htmlspecialchars
	 * @param array $variables array of variables to append to the url
	 * @param string $format 'html', 'component', 'raw', 'rawrel'
	 * @return string
	 */
	public function backendViewUrl( $task, $htmlspecialchars = true, $variables = array(), $format = 'html' )
	{
		$extra				=	null;

		if ( $variables && is_array( $variables ) ) foreach ( $variables as $name => $value ) {
			if ( $name && ( $value !== null ) ) {
				if ( is_array( $value ) ) {
					$value	=	implode( '|*|', $value );
				}

				$extra		.=	'&' . urlencode( $name ) . '=' . urlencode( $value );
			}
		}

		return $this->backendUrl( 'index.php?option=com_comprofiler' . ( $task ? '&view=' . urlencode( $task ) : null ) . $extra, $htmlspecialchars, $format );
	}

	/**
	 * Returns a field class URL from field name (e.g. avatar)
	 *
	 * @param string|array $field            string: field name, array: full url params for string itemid parsing (e.g. array( 'field' => 'field', 'func' => 'test' ) or array( 'field', 'func' => 'test' ))
	 * @param bool         $htmlspecialchars
	 * @param array        $variables        array of variables to append to the url; if $field is an array they will also be prepended as if $variables
	 * @param string       $format           'html', 'component', 'raw', 'rawrel'
	 * @param int          $ssl              '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param bool         $raw              TRUE: without sef, FALSE: with sef
	 * @return string
	 */
	public function fieldClassUrl( $field, $htmlspecialchars = true, $variables = array(), $format = 'html', $ssl = 0, $raw = false )
	{
		if ( is_array( $field ) ) {
			$view					=	array( 'view' => 'fieldclass' );

			// If field => field isn't supplied treat first value as field:
			if ( ! isset( $field['field'] ) ) {
				$view['field']		=	array_shift( $field );
			}

			$view					=	array_merge( $view, $field );
		} else {
			$view					=	array( 'view' => 'fieldclass', 'field' => $field );
		}

		return $this->viewUrl( $view, $htmlspecialchars, $variables, $format, $ssl, $raw );
	}

	/**
	 * Returns a tab class URL from tab class (e.g. cbblogsTab)
	 *
	 * @param string|array $tab              string: tab class, array: full url params for string itemid parsing (e.g. array( 'tab' => 'tab', 'func' => 'test' ) or array( 'tab', 'func' => 'test' ))
	 * @param bool         $htmlspecialchars
	 * @param array        $variables        array of variables to append to the url; if $tab is an array they will also be prepended as if $variables
	 * @param string       $format           'html', 'component', 'raw', 'rawrel'
	 * @param int          $ssl              '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param bool         $raw              TRUE: without sef, FALSE: with sef
	 * @return string
	 */
	public function tabClassUrl( $tab, $htmlspecialchars = true, $variables = array(), $format = 'html', $ssl = 0, $raw = false )
	{
		if ( is_array( $tab ) ) {
			$view					=	array( 'view' => 'tabclass' );

			// If tab => tab isn't supplied treat first value as tab:
			if ( ! isset( $tab['tab'] ) ) {
				$view['tab']		=	array_shift( $tab );
			}

			$view					=	array_merge( $view, $tab );
		} else {
			$view					=	array( 'view' => 'tabclass', 'tab' => $tab );
		}

		return $this->viewUrl( $view, $htmlspecialchars, $variables, $format, $ssl, $raw );
	}

	/**
	 * Returns a plugin class URL from plugin element (e.g. cbblogs)
	 *
	 * @param string|array $plugin           string: plugin element, array: full url params for string itemid parsing (e.g. array( 'plugin' => 'element', 'func' => 'test' ) or array( 'element', 'func' => 'test' ))
	 * @param bool         $htmlspecialchars
	 * @param array        $variables        array of variables to append to the url; if $plugin is an array they will also be prepended as if $variables
	 * @param string       $format           'html', 'component', 'raw', 'rawrel'
	 * @param int          $ssl              '1' force HTTPS, '0' leave as is, '-1' force HTTP
	 * @param bool         $raw              TRUE: without sef, FALSE: with sef
	 * @return string
	 */
	public function pluginClassUrl( $plugin, $htmlspecialchars = true, $variables = array(), $format = 'html', $ssl = 0, $raw = false )
	{
		if ( is_array( $plugin ) ) {
			$view					=	array( 'view' => 'pluginclass' );

			// If plugin => element isn't supplied treat first value as element:
			if ( ! isset( $plugin['plugin'] ) ) {
				$view['plugin']		=	array_shift( $plugin );
			}

			$view					=	array_merge( $view, $plugin );
		} else {
			$view					=	array( 'view' => 'pluginclass', 'plugin' => $plugin );
		}

		return $this->viewUrl( $view, $htmlspecialchars, $variables, $format, $ssl, $raw );
	}

	/**
	 * Returns a backend plugin menu URL from plguin id
	 *
	 * @param string $plugin plugin id
	 * @param bool $htmlspecialchars
	 * @param array $variables array of variables to append to the url
	 * @param string $format 'html', 'component', 'raw', 'rawrel'
	 * @return string
	 */
	public function backendPluginMenuUrl( $plugin, $htmlspecialchars = true, $variables = array(), $format = 'html' )
	{
		$urlVariables					=	array();

		if ( $plugin ) {
			$urlVariables['pluginid']	=	$plugin;
		}

		if ( $variables && is_array( $variables ) ) {
			$variables					=	array_merge( $urlVariables, $variables );
		} else {
			$variables					=	$urlVariables;
		}

		return $this->backendViewUrl( 'pluginmenu', $htmlspecialchars, $variables, $format );
	}

	/**
	 * Gets the CMS User object
	 *
	 * @param  int    $cmsUserId
	 * @return JUser
	 * @throws UnexpectedValueException
	 *
	 * @deprecated 2.8.3
	 * @see Application::Cms()->getCmsUser( $cmsUserId )->asCmsUser()
	 */
	public function & _getCmsUserObject( $cmsUserId = null )
	{
		$obj					=	new User();

		if ( $cmsUserId ) {
			if ( ! $obj->load( (int) $cmsUserId ) ) {
				throw new UnexpectedValueException( CBTxt::T( 'UNABLE_TO_LOAD_USER_ID', 'User id failed to load: [user_id]', array( '[user_id]' => (int) $cmsUserId ) ) );
			}
		}

		return $obj;
	}

	/**
	 * Gets the CMS user id selected by $field having $value
	 *
	 * @param  string    $field  CMS Field name
	 * @param  string    $value  CMS Field value to select upon
	 * @return int|null          User-id or null if not found or not unique
	 */
	public function getUserIdFrom( $field, $value )
	{
		global $_CB_database;

		$_CB_database->setQuery( 'SELECT id FROM #__users u WHERE u.' . $_CB_database->NameQuote( $field ) . ' = ' . $_CB_database->Quote( $value ), 0, 1 );
		$results		=	$_CB_database->loadResultArray();
		if ( $results && ( count( $results ) == 1 ) ) {
			return $results[0];
		}
		return null;
	}

	/**
	 * Returns is user is "online" and last time online of the user
	 *
	 * @param  int  $userId
	 * @return int|null      last online time of the user
	 */
	public function userOnlineLastTime( $userId )
	{
		static $cache				=	array();
		if ( ! array_key_exists( (int) $userId, $cache ) ) {	// isset doesn't work as offline users return null
			global $_CB_database;
			$_CB_database->setQuery( 'SELECT MAX(time) FROM #__session WHERE userid = ' . (int) $userId . ' AND guest = 0');
			$cache[(int) $userId]	=	$_CB_database->loadResult();
		}
		return $cache[(int) $userId];
	}

	/**
	 * Displays the CMS HTML-editor
	 * @see JEditor::display
	 *
	 * @param   string   $fieldName     The control name.
	 * @param   string   $content     The contents of the text area.
	 * @param   string   $width    The width of the text area (px or %).
	 * @param   string   $height   The height of the text area (px or %).
	 * @param   integer  $col      The number of columns for the textarea.
	 * @param   integer  $row      The number of rows for the textarea.
	 * Since 2.0:
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $id       An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param   string   $asset    The object asset
	 * @param   object   $author   The author.
	 * @param   array    $params   Associative array of editor parameters.
	 *
	 * @return  string
	 *
	 * @deprecated 2.5.0
	 * @see \CBLib\Cms\CmsInterface::displayCmsEditor() use it with Application::Cms()->displayCmsEditor()
	 */
	public function displayCmsEditor( $fieldName, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array() )
	{
		return Application::Cms()->displayCmsEditor( $fieldName, $content, $width, $height, $col, $row, $buttons, $id, $asset, $author, $params );
	}

	/**
	 * Saves the CMS HTML-editor
	 * @see JEditor::save
	 *
	 * @param  string   $fieldName     The control name.
	 * @param  int      $outputId
	 * @param  boolean  $outputOnce
	 * @return string
	 *
	 * @deprecated 2.5.0, removed 3.0   No need to use this anymore since Joomla 3.
	 */
	public function saveCmsEditorJS( $fieldName, $outputId = 0, $outputOnce = true )
	{
		return '';
	}

	/**
	 * Returns the start time of CB's pageload
	 *
	 * @return int     Unix-time in seconds
	 */
	public function now( ) {
		return Application::Application()->getStartTime();
	}

	/**
	 * Returns date( 'Y-m-d H:i:s' or 'Y-m-d' ) but in UTC or if database time is not UTC in the CMS, taking in account system offset for database's NOW()
	 *
	 * @param  boolean  $time  TRUE if time should be output too ('Y-m-d H:i:s'), FALSE if not ('Y-m-d')
	 * @return string 'YYYY-MM-DD HH:mm:ss' if $time = TRUE, 'YYYY-MM-DD' if $time = FALSE
	 * @deprecated 2.0 use Application::Database()->getUtcDateTime()
	 */
	public function dateDbOfNow( $time = true )
	{
		static $cache		=	array();

		if ( ! isset( $cache[$time] ) ) {
			$cache[$time]	=	Application::Database()->getUtcDateTime( null, ( $time ? 'datetime' : 'date' ) );
		}

		return $cache[$time];
	}

	/**
	 * returns a UTC formated now
	 *
	 * @param string $obsoleteOffset Unused as timestamp is always UTC
	 * @return int
	 * @deprecated 2.0 use Application::Date()->getTimestamp()
	 */
	static public function getUTCNow( /** @noinspection PhpUnusedParameterInspection */ $obsoleteOffset = null )
	{
		return Application::Date()->getTimestamp();
	}

	/**
	 * returns a UTC formated timestamp
	 *
	 * @param string|int $time
	 * @param string|int $now
	 * @param string|int $obsoleteOffset Unused as timestamp is always UTC
	 * @param string $formatFrom
	 * @return int
	 * @deprecated 2.0 use Application::Date( $time, 'UTC', $formatFrom )->getTimestamp() or Application::Date( $now, 'UTC', $formatFrom )->modify( $time )->getTimestamp()
	 */
	static public function getUTCTimestamp( $time = null, $now = null, /** @noinspection PhpUnusedParameterInspection */ $obsoleteOffset = null, $formatFrom = null )
	{
		if ( $now ) {
			$timestamp	=	Application::Date( $now, 'UTC', $formatFrom )->modify( $time )->getTimestamp();
		} else {
			$timestamp	=	Application::Date( $time, 'UTC', $formatFrom )->getTimestamp();
		}

		return $timestamp;
	}

	/**
	 * returns a UTC formated date
	 *
	 * @param string|array $format
	 * @param string|int $timestamp
	 * @param string|int $offset
	 * @return string
	 * @deprecated 2.0 use Application::Date( $timestamp, $offset, $formatFrom )->format( $formatTo )
	 */
	static public function getUTCDate( $format = 'Y-m-d H:i:s', $timestamp = null, $offset = null )
	{
		if ( ! $format ) {
			$formatTo		=	'Y-m-d H:i:s';
			$formatFrom		=	null;
		} elseif ( is_array( $format ) ) {
			$formatTo		=	( isset( $format[0] ) ? $format[0] : 'Y-m-d H:i:s' );
			$formatFrom		=	( isset( $format[1] ) && ( $formatTo != $format[1] ) ? $format[1] : null );
		} else {
			$formatTo		=	$format;
			$formatFrom		=	null;
		}

		if ( ! $timestamp ) {
			$timestamp		=	'now';
		}

		if ( $timestamp == 'now' ) {
			$formatFrom		=	null;
		}

		if ( ! $offset ) {
			$offset			=	'UTC';
		}

		return Application::Date( $timestamp, $offset, $formatFrom )->format( $formatTo );
	}

	/**
	 * compares two dates and returns the difference object or false if failed
	 *
	 * @param string|int $from
	 * @param string|int $to
	 * @param string|int $offset
	 * @param string $formatFrom
	 * @return DateInterval|boolean
	 * @deprecated 2.0 use Application::Date( $from, $offset, $formatFrom )->diff( $to )
	 */
	static public function getUTCDateDiff( $from = 'now', $to = null, $offset = null, $formatFrom = null )
	{
		return Application::Date( $from, $offset, $formatFrom )->diff( $to );
	}

	/**
	 * Sets the page title
	 *
	 * @param  string  $title
	 * @return void
	 */
	public function setPageTitle( $title )
	{
		if ( ! Application::Cms()->getClientId() ) {
			if ( $this->getCfg( 'sitename_pagetitles' ) == 1 ) {
				$title		=	Text::sprintf( 'JPAGETITLE', $this->getCfg( 'sitename' ), $title );
			} elseif ( $this->getCfg( 'sitename_pagetitles' ) == 2 ) {
				$title		=	Text::sprintf( 'JPAGETITLE', $title, $this->getCfg( 'sitename' ) );
			}
		}

		$this->document->setPageTitle( $title );
	}

	/**
	 * Appends $title with $link to the Pathway display of the page
	 *
	 * @param  string       $title  Title
	 * @param  string|null  $link   [optional] URL of link of Title
	 * @return boolean              True on success
	 */
	public function appendPathWay( $title, $link = null )
	{
		if ( method_exists( $this->_baseFramework, 'appendPathWay' ) ) {
			return $this->_baseFramework->appendPathWay( $title, $link );
		}

		if ( method_exists( $this->_baseFramework, 'getPathway' ) ) {
			return $this->_baseFramework->getPathway()->addItem( $title, $link );
		}

		return false;
	}

	/**
	 * Adds current active menu item page title, description, keywords, and robots to head
	 *
	 * @return void
	 */
	public function setMenuMeta()
	{
		static $cache		=	null;

		if ( $cache === null ) {
			$menu			=	Application::Cms()->getActiveMenuWithParams();

			if ( $menu->get( 'id' ) ) {
				$cbUser		=	CBuser::getMyInstance();
				$title		=	null;

				if ( $this->getCfg( 'sitename_pagetitles' ) != 0 ) {
					// Always set the initial title if available to add sitename prefix/suffix:
					$title	=	$menu->get( 'title' );
				}

				if ( $menu->get( 'params/page_title' ) ) {
					$title	=	$cbUser->replaceUserVars( $menu->get( 'params/page_title' ), true, false );
				}

				if ( $title ) {
					$this->setPageTitle( $title );
				}

				if ( $menu->get( 'params/menu-meta_description' ) ) {
					$this->document->addHeadMetaData( 'description', $cbUser->replaceUserVars( $menu->get( 'params/menu-meta_description' ), true, false ) );
				}

				if ( $menu->get( 'params/menu-meta_keywords' ) ) {
					$this->document->addHeadMetaData( 'keywords', $cbUser->replaceUserVars( $menu->get( 'params/menu-meta_keywords' ), true, false ) );
				}

				if ( $menu->get( 'params/robots' ) ) {
					$this->document->addHeadMetaData( 'robots', $menu->get( 'params/robots' ) );
				}

				$cache		=	true;
			}
		}
	}

	/**
	 * Returns the current active menu page class
	 *
	 * @since 2.0.3
	 * @deprecated 2.5.0
	 * @see Application::Cms()->getPageClasses();
	 *
	 * @return null|string
	 */
	public function getMenuPageClass()
	{
		return Application::Cms()->getPageCssClasses();
	}

	/**
	 * Gets a session-based user-state
	 *
	 * @param  string  $stateName  Unique name of state
	 * @param  string  $default    First value if not initialized
	 * @return string              State
	 */
	public function getUserState( $stateName, $default = null )
	{
		return $this->_baseFramework->getUserState( $stateName, $default );
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @param  string  $stateName  Unique name of state
	 * @param  string  $reqName    The name of the variable passed in a request.
	 * @param  string  $default    First value if not initialized
	 * @return string              State
	 */
	public function getUserStateFromRequest( $stateName, $reqName, $default = null )
	{
		return $this->_baseFramework->getUserStateFromRequest( $stateName, $reqName, $default );
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param  string  $stateName   The path of the state.
	 * @param  string  $stateValue  The value of the variable.
	 * @return mixed                The previous state, if one existed.
	 */
	public function setUserState( $stateName, $stateValue )
	{
		return $this->_baseFramework->setUserState( $stateName, $stateValue );
	}

	/**
	 * User id being displayed
	 *
	 * @param  int|null  $uid  [Optional] (Only To set it)
	 * @return int             User-id
	 * @deprecated use getDisplayedUser and setDisplayedUser instead
	 */
	public function displayedUser( $uid = null )
	{
		if ( $uid !== null ) {
			$this->setDisplayedUser( $uid );
		}

		return $this->getDisplayedUser();
	}

	/**
	 * @return null|int
	 */
	public function getDisplayedUser()
	{
		return self::$displayedUser;
	}

	/**
	 * @param null|int $userId
	 */
	public function setDisplayedUser( $userId )
	{
		self::$displayedUser	=	$userId;
	}

	/**
	 * Only used to set the 'ui'
	 *
	 * @deprecated 2.0
	 *
	 * @param  string  $name   'ui'
	 * @param  int     $value  1 or 2
	 */
	public function cbset( $name, $value )
	{
		$this->$name			=	$value;
	}

	/**
	 * @deprecated 2.0 (unused in CB)
	 *
	 * @param  string  $javascriptCode
	 * @return void
	 */
	public function outputCbJs( $javascriptCode )
	{
		$this->_jsCodes[]		=	$javascriptCode;
	}

	/*********************
	 * JS + JQUERY LIB:
	 *
	 */

	/**
	 * Javascript code to output
	 * @var string[]
	 */
	var $_jsCodes				=	array();
	/**
	 * jQuery code to output
	 * @var string[]
	 */
	var $_jQueryCodes			=	array();
	/**
	 * jQuery plugins to load:  array( 'pluginName' => 'path' )
	 * @var array
	 */
	var $_jQueryPlugins			=	array();
	/**
	 * jQuery plugins already sent to html header:  array( 'path' => true )
	 * @var array
	 */
	var $_jQueryPluginsSent		=	array();
	/**
	 * jQuery plugins dependencies: array( 'pluginName' => array( 1 (load afther this) | -1: load before this => array( 'pluginsNames' )
	 * @var array
	 */
	var $_jqueryDependencies	=	array(
											'ui-all'			=>	array( 1 => array( 'touchpunch' ) ),
											'touchpunch'		=>	array( -1 => array( 'mobile' ) ),
											'flot'				=>	array( 1 => array( 'excanvas' ) ),
											'fileupload'		=>	array( -1 => array( 'ui-all', 'iframe-transport' ) ),
											'colorpicker'		=>	array( -1 => array( 'ui-all' ) ),
											'timepicker'		=>	array( -1 => array( 'ui-all' ) ),
											'cbvalidate'		=>	array( -1 => array( 'validate', 'scrollto' ) ),
											'cbinputmask'		=>	array( -1 => array( 'inputmask' ) ),
											'cbtooltip'			=>	array( -1 => array( 'qtip' ) ),
											'cbtimeago'			=>	array( -1 => array( 'livestamp' ) ),
											'cbselect'			=>	array( -1 => array( 'select2' ) ),
											'cbrepeat'			=>	array( -1 => array( 'ui-all' ) ),
											'cbdatepicker'		=>	array( -1 => array( 'ui-all', 'timepicker', 'combodate' ) ),
											'cbscroller'		=>	array( -1 => array( 'scrollto' ) )
										 );
	/**
	 * related CSS files with media: array( 'pluginName => array( 'cssFilePath.css' => array( boolean: minifiedVersionExists?, string|null: specific media ) )
	 * @var array
	 */
	var $_jqueryCssFiles		=	array(
											'ui-all'			=>	array( 'jquery/ui/ui.all.css' => array( false, null ) ),
											'select2'			=>	array( 'jquery/select2/select2.css' => array( false, null ) ),
											'colorpicker'		=>	array( 'jquery/colorpicker/colorpicker.css' => array( false, null ) ),
											'timepicker'		=>	array( 'jquery/timepicker/timepicker.css' => array( false, null ) ),
											'rateit'			=>	array( 'jquery/rateit/rateit.css' => array( false, null ) ),
											'qtip'				=>	array( 'jquery/qtip/qtip.css' => array( false, null ) )
										 );
	/**
	 * related JS files with min: array( 'pluginName => array( 'jsFile.js' => boolean: minifiedVersionExists? )
	 * @var array
	 */
	var $_jqueryJsFiles			=	array(
											'livestamp'			=>	array( 'moment.js' => true ),
											'combodate'			=>	array( 'moment.js' => true, 'moment-timezone.js' => true )
										 );

	/**
	 * returns the absolute file or url path to a jQuery plugin
	 *
	 * @param  string  $jQueryPlugin
	 * @param  string  $pathType      ('live_site' for URL or 'absolute_path' for file-path)
	 * @return string
	 */
	private function _coreJQueryFilePath( $jQueryPlugin, $pathType = 'live_site' )
	{
		if ( $pathType == 'live_site' ) {
			$base				=	'';			// paths are calculated at output in $this->document->addHeadScriptUrl()
		} else {
			$base				=	$this->getCfg( $pathType );
		}
		return $base . '/components/com_comprofiler/js/jquery/jquery.' . $jQueryPlugin . '.js';
	}

	/**
	 * Adds an external JQuery plugin to the known JQuery plugins (if not already known)
	 *
	 * @param  string|array   $jQueryPlugins  Short Name of plugin or array of short names
	 * @param  string|boolean $path           Path to file from root of website (including leading / ) so that it can be appended to absolute_path or live_site (OR TRUE: part of core)
	 * @param  array          $dependencies   array( 1	=>	array( pluginNames ) ) for plugins to load after and -1 for plugins to load before.
	 * @param  array          $cssfiles       array( filename => array( minVersionExists, media ) ) : media = null or 'screen'.
	 * @param  array          $jsfiles        array( filename => minVersionExists )
	 */
	public function addJQueryPlugin( $jQueryPlugins, $path, $dependencies = null, $cssfiles = null, $jsfiles = null )
	{

		$jQueryPlugins										=	(array) $jQueryPlugins;

		foreach ( $jQueryPlugins as $jQueryPlugin ) {

			if ( ( $path === true ) || file_exists( $this->_coreJQueryFilePath( $jQueryPlugin, 'absolute_path' ) ) ) {
				$path										=	$this->_coreJQueryFilePath( $jQueryPlugin );
			} else {
				if ( $dependencies !== null ) {
					$this->_jqueryDependencies				=	array_merge( $this->_jqueryDependencies, array( $jQueryPlugin => $dependencies ) );
				}
				if ( $cssfiles !== null ) {
					$this->_jqueryCssFiles					=	array_merge( $this->_jqueryCssFiles, array( $jQueryPlugin => $cssfiles ) );
				}
				if ( $jsfiles !== null ) {
					$this->_jqueryJsFiles					=	array_merge( $this->_jqueryJsFiles, array( $jQueryPlugin => $jsfiles ) );
				}
			}

			if ( ! isset( $this->_jQueryPlugins[$jQueryPlugin] ) ) {
				// not yet configured for loading: check dependencies: -1: before:
				if ( isset( $this->_jqueryDependencies[$jQueryPlugin][-1] ) ) {
					foreach ( $this->_jqueryDependencies[$jQueryPlugin][-1] as $jLib ) {
						if ( ! isset( $this->_jQueryPlugins[$jLib] ) ) {
							$this->addJQueryPlugin( $jLib, true );
						}
					}
				}
				$this->_jQueryPlugins[$jQueryPlugin]		=	$path;
				// +1: dependencies after:
				if ( isset( $this->_jqueryDependencies[$jQueryPlugin][1] ) ) {
					foreach ( $this->_jqueryDependencies[$jQueryPlugin][1] as $jLib ) {
						if ( ! isset( $this->_jQueryPlugins[$jLib] ) ) {
							$this->addJQueryPlugin( $jLib, true );
						}
					}
				}
			}
		}
	}

	/**
	 * Outputs a JQuery init string into JQuery strings at end of page,
	 * and adds if needed JS file inclusions at begin of page.
	 * Pro-memo, JQuery runs in CB in noConflict mode.
	 *
	 * @param  string  $javascriptCode  Javascript code ended by ; which will be put in between jQuery(document).ready(function($){ AND });
	 * @param  string  $jQueryPlugin    (optional) name of plugin to auto-load (if core plugin, or call first addJQueryPlugin).
	 */
	public function outputCbJQuery( $javascriptCode, $jQueryPlugin = null )
	{
		if ( Application::Config()->get( 'jsJqueryMigrate', 0 ) ) {
			$this->addJQueryPlugin( 'migrate', true );
		}

		if ( $jQueryPlugin ) {
			$this->addJQueryPlugin( $jQueryPlugin, true );
		}
		if ( $javascriptCode ) {
			$this->_jQueryCodes[]	=	$javascriptCode;
		}
		if ( $this->document->isHeadOutputed() ) {
			$this->getAllJsPageCodes();
		}
	}

	/**
	 * Method to load jQuery frameworks and non-conflicting javascript code into the document head
	 * Loading and use is done in "deep non-conflict mode", which allows several concurent versions of jQuery to be loaded and run simultaneously with any other javascript framework, including another conflicting jQuery instance.
	 * It means that the window's Javascript global namespace is not used in a fixed way: neither $ nor jQuery global variables are used. Reference: http://api.jquery.com/jQuery.noConflict/
	 *
	 * Outputs to head
	 *
	 * @return void
	 */
	public function getAllJsPageCodes( )
	{
		// jQuery code loading:

		if ( count( $this->_jQueryCodes ) > 0 ) {
			foreach ( array_keys( $this->_jQueryPlugins ) as $plugin ) {
				if ( isset( $this->_jqueryCssFiles[$plugin] ) ) {
					foreach ( $this->_jqueryCssFiles[$plugin] as $templateFile => $minExistsmedia ) {
						$templateFileWPath	=	selectTemplate( 'absolute_path' ) . '/' . $templateFile;
						if ( file_exists( $templateFileWPath ) ) {
							$templateFileUrl	=	selectTemplate( 'relative_path' ) . '/' . $templateFile;
						} else {
							$templateFileUrl	=	selectTemplate( 'relative_path', 'default' ) . '/' . $templateFile;
						}
						if ( ! isset( $this->_jQueryPluginsSent[$templateFileUrl] ) ) {
							$this->document->addHeadStyleSheet( $templateFileUrl, $minExistsmedia[0], $minExistsmedia[1] );
							$this->_jQueryPluginsSent[$templateFileUrl]		=	true;
						}
					}
				}
				if ( isset( $this->_jqueryJsFiles[$plugin] ) ) {
					foreach ( $this->_jqueryJsFiles[$plugin] as $jsFile => $minExists ) {
						$jsFilePath		=	'/components/com_comprofiler/js/' . $jsFile;
						if ( ! isset( $this->_jQueryPluginsSent[$jsFilePath] ) ) {
							$this->document->addHeadScriptUrl( $jsFilePath, $minExists );
							$this->_jQueryPluginsSent[$jsFilePath]		=	true;
						}
					}
				}
			}
			/*
						if ( FALSE && checkJversion() >= 2 && is_callable( array( 'JHtml', 'isCallable_' ) ) && JHtml::isCallable_('behavior.jquery' ) ) {
							// Based on proposed contribution of 12.01.2012 to Joomla:
							JHtml::_('behavior.jquery', _CB_JQUERY_VERSION, 'components/com_comprofiler/js/jquery-' . _CB_JQUERY_VERSION . '/', null, trim( implode( "\n", $this->_jQueryCodes ) ));
							$plugins = array();
							foreach ($this->_jQueryPlugins as $k => $v) {
								$matches	=	null;
								if ( preg_match( '|^/?(.+/)([^/]+)(?:\.min)?\.js$|', $v, $matches ) ) {
									JHtml::_('behavior.jquery', _CB_JQUERY_VERSION, $matches[1], array( $matches[2] ) );
								}
							}
							// JHtml::_('behavior.jquery', _CB_JQUERY_VERSION, 'components/com_comprofiler/../com_comprofiler/js/jquery-' . _CB_JQUERY_VERSION . '/', array( 'jquery.autogrow', 'jquery.colorinput', 'jquery.jmap'), '$("p").css("color","red");');
							// JHtml::_('behavior.jquery', '1.6.7', 'components/com_comprofiler/js/../js/jquery-' . _CB_JQUERY_VERSION . '/', array( 'jquery.autogrow', 'jquery.colorinput', 'jquery.jmap'), '$("p").css("color","red") ;');
						} else {
			*/

			// Store previous defines if present so they can be restored later:
			$preJquery	=		"if ( typeof window.$ != 'undefined' ) {"
				. "\n\t"
				.			'window.cbjqldr_tmpsave$ = window.$;'
				. "\n"
				.		'}'
				. "\n"
				.		"if ( typeof window.jQuery != 'undefined' ) {"
				. "\n\t"
				.			'window.cbjqldr_tmpsavejquery = window.jQuery;'
				. "\n"
				.		'}'
			;

			static $cbjqueryloaded	=	false;

			if ( ! defined( 'CB_JQUERY_LOADED' ) ) {
				global $ueConfig;

				$loadJquery			=	( isset( $ueConfig['jsJquery'] ) ? (int) $ueConfig['jsJquery'] : 1 );

				if ( $loadJquery || ( $this->getUi() == 2 ) ) {
					// Define CBs deep no conflict and redefine jquery variables for usage inside of CB plugins and jquery scripts:
					$postJquery		=		'var cbjQuery = jQuery.noConflict( true );';
					$this->document->addHeadScriptUrl( '/components/com_comprofiler/js/jquery/jquery-' . _CB_JQUERY_VERSION . '.js', true, $preJquery, $postJquery );
					$cbjqueryloaded	=	true;
					$preJquery		=	'';
				}

				define( 'CB_JQUERY_LOADED', 1 );
			}

			// Let's set the cbjquery selector if cb's jquery was never used:
			if ( ! $cbjqueryloaded ) {
				static $cbjquerySet	=	0;
				if ( ! $cbjquerySet++ ) {
					$preJquery	.=	"\n"
						.		'var cbjQuery = window.jQuery;'
						.	"\n"
					;
				}
			}

			// Now that the existing $ and jQuery are saved, impose use of CB's jQuery for CB plugins:
			$preJquery		.=	'window.$ = cbjQuery;'
				.	"\n"
				.	'window.jQuery = cbjQuery;'
			;

			foreach ( $this->_jQueryPlugins as $plugin => $pluginPath ) {
				if ( ! isset( $this->_jQueryPluginsSent[$plugin] ) ) {
					$this->document->addHeadScriptUrl( $pluginPath, true, $preJquery, null, ( $plugin == 'excanvas' ? '<!--[if lte IE 8]>' : '' ), ( $plugin == 'excanvas' ? '<![endif]-->' : '' ) );
					$preJquery								=	null;
					$this->_jQueryPluginsSent[$plugin]		=	true;
				}
			}

			// If no plugins outputed, we still need to have the $preJquery initializations:
			$jsCodeTxt		=	$preJquery;

			$jQcodes		=	trim( implode( "\n", $this->_jQueryCodes ) );
			if ( $jQcodes !== '' ) {
				$jsCodeTxt	.=	'cbjQuery( document ).ready( function( $ ) {'
					.	"\n"
					.		'var jQuery = $;' // lets ensure plugins using jQuery inline are getting cbs jquery object (for late loaded jquery)
					.	"\n"
					.		$jQcodes
					.	"\n"
					.	"});"
					.	"\n"
				;
			}
			$this->_jQueryCodes		=	array();

			$jsCodeTxt		.=		"if ( typeof window.cbjqldr_tmpsave$ != 'undefined' ) {"
				.	"\n\t"
				.			'window.$ = window.cbjqldr_tmpsave$;'
				.	"\n"
				.		'}'
				.	"\n"
				.		"if ( typeof window.cbjqldr_tmpsavejquery != 'undefined' ) {"
				.	"\n\t"
				.			'window.jQuery = window.cbjqldr_tmpsavejquery;'
				.	"\n"
				.		'}'
			;
			$this->document->addHeadScriptDeclaration( $jsCodeTxt );
			/*
						}
			*/
		}

		// classical standalone javascript loading (for compatibility), depreciated ! :

		if ( count( $this->_jsCodes ) > 0 ) {
			$this->document->addHeadScriptDeclaration( implode( "\n", $this->_jsCodes ) );
			$this->_jsCodes	=	array();
		}
	}
}
