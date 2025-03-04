<?php
/**
* CBLib, Community Builder Library(TM)
* @version $Id: 6/20/14 6:46 PM $
* @copyright (C) 2004-2024 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Language\CBTxt;

defined('CBLIB') or die();

/**
 * cbValidator Class implementation
 * Form validation support class
 */
class cbValidator
{
	/**
	 * Class rules for validation
	 * @var array
	 */
	static $classRules	=	array();

	/**
	 * Rules for validation
	 * @var array
	 */
	static $rules		=	array();

	/**
	 * Determines if inputmasks exist to see if we need to load inputmask jQuery
	 * @var bool
	 */
	static $masks		=	false;

	/**
	 * Determines if resize exist to see if we need to load ui-all jQuery
	 * @var bool
	 */
	static bool $resize		=	false;

	/**
	 * Add validation rules for a CSS class
	 *
	 * @param  string  $class  Name of CSS class
	 * @param  array   $rules  Associative array of rules to apply to the class
	 * @return void
	 */
	static function addClassRule( $class, $rules )
	{
		self::$classRules[$class]	=	$rules;
	}

	/**
	 * Adds a validation rule
	 *
	 * @param  string  $rule        The validation rule
	 * @param  string  $validation  The JS validation code (should return true or false)
	 * @param  string  $message     The default invalid message
	 * @return void
	 */
	static function addRule( $rule, $validation, $message = null )
	{
		if ( ! $message ) {
			$message			=	CBTxt::T( 'VALIDATION_ERROR_FIELD_NEEDS_FIX', 'Please fix this field.' );
		}

		self::$rules[$rule]		=	array( $validation, $message );
	}

	/**
	 * Returns html attributes for a validation rule
	 *
	 * @param  string  $rule     The validation rule
	 * @param  mixed   $params   The parameters to be used by the validation rule
	 * @param  string  $message  The invalid message
	 * @return string
	 */
	static function getRuleHtmlAttributes( $rule, $params = true, $message = null )
	{
		if ( $rule === 'accept' ) {
			// Replace the old accept usage with mimetype rule to avoid conflict with native accept validation
			$rule			=	'mimetype';
		}

		if ( ( $rule === 'resize' )
			 && ( ! self::$resize )
			 && is_array( $params )
			 && ( (int) ( $params[2] ?? 0 ) === 3 )
		) {
			// Force jQuery UI-ALL to load if client side resizing is enabled with user cropping
			self::$resize	=	true;
		}

		if ( is_bool( $params ) ) {
			$params			=	( $params ? 'true' : 'false' );
		} elseif ( is_array( $params ) || is_object( $params ) ) {
			$params			=	json_encode( $params );
		} elseif ( $rule == 'pattern' ) {
			$params			=	rawurlencode( $params );
		}

		$attributes			=	' data-rule-' . htmlspecialchars( $rule ) . '="' . htmlspecialchars( $params ) . '"';

		if ( $message ) {
			$attributes		.=	' data-msg-' . htmlspecialchars( $rule ) . '="' . htmlspecialchars( $message ) . '"';
		}

		return $attributes;
	}

	/**
	 * Returns html attributes for a validation messages
	 *
	 * @param  string  $validMessage    The valid message
	 * @param  string  $invalidMessage  The invalid message
	 * @return string
	 */
	static function getMsgHtmlAttributes( $validMessage = null, $invalidMessage = null )
	{
		$attributes			=	null;

		if ( $validMessage ) {
			$attributes		.=	' data-msg-success="' . htmlspecialchars( $validMessage ) . '"';
		}

		if ( $invalidMessage ) {
			$attributes		.=	' data-msg="' . htmlspecialchars( $invalidMessage ) . '"';
		}

		return $attributes;
	}

	/**
	 * Returns html attributes for a validation submit button
	 *
	 * @param  string  $submitMessage   The message to change the button to on submit
	 * @return string
	 */
	static function getSubmitBtnHtmlAttributes( $submitMessage = null )
	{
		if ( ! $submitMessage ) {
			$submitMessage	=	CBTxt::T( 'FORM_SUBMIT_LOADING', 'Loading...' );
		}

		return ' data-submit-text="' . htmlspecialchars( $submitMessage ) . '"';
	}

