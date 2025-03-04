<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_conveythis
 *
 * @copyright   Copyright (C) 2024 www.conveythis.com, All rights reserved.
 * @license     ConveyThis Translate is licensed under GPLv2 license.
 */
defined( '_JEXEC' ) or die;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;



//if (!defined( 'CONVEYTHIS_JAVASCRIPT_PLUGIN_URL'))
//	define( 'CONVEYTHIS_JAVASCRIPT_PLUGIN_URL', '//cdn.conveythis.com/javascript/65' );

if (!defined( 'CONVEYTHIS_API_URL'))
	define( 'CONVEYTHIS_API_URL', 'https://api.conveythis.com' );


$title = JText::_('ConveyThis');

JToolBarHelper::title( $title, 'conveythis' );

$document = Factory::getDocument();

$document->setTitle( $title );

$document->addStyleSheet(JUri::root() . '/media/conveythis/css/dropdown.min.css');
$document->addStyleSheet(JUri::root() . '/media/conveythis/css/transition.min.css');
$document->addStyleSheet(JUri::root() . '/media/conveythis/css/range.css');

$document->addScript(JUri::root() . '/media/conveythis/js/jquery-3.3.1.min.js');
$document->addScript(JUri::root() . '/media/conveythis/js/dropdown.min.js');
$document->addScript(JUri::root() . '/media/conveythis/js/transition.min.js');
$document->addScript(JUri::root() . '/media/conveythis/js/range.js');

