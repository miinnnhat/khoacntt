<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Registry\GetterInterface;
use CBLib\Language\CBTxt;
use Joomla\CMS\Helper\ModuleHelper;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_framework, $ueConfig, $_CB_PMS, $cbSpecialReturnAfterLogin, $cbSpecialReturnAfterLogout;

if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
	echo 'CB not installed'; return;
}

/** @noinspection PhpIncludeInspection */
include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

cbimport( 'cb.html' );
cbimport( 'language.front' );

/** @var \Joomla\Registry\Registry $params */
/** @var array $attribs */

if ( (int) $params->get( 'maincbtpl', 1 ) ) {
	outputCbTemplate();
}

require_once( dirname( __FILE__ ) . '/helper.php' );

$moduleLayout						=	$params->get( 'layout', '_:bootstrap' );

if ( $moduleLayout == '_:registration' ) {
	// If user is logged in, viewing/saving registration, or viewing a form that could conflict already then hide module based registration:
	if ( Application::MyUser()->getUserId()
		 || (
		 		( Application::Input()->get( 'option', null, GetterInterface::STRING ) == 'com_comprofiler' )
				&& ( in_array( Application::Input()->get( 'view', null, GetterInterface::STRING ), array( 'registers', 'saveregisters', 'userslist' ) ) )
		 )
	) {
		return;
	}

	global $_PLUGINS;

	$_PLUGINS->loadPluginGroup( 'user' );
	$_PLUGINS->loadPluginGroup( 'templates' );

	outputCbJs();
	initToolTip();

	cbValidator::loadValidation();

	$type							=	'registration';
} else {
	$type							=	modCBLoginHelper::getType();

	if ( (int) $params->get( 'cb_plugins', 1 ) ) {
		global $_PLUGINS;

		$_PLUGINS->loadPluginGroup( 'user' );
	}

	if ( ! Application::Config()->getBool( 'forgotlogin_type', true ) ) {
		Application::Cms()->getLanguage()->load( 'mod_login' );
	}
}

$cbUser								=	CBuser::getMyInstance();

if ( ! $cbUser ) {
	$cbUser							=	CBuser::getInstance( null );
}

$user								=	$cbUser->getUserData();
$livePath							=	$_CB_framework->getCfg( 'live_site' ) . '/modules/mod_cblogin';
$templateClass						=	'cb_template cb_template_' . selectTemplate( 'dir' );

$showButton							=	(int) $params->get( 'show_buttons_icons', 0 );
$secureForm							=	(int) $params->get( 'https_post', 0 );
$showUsernameLabel					=	(int) $params->get( 'name_label', 5 );
$usernameInputLength				=	(int) $params->get( 'name_length', 14 );
$showPasswordLabel					=	(int) $params->get( 'pass_label', 5 );
$passwordInputLength				=	(int) $params->get( 'pass_length', 14 );
$showSecretKeyLabel					=	(int) $params->get( 'key_label', 5 );
$secretKeyInputLength				=	(int) $params->get( 'key_length', 14 );
$showRememberMe						=	(int) $params->get( 'remember_enabled', 1 );
$showForgotLogin					=	(int) $params->get( 'show_lostpass', 1 );
$showRegister						=	( ( $_CB_framework->getCfg( 'allowUserRegistration' ) || ( isset( $ueConfig['reg_admin_allowcbregistration'] ) && ( $ueConfig['reg_admin_allowcbregistration'] == 1 ) ) ) && $params->get( 'show_newaccount', 1 ) );
$showPrivateMessages				=	(int) $params->get( 'show_pms', 0 );
$showConnectionRequests				=	(int) $params->get( 'show_connection_notifications', 0 );
$regLayout							=	$params->get( 'reg_layout', 'flat' );
$styleUsername						=	$params->get( 'style_username_cssclass' );
$stylePassword						=	$params->get( 'style_password_cssclass' );
$styleSecretKey						=	$params->get( 'style_secretkey_cssclass' );
$styleLogin							=	$params->get( 'style_login_cssclass' );
$styleLogout						=	$params->get( 'style_logout_cssclass' );
$styleForgotLogin					=	$params->get( 'style_forgotlogin_cssclass' );
$styleRegister						=	$params->get( 'style_register_cssclass' );
$styleProfile						=	$params->get( 'style_profile_cssclass' );
$styleProfileEdit					=	$params->get( 'style_profileedit_cssclass' );
$styleConnRequests					=	$params->get( 'style_connrequests_cssclass' );
$stylePrivateMsgs					=	$params->get( 'style_privatemsgs_cssclass' );

if ( $params->get( 'logoutpretext' ) ) {
	$preLogoutText					=	$cbUser->replaceUserVars( $params->get( 'logoutpretext' ) );
} else {
	$preLogoutText					=	null;
}