	/**
	 * Returns html attributes for a input mask
	 *
	 * @param string                   $mask      The type of input mask to apply
	 * @param string|array|object|null $params    The parameters to be used by the input mask
	 * @param int|null                 $direction 0|null: left to right, 1: right to left; note this only applies to non-alias/pattern usages
	 * @return string
	 */
	static function getMaskHtmlAttributes( $mask = null, $params = null, $direction = 0 )
	{
		self::$masks		=	true;

		if ( is_bool( $params ) ) {
			$params			=	( $params ? 'true' : 'false' );
		}

		$inputMask			=	null;

		if ( is_array( $params ) || is_object( $params ) ) {
			$inputMask		=	' data-cbinputmask-options="' . htmlspecialchars( json_encode( $params ) ) . '"';
		} elseif ( $mask == 'alias' ) {
			$inputMask		=	' data-cbinputmask-alias="' . htmlspecialchars( $params ) . '"';
		} elseif ( $mask == 'pattern' ) {
			$inputMask		=	' data-cbinputmask-regex="' . htmlspecialchars( rawurlencode( $params ) ) . '"';
		} else {
			$inputMask		=	' data-cbinputmask-mask="' . htmlspecialchars( $params ) . '"';

			if ( $direction ) {
				$inputMask	.=	' data-cbinputmask-direction="r2l"';
			}
		}

		return $inputMask;
	}

