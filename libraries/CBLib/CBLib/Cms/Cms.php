<?php
/**
* CBLib, Community Builder Library(TM)
* @version $Id: 09.06.13 01:23 $
* @package ${NAMESPACE}
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

namespace CBLib\Cms;

use CBLib\Application\ApplicationContainerInterface;

defined('CBLIB') or die();

/**
 * CBLib\Cms Class implementation
 * 
 */
abstract class Cms
{
	/**
	 * Returns the Cms object corresponding to the CMS running.
	 *
	 * @throws \LogicException
	 *
	 * @return CmsInterface|callable
	 */
	public static function getGetCmsFunction( ) {
		return function ( ApplicationContainerInterface $di )
			{
				if ( ! defined( 'JVERSION' ) ) {
					throw new \LogicException( 'Unknown CMS', 500 );
				}

				if ( version_compare( JVERSION, '5.0', 'ge' ) ) {
					return new Joomla\Joomla5( $di );
				}

				if ( version_compare( JVERSION, '4.0-beta2', 'ge' ) ) {
					return new Joomla\Joomla4( $di );
				}

				if ( version_compare( JVERSION, '3.0', 'ge' ) ) {
					return new Joomla\Joomla3( $di );
				}

				throw new \LogicException( 'Unsupported Joomla version', 500 );
			};
	}
}