if ( $params->get( 'logoutposttext' ) ) {
	$postLogoutText					=	$cbUser->replaceUserVars( $params->get( 'logoutposttext' ) );
} else {
	$postLogoutText					=	null;
}

if ( $params->get( 'text_show_profile' ) ) {
	$profileViewText				=	$cbUser->replaceUserVars( $params->get( 'text_show_profile' ) );
} else {
	$profileViewText				=	null;
}

if ( $params->get( 'text_edit_profile' ) ) {
	$profileEditText				=	$cbUser->replaceUserVars( $params->get( 'text_edit_profile' ) );
} else {
	$profileEditText				=	null;
}

$greetingText						=	$cbUser->replaceUserVars( CBTxt::T( 'Hi, [formatname]' ) );

if ( $params->get( 'pretext' ) ) {
	$preLogintText					=	$cbUser->replaceUserVars( $params->get( 'pretext' ) );
} else {
	$preLogintText					=	null;
}

if ( $params->get( 'posttext' ) ) {
	$postLoginText					=	$cbUser->replaceUserVars( $params->get( 'posttext' ) );
} else {
	$postLoginText					=	null;
}

$loginMethod						=	( isset( $ueConfig['login_type'] ) ? (int) $ueConfig['login_type'] : 0 );

if ( $loginMethod == 4 ) {
	$showForgotLogin				=	0;
}

switch ( $loginMethod ) {
	case 2:
		$userNameText				=	CBTxt::T( 'Email' );
		break;
	case 1:
		$userNameText				=	CBTxt::T( 'Username or email' );
		break;
	case 0:
	default:
		$userNameText				=	CBTxt::T( 'Username' );
		break;
}

if ( ! isset( $attribs ) ) {
	if ( isset( $module ) ) {
		// Joomla 4:
		$attribs					=	(array) $module;
		unset( $attribs['params'] );
	} else {
		$attribs					=	array();
	}
}

$loginReturnUrl						=	modCBLoginHelper::getReturnURL( $params, $type, $attribs );
$logoutReturnUrl					=	modCBLoginHelper::getReturnURL( $params, $type, $attribs );

if ( in_array( $showButton, array( 2, 4 ) ) ) {
	$buttonStyle					=	' style="color: black; font-weight: normal; box-shadow: none; text-shadow: none; background: none; border: 0; padding: 0;"';
} else {
	$buttonStyle					=	null;
}

if ( $showPrivateMessages && $_CB_PMS ) {
	$newMessageCount				=	$_CB_PMS->getPMSunreadCount( (int) $user->get( 'id' ) );
	$privateMessageURL				=	$_CB_PMS->getPMSlinks( null, (int) $user->get( 'id' ), null, null, 2 );

	if ( isset( $newMessageCount[0] ) ) {
		$newMessageCount			=	(int) $newMessageCount[0];
	} else {
		$newMessageCount			=	0;
	}

	if ( isset( $privateMessageURL[0]['url'] ) ) {
		$privateMessageURL			=	cbSef( $privateMessageURL[0]['url'] );
	} else {
		$privateMessageURL			=	$_CB_framework->userProfileUrl();
	}

	if ( ( $showPrivateMessages == 1 ) && ( ! $newMessageCount ) ) {
		$showPrivateMessages		=	0;
	}
} else {
	$showPrivateMessages			=	0;
	$newMessageCount				=	0;
	$privateMessageURL				=	$_CB_framework->userProfileUrl();
}

if ( $showConnectionRequests && ( isset( $ueConfig['allowConnections'] ) && $ueConfig['allowConnections'] ) ) {
	$cbConnections					=	new cbConnection( (int) $user->get( 'id' ) );
	$newConnectionRequests			=	(int) $cbConnections->getPendingConnectionsCount( (int) $user->get( 'id' ) );

	if ( ( $showConnectionRequests == 1 ) && ( ! $newConnectionRequests ) ) {
		$showConnectionRequests		=	0;
	}
} else {
	$showConnectionRequests			=	0;
	$newConnectionRequests			=	0;
}

$twoFactorMethods					=	modCBLoginHelper::getTwoFactorMethods();
$extraButtons						=	modCBLoginHelper::getExtraButtons( 'login-form-' . ( $module->id ?? 0 ) );

if ( checkJversion( '4.0+' ) && ( ( $type == 'login' ) || ( $type == 'logout' ) ) && ( strpos( $moduleLayout, 'bootstrap' ) !== false ) ) {
	$moduleLayout					.=	'_j4';
}

if ( $type == 'logout' ) {
	$moduleLayout					.=	'_logout';
}

require ModuleHelper::getLayoutPath( 'mod_cblogin', $moduleLayout );