<?php
/**
* CBLib, Community Builder Library(TM)
* @version $Id: 6/20/14 7:18 PM $
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Database\Table\Table;
use CBLib\Language\CBTxt;
use CBLib\Xml\SimpleXMLElement;
use CBLib\Registry\GetterInterface;
use CBLib\Registry\Registry;
use CBLib\Registry\ParamsInterface;
use CB\Database\Table\PluginTable;

defined('CBLIB') or die();

/**
 * CBInstallPlugin Class implementation
 * 
 * Used for implementing the Model for CB Plugins installation screens of CB
 * for the store() method to install plugins.
 */
class CBInstallPlugin extends Table
{
	/**
	 * @var int
	 */
	public $id = null;
	/**
	 * @var string
	 */
	public $func;
	/**
	 * @var string
	 */
	public $localdirectory;
	/**
	 * @var string
	 */
	public $packageweburl;
	/**
	 * @var string
	 */
	public $packagewebname;
	/**
	 * @var string
	 */
	public $packageurl;
	/**
	 * @var string
	 */
	public $plgfile;

	/**
	 * @var string
	 */
	private $_resultMessage	=	null;

	/**
	 *	Binds an array/hash from database to this object
	 *
	 *	@param  int $oid  optional argument, if not specifed then the value of current key is used
	 *	@return mixed     any result from the database operation
	 */
	public function load( $oid = null )
	{
		return true;
	}

	/**
	 * If table key (id) is NULL : inserts a new row
	 * otherwise updates existing row in the database table
	 *
	 * Can be overridden or overloaded by the child class
	 *
	 * @param  boolean  $updateNulls  TRUE: null object variables are also updated, FALSE: not.
	 * @return boolean                TRUE if successful otherwise FALSE
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function store( $updateNulls = false )
	{
		cbimport( 'cb.tabs' );
		cbimport( 'cb.imgtoolbox' );
		cbimport( 'cb.adminfilesystem' );
		cbimport( 'cb.installer' );
		cbimport( 'cb.params' );
		cbimport( 'cb.pagination' );

		if ( method_exists( Application::Cms(), 'getFormToken' ) ) {
			if ( ! Application::Session()->checkFormToken() ) {
				return false;
			}
		} else {
			cbSpoofCheck( 'plugin' );
		}

		checkCanAdminPlugins( 'core.admin' );

		ob_start();

		switch ( $this->func ) {
			case 'installPluginUpload':
				$success	=	$this->installPluginUpload();
				break;
			case 'installPluginWeb':
				$success	=	$this->installPluginURL( $this->packageweburl, $this->packagewebname, true );
				break;
			case 'installPluginDir':
				$success	=	$this->installPluginDir( $this->localdirectory );
				break;
			case 'installPluginURL':
				$success	=	$this->installPluginURL( $this->packageurl );
				break;
			case 'installPluginDisc':
				$success	=	$this->installPluginDisc( $this->plgfile );
				break;
			default:
				throw new \InvalidArgumentException( CBTxt::T( 'INVALID_FUNCTION', 'Invalid function' ), 500 );
		}

		$html		=	ob_get_contents();
		ob_end_clean();

		$this->_resultMessage	=	$html;

		if ( ! $success ) {
			$this->setError( 'Installation error' );
		}

		return $success;
	}

	/**
	 * After store() this function may be called to get a result information message to display. Override if it is needed.
	 *
	 * @return string|null  STRING to display or NULL to not display any information message (Default: NULL)
	 */
	public function cbResultOfStore( )
	{
		return $this->_resultMessage;
	}

	/**
	 * returns html for maximum upload file size
	 * Used by Backend XML only
	 * @deprecated Do not use directly, only for XML tabs backend
	 *
	 * @return string
	 */
	public function displayUploadMaxFilesize( )
	{
		return ini_get( 'upload_max_filesize' )
		. ' <small>(upload_max_filesize in '
		. ( is_callable( 'php_ini_loaded_file' ) && php_ini_loaded_file() ? htmlspecialchars( php_ini_loaded_file() ) : 'php.ini' )
		. ')</small>';

	}

	/**
	 * Returns HTML for "install from discovery" tab
	 * Used by Backend XML only
	 * @deprecated Do not use directly, only for XML tabs backend
	 *
	 * @return string
	 */
	public function displayDiscoveries( )
	{
		global $_CB_framework, $_CB_database;

		// Prepare array of discovered plugins (not installed, but exists):
		$allPlgsFolders										=	array();
		$discoveredPlgs										=	array();
		$existingPlgList									=	array();
		$existingPlgFolders									=	array();
		$failingXmlFiles									=	array();

		// Discovers all installed plugins
		$query												=	'SELECT ' . $_CB_database->NameQuote( 'folder' )
			.	', ' . $_CB_database->NameQuote( 'type' )
			.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' );
		$_CB_database->setQuery( $query );
		$existingPlgs										=	$_CB_database->loadAssocList();

		// Constructs list of installed plugins': 1) folders by type ($existingPlgList) and 2) list of installed folder paths ($existingPlgFolders)
		foreach ( $existingPlgs as $existingPlg ) {
			$plgType										=	$existingPlg['type'];

			$existingPlgList[$plgType][]					=	$existingPlg['folder'];

			$existingPlgFolders[]							=	$existingPlg['type'] . '/' . $existingPlg['folder'];
		}

		// Discovers inside each type all the directories:
		foreach ( $existingPlgList as $plgType => $existingPlgs ) {
			$plgFolders										=	array_filter(
																	cbReadDirectory( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/' . $plgType ),
																	function ( $subSubFolder )
																	{
																		return ! in_array( $subSubFolder, array( 'index.html', 'default' ) );
																	}
																);

			// Adds each directory of each type to the list of checks:
			foreach ( $plgFolders as $plgFolder ) {
				$plgFolderAndType							=	$plgType . '/' . $plgFolder;

				$allPlgsFolders[]							=	$plgFolderAndType;

				// Checks for sub-plugins, templates and known folders that might contain plugins:
				foreach ( array( 'plugin', 'templates', 'processors', 'products' ) as $subFolder ) {
					$subfolderPath							=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/' . $plgFolderAndType . '/' . $subFolder;

					if ( file_exists( $subfolderPath ) ) {
						$subPluginsFolders					=	array_map(
							function ( $subSubFolder ) use ( $plgFolderAndType, $subFolder )
							{
								return $plgFolderAndType . '/' . $subFolder . '/' . $subSubFolder;
							},
							array_filter(
								cbReadDirectory( $subfolderPath ),
								function ( $subSubFolder )
								{
									return ! in_array( $subSubFolder, array( 'index.html', 'default' ) );
								}
							)
						);

						// Consolidates sub-folders:
						$allPlgsFolders							=	array_merge( $allPlgsFolders, $subPluginsFolders );
					}
				}
			}
		}

		// As discoveries above might lead to multiple entries depending on database of installed plugins, makes discoveries unique:
		$allPlgsFolders										=	array_unique( $allPlgsFolders );

		// Checks for each discovered folder if there are cbinstall-xml files, and if yes, if they are in the installed plugins list:
		foreach ( $allPlgsFolders as $plgFolderAndType ) {
			$plgFolderDir									=	$_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/' . $plgFolderAndType;

			if ( ( ! is_file( $plgFolderDir ) ) && ( ! in_array( $plgFolderAndType, $existingPlgFolders ) ) ) {
				$plgFiles									=	cbReadDirectory( $plgFolderDir );

				if ( $plgFiles ) foreach ( $plgFiles as $plgFile ) {
					if ( preg_match( '/^.+\.xml$/i', $plgFile ) ) {
						$plgPath							=	$plgFolderDir . ( substr( $plgFolderDir, -1, 1 ) == '/' ? '' : '/' ) . $plgFile;
						try {
							$plgXml							=	@new SimpleXMLElement( trim( file_get_contents( $plgPath ) ) );
							$elements						=	explode( '/', $plgFolderAndType );
							$lastFolder						=	array_pop( $elements );

							if ( ( $plgXml->getName() == 'cbinstall' ) && ( $lastFolder != 'default' ) && ( ! array_key_exists( $plgFolderAndType, $discoveredPlgs ) ) ) {
								$discoveredPlgs[$plgFolderAndType]	=	array( 'name' => ( isset( $plgXml->name ) ? (string) $plgXml->name : $plgFolderAndType ), 'file' => $plgFolderAndType );
							}
						} catch ( \Exception $e ) {
							$failingXmlFiles[]				=	$plgPath;
						}
					}
				}
			}
		}

		$return				=	'';

		if ( count( $failingXmlFiles ) > 0 ) {
			$return			.=	'<div class="col-sm-12">'
							.		'<div class="col-sm-12 alert alert-danger" role="alert">'
							.			'<h4>'
							.				CBTxt::Th( 'Malformed XML files discovered in CB plugin folders:' )
							.			'</h4>';

			foreach ( $failingXmlFiles as $failedFilePath ) {
				$return		.=			'<div class="cbft_text form-group row no-gutters cb_form_line">'
							.				CBTxt::Th( 'XML_FILE_FILE_IS_MALFORMED', 'XML file [FILE_PATH_AND_NAME] is malformed and should be replaced or fixed, or the plugin should be removed', array( '[FILE_PATH_AND_NAME]' => '<strong>' . htmlspecialchars( $failedFilePath ) . '</strong>' ) )
							.			'</div>';
			}

			$return			.=		'</div>'
							.	'</div>';

		}

		if ( $discoveredPlgs ) {
			foreach ( $discoveredPlgs as $discoveredPlg ) {
				$return		.=		'<div class="cbft_text form-group row no-gutters cb_form_line">'
							.			'<div class="col-form-label col-sm-3 pr-sm-2">'
							.				htmlspecialchars( $discoveredPlg['name'] )
							.			'</div>'
							.			'<div class="cb_field col-sm-9">'
							.				'<input type="button" class="btn btn-primary btn-sm" value="' . htmlspecialchars( CBTxt::T( 'Install Package' ) ) . '" onclick="submitbutton( \'act=apply&amp;func=installPluginDisc&amp;plgfile=' . addslashes( $discoveredPlg['file'] ) . '\' )" />'
							.			'</div>'
							.		'</div>';
			}
		} else {
			$return			.=		'<div class="col-sm-12">'
							.			CBTxt::Th( 'No plugins discovered.' )
							.		'</div>';
		}

		return $return;
	}

	/**
	 * Returns notice with link to get a site key
	 * Used by Backend XML only
	 *
	 * @return string
	 */
	static public function displayWebNotice()
	{
		global $_CB_framework;

		$siteKeyURL	=	'https://www.joomlapolis.com/downloads/site-keys?site=' . urlencode( base64_encode( $_CB_framework->getCfg( 'live_site' ) ) );

		return CBTxt::Th( 'INSTALL_FROM_WEB_GET_SITE_KEY', 'Looks like you do not have a Site Key yet. A Site Key will allow Install from Web to provide access to the downloads accessible to your Joomlapolis.com account membership. This makes updating and installing add-ons reliable and simple by any site super-administrator. If you would like to create a Site Key then <a href="[sitekey_url]" target="_blank" class="btn btn-primary">please check here to get your site key</a>. The Site Key is optional and not needed for Community Builder itself.', array( '[sitekey_url]' => htmlspecialchars( $siteKeyURL ) ) );
	}

	/**
	 * Returns HTML for "install from web" tab
	 * Used by Backend XML only
	 *
	 * @return string
	 */
	public function displayWeb()
	{
		global $_CB_framework, $_CB_database;

		$cacheFile										=	$_CB_framework->getCfg( 'absolute_path' ) . '/cache/' . md5( $_CB_framework->getCfg( 'secret' ) . 'cbpluginsweb' ) . '.json';
		$siteKey										=	Application::Config()->getString( 'installFromWebKey', '' );

		if ( $siteKey ) {
			$accessToken								=	Application::Config()->getString( 'installFromWebToken', '' );
		} else {
			$accessToken								=	Application::Session()->getString( 'installFromWebToken', '' );
		}

		$newAccessToken									=	'';
		$webJSON										=	Application::Input()->getRaw( 'post/webjson', '' );
		$packagesJSON									=	'';
		$cacheTime										=	'';
		$request										=	false;
		$search											=	Application::Input()->getString( 'websearch', '' );

		if ( $webJSON ) {
			// Ensure the POST was sent from install from web and ensure Super User permission before storing the JSON; DO NOT REMOVE:
			if ( ( method_exists( Application::Cms(), 'getFormToken' ) ? Application::Session()->checkFormToken( 'post', 0 ) : cbSpoofCheck( 'webstore', 'POST', 2 ) ) && Application::MyUser()->isSuperAdmin() ) {
				if ( $webJSON === 'request' ) {
					if ( $siteKey ) {
						$client							=	new \GuzzleHttp\Client();
						$hash							=	hash( 'sha256', $_CB_framework->getCfg( 'live_site' ) );

						try {
							$query						=	[	'key'			=>	$siteKey,
																'domain_hash'	=>	$hash,
															];

							if ( Application::Input()->getBool( 'post/refresh', false ) ) {
								$query['refresh']		=	'true';
							}

							if ( cbGuzzleVersion() >= 6 ) {
								// Can't mix URL parameters and query option in Guzzle 6.0.0+ so set the path entirely in query option:
								$query					=	array_merge( [	'option'		=>	'com_comprofiler',
																			'view'			=>	'pluginclass',
																			'plugin'		=>	'cbpackagebuilder',
																			'action'		=>	'web',
																			'channel'		=>	( Application::Config()->getString( 'updateChannel', '' ) === 'stable' ? 'stable' : null ),
																			'format'		=>	'raw',
															], $query );

								$result					=	$client->get( 'https://www.joomlapolis.com/index.php', [ 'query' => $query ] );
							} else {
								$result					=	$client->get( 'https://www.joomlapolis.com/index.php?option=com_comprofiler&view=pluginclass&plugin=cbpackagebuilder&action=web' . ( Application::Config()->getString( 'updateChannel', '' ) === 'stable' ? '&channel=stable' : '' ) . '&format=raw', [ 'query' => $query ] );
							}

							if ( ( $result->getStatusCode() === 200 ) && ( $result !== false ) ) {
								$packagesJSON			=	new Registry( json_decode( (string) $result->getBody(), true ) );

								if ( $packagesJSON->getString( 'token', '' ) ) {
									$newAccessToken		=	$packagesJSON->getString( 'token', '' );

									$packagesJSON->unsetEntry( 'token' );
								}

								file_put_contents( $cacheFile, $packagesJSON->asJson() );
							}
						} catch ( Exception $e ) {
							return CBTxt::T( 'FAILED_INSTALL_WEB_ERROR', 'Failed to load install from web. Please reload to retry. Error: [error]', [ '[error]' => str_replace( [ $siteKey, $hash ], '{removed}', $e->getMessage() ) ] );
						}
					}
				} else {
					try {
						$packagesJSON					=	new Registry( $webJSON );

						if ( $packagesJSON->getString( 'token', '' ) ) {
							$newAccessToken				=	$packagesJSON->getString( 'token', '' );

							$packagesJSON->unsetEntry( 'token' );
						}

						file_put_contents( $cacheFile, $packagesJSON->asJson() );
					} catch( Exception $e ) {
						return CBTxt::T( 'FAILED_INSTALL_WEB_ERROR', 'Failed to load install from web. Please reload to retry. Error: [error]', [ '[error]' => $e->getMessage() ] );
					}
				}
			}
		} elseif ( file_exists( $cacheFile ) ) {
			$cacheTime									=	filemtime( $cacheFile );

			if ( intval( ( $_CB_framework->now() - $cacheTime ) / 3600 ) > 24 ) {
				$request								=	true;
			} else {
				$packagesJSON							=	new Registry( file_get_contents( $cacheFile ) );
			}
		} else {
			$request									=	true;
		}

		// Call this early as we need the timeago jQuery to be prepared:
		$cacheTimeAgo									=	cbFormatDate( Application::Date( ( $cacheTime ?: 'now'  ), 'UTC' )->modify( '+24 HOURS' )->getTimestamp(), false, 'timeago' );

		if ( $newAccessToken && ( $accessToken !== $newAccessToken ) ) {
			$accessToken								=	$newAccessToken;

			if ( $siteKey ) {
				// Store site key tokens into configuration so we can reuse them:
				Application::Config()->set( 'installFromWebToken', $newAccessToken );

				$corePlugin								=	new PluginTable();

				$corePlugin->load( 1 );

				$coreParams								=	new Registry( $corePlugin->params );

				$coreParams->set( 'installFromWebToken', $newAccessToken );

				$corePlugin->set( 'params', $coreParams->asJson() );

				$corePlugin->store();
			} else {
				// Just store temporary tokens into the current session so they can be sent with download requests:
				Application::Session()->set( 'installFromWebToken', $newAccessToken );
			}
		}

		if ( ( ! $request ) && ( ! $accessToken ) ) {
			// We have a cached output, but we don't have a token so force a request to try and get one:
			$request									=	true;
		}

		if ( Application::Input()->getString( 'format', '' ) !== 'raw' ) {
			cbGetRegAntiSpamInputTag();

			$cbGetRegAntiSpams							=	cbGetRegAntiSpams();

			$loading									=	'<div class="cbWebLoading text-center"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

			if ( $siteKey ) {
				// No need to make a JSONP call so lets just call our install directly and grab the web JSON using normal HTTPS request:
				$js										=	"$.cbweb = function( refresh ) {"
														.		"$( '.cbWeb' ).html( " . json_encode( $loading, JSON_HEX_TAG ) . " );"
														.		"$.ajax({"
														.			"url: " . json_encode( $_CB_framework->backendViewUrl( 'installfromweb', false, [], 'raw' ), JSON_HEX_TAG ) . ","
														.			"type: 'POST',";
				if ( method_exists( Application::Cms(), 'getFormToken' ) ) {
					$js									.=			"headers: {"
														.				"'X-CSRF-Token': " . json_encode( Application::Session()->getFormTokenName() )
														.			"},";
				}
				$js										.=			"data: {"
														.				"webjson: 'request',";
				if ( ! method_exists( Application::Cms(), 'getFormToken' ) ) {
					$js									.=				cbSpoofField() . ": " . json_encode( cbSpoofString( null, 'webstore' ), JSON_HEX_TAG ) . ","
														.				cbGetRegAntiSpamFieldName() . ": " . json_encode( $cbGetRegAntiSpams[0], JSON_HEX_TAG ) . ",";
				}
				$js										.=				"refresh: refresh"
														.			"},"
														.			"dataType: 'html',"
														.			"cache: false"
														.		"}).fail( function( jqXHR, textStatus, errorThrown ) {"
														.			"$( '.cbWebLoading' ).remove();"
														.			"$( '.cbWeb' ).html( " . json_encode( CBTxt::T( 'Failed to load install from web. Please reload to retry.' ), JSON_HEX_TAG ) . " );"
														.		"}).done( function( webHtml, textStatus, jqXHR ) {"
														.			"$( '.cbWebLoading' ).remove();"
														.			"$( '.cbWeb' ).html( webHtml );"
														.			"$( '.cbWeb' ).find( '.cbTooltip,[data-hascbtooltip=\"true\"]' ).cbtooltip();"
														.			"$( '.cbWeb' ).find( '.cbDateTimeago' ).cbtimeago();"
														.		"});"
														.	"};";
			} else {
				// We don't have a token so we have to first attempt a JSONP call to see if login cookie can be detected:
				$js										=	"$.cbwebCB = function( webJSON ) {"
														.		"if ( webJSON ) {"
														.			"$.ajax({"
														.				"url: '" . $_CB_framework->backendViewUrl( 'installfromweb', false, [], 'raw' ) . "',"
														.				"type: 'POST',";
				if ( method_exists( Application::Cms(), 'getFormToken' ) ) {
					$js									.=				"headers: {"
														.					"'X-CSRF-Token': " . json_encode( Application::Session()->getFormTokenName() )
														.				"},";
				}
				$js										.=				"data: {";
				if ( ! method_exists( Application::Cms(), 'getFormToken' ) ) {
					$js									.=				cbSpoofField() . ": " . json_encode( cbSpoofString( null, 'webstore' ), JSON_HEX_TAG ) . ","
														.				cbGetRegAntiSpamFieldName() . ": " . json_encode( $cbGetRegAntiSpams[0], JSON_HEX_TAG ) . ",";
				}
				$js										.=					"webjson: JSON.stringify( webJSON )"
														.				"},"
														.				"dataType: 'html',"
														.				"cache: false"
														.			"}).fail( function( jqXHR, textStatus, errorThrown ) {"
														.				"$( '.cbWebLoading' ).remove();"
														.				"$( '.cbWeb' ).html( " . json_encode( CBTxt::T( 'Failed to load install from web. Please reload to retry.' ), JSON_HEX_TAG ) . " );"
														.			"}).done( function( webHtml, textStatus, jqXHR ) {"
														.				"$( '.cbWebLoading' ).remove();"
														.				"$( '.cbWeb' ).html( webHtml );"
														.				"$( '.cbWeb' ).find( '.cbTooltip,[data-hascbtooltip=\"true\"]' ).cbtooltip();"
														.				"$( '.cbWeb' ).find( '.cbDateTimeago' ).cbtimeago();"
														.			"});"
														.		"} else {"
														.			"$( '.cbWebLoading' ).remove();"
														.			"$( '.cbWeb' ).html( " . json_encode( CBTxt::T( 'Failed to load install from web data at <a href="https://www.joomlapolis.com/" target="_blank" rel="nofollow">https://www.joomlapolis.com/</a> from your browser. Please check your internet connection or any browser-side Request Policy setting and retry.' ), JSON_HEX_TAG ) . " );"
														.		"}"
														.	"};"
														.	"$.cbwebJSONP = function( refresh ) {"
														.		"$.ajax({"
														.			"url: 'https://www.joomlapolis.com/index.php?option=com_comprofiler&view=pluginclass&plugin=cbpackagebuilder&action=web" . ( Application::Config()->getString( 'updateChannel', '' ) === 'stable' ? '&channel=stable' : '' ) . "&format=raw' + ( refresh === true ? '&refresh=true' : '' ),"
														.			"type: 'GET',"
														.			"data: { domain_hash: " . json_encode( hash( 'sha256', $_CB_framework->getCfg( 'live_site' ) ), JSON_HEX_TAG ) . " },"
														.			"dataType: 'jsonp',"
														.			"crossDomain: true,"
														.			"cache: false"
														.		"}).fail( function( jqXHR, textStatus, errorThrown ) {"
														.			"$( '.cbWebLoading' ).remove();"
														.			"$( '.cbWeb' ).html( " . json_encode( CBTxt::T( 'Failed to access install from web data at <a href="https://www.joomlapolis.com/" target="_blank" rel="nofollow">https://www.joomlapolis.com/</a> from your browser. Please check your internet connection or any browser-side Request Policy setting and retry.' ), JSON_HEX_TAG ) . " );"
														.		"}).done( function( webJSON, textStatus, jqXHR ) {"
														.			"$.cbwebCB( webJSON );"
														.		"});"
														.	"};"
														.	"$.cbweb = function( refresh ) {"
														.		"$( '.cbWeb' ).html( " . json_encode( $loading, JSON_HEX_TAG ) . " );"
														.		"$.ajax({"
														.			"url: 'https://www.joomlapolis.com/index.php?option=com_comprofiler&view=pluginclass&plugin=cbpackagebuilder&action=web" . ( Application::Config()->getString( 'updateChannel', '' ) === 'stable' ? '&channel=stable' : '' ) . "&format=raw' + ( refresh === true ? '&refresh=true' : '' ),"
														.			"type: 'GET',"
														.			"data: { domain_hash: " . json_encode( hash( 'sha256', $_CB_framework->getCfg( 'live_site' ) ), JSON_HEX_TAG ) . " },"
														.			"dataType: 'json',"
														.			"crossDomain: true,"
														.			"xhrFields: {"
														.				"withCredentials: true"
														.			"},"
														.			"cache: false"
														.		"}).fail( function( jqXHR, textStatus, errorThrown ) {"
														.			"$.cbwebJSONP( refresh );" // retry with JSONP if CORS failed
														.		"}).done( function( webJSON, textStatus, jqXHR ) {"
														.			"if ( webJSON.authorized === false ) {"
														.				"$.cbwebJSONP( refresh );" // retry with JSONP if CORS failed to authorize the user (could be due to cookie access fail)
														.			"} else {"
														.				"$.cbwebCB( webJSON );"
														.			"}"
														.		"});"
														.	"};";
			}

			$js											.=	"$( '.cbWeb' ).on( 'click', '.cbWebType > .cbWebTypeLink', function( e ) {"
														.		"e.preventDefault();"
														.		"$( '.cbWebSearch' ).val( '' ).trigger( 'change' );"
														.		"$( '.cbWebTypeLink,.cbWebSubTypeLink' ).removeClass( 'active' );"
														.		"$( '.cbWebPackagesType,.cbWebSubTypes' ).addClass( 'hidden' );"
														.		"$( this ).addClass( 'active' );"
														.		"$( this ).siblings( '.cbWebSubTypes' ).removeClass( 'hidden' );"
														.		"$( '.cbWebPackagesType[data-cbweb-type=\"' + $( this ).data( 'cbweb-type' ) + '\"]' ).removeClass( 'hidden' );"
														.		"if ( $( this ).parent().hasClass( 'cbWebTypeEmpty' ) ) {"
														.			"$( this ).parent().find( '.cbWebSubType:first > .cbWebSubTypeLink' ).click()"
														.		"}"
														.	"}).on( 'click', '.cbWebSubType > .cbWebSubTypeLink', function( e ) {"
														.		"e.preventDefault();"
														.		"$( '.cbWebSearch' ).val( '' ).trigger( 'change' );"
														.		"$( '.cbWebSubTypeLink' ).removeClass( 'active' );"
														.		"$( '.cbWebPackagesType' ).addClass( 'hidden' );"
														.		"$( this ).addClass( 'active' );"
														.		"$( '.cbWebPackagesType[data-cbweb-type=\"' + $( this ).data( 'cbweb-type' ) + '\"]' ).removeClass( 'hidden' );"
														.	"}).on( 'click', '.cbWebPackageDownload', function( e ) {"
														.		"e.preventDefault();"
														.		"$( '#packagewebname' ).val( $( this ).data( 'cbweb-file' ) );"
														.		"$( '#packageweburl' ).val( $( this ).data( 'cbweb-download' ) );"
														.		"submitbutton( 'act=apply&func=installPluginWeb' );"
														.	"}).on( 'click', '.cbWebRefresh', function( e ) {"
														.		"e.preventDefault();"
														.		"$.cbweb( true );"
														.	"});"
														.	"$( '.cb_packageinstaller' ).on( 'cbpackagebuilder.install.done', function() {"
														.		"$.cbweb( true );"
														.	"});"
														.	"$( '.cbWebSearchCancel' ).on( 'click', function() {"
														.		"$( '.cbWebSearch' ).val( '' ).trigger( 'change' ).focus();"
														.	"});"
														.	"const searchable = [];"
														.	"$( '.cbWebSearch' ).on( 'change keyup', function() {"
														.		"const search = $( this ).val().trim().toLowerCase();"
														.		"$( '.cbWebSearchingHidden' ).removeClass( 'cbWebSearchingHidden' );"
														.		"$( '.cbWebSearchingType' ).removeClass( 'cbWebSearchingType' );"
														.		"$( '.cbWebSearchingPackage' ).removeClass( 'cbWebSearchingPackage' );"
														.		"if ( ! search ) {"
														.			"$( '.cbWebSearchCancel' ).addClass( 'hidden' );"
														.			"return;"
														.		"}"
														.		"$( '.cbWebSearchCancel' ).removeClass( 'hidden' );"
														.		"if ( ! searchable.length ) {"
														.			"$( '.cbWebPackagesType' ).each( function() {"
														.				"const type = $( this ).data( 'cbweb-type' );"
														.				"if ( ( type === 'home' ) || ( type === 'updates' ) || ( type === 0 ) ) {"
														.					"return;"
														.				"}"
														.				"$( this ).find( '.cbWebPackage' ).each( function() {"
														.					"searchable.push( {"
														.						"id: $( this ).data( 'cbweb-id' ),"
														.						"type: type,"
														.						"title: $( this ).find( '.cbWebPackageHeader > a' ).text().trim().toLowerCase(),"
														.						"description: $( this ).find( '.cbWebPackageDescription' ).text().trim().toLowerCase(),"
														.					"} );"
														.				"});"
														.			"});"
														.		"}"
														.		"let found = false;"
														.		"$.each( searchable, function( k, v ) {"
														.			"if ( v.title.includes( search ) || v.description.includes( search ) ) {"
														.				"$( '.cbWebPackagesType[data-cbweb-type=\"' + v.type + '\"]' ).addClass( 'cbWebSearchingType' );"
														.				"$( '.cbWebPackage[data-cbweb-id=\"' + v.id + '\"]' ).addClass( 'cbWebSearchingPackage' );"
														.				"found = true;"
														.			"}"
														.		"});"
														.		"if ( found ) {"
														.			"$( '.cbWebPackagesType:not(.cbWebSearchingType)' ).addClass( 'cbWebSearchingHidden' );"
														.			"$( '.cbWebPackage:not(.cbWebSearchingPackage)' ).addClass( 'cbWebSearchingHidden' );"
														.		"}"
														.	"});";

			if ( $search ) {
				$js										.=	"$( '.cbWebSearch' ).trigger( 'change' );";
			}

			if ( $request ) {
				$js										.=	"$.cbweb();";
			}

			$_CB_framework->outputCbJQuery( $js );

			$css										=	".cb_template .cbWeb .cbWebSearchingType {"
														.		"display: grid !important;"
														.	"}"
														.	".cb_template .cbWeb .cbWebSearchingPackage {"
														.		"display: flex !important;"
														.	"}"
														.	".cb_template .cbWeb .cbWebSearchingHidden {"
														.		"display: none !important;"
														.	"}";

			$_CB_framework->document->addHeadStyleInline( $css );

			initToolTip();

			if ( $request ) {
				return '<div class="cbWeb">' . $loading . '</div>';
			}
		}

		if ( $packagesJSON ) {
			$packages									=	$packagesJSON->subTree( 'packages.' . ( checkJversion( '5.0+' ) ? 'j50' : ( checkJversion( '4.0+' ) ? 'j40' : 'j30' ) ) );
		} else {
			$packages									=	false;
		}

		if ( $packages === false ) {
			return CBTxt::T( 'Failed to load install from web. Please reload to retry.' );
		}

		if ( ! $packages ) {
			return CBTxt::T( 'There are no packages available at this time.' );
		}

		$news											=	$packagesJSON->getHtml( 'news', '' );
		$menu											=	'';
		$new											=	[];
		$popular										=	[];
		$recommended									=	[];
		$updates										=	[];
		$conditionedUpdates								=	[];
		$items											=	[ 'home' => [], 'updates' => [] ];

		// JSON moved presets to the top as its key is 0. Move it back to the bottom:
		if ( $packages->has( '0' ) ) {
			$presets									=	$packages->subTree( '0' );

			$packages->unsetEntry( '0' );

			$packages->set( '0', $presets );
		}

		// Create a list of tag => title pairs for installed languages:
		static $installedLanguages						=	null;

		if ( $installedLanguages === null ) {
			$query										=	'SELECT *'
														.	"\n FROM " . $_CB_database->NameQuote( '#__languages' );
			$_CB_database->setQuery( $query );
			$installedLanguages							=	$_CB_database->loadAssocList( 'lang_code', 'title' );
		}

		// Parse through the packages and build the menu structure from Type > Subtypes:
		foreach ( $packages as $typeId => $type ) {
			/** @var ParamsInterface $type */
			$typeValue									=	strtolower( preg_replace( '/[^-a-zA-Z0-9_]/', '', $typeId ) );

			switch ( $typeId ) {
				case '0': // Presets
				case '1': // Joomla Packages
					$icon								=	'cubes';
					break;
				case '3': // Joomla Components
					$icon								=	'gears';
					break;
				case '2': // Joomla Libraries
					$icon								=	'book';
					break;
				case '6': // Joomla Languages
					$icon								=	'comments-o';
					break;
				case '7': // Joomla Templates
					$icon								=	'eye';
					break;
				case '4': // Joomla Plugins
				case '8': // Community Builder Plugins
					$icon								=	'plug';
					break;
				case '5': // Joomla Modules
					$icon								=	'cube';
					break;
				default:
					$icon								=	'puzzle-piece';
					break;
			}

			$typePackages								=	[];
			$typeCount									=	0;

			// We need to keep track of new, popular, and packages with updates; lets also pre-process version check:
			foreach ( $type->subTree( 'packages' ) as $packageId => $typePackage ) {
				/** @var ParamsInterface $typePackage */
				$isLatest								=	$this->checkPackageVersion( $typePackage );

				$typePackage->set( 'latest', $isLatest );
				$typePackage->set( 'type_icon', $icon );
				$typePackage->set( 'type_label', $type->getHtml( 'label', '' ) );

				$pkgTypeId								=	( $typeId === '0' ? 'preset_' : 'package_' ) . $packageId;

				if ( $isLatest === -1 ) {
					if ( $typePackage->getBool( 'conditioned', false ) ) {
						$conditionedUpdates[$pkgTypeId]		=	$typePackage;
					} else {
						if ( ! in_array( $packageId, $updates, true ) ) {
							$updates[]						=	$packageId;
						}

						$items['updates'][$pkgTypeId]		=	$typePackage;
					}
				}

				if ( $typePackage->getBool( 'hidden', false ) ) {
					// We don't want hidden packages displayed normally, but we want the above update check to still display them so stop here:
					continue;
				}

				if ( ( strpos( $typePackage->getString( 'file', '' ), 'cbplug_lang_' ) === false )
					 && ( strpos( $typePackage->getString( 'file', '' ), 'plug_language_' ) === false )
				) {
					// Skip language plugins for new and popular output:
					if ( $typePackage->getBool( 'new', false ) ) {
						$new[$pkgTypeId]				=	$typePackage;
					} elseif ( $typePackage->getBool( 'popular', false) ) {
						$popular[$pkgTypeId]			=	$typePackage;
					}
				} elseif ( $isLatest === 0 ) {
					// Check the installed languages to see if there's a matching CB language plugin to recommend:
					foreach ( $installedLanguages as $langTag => $langTitle ) {
						if ( strpos( $langTag, 'en-' ) === 0 ) {
							// Skip all English tags:
							continue;
						}

						if ( ( strpos( $typePackage->getString( 'file', '' ), $langTag ) === false )
							 && ( strpos( $typePackage->getString( 'name', '' ), $langTag ) === false )
							 && ( strpos( $typePackage->getString( 'name', '' ), $langTitle ) === false )
						) {
							continue;
						}

						$recommended[$pkgTypeId]		=	$typePackage;
						break;
					}
				}

				$typePackages[$pkgTypeId]				=	$typePackage;

				$typeCount++;
			}

			$subMenu									=	'';

			foreach ( $type->subTree( 'subtypes' ) as $subTypeId => $subType ) {
				/** @var ParamsInterface $subType */
				$subTypePackages						=	[];
				$subTypeCount							=	0;

				// We need to keep track of new, popular, and packages with updates; lets also pre-process version check:
				foreach ( $subType->subTree( 'packages' ) as $packageId => $subTypePackage ) {
					/** @var ParamsInterface $subTypePackage */
					$isLatest							=	$this->checkPackageVersion( $subTypePackage );

					$subTypePackage->set( 'latest', $isLatest );
					$subTypePackage->set( 'type_icon', $icon );
					$subTypePackage->set( 'type_label', $type->getHtml( 'label', '' ) );

					$pkgTypeId							=	( $typeId === '0' ? 'preset_' : 'package_' ) . $packageId;

					if ( $isLatest === -1 ) {
						if ( $subTypePackage->getBool( 'conditioned', false ) ) {
							$conditionedUpdates[$pkgTypeId]		=	$subTypePackage;
						} else {
							if ( ! in_array( $packageId, $updates, true ) ) {
								$updates[]						=	$packageId;
							}

							$items['updates'][$pkgTypeId]		=	$subTypePackage;
						}
					}

					if ( $subTypePackage->getBool( 'hidden', false ) ) {
						// We don't want hidden packages displayed normally, but we want the above update check to still display them so stop here:
						continue;
					}

					if ( ( strpos( $subTypePackage->getString( 'file', '' ), 'cbplug_lang_' ) === false )
						 && ( strpos( $subTypePackage->getString( 'file', '' ), 'plug_language_' ) === false )
					) {
						// Skip language plugins for new and popular output:
						if ( $subTypePackage->getBool( 'new', false ) ) {
							$new[$pkgTypeId]			=	$subTypePackage;
						} elseif ( $subTypePackage->getBool( 'popular', false ) ) {
							$popular[$pkgTypeId]		=	$subTypePackage;
						}
					} elseif ( $isLatest === 0 ) {
						// Check the installed languages to see if there's a matching CB language plugin to recommend:
						foreach ( $installedLanguages as $langTag => $langTitle ) {
							if ( strpos( $langTag, 'en-' ) === 0 ) {
								// Skip all English tags:
								continue;
							}

							if ( ( strpos( $subTypePackage->getString( 'file', '' ), $langTag ) === false )
								 && ( strpos( $subTypePackage->getString( 'name', '' ), $langTag ) === false )
								 && ( strpos( $subTypePackage->getString( 'name', '' ), $langTitle ) === false )
							) {
								continue;
							}

							$recommended[$pkgTypeId]	=	$subTypePackage;
							break;
						}
					}

					$subTypePackages[$pkgTypeId]		=	$subTypePackage;

					$typeCount++;
					$subTypeCount++;
				}

				if ( ! $subTypePackages ) {
					continue;
				}

				$subTypeValue							=	$typeValue . '_' . strtolower( preg_replace( '/[^-a-zA-Z0-9_]/', '', $subTypeId ) );

				$subMenu								.=			'<li class="cbWebSubType nav-item">'
														.				'<button type="button" class="d-flex gap-2 align-items-center btn btn-block btn-link text-left nav-link rounded-0 m-0 cbWebSubTypeLink" data-cbweb-type="' . htmlspecialchars( $subTypeValue ) . '">'
														.					'<span class="fa fa-level-down" style="width: 20px;"></span>'
														.					'<span class="fa fa-' . htmlspecialchars( $icon ) . '" style="width: 20px;"></span>'
														.					'<span class="flex-grow-1">' . $subType->getHtml( 'label', '' ) . '</span>'
														.					'<span class="badge badge-pill badge-secondary">' . $subTypeCount . '</span>'
														.				'</button>'
														.			'</li>';

				$items[$subTypeValue]					=	$subTypePackages;
			}

			if ( ( ! $typePackages ) && ( ! $subMenu ) ) {
				// There's no top level packages or sub type packages so just don't output this type at all:
				continue;
			}

			$items[$typeValue]							=	$typePackages;

			$menu										.=	'<li class="cbWebType' . ( ! count( $items[$typeValue] ) ? ' cbWebTypeEmpty' : '' ) . ' nav-item">'
														.		'<button type="button" class="d-flex gap-2 align-items-center btn btn-block btn-link text-left nav-link rounded-0 m-0 cbWebTypeLink" data-cbweb-type="' . htmlspecialchars( $typeValue ) . '">'
														.			'<span class="fa fa-' . htmlspecialchars( $icon ) . '" style="width: 20px;"></span>'
														.			'<span class="flex-grow-1">' . $type->getHtml( 'label', '' ) . '</span>'
														.			'<span class="badge badge-pill badge-secondary">' . $typeCount . '</span>'
														.		'</button>'
														.		'<ul class="cbWebSubTypes nav flex-column nav-pills hidden">'
														.			$subMenu
														.		'</ul>'
														.	'</li>';
		}

		// Be sure condition updates always show after actual available updates:
		if ( $conditionedUpdates ) {
			$items['updates']							+=	$conditionedUpdates;
		}

		// Limit home to just the first 6 packages:
		$items['home']									=	( $recommended + array_slice( ( $new + $popular ), 0, 12, true ) );

		// Build the update all url if there are updates available:
		$updateAll										=	'';

		if ( $updates ) {
			$updateAll									=	'https://www.joomlapolis.com/index.php?option=com_comprofiler&view=pluginclass&plugin=cbpackagebuilder&action=packages&func=download&packages=' . implode( ',', cbToArrayOfInt( $updates ) ) . ( $accessToken ? '&token=' . urlencode( $accessToken ) : null ) . ( Application::Config()->getString( 'updateChannel', '' ) === 'stable' ? '&channel=stable' : '' ) . '&format=raw';
		}

		$configUrl										=	$_CB_framework->backendViewUrl( 'showconfig', true, [ 'tab' => 'config7' ] );

		$return											=	'<div class="d-flex flex-column gap-4 cbWeb">';

		if ( $packagesJSON->getBool( 'valid', null ) === false ) {
			$return										.=	'<div class="m-0 alert alert-error">' . CBTxt::Th( 'INSTALL_FROM_WEB_INVALID_SITE_KEY', 'The supplied Site Key does not appear to be valid or has been disabled by administration. Your Site Key is domain strict and must match the domain provided by Joomla exactly, which would be your live_site value "[live_site]". Please supply a valid Site Key within your <a href="[config_url]">Community Builder configuration</a> then <button type="button" class="btn btn-secondary btn-sm cbWebRefresh">click here to refresh Install from Web</button>.', [ '[config_url]' => $configUrl, '[live_site]' => $_CB_framework->getCfg( 'live_site' ) ] ) . '</div>';
		}

		if ( $packagesJSON->getBool( 'authorized', null ) === false ) {
			$return										.=	'<div class="m-0 alert alert-info">' . CBTxt::Th( 'INSTALL_FROM_WEB_NOT_AUTHORIZED', 'You do not appear to be logged into <a href="https://www.joomlapolis.com/" target="_blank" rel="nofollow">https://www.joomlapolis.com/</a>. This will cause Install from Web to treat you as a guest. Ensure you are logged in at <a href="https://www.joomlapolis.com/" target="_blank" rel="nofollow">https://www.joomlapolis.com/</a> and check browser settings to ensure 3rd party cookies are not blocked or consider generating a Site Key from your Joomlapolis profile and save it to your <a href="[config_url]">Community Builder configuration</a>. Once you have logged in or set your Site Key <button type="button" class="btn btn-secondary btn-sm cbWebRefresh">click here to refresh Install from Web</button>.', [ '[config_url]' => $configUrl ] ) . '</div>';
		}

		$return											.=	'<div class="d-flex flex-column flex-lg-row gap-4 cbWebResponse">'
														.		'<div class="flex-shrink-0 cbWebMenu" style="min-width: min(100%,350px);">'
														.			'<div class="input-group mb-3">'
														.				'<input type="text" value="' . htmlspecialchars( $search ) . '" placeholder="' . htmlspecialchars( CBTxt::T( 'Search...' ) ) . '" class="form-control rounded-0 cbWebSearch" aria-label="' . htmlspecialchars( CBTxt::T( 'Search' ) ) . '" />'
														.				'<div class="input-group-append cbWebSearchCancel hidden">'
														.					'<button type="button" class="btn btn-light border" aria-label="' . htmlspecialchars( CBTxt::T( 'Cancel Search' ) ) . '"><span class="fa fa-times"></span></button>'
														.				'</div>'
														.			'</div>'
														.			'<ul class="cbWebTypes nav flex-column nav-pills bg-light">';

		if ( $news || $items['home'] ) {
			$return										.=				'<li class="cbWebType nav-item">'
														.					'<button type="button" class="d-flex gap-2 align-items-center btn btn-block btn-link text-left nav-link rounded-0 m-0 cbWebTypeLink' . ( ! $items['updates'] ? ' active' : '' ) . '" data-cbweb-type="home">'
														.						'<span class="fa fa-home" style="width: 20px;"></span>'
														.						'<span class="flex-grow-1">' . CBTxt::T( 'Home' ) . '</span>'
														.						( $items['home'] ? '<span class="badge badge-pill badge-secondary">' . count( $items['home'] ) . '</span>' : '' )
														.					'</button>'
														.				'</li>';
		}

		if ( $items['updates'] ) {
			$return										.=				'<li class="cbWebType nav-item">'
														.					'<button type="button" class="d-flex gap-2 align-items-center btn btn-block btn-link text-left nav-link rounded-0 m-0 cbWebTypeLink active" data-cbweb-type="updates">'
														.						'<span class="fa fa-undo" style="width: 20px;"></span>'
														.						'<span class="flex-grow-1">' . CBTxt::T( 'Updates' ) . '</span>'
														.						'<span class="badge badge-pill badge-secondary">' . count( $items['updates'] ) . '</span>'
														.					'</button>'
														.				'</li>';
		}

		$return											.=				$menu
														.			'</ul>'
														.		'</div>'
														.		'<div class="d-flex flex-column gap-4 cbWebPackages col-12 col-md">';

		if ( $news ) {
			$return										.=			'<div class="cbWebPackagesType" data-cbweb-type="home">'
														.				'<div class="cbWebPackageNews">'
														.					$news // HTML filtered above at definition
														.				'</div>'
														.			'</div>';
		}

		// Parse through the items to output their package containers:
		foreach ( $items as $type => $typeItems ) {
			if ( ! $typeItems ) {
				continue;
			}

			$i											=	0;

			$return										.=			'<div class="d-grid gap-4 cbWebPackagesType' . ( $type === 'home' ? ( $items['updates'] ? ' hidden' : '' ) : ( $type !== 'updates' ? ' hidden' : '' ) ) . '" data-cbweb-type="' . htmlspecialchars( $type ) . '" style="grid-template-columns: repeat(auto-fill, minmax(min(100%,300px), 1fr));">';

			if ( ( $type === 'updates' ) && $updateAll ) {
				$return									.=				'<div class="text-right cbWebUpdateAll" style="grid-column: 1 / -1">'
														.					'<button type="button" data-cbweb-file="' . htmlspecialchars( CBTxt::T( 'Update All' ) ) . '" data-cbweb-download="' . htmlspecialchars( $updateAll ) . '" class="cbWebPackageDownload btn btn-primary"><span class="fa fa-download"></span> ' . CBTxt::T( 'Update All' ) . '</button>'
														.				'</div>';
			}

			if ( ( $type === 'home' ) && ( $new || $popular ) && $recommended ) {
				$return									.=					'<div class="cbWebPackageBreak" style="grid-column: 1 / -1">'
														.						'<div class="cb-page-header">'
														.							'<h3 class="m-0 p-0 cb-page-header-title">' . CBTxt::T( 'Recommended' ) . '</h3>'
														.						'</div>'
														.					'</div>';
			}

			foreach ( $typeItems as $itemId => $item ) {
				/** @var ParamsInterface $item */
				$buttons								=	'';

				if ( $item->getString( 'demo', '' ) && ( $item->getBool( 'conditioned', false ) || ( $item->getInt( 'latest', 0 ) === 0 ) ) ) {
					$buttons							.=	'<a href="' . htmlspecialchars( $item->getString( 'demo', '' ) ) . '" target="_blank" rel="nofollow" class="w-100 mb-1 btn btn-sm-block btn-info">' . CBTxt::T( 'Demo' ) . '</a> ';
				}

				if ( ! $item->getBool( 'conditioned', false ) ) {
					if ( $item->getInt( 'type', 0 ) === 12 ) {
						$buttons						.=	'<a href="' . htmlspecialchars( $item->getString( 'download', '' ) ) . '" target="_blank" rel="nofollow" class="w-100 btn btn-sm-block btn-primary">' . CBTxt::T( 'Download' ) . '</a>';
					} elseif ( $item->getInt( 'latest', 0 ) === 1 ) {
						$buttons						.=	'<button type="button" data-cbweb-file="' . htmlspecialchars( $item->getString( 'name', '' ) ) . '" data-cbweb-download="' . htmlspecialchars( $item->getString( 'download', '' ) ) . '" class="w-100 btn btn-sm-block btn-secondary cbWebPackageDownload">' . CBTxt::T( 'Already Installed' ) . '</button>';
					} elseif ( $item->getInt( 'latest', 0 ) === -1 ) {
						$buttons						.=	'<button type="button" data-cbweb-file="' . htmlspecialchars( $item->getString( 'name', '' ) ) . '" data-cbweb-download="' . htmlspecialchars( $item->getString( 'download', '' ) ) . '" class="w-100 btn btn-sm-block btn-danger cbWebPackageDownload">' . CBTxt::T( 'Update' ) . '</button>';
					} else {
						$buttons						.=	'<button type="button" data-cbweb-file="' . htmlspecialchars( $item->getString( 'name', '' ) ) . '" data-cbweb-download="' . htmlspecialchars( $item->getString( 'download', '' ) ) . '" class="w-100 btn btn-sm-block btn-primary cbWebPackageDownload">' . CBTxt::T( 'Install' ) . '</button>';
					}
				} else {
					$button								=	$item->getHtml( 'button', '' );

					if ( ! $button ) {
						$button							=	CBTxt::T( 'Download' );
					}

					$buttons							.=	'<a href="' . htmlspecialchars( $item->getString( 'download', '' ) ) . '" target="_blank" rel="nofollow" class="w-100 btn btn-sm-block btn-primary">' . $button . '</a>';
				}

				$logo									=	$item->getString( 'logo', '' );

				if ( ! $logo ) {
					foreach ( $item->getRaw( 'media', [] ) as $packageMedia ) {
						if ( $packageMedia['type'] !== 'image' ) {
							continue;
						}

						// Lets just output the first available media for now as a sort of logo:
						$logo							=	$packageMedia['url'];
						break;
					}
				}

				if ( $logo ) {
					$logo								=	'<img src="' . htmlspecialchars( $logo ) . '" class="img-fluid pkbMediaImage" style="height: 100px;" />';
				}

				$return									.=						'<div class="card h-100 cbWebPackage" data-cbweb-id="' . htmlspecialchars( $itemId ) . '">'
														.							'<div class="card-header p-2 text-large text-center text-wrap justify-content-center cbWebPackageHeader">'
														.								( $item->getString( 'url', '' ) ? '<a href="' . htmlspecialchars( $item->getString( 'url', '' ) ) . '" target="_blank" rel="nofollow" class="font-weight-bold">' . $item->getHtml( 'name', '' ) . '</a>' : $item->getHtml( 'name', '' ) )
														.							'</div>'
														.							'<div class="card-body p-0 cbWebPackageBody">'
														.								( $logo ? '<div class="m-2 text-center cbWebPackageMedia">' . ( $item->getString( 'url', '' ) ? '<a href="' . htmlspecialchars( $item->getString( 'url', '' ) ) . '" target="_blank" rel="nofollow">' . $logo . '</a>' : $logo ) . '</div>' : '' )
														.								( $item->getHtml( 'description', '' ) ? '<div class="m-2 text-center text-wrap cbWebPackageDescription">' . $item->getHtml( 'description', '' ) . '</div>' : '' )
														.							'</div>'
														.							'<div class="card-footer pl-1 pt-1 pr-0 pb-0 row no-gutters cbWebPackageFooter">';

				if ( $item->getString( 'version', '' ) ) {
					$return								.=								'<div class="col-12 pb-1 pr-1 cbWebInfo cbWebInfoVersion">'
														.									'<span class="d-inline-block h-100 w-100 badge badge-light border p-2 font-weight-normal text-wrap cbWebPackageVersion">' . CBTxt::T( 'WEB_PACKAGE_VERSION', 'Version: [version]', [ '[version]' => htmlspecialchars( $item->getString( 'version', '' ) ) ] ) . '</span>'
														.								'</div>';
				}

				if ( $item->getString( 'size', '' ) ) {
					$return								.=								'<div class="col-auto flex-grow-1 pb-1 pr-1 cbWebInfo cbWebInfoDate">'
														.									'<span class="d-inline-block h-100 w-100 badge badge-light border p-2 font-weight-normal text-wrap cbWebPackageInfoDate">' . CBTxt::T( 'WEB_PACKAGE_SIZE', 'Size: [size]', [ '[size]' => htmlspecialchars( $item->getString( 'size', '' ) ) ] ) . '</span>'
														.								'</div>';
				}

				if ( $item->getString( 'date', '' ) ) {
					$return								.=								'<div class="col-auto flex-grow-1 pb-1 pr-1 cbWebInfo cbWebInfoSize">'
														.									'<span class="d-inline-block h-100 w-100 badge badge-light border p-2 font-weight-normal text-wrap cbWebPackageInfoSize">'
														.										CBTxt::T( 'WEB_PACKAGE_DATE', 'Date: [date]', [ '[date]' => htmlspecialchars( $item->getString( 'date', '' ) ) ] )
														.										( $item->getBool( 'new', false ) ? ' <span class="badge badge-pill badge-success align-text-bottom cbWebPackageNew">' . CBTxt::T( 'New' ) . '</span>' : '' )
														.									'</span>'
														.								'</div>';
				}

				if ( in_array( $type, [ 'home', 'updates' ], true ) ) {
					$pkgTypeIcon						=	cbTooltip( null, $item->getString( 'type_label', '' ), null, 'auto', null, '<span class="fa fa-' . htmlspecialchars( $item->getString( 'type_icon', '' ) ) . '"></span>', null, 'data-cbtooltip-simple="true"' );

					$return								.=								'<div class="col-auto flex-grow-1 pb-1 pr-1 cbWebInfo cbWebInfoType">'
														.									'<span class="d-inline-block h-100 w-100 badge badge-light border p-2 font-weight-normal text-wrap cbWebPackageInfoType">'
														.										CBTxt::T( 'WEB_PACKAGE_TYPE', 'Type: [type]', [ '[type]' => $pkgTypeIcon ] )
														.									'</span>'
														.								'</div>';
				}

				if ( $item->getString( 'hits', '' ) ) {
					$return								.=								'<div class="col-auto flex-grow-1 pb-1 pr-1 cbWebInfo cbWebInfoHits">'
														.									'<span class="d-inline-block h-100 w-100 badge badge-light border p-2 font-weight-normal text-wrap cbWebPackageInfoHits">'
														.										CBTxt::T( 'WEB_PACKAGE_HITS', 'Hits: [hits]', [ '[hits]' => htmlspecialchars( $item->getString( 'hits', '' ) ) ] )
														.										( $item->getBool( 'popular', false ) ? ' <span class="badge badge-pill badge-danger align-text-bottom cbWebPackagePopular">' . CBTxt::T( 'Hot' ) . '</span>' : '' )
														.									'</span>'
														.								'</div>';
				}

				$return									.=							'</div>';

				if ( $buttons ) {
					$return								.=							'<div class="bg-white card-footer p-1 cbWebPackageActions">'
														.								$buttons
														.							'</div>';
				}

				$return									.=						'</div>';

				$i++;

				if ( ( $type === 'home' ) && ( $new || $popular ) && $recommended && ( $i === count( $recommended ) ) ) {
					$return								.=					'<div class="cbWebPackageBreak" style="grid-column: 1 / -1">'
														.						'<div class="cb-page-header">'
														.							'<h3 class="m-0 p-0 cb-page-header-title">' . CBTxt::T( 'New & Popular' ) . '</h3>'
														.						'</div>'
														.					'</div>';
				}
			}

			$return										.=			'</div>';
		}

		$return											.=		'</div>'
														.	'</div>'
														.	'<div class="text-right text-muted cbWebCached">'
														.		'<button type="button" class="btn btn-secondary align-middle cbWebRefresh"><span class="fa fa-refresh"></span> ' . CBTxt::T( 'Refresh Now' ) . '</button>'
														.	'</div>'
														.	'<input name="packagewebname" id="packagewebname" type="hidden" value="" />'
														.	'<input name="packageweburl" id="packageweburl" type="hidden" value="" />'
														.	'</div>';

		return $return;
	}

	/**
	 * Checks an install from web packages version
	 *
	 * @param ParamsInterface $package
	 * @return int
	 */
	private function checkPackageVersion( $package )
	{
		global $_CB_database, $_PLUGINS;

		static $installedPlugins				=	null;
		static $installedExtensions				=	null;

		if ( $installedPlugins === null ) {
			$query								=	'SELECT *'
												.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' );
			$_CB_database->setQuery( $query );
			$installedPlugins					=	$_CB_database->loadObjectList();
		}

		if ( $installedExtensions === null ) {
			$query								=	'SELECT *'
												.	"\n FROM " . $_CB_database->NameQuote( '#__extensions' );
			$_CB_database->setQuery( $query );
			$installedExtensions				=	$_CB_database->loadObjectList();
		}

		$isLatest								=	0; // Not Installed

		// Lets see if we can find an existing plugin or extension either by name or by folder/element match so we can compare versions:
		if ( $package->get( 'version', null, GetterInterface::STRING ) ) {
			if ( $package->get( 'type', null, GetterInterface::INT ) == 8 ) {
				foreach ( $installedPlugins as $installedPlugin ) {
					$plgElement					=	$package->get( 'details.element', null, GetterInterface::STRING );
					$plgType					=	$package->get( 'details.type', null, GetterInterface::STRING );
					$plgName					=	$package->get( 'name', GetterInterface::STRING );
					$plgFolder					=	null;

					if ( $package->has( 'details.folder' ) ) {
						$plgFolder				=	$package->get( 'details.folder', null, GetterInterface::STRING );
					}

					if ( ! $plgFolder ) {
						$plgFolder				=	trim( str_replace( $package->get( 'version', null, GetterInterface::STRING ), '', pathinfo( $package->get( 'file', null, GetterInterface::STRING ), PATHINFO_FILENAME ) ), '_ -' );
					}

					if ( $plgType && ( $plgType != $installedPlugin->type ) ) {
						// Type was supplied and doesn't match:
						continue;
					}

					// Lets try to guess a partial match if the exact element wasn't provided:
					$guessMatch					=	false;

					if ( ! $plgElement ) {
						if ( $installedPlugin->type == 'language' ) {
							if ( ( $installedPlugin->element == 'cbpaidsubscriptions_language' ) || ( strpos( $installedPlugin->element, 'cbsubs' ) === 0 ) ) {
								$guessMatch		=	preg_match( '/cbsubs_\w+_' . str_replace( 'cbpaidsubscriptions-', '', $installedPlugin->folder ) . '$/i', $plgFolder );
							} else {
								$guessMatch		=	preg_match( '/language_\w+_' . preg_quote( $installedPlugin->folder ) . '$/i', $plgFolder );
							}
						} else {
							$guessMatch			=	preg_match( '/' . preg_quote( str_replace( 'plug_', '', $installedPlugin->folder ) ) . '$/i', $plgFolder );
						}
					}

					if ( ( $plgElement && ( $plgElement == $installedPlugin->element ) )
						 || ( $plgFolder && ( $plgFolder == $installedPlugin->folder ) )
						 || ( $plgName && ( $plgName == $installedPlugin->name ) )
						 || $guessMatch
					) {
						$currentVersion			=	$_PLUGINS->getPluginVersion( $installedPlugin->id, true );

						if ( ! $currentVersion ) {
							break;
						}

						$versionCompare			=	str_replace( '+build.', '+', $currentVersion );

						if ( strpos( $package->get( 'version', null, GetterInterface::STRING ), '+build' ) === false ) {
							// Stable doesn't store metadata in XML so we need to remove it before comparing:
							$latestCompare		=	preg_replace( '/\+.*/', '', $package->get( 'version', null, GetterInterface::STRING ) );
						} else {
							$latestCompare		=	str_replace( '+build.', '+', $package->get( 'version', null, GetterInterface::STRING ) );
						}

						if ( $versionCompare == $latestCompare ) {
							$isLatest			=	1; // Up to Date
						} elseif ( version_compare( $latestCompare, $versionCompare, '>' ) ) {
							$isLatest			=	-1; // Out of Date
						} else {
							$isLatest			=	1; // Up to Date
						}

						if ( ! $guessMatch ) {
							// We only want to give up checking IF we know it's an exact match; continue checking for an exact match for guesses:
							break;
						}
					}
				}
			} elseif ( ( $package->get( 'type', null, GetterInterface::INT ) >= 1 ) && ( $package->get( 'type', null, GetterInterface::INT ) <= 7 ) ) {
				foreach ( $installedExtensions as $installedExtension ) {
					switch ( $package->get( 'type', null, GetterInterface::INT ) ) {
						case 1:
							$extType			=	'package';
							break;
						case 2:
							$extType			=	'library';
							break;
						case 3:
							$extType			=	'component';
							break;
						case 4:
							$extType			=	'plugin';
							break;
						case 5:
							$extType			=	'module';
							break;
						case 6:
							$extType			=	'language';
							break;
						case 7:
							$extType			=	'template';
							break;
						default:
							continue 2;
							break;
					}

					if ( $installedExtension->type != $extType ) {
						continue;
					}

					$extElement					=	$package->get( 'details.element', null, GetterInterface::STRING );
					$extName					=	$package->get( 'name', GetterInterface::STRING );
					$extFolder					=	trim( str_replace( $package->get( 'version', null, GetterInterface::STRING ), '', pathinfo( $package->get( 'file', null, GetterInterface::STRING ), PATHINFO_FILENAME ) ), '_ -' );
					$guessMatch					=	( $extElement ? false : preg_match( '/' . preg_quote( $installedExtension->element ) . '$/i', $extFolder ) );

					if ( ( $extElement && ( $extElement == $installedExtension->element ) )
						 || ( $extFolder && ( $extFolder == $installedExtension->element ) )
						 || ( $extName && ( $extName == $installedExtension->name ) )
						 || $guessMatch
					) {
						if ( ! $installedExtension->manifest_cache ) {
							break;
						}

						if ( $extFolder == 'pkg_communitybuilder' ) {
							// CBs version is more accurately checked using CB Core version since we don't give Joomla the +build version:
							$currentVersion		=	$_PLUGINS->getPluginVersion( 1, true );
						} else {
							$extDetails			=	new Registry( $installedExtension->manifest_cache );
							$currentVersion		=	$extDetails->get( 'version', null, GetterInterface::STRING );
						}

						if ( ! $currentVersion ) {
							break;
						}

						$versionCompare			=	str_replace( '+build.', '+', $currentVersion );

						if ( ( $extFolder == 'pkg_communitybuilder' ) || ( strpos( $currentVersion, '+build' ) !== false ) ) {
							if ( strpos( $package->get( 'version', null, GetterInterface::STRING ), '+build' ) === false ) {
								// Stable doesn't store metadata in XML so we need to remove it before comparing:
								$latestCompare	=	preg_replace( '/\+.*/', '', $package->get( 'version', null, GetterInterface::STRING ) );
							} else {
								$latestCompare	=	str_replace( '+build.', '+', $package->get( 'version', null, GetterInterface::STRING ) );
							}
						} else {
							// Joomla doesn't typically contain metadata so if it doesn't already have metadata then don't compare (e.g. don't check nightly against stable):
							$latestCompare		=	preg_replace( '/\+.*/', '', $package->get( 'version', null, GetterInterface::STRING ) );
						}

						if ( $versionCompare == $latestCompare ) {
							$isLatest			=	1; // Up to Date
						} elseif ( version_compare( $latestCompare, $versionCompare, '>' ) ) {
							$isLatest			=	-1; // Out of Date
						} else {
							$isLatest			=	1; // Up to Date
						}

						if ( ! $guessMatch ) {
							// We only want to give up checking IF we know it's an exact match; continue checking for an exact match for guesses:
							break;
						}
					}
				}
			}
		}

		return $isLatest;
	}

	/**
	 * Installs plugin by upload from URL
	 *
	 * @return boolean
	 */
	private function installPluginUpload()
	{
		global $_FILES;

		// Try extending time, as unziping/ftping took already quite some... :
		@set_time_limit( 240 );

		_CBsecureAboveForm();

		outputCbTemplate( 2 );
		outputCbJs();
		initToolTip( 2 );

		$installer	=	new cbInstallerPlugin();

		// Check if file uploads are enabled
		if ( ! (bool) ini_get( 'file_uploads' ) ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('The installer cannot continue before file uploads are enabled. Please use the install from directory method.'),
				CBTxt::T('Installer - Error'),
				false
			);
			return false;
		}

		// Check that the zlib is available
		if( ! extension_loaded( 'zlib' ) ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('The installer cannot continue before zlib is installed'),
				CBTxt::T('Installer - Error'),
				false
			);
			return false;
		}