if( file_exists( JPATH_PLUGINS . '/system/conveythis/conveythis.php' ) )
{
	$plugin = PluginHelper::getPlugin('system', 'conveythis');

	if( $plugin )
	{
		$params = new JRegistry( $plugin->params );

		$app = JFactory::getApplication();
		$input = $app->input->post->getArray();
        $config = JFactory::getConfig();
        $allow_url_rewrite = $config->get('sef_rewrite');

        if( !empty( $input ) )
		{
            if (!empty($input['api_key'])) {
                $pattern = '/^(pub_)?[a-zA-Z0-9]{32}$/';

                if (preg_match($pattern, $input['api_key'])) {
                    $params->set('api_key', $input['api_key']);
                }else {
                    $message = 'The API key you supplied is incorrect. Please try again.';
                    $input['api_key'] = '';
                    $app->enqueueMessage($message, 'error');
                }
            }

			if( !empty( $input['source_language'] ) )
			{
				$params->set( 'source_language', $input['source_language'] );
			}

			if( !empty( $input['target_languages'] ) )
			{
				$target_languages = explode( ',', $input['target_languages'] );
				$params->set( 'target_languages', $target_languages );
			}
			
			if( isset( $input['auto_translate'] ) )
			{
				$params->set( 'auto_translate', $input['auto_translate'] );
			}
			
			if( isset( $input['hide_conveythis_logo'] ) )
			{
				$params->set( 'hide_conveythis_logo', $input['hide_conveythis_logo'] );
			}
			
			if( !empty( $input['style_change_language'] ) )
			{
				$params->set( 'style_change_language', $input['style_change_language'] );
			}

			if( !empty( $input['style_change_flag'] ) )
			{
				$params->set( 'style_change_flag', $input['style_change_flag'] );
			}

			if( !empty( $input['style_flag'] ) )
			{
				$params->set( 'style_flag', $input['style_flag'] );
			}

			if( !empty( $input['style_text'] ) )
			{
				$params->set( 'style_text', $input['style_text'] );
			}

			if( !empty( $input['style_position_vertical'] ) )
			{
				$params->set( 'style_position_vertical', $input['style_position_vertical'] );
			}

			if( !empty( $input['style_position_horizontal'] ) )
			{
				$params->set( 'style_position_horizontal', $input['style_position_horizontal'] );
			}
			
			if( !empty( $input['style_indenting_vertical'] ) )
			{
				$params->set( 'style_indenting_vertical', $input['style_indenting_vertical'] );
			}
			
			if( !empty( $input['style_indenting_horizontal'] ) )
			{
				$params->set( 'style_indenting_horizontal', $input['style_indenting_horizontal'] );
			}else{
				$params->set( 'style_indenting_horizontal', 0 );
			}
			
			if( !empty( $input['style_position_type'] ) )
			{
				$params->set( 'style_position_type', $input['style_position_type'] );
			}
			if( !empty( $input['style_position_vertical_custom'] ) )
			{
				$params->set( 'style_position_vertical_custom', $input['style_position_vertical_custom'] );
			}
			if( !empty( $input['style_selector_id'] ) )
			{
				$params->set( 'style_selector_id', $input['style_selector_id'] );
			}			

			if( isset( $input['alternate'] ) )
			{
				if( !empty( $input['alternate'] ) )
				{
					$params->set( 'alternate', 'on' );
				}

				else
				{
					$params->set( 'alternate', 'off' );
				}
			}

			$extension = JTable::getInstance( 'extension' );
			$extension->load( array('element' => 'conveythis') );
			$extension->bind( array(
				'params' => $params->toString()
			));

			if (!$extension->check()) {
				$this->setError($extension->getError());
				return false;
			}
			if (!$extension->store()) {
				$this->setError($extension->getError());
				return false;
			}
		}

		PluginHelper::importPlugin( 'system', 'conveythis' );
		
        list($languages) = $app->triggerEvent('onGetLanguages');
        
        list($flags) = $app->triggerEvent('onGetFlags');
        
		$language_code = '';
		$referrer = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$error = array();
		$api_key = $params->get( 'api_key', '' );
		$source_language = $params->get( 'source_language', '' );
		$target_languages = $params->get( 'target_languages', array() );
		$style_change_language = $params->get( 'style_change_language', array() );
		$style_change_flag = $params->get( 'style_change_flag', array() );
		$style_flag = $params->get( 'style_flag', 'rect' );
		$style_text = $params->get( 'style_text', 'full-text' );
		$style_position_vertical = $params->get( 'style_position_vertical', 'bottom' );
		$style_position_horizontal = $params->get( 'style_position_horizontal', 'right' );
		$style_indenting_vertical = $params->get( 'style_indenting_vertical', '0' );
		$style_indenting_horizontal = $params->get( 'style_indenting_horizontal', '24' );
		$auto_translate = $params->get( 'auto_translate', '1' );
		$hide_conveythis_logo = $params->get( 'hide_conveythis_logo', '0' );
		$alternate = $params->get( 'alternate', 'on' );
		$style_position_type = $params->get( 'style_position_type', 'fixed' );
		$style_position_vertical_custom = $params->get( 'style_position_vertical_custom', 'bottom' );
		$style_selector_id = $params->get( 'style_selector_id', '' );

		if( !empty( $api_key ) )
		{
			
			if (($key = array_search($source_language, $target_languages)) !== false) { //remove source_language from target_languages
				unset($target_languages[$key]);
			}
            $data = $app->triggerEvent('onSend', ['GET', "/admin/account/plan/api-key/" . $api_key . "/" ]);

            $expiryDate = new DateTime($data[0]['trial_expires_at']);
            $currentDate = new DateTime();
            $diffInSeconds = $expiryDate->getTimestamp() - $currentDate->getTimestamp();
            $remainingDays = ceil($diffInSeconds / (60 * 60 * 24));

			$app->triggerEvent('onSend', [
			    'PUT',
            	'/website/update/',
                	[
                		'referrer' => $referrer,
                		'source_language' => $source_language,
                		'target_languages' => $target_languages,
                		'accept_language' => 0, // Option
                		'blockpages' => array(),
                	]
			    ]
            );
            $app->triggerEvent('onSend', [
                'PUT',
                '/plugin/settings/',
                [
                    'referrer' => $referrer,
                    'accept_language' => 0,
                    'blockpages' => [],
                    'technology' => 'joomla',
                    'settings' => [
                        'source_language' => $source_language,
                        'target_languages' => $target_languages,
                        'style_change_language' => $style_change_language,
                        'style_change_flag' => $style_change_flag,
                        'style_flag' => $style_flag,
                        'style_text' => $style_text,
                        'style_position_vertical' => $style_position_vertical,
                        'style_position_horizontal' => $style_position_horizontal,
                        'style_indenting_vertical' => $style_indenting_vertical,
                        'style_indenting_horizontal' => $style_indenting_horizontal,
                        'auto_translate' => $auto_translate,
                        'hide_conveythis_logo' => $hide_conveythis_logo,
                        'style_position_type' => $style_position_type,
                        'style_position_vertical_custom' => $style_position_vertical_custom,
                        'style_selector_id' => $style_selector_id,
                    ]
                ]
            ]);
        }
		else
		{
			$error['message'] = 'ConveyThis plugin installed but not yet configured.';
		}

		require_once('settings.php');
	}

	else
	{
		echo '<p>ConveyThis plugin not published.</p>';
		echo '<p>Please go to the "Extensions / Plugins" and enable ConveyThis plugin.</p>';
	}
}

else
{
	echo '<p>ConveyThis plugin not installed.</p>';
	echo '<p>Please download and install the ConveyThis plugin with the Joomla! Extensions Directory.</p>';
}