	/**
	 * Loads the CB jQuery Validation into the header
	 *
	 * @param  string  $selector  The jQuery selector to bind validation to
	 * @return void
	 */
	static function loadValidation( $selector = '.cbValidation' )
	{
		global $_CB_framework;

		static $options				=	null;

		if ( ! $options ) {
			$liveSite				=	$_CB_framework->getCfg( 'live_site' ) . ( $_CB_framework->getUi() == 2 ? '/administrator' : null );

			$messages				=	array(	'required'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_REQUIRED', 'This field is required.' ),
												'requiredIf'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_REQUIRED', 'This field is required.' ),
												'remote'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_NEEDS_FIX', 'Please fix this field.' ),
												'email'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_EMAIL', 'Please enter a valid email address.' ),
												'url'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_URL', 'Please enter a valid URL.' ),
												'date'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_DATE', 'Please enter a valid date.' ),
												'dateISO'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_DATE_ISO', 'Please enter a valid date (ISO).' ),
												'number'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_NUMBER', 'Please enter a valid number.' ),
												'digits'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_DIGITS_ONLY', 'Please enter only digits.' ),
												'creditcard'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_CREDIT_CARD_NUMBER', 'Please enter a valid credit card number.' ),
												'equalTo'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_SAME_VALUE_AGAIN', 'Please enter the same value again.' ),
												'notEqualTo'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_DIFFERENT_VALUE', 'Please enter a different value, values must not be the same.' ),
												'mimetype'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_EXTENSION', 'Please enter a value with a valid extension.' ),
												'maxlength'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_MORE_THAN_CHARS', 'Please enter no more than {0} characters.' ),
												'minlength'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_LEAST_CHARS', 'Please enter at least {0} characters.' ),
												'maxselect'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_MORE_THAN_OPTS', 'Please select no more than {0} options.' ),
												'minselect'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_LEAST_OPTS', 'Please select at least {0} options.' ),
												'maxage'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_MAX_AGE', 'You must be no more than {0} years old.' ),
												'minage'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_MIN_AGE', 'You must be at least {0} years old.' ),
												'rangeage'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_AGE_RANGE', 'You must be at least {0} years old, but not older than {1}.' ),
												'rangelength'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_BETWEEN_AND_CHARS', 'Please enter a value between {0} and {1} characters long.' ),
												'range'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_BETWEEN_AND_NUMBER', 'Please enter a value between {0} and {1}.' ),
												'max'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_LESS_OR_EQUAL_TO', 'Please enter a value less than or equal to {0}.' ),
												'min'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_GREATER_OR_EQUAL_TO', 'Please enter a value greater than or equal to {0}.' ),
												'step'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_STEP', 'Please enter a multiple of {0}.' ),
												'maxWords'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_MORE_THAN_WORDS', 'Please enter {0} words or less.' ),
												'minWords'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_LEAST_WORDS', 'Please enter at least {0} words.' ),
												'rangeWords'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_BETWEEN_AND_WORDS', 'Please enter between {0} and {1} words.' ),
												'extension'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_EXTENSION', 'Please enter a value with a valid extension.' ),
												'pattern'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_PATTERN', 'Invalid format.' ),
												'isPattern'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_IS_PATTERN', 'Invalid regular expression.' ),
												'cbfield'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_NEEDS_FIX', 'Please fix this field.' ),
												'cbremote'			=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_NEEDS_FIX', 'Please fix this field.' ),
												'cbusername'		=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_USERNAME', 'Please enter a valid username with no space at beginning or end and must not contain the following characters: < > \ " \' % ; ( ) &' ),
												'cburl'				=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_URL', 'Please enter a valid URL.' ),
												'filesize'			=>	CBTxt::T( 'VALIDATION_ERROR_FILZSIZE', 'File size must exceed the minimum of {0} {2}s, but not the maximum of {1} {2}s.' ),
												'filesizemin'		=>	CBTxt::T( 'VALIDATION_ERROR_FILZSIZE_MIN', 'File size exceeds the minimum of {0} {2}s.' ),
												'filesizemax'		=>	CBTxt::T( 'VALIDATION_ERROR_FILZSIZE_MAX', 'File size exceeds the maximum of {1} {2}s.' ),
												'cropwidth'			=>	CBTxt::T( 'VALIDATION_IMAGE_CROP_WIDTH', 'Image exceeds the maximum width. Please select the area to crop your image within the maximum width.' ),
												'cropheight'		=>	CBTxt::T( 'VALIDATION_IMAGE_CROP_HEIGHT', 'Image exceeds the maximum height. Please select the area to crop your image within the maximum height.' ),
												'forbiddenWords'	=>	CBTxt::T( 'VALIDATION_ERROR_FIELD_PATTERN', 'Invalid format.' )
			);

			$settings				=	array();

			$settings['cbfield']	=	array( 'url' => $liveSite . '/index.php?option=com_comprofiler&view=fieldclass&function=[function]&user=[user]&field=[field]&reason=[reason]&format=raw' );

			$settings['cbremote']	=	array( 'csrf' => Application::Session()->getFormTokenName() );

			$options				=	array( 'messages' => $messages, 'settings' => $settings );
		}

		$js							=	null;

		static $selectors			=	array();

		if ( ! isset( $selectors[$selector] ) ) {
			$selectors[$selector]	=	true;

			$js						.=	"$( " . json_encode( $selector, JSON_HEX_TAG ) . " ).cbvalidate(" . json_encode( $options ) . ");";

			if ( self::$masks ) {
				$js					.=	"$( " . json_encode( $selector, JSON_HEX_TAG ) . " ).find( 'input[data-cbinputmask-options],input[data-cbinputmask-mask],input[data-cbinputmask-regex],input[data-cbinputmask-alias]' ).cbinputmask();";
			}
		}

		static $rules				=	array();

		foreach ( self::$rules as $method => $rule ) {
			if ( ! isset( $rules[$method] ) ) {
				$rules[$method]		=	true;

				$js					.=	"$.validator.addMethod( " . json_encode( $method, JSON_HEX_TAG ) . ", function( value, element, params ) {"
									.		$rule[0]
									.	"}, $.validator.format( " . json_encode( $rule[1], JSON_HEX_TAG ) . " ) );";
			}
		}

		static $classRules			=	array();

		foreach ( self::$classRules as $class => $rules ) {
			if ( ! isset( $classRules[$class] ) ) {
				$classRules[$class]	=	true;

				$js					.=	"$.validator.addClassRules( " . json_encode( $class, JSON_HEX_TAG ) . ", JSON.parse( '" . addcslashes( json_encode( $rules ), "'" ) . "' ) );";
			}
		}

		if ( $js ) {
			$plugins				=	[ 'cbvalidate' ];

			if ( self::$masks ) {
				$plugins[]			=	'cbinputmask';
			}

			if ( self::$resize ) {
				$plugins[]			=	'ui-all';
			}

			$_CB_framework->outputCbJQuery( $js, $plugins );
		}
	}

	/**
	 * Outputs the validator Javascript into the header using the CB jQuery output methods
	 * @deprecated 2.0 Use loadValidation instead
	 *
	 * @param  string  $js        [optional] Additional Javascript to output
	 * @param  string  $selector  [optional] CSS Selector for the outer DOM element to apply validation for [default '#cbcheckedadminForm']
	 * @return void
	 */
	static function outputValidatorJs( $js = null, $selector = '#cbcheckedadminForm' )
	{
		global $_CB_framework;

		if ( ! $selector ) {
			$selector	=	'#cbcheckedadminForm';
		}

		if ( $js ) {
			$_CB_framework->outputCbJQuery( $js );
		}

		self::loadValidation( $selector );
	}
}