		$userfile				=	cbGetParam( $_FILES, 'uploadfile', null );

		if ( ! $userfile || ( $userfile == null ) ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('No file selected'),
				CBTxt::T('Upload new plugin - error'),
				false
			);
			return false;
		}

//	$userfile['tmp_name']	=	stripslashes( $userfile['tmp_name'] );
//	$userfile['name']		=	stripslashes( $userfile['name'] );

		$msg		=	'';
		$localName	=	$_FILES['uploadfile']['name'];
		$resultdir	=	$this->uploadFile( $_FILES['uploadfile']['tmp_name'], $localName , $msg );		// $localName is updated here

		if ( $resultdir === false ) {
			cbInstaller::showInstallMessage(
				$msg,
				CBTxt::T( 'UPLOAD_ERROR', 'Upload Error' ),
				false
			);
			return false;
		}

		if ( ! $installer->upload( $localName ) ) {
			if ( $installer->unpackDir() ) {
				$installer->cleanupInstall( $localName, $installer->unpackDir() );
			}
			cbInstaller::showInstallMessage(
				$installer->getError(),
				CBTxt::T( 'UPLOAD_FAILED', 'Upload Failed'),
				false
			);
			return false;
		}

		$ret	=	$installer->install();

		$installer->cleanupInstall( $localName, $installer->unpackDir() );

		cbInstaller::showInstallMessage(
			$installer->getError(),
			( $ret ? CBTxt::T( 'UPLOAD_SUCCESS', 'Upload Success' ) : CBTxt::T( 'UPLOAD_FAILED', 'Upload Failed' ) ),
			$ret
		);

		$installer->cleanupInstall( $localName, $installer->unpackDir() );

		return $ret;
	}

	/**
	 * Changes the permissions of file
	 *
	 * @param  string   $filename  Filename with path
	 * @return boolean             Success
	 */
	private function _cbAdmin_chmod( $filename )
	{
		global $_CB_framework;

		cbimport( 'cb.adminfilesystem' );
		$adminFS			=	cbAdminFileSystem::getInstance();

		$origmask			=	null;
		if ( $_CB_framework->getCfg( 'dirperms' ) == '' ) {
			// rely on umask
			// $mode			=	0777;
			return true;
		} else {
			$origmask		=	@umask( 0 );
			$mode			=	octdec( $_CB_framework->getCfg( 'dirperms' ) );
		}

		$ret				=	$adminFS->chmod( $filename, $mode );

		if ( isset( $origmask ) ) {
			@umask( $origmask );
		}
		return $ret;
	}

	/**
	 * Uploads a file into the filesystem
	 *
	 * @param  string  $filename       Input filename for move_uploaded_file()
	 * @param  string  $userfile_name  INPUT+OUTPUT: Destination filesname
	 * @param  string  $msg            OUTPUT: Message for user
	 * @return boolean                 Success
	 */
	private function uploadFile( $filename, &$userfile_name, &$msg )
	{
		global $_CB_framework;

		cbimport( 'cb.adminfilesystem' );
		$adminFS			=	cbAdminFileSystem::getInstance();

		$baseDir			=	_cbPathName( $_CB_framework->getCfg('tmp_path') );
		$userfile_name		=	$baseDir . $userfile_name;		// WARNING: this parameter is returned !

		if ( $adminFS->file_exists( $baseDir ) ) {
			if ( $adminFS->is_writable( $baseDir ) ) {
				if ( move_uploaded_file( $filename, $userfile_name ) ) {
//			    if ( $this->_cbAdmin_chmod( $userfile_name ) ) {
					return true;
//				} else {
//					$msg = CBTxt::T('Failed to change the permissions of the uploaded file.');
//				}
				} else {
					$msg = sprintf( CBTxt::T('Failed to move uploaded file to %s directory.'), '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
				}
			} else {
				$msg = sprintf( CBTxt::T('Upload failed as %s directory is not writable.'), '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
			}
		} else {
			$msg = sprintf( CBTxt::T('Upload failed as %s directory does not exist.'), '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
		}
		return false;
	}

	/**
	 * Installs the plugin From Directory
	 *
	 * @param  string   $userfile  Filename
	 * @return boolean             Success
	 */
	private function installPluginDir( $userfile )
	{
		// Try extending time, as unziping/ftping took already quite some... :
		@set_time_limit( 240 );

		_CBsecureAboveForm();

		outputCbTemplate( 2 );
		outputCbJs();
		initToolTip( 2 );

		$installer = new cbInstallerPlugin();

		// Check if file name exists
		if ( ! $userfile ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('No file selected'),
				CBTxt::T('Install new plugin from directory - error'),
				false
			);
			return false;
		}

		$path = _cbPathName( $userfile );
		if (!is_dir( $path )) {
			$path = dirname( $path );
		}

		$ret = $installer->install( $path);

		cbInstaller::showInstallMessage(
			$installer->getError(),
			sprintf( CBTxt::T('Install new plugin from directory %s'), $userfile ) . ' - ' . ( $ret ? CBTxt::T('Success') : CBTxt::T('Failed') ),
			$ret
		);

		return $ret;
	}

	/**
	 * Installs the plugin From URL
	 *
	 * @param string  $userfileURL   Url
	 * @param string  $userfileTitle Title
	 * @param boolean $fromWeb       true: from install from web; false: from install from url
	 * @return boolean               Success
	 */
	private function installPluginURL( $userfileURL, $userfileTitle = null, $fromWeb = false )
	{
		global $_CB_framework;

		// Try extending time, as unziping/ftping took already quite some... :
		@set_time_limit( 240 );

		_CBsecureAboveForm();

		outputCbTemplate( 2 );
		outputCbJs();
		initToolTip( 2 );

		$installer = new cbInstallerPlugin();

		// Check that the zlib is available
		if( ! extension_loaded( 'zlib' ) ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('The installer cannot continue before zlib is installed'),
				CBTxt::T('Installer - Error'),
				false
			);
			return false;
		}

		if ( ! $userfileURL ) {
			cbInstaller::showInstallMessage(
				CBTxt::T('No URL selected'),
				CBTxt::T('Upload new plugin - error'),
				false
			);
			return false;
		}


		cbimport( 'cb.adminfilesystem' );
		$adminFS			=	cbAdminFileSystem::getInstance();

		if ( $adminFS->isUsingStandardPHP() ) {
			$baseDir		=	_cbPathName( $_CB_framework->getCfg('tmp_path') );
		} else {
			$baseDir		=	$_CB_framework->getCfg( 'absolute_path' ) . '/tmp/';
		}
		$userfileName		=	$baseDir . 'comprofiler_temp.zip';


		$msg			=	'';

		$resultdir		=	$this->uploadFileURL( $userfileURL, $userfileName, $msg, $userfileTitle, $fromWeb );

		if ( $resultdir === false ) {
			cbInstaller::showInstallMessage(
				$msg,
				sprintf(CBTxt::T('Download %s - Download Error'), ( $userfileTitle ? $userfileTitle : $userfileURL )),
				false
			);
			return false;
		}

		if ( ! $installer->upload( $userfileName ) ) {
			cbInstaller::showInstallMessage(
				$installer->getError(),
				sprintf(CBTxt::T('Download %s - Upload Failed'), ( $userfileTitle ? $userfileTitle : $userfileURL )),
				false
			);
			return false;
		}

		$ret = $installer->install();

		cbInstaller::showInstallMessage(
			$installer->getError(),
			sprintf( CBTxt::T('Download %s'), ( $userfileTitle ? $userfileTitle : $userfileURL ) ) . ' - ' . ( $ret ? CBTxt::T('Success') : CBTxt::T('Failed') ),
			$ret
		);

		$installer->cleanupInstall( $userfileName, $installer->unpackDir() );

		return $ret;
	}

	/**
	 * Installs the plugin By in-place Discovery
	 *
	 * @param  string   $plgFile  Directory discovered
	 * @return boolean            Success
	 */
	private function installPluginDisc( $plgFile )
	{
		global $_CB_framework;

		// Try extending time, as unziping/ftping took already quite some... :
		@set_time_limit( 240 );

		_CBsecureAboveForm();

		outputCbTemplate( 2 );
		outputCbJs();
		initToolTip( 2 );

		$installer	=	new cbInstallerPlugin();

		// Check if file xml exists
		if ( ! $plgFile ) {
			cbInstaller::showInstallMessage(
				CBTxt::T( 'No file selected' ),
				CBTxt::T( 'Install new plugin from discovery - error' ),
				false
			);
			return false;
		}

		$path		=	_cbPathName( $_CB_framework->getCfg( 'absolute_path' ) . '/components/com_comprofiler/plugin/' . $plgFile );

		if ( ! is_dir( $path ) ) {
			$path	=	dirname( $path );
		}

		if ( ! is_dir( $path ) ) {
			cbInstaller::showInstallMessage(
				CBTxt::T( 'FILE_DOES_NOT_EXIST_FILE', 'File does not exist - [file]', array( '[file]' => $path ) ),
				CBTxt::T( 'INSTALL_NEW_PLUGIN_FROM_DISCOVERY_ERROR', 'Install new plugin from discovery - error' ),
				false
			);
			return false;
		}

		$ret		=	$installer->install( $path, true );

		cbInstaller::showInstallMessage(
			$installer->getError(),
			CBTxt::T( 'INSTALL_NEW_PLUGIN_FROM_DISCOVERY_ERROR_FILE_STATUS', 'Install new plugin from discovery - [file] - [status]',
				array( '[file]' => $path, '[status]' => ( $ret ? CBTxt::T( 'Success' ) : CBTxt::T( 'Failed' ) ) )
			),
			$ret
		);

		return $ret;
	}

	/**
	 * Uploads a file from a Url into a file on the filesystem
	 *
	 * @param  string  $userfileURL    Url
	 * @param  string  $userfile_name  INPUT+OUTPUT: Destination filesname
	 * @param  string  $msg            OUTPUT: Message for user
	 * @param  string  $userfileTitle  Title of what is being downloaded    
	 * @param  boolean $fromWeb        true: from install from web; false: from install from url
	 * @return boolean                 Success
	 */
	private function uploadFileURL( $userfileURL, $userfile_name, &$msg, $userfileTitle = null, $fromWeb = false )
	{
		global $_CB_framework;

		cbimport( 'cb.adminfilesystem' );
		$adminFS					=	cbAdminFileSystem::getInstance();

		if ( $adminFS->isUsingStandardPHP() ) {
			$baseDir				=	_cbPathName( $_CB_framework->getCfg('tmp_path') );
		} else {
			$baseDir				=	$_CB_framework->getCfg( 'absolute_path' ) . '/tmp';
		}

		if ( file_exists( $baseDir ) ) {
			if ( $adminFS->is_writable( $baseDir ) || ! $adminFS->isUsingStandardPHP() ) {
				$resultError			=	null;

				try {
					$guzzleHttpClient	=	new GuzzleHttp\Client();

					if ( $fromWeb ) {
						// Send the access token and domain with the install from web download request:
						if ( Application::Config()->get( 'installFromWebKey', null, GetterInterface::STRING ) ) {
							$token				=	Application::Config()->get( 'installFromWebToken', null, GetterInterface::STRING );
						} else {
							$token				=	Application::Session()->get( 'installFromWebToken', null, GetterInterface::STRING );
						}

						$query					=	array(	'token'			=>	$token,
															'domain_hash'	=>	hash( 'sha256', $_CB_framework->getCfg( 'live_site' ) )
													);

						if ( cbGuzzleVersion() >= 6 ) {
							// Can't mix URL parameters and query option in Guzzle 6.0.0+ so set the path entirely in query option:
							$urlQuery			=	array();

							parse_str( parse_url( $userfileURL, PHP_URL_QUERY ), $urlQuery );

							$guzzleRequest		=	$guzzleHttpClient->get( 'https://www.joomlapolis.com/index.php', array( 'timeout' => 90, 'query' => array_merge( $urlQuery, $query ) ) );
						} else {
							// Prioritize sending the access token we have stored over the one included with the URL:
							if ( $token && ( strpos( $userfileURL, 'token' ) !== false ) ) {
								$userfileURL	=	preg_replace( '/&(?:amp;)?token=[^&]*/', '', $userfileURL );
							}

							$guzzleRequest		=	$guzzleHttpClient->get( $userfileURL, array( 'timeout' => 90, 'query' => $query ) );
						}
					} else {
						$guzzleRequest	=	$guzzleHttpClient->get( $userfileURL, array( 'timeout' => 90 ) );
					}
				} catch( Exception $e ) {
					if ( $e->hasResponse() ) {
						$resultError	=	htmlspecialchars( $e->getResponse()->getReasonPhrase() ) . ': ' . $e->getResponse()->getStatusCode();
					} else {
						$resultError	=	$e->getMessage();
					}

					$guzzleRequest		=	false;
				}

				if ( $guzzleRequest !== false ) {
					if ( $guzzleRequest->getStatusCode() == 200 ) {
						$adminFS		=	cbAdminFileSystem::getInstance();
						if ( $adminFS->file_put_contents( $userfile_name, (string) $guzzleRequest->getBody() ) ) {
//							if ( $this->_cbAdmin_chmod( $userfile_name ) ) {
								return true;
//							} else {
//								$msg = sprintf(CBTxt::T('Failed to change the permissions of the uploaded file %s'), $userfile_name);
//							}
						} else {
							$msg = sprintf(CBTxt::T('Failed to create and write uploaded file in %s'), $userfile_name);
						}
					} else {
						$msg = sprintf( CBTxt::T('Failed to download package file from <code>%s</code> to webserver due to following status: %s'), ( $userfileTitle ? $userfileTitle : $userfileURL ), htmlspecialchars( $guzzleRequest->getReasonPhrase() ) . ': ' . $guzzleRequest->getStatusCode() );
					}
				} elseif ( $resultError ) {
					$msg = sprintf( CBTxt::T('Failed to download package file from <code>%s</code> to webserver due to following error: %s'), ( $userfileTitle ? $userfileTitle : $userfileURL ), htmlspecialchars( $resultError ) );
				}
			} else {
				$msg = sprintf( CBTxt::T('Upload failed as %s directory is not writable.'), '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
			}
		} else {
			$msg = sprintf( CBTxt::T('Upload failed as %s directory does not exist.'), '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
		}
		return false;
	}
}
