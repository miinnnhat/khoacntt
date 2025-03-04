<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Conveythis
 *
 * @copyright   Copyright (C) 2018 www.conveythis.com, All rights reserved.
 * @license     ConveyThis Translate is licensed under GPLv2 license.
 */
defined( '_JEXEC' ) or die;

define( 'DEV_CONVEYTHIS_JAVASCRIPT_PLUGIN_URL', '//dev-cdn.conveythis.com/javascript' );
define( 'CONVEYTHIS_JAVASCRIPT_PLUGIN_URL', '//cdn.conveythis.com/javascript' );
//define( 'CONVEYTHIS_JAVASCRIPT_PLUGIN_URL', '//cnd.conveythis.com/javascript' );
define( 'CONVEYTHIS_API_URL', 'https://api.conveythis.com' );

class plgSystemConveythis extends JPlugin
{
	var $segments = array();
    var $segments_hash = array();
    var $items = array();
    var $links = array();
    var $exclusions = array();
    var $glossary = array();
    var $exclusion_blocks = array();
    var $exclusion_block_ids = array();
    var $account;
    var $api_key = '';
    var $source_language = '';
    var $target_languages = '';
    var $default_language = '';
    var $target_languages_translations = '';
    var $language_code = '';
    var $site_url;
    var $site_host;
    var $site_prefix;
    var $plan = 'free';

    private $translate_media;
    private $translate_document;
    private $translate_links;
    private $show_widget = true;
    private $exceeded = false;

	var $languages = array(
		703 => array( 'title' => 'English', 'code2' => 'en', 'code3' => 'eng', 'flag' => 'Dw0' ),
		704 => array( 'title' => 'Afrikaans', 'code2' => 'af', 'code3' => 'afr', 'flag' => '7xS' ),
		705 => array( 'title' => 'Albanian', 'code2' => 'sq', 'code3' => 'sqi', 'flag' => '5iM' ),
		706 => array( 'title' => 'Amharic', 'code2' => 'am', 'code3' => 'amh', 'flag' => 'ZH1' ),
		707 => array( 'title' => 'Arabic', 'code2' => 'ar', 'code3' => 'ara', 'flag' => 'J06' ),
		708 => array( 'title' => 'Armenian', 'code2' => 'hy', 'code3' => 'hye', 'flag' => 'q9U' ),
		709 => array( 'title' => 'Azerbaijan', 'code2' => 'az', 'code3' => 'aze', 'flag' => 'Wg1' ),
		710 => array( 'title' => 'Bashkir', 'code2' => 'ba', 'code3' => 'bak', 'flag' => 'D1H' ),
		711 => array( 'title' => 'Basque', 'code2' => 'eu', 'code3' => 'eus', 'flag' => 'none' ),
		712 => array( 'title' => 'Belarusian', 'code2' => 'be', 'code3' => 'bel', 'flag' => 'O8S' ),
		713 => array( 'title' => 'Bengali', 'code2' => 'bn', 'code3' => 'ben', 'flag' => '63A' ),
		714 => array( 'title' => 'Bosnian', 'code2' => 'bs', 'code3' => 'bos', 'flag' => 'Z1t' ),
		715 => array( 'title' => 'Bulgarian', 'code2' => 'bg', 'code3' => 'bul', 'flag' => 'V3p' ),
		716 => array( 'title' => 'Burmese', 'code2' => 'my', 'code3' => 'mya', 'flag' => 'YB9' ),
		717 => array( 'title' => 'Catalan', 'code2' => 'ca', 'code3' => 'cat', 'flag' => 'Pw6' ),
		718 => array( 'title' => 'Cebuano', 'code2' => 'ceb', 'code3' => 'ceb', 'flag' => 'none' ),
		719 => array( 'title' => 'Chinese', 'code2' => 'zh', 'code3' => 'zho', 'flag' => 'Z1v' ),
		720 => array( 'title' => 'Croatian', 'code2' => 'hr', 'code3' => 'hrv', 'flag' => '7KQ' ),
		721 => array( 'title' => 'Czech', 'code2' => 'cs', 'code3' => 'cze', 'flag' => '1ZY' ),
		722 => array( 'title' => 'Danish', 'code2' => 'da', 'code3' => 'dan', 'flag' => 'Ro2' ),
		723 => array( 'title' => 'Dutch', 'code2' => 'nl', 'code3' => 'nld', 'flag' => '8jV' ),
		724 => array( 'title' => 'Esperanto', 'code2' => 'eo', 'code3' => 'epo', 'flag' => 'Dw0' ),
		725 => array( 'title' => 'Estonian', 'code2' => 'et', 'code3' => 'est', 'flag' => 'VJ8' ),
		726 => array( 'title' => 'Finnish', 'code2' => 'fi', 'code3' => 'fin', 'flag' => 'nM4' ),
		727 => array( 'title' => 'French', 'code2' => 'fr', 'code3' => 'fre', 'flag' => 'E77' ),
		728 => array( 'title' => 'Galician', 'code2' => 'gl', 'code3' => 'glg', 'flag' => 'A5d' ),
		729 => array( 'title' => 'Georgian', 'code2' => 'ka', 'code3' => 'kat', 'flag' => '8Ou' ),
		730 => array( 'title' => 'German', 'code2' => 'de', 'code3' => 'ger', 'flag' => 'K7e' ),
		731 => array( 'title' => 'Greek', 'code2' => 'el', 'code3' => 'ell', 'flag' => 'kY8' ),
		732 => array( 'title' => 'Gujarati', 'code2' => 'gu', 'code3' => 'guj', 'flag' => 'My6' ),
		733 => array( 'title' => 'Haitian', 'code2' => 'ht', 'code3' => 'hat', 'flag' => 'none' ),
		734 => array( 'title' => 'Hebrew', 'code2' => 'he', 'code3' => 'heb', 'flag' => '5KS' ),
		735 => array( 'title' => 'Hill Mari', 'code2' => 'mrj', 'code3' => 'mrj', 'flag' => 'none' ),
		736 => array( 'title' => 'Hindi', 'code2' => 'hi', 'code3' => 'hin', 'flag' => 'My6' ),
		737 => array( 'title' => 'Hungarian', 'code2' => 'hu', 'code3' => 'hun', 'flag' => 'OU2' ),
		738 => array( 'title' => 'Icelandic', 'code2' => 'is', 'code3' => 'isl', 'flag' => 'Ho8' ),
		739 => array( 'title' => 'Indonesian', 'code2' => 'id', 'code3' => 'ind', 'flag' => 't0X' ),
		740 => array( 'title' => 'Irish', 'code2' => 'ga', 'code3' => 'gle', 'flag' => '5Tr' ),
		741 => array( 'title' => 'Italian', 'code2' => 'it', 'code3' => 'ita', 'flag' => 'BW7' ),
		742 => array( 'title' => 'Japanese', 'code2' => 'ja', 'code3' => 'jpn', 'flag' => '4YX' ),
		743 => array( 'title' => 'Javanese', 'code2' => 'jv', 'code3' => 'jav', 'flag' => 'C9k' ),
		744 => array( 'title' => 'Kannada', 'code2' => 'kn', 'code3' => 'kan', 'flag' => 'My6' ),
		745 => array( 'title' => 'Kazakh', 'code2' => 'kk', 'code3' => 'kaz', 'flag' => 'QA5' ),
		746 => array( 'title' => 'Khmer', 'code2' => 'km', 'code3' => 'khm', 'flag' => 'o8B' ),
		747 => array( 'title' => 'Korean', 'code2' => 'ko', 'code3' => 'kor', 'flag' => '0W3' ),
		748 => array( 'title' => 'Kyrgyz', 'code2' => 'ky', 'code3' => 'kir', 'flag' => 'uP6' ),
		749 => array( 'title' => 'Laotian', 'code2' => 'lo', 'code3' => 'lao', 'flag' => 'Qy5' ),
		750 => array( 'title' => 'Latin', 'code2' => 'la', 'code3' => 'lat', 'flag' => 'BW7' ),
		751 => array( 'title' => 'Latvian', 'code2' => 'lv', 'code3' => 'lav', 'flag' => 'j1D' ),
		752 => array( 'title' => 'Lithuanian', 'code2' => 'lt', 'code3' => 'lit', 'flag' => 'uI6' ),
		753 => array( 'title' => 'Luxemb', 'code2' => 'lb', 'code3' => 'ltz', 'flag' => 'EV8' ),
		754 => array( 'title' => 'Macedonian', 'code2' => 'mk', 'code3' => 'mkd', 'flag' => '6GV' ),
		755 => array( 'title' => 'Malagasy', 'code2' => 'mg', 'code3' => 'mlg', 'flag' => '4tE' ),
		756 => array( 'title' => 'Malay', 'code2' => 'ms', 'code3' => 'msa', 'flag' => 'C9k' ),
		757 => array( 'title' => 'Malayalam', 'code2' => 'ml', 'code3' => 'mal', 'flag' => 'My6' ),
		758 => array( 'title' => 'Maltese', 'code2' => 'mt', 'code3' => 'mlt', 'flag' => 'N11' ),
		759 => array( 'title' => 'Maori', 'code2' => 'mi', 'code3' => 'mri', 'flag' => '0Mi' ),
		760 => array( 'title' => 'Marathi', 'code2' => 'mr', 'code3' => 'mar', 'flag' => 'My6' ),
		761 => array( 'title' => 'Mari', 'code2' => 'mhr', 'code3' => 'chm', 'flag' => 'none' ),
		762 => array( 'title' => 'Mongolian', 'code2' => 'mn', 'code3' => 'mon', 'flag' => 'X8h' ),
		763 => array( 'title' => 'Nepali', 'code2' => 'ne', 'code3' => 'nep', 'flag' => 'E0c' ),
		764 => array( 'title' => 'Norwegian', 'code2' => 'no', 'code3' => 'nor', 'flag' => '4KE' ),
		765 => array( 'title' => 'Papiamento', 'code2' => 'pap', 'code3' => 'pap', 'flag' => 'none' ),
		766 => array( 'title' => 'Persian', 'code2' => 'fa', 'code3' => 'per', 'flag' => 'Vo7' ),
		767 => array( 'title' => 'Polish', 'code2' => 'pl', 'code3' => 'pol', 'flag' => 'j0R' ),
		768 => array( 'title' => 'Portuguese', 'code2' => 'pt', 'code3' => 'por', 'flag' => '1oU' ),
		769 => array( 'title' => 'Punjabi', 'code2' => 'pa', 'code3' => 'pan', 'flag' => 'n4T' ),
		770 => array( 'title' => 'Romanian', 'code2' => 'ro', 'code3' => 'rum', 'flag' => 'V5u' ),
		771 => array( 'title' => 'Russian', 'code2' => 'ru', 'code3' => 'rus', 'flag' => 'D1H' ),
		772 => array( 'title' => 'Scottish', 'code2' => 'gd', 'code3' => 'gla', 'flag' => '9MI' ),
		773 => array( 'title' => 'Serbian', 'code2' => 'sr', 'code3' => 'srp', 'flag' => 'GC6' ),
		774 => array( 'title' => 'Sinhala', 'code2' => 'si', 'code3' => 'sin', 'flag' => '9JL' ),
		775 => array( 'title' => 'Slovakian', 'code2' => 'sk', 'code3' => 'slk', 'flag' => 'Y2i' ),
		776 => array( 'title' => 'Slovenian', 'code2' => 'sl', 'code3' => 'slv', 'flag' => 'ZR1' ),
		777 => array( 'title' => 'Spanish', 'code2' => 'es', 'code3' => 'spa', 'flag' => 'A5d' ),
		778 => array( 'title' => 'Sundanese', 'code2' => 'su', 'code3' => 'sun', 'flag' => 'Wh1' ),
		779 => array( 'title' => 'Swahili', 'code2' => 'sw', 'code3' => 'swa', 'flag' => 'X3y' ),
		780 => array( 'title' => 'Swedish', 'code2' => 'sv', 'code3' => 'swe', 'flag' => 'oZ3' ),
		781 => array( 'title' => 'Tagalog', 'code2' => 'tl', 'code3' => 'tgl', 'flag' => '2qL' ),
		782 => array( 'title' => 'Tajik', 'code2' => 'tg', 'code3' => 'tgk', 'flag' => '7Qa' ),
		783 => array( 'title' => 'Tamil', 'code2' => 'ta', 'code3' => 'tam', 'flag' => 'My6' ),
		784 => array( 'title' => 'Tatar', 'code2' => 'tt', 'code3' => 'tat', 'flag' => 'D1H' ),
		785 => array( 'title' => 'Telugu', 'code2' => 'te', 'code3' => 'tel', 'flag' => 'My6' ),
		786 => array( 'title' => 'Thai', 'code2' => 'th', 'code3' => 'tha', 'flag' => 'V6r' ),
		787 => array( 'title' => 'Turkish', 'code2' => 'tr', 'code3' => 'tur', 'flag' => 'YZ9' ),
		788 => array( 'title' => 'Udmurt', 'code2' => 'udm', 'code3' => 'udm', 'flag' => 'none' ),
		789 => array( 'title' => 'Ukrainian', 'code2' => 'uk', 'code3' => 'ukr', 'flag' => '2Mg' ),
		790 => array( 'title' => 'Urdu', 'code2' => 'ur', 'code3' => 'urd', 'flag' => 'n4T' ),
		791 => array( 'title' => 'Uzbek', 'code2' => 'uz', 'code3' => 'uzb', 'flag' => 'zJ3' ),
		792 => array( 'title' => 'Vietnamese', 'code2' => 'vi', 'code3' => 'vie', 'flag' => 'l2A' ),
		793 => array( 'title' => 'Welsh', 'code2' => 'cy', 'code3' => 'wel', 'flag' => 'D4b' ),
		794 => array( 'title' => 'Xhosa', 'code2' => 'xh', 'code3' => 'xho', 'flag' => '7xS' ),
		795 => array( 'title' => 'Yiddish', 'code2' => 'yi', 'code3' => 'yid', 'flag' => '5KS' ),
	);
	
	public $siblingsAllowArray = ["A", "ABBR", "ACRONYM", "BDO", "BDI", "STRONG","BR","SPAN", "EM", "I", "B", "CITE", "DEL", "DFN", "INS", "MARK", "Q", "BIG", "SMALL", "SUB", "SUP", "U"];
    public $siblingsAvoidArray = ["P", "DIV", "H1", "H2", "H3", "H4", "H5", "H6", "LABEL", "LI", "SVG", "PRE"];

	var $flags = array(
		312 => array( 'title' => 'Afghanistan', 'code' => 'NV2'),
		313 => array( 'title' => 'Albania', 'code' => '5iM'),
		314 => array( 'title' => 'Algeria', 'code' => '5W5'),
		315 => array( 'title' => 'Andorra', 'code' => '0Iu'),
		316 => array( 'title' => 'Angola', 'code' => 'R3d'),
		317 => array( 'title' => 'Antigua and Barbuda', 'code' => '16M'),
		318 => array( 'title' => 'Argentina', 'code' => 'V1f'),
		319 => array( 'title' => 'Armenia', 'code' => 'q9U'),
		320 => array( 'title' => 'Australia', 'code' => '2Os'),
		321 => array( 'title' => 'Austria', 'code' => '8Dv'),
		322 => array( 'title' => 'Azerbaijan', 'code' => 'Wg1'),
		323 => array( 'title' => 'Bahamas', 'code' => '0qL'),
		324 => array( 'title' => 'Bahrain', 'code' => 'D9A'),
		325 => array( 'title' => 'Bangladesh', 'code' => '63A'),
		326 => array( 'title' => 'Barbados', 'code' => 'u7L'),
		327 => array( 'title' => 'Belarus', 'code' => 'O8S'),
		328 => array( 'title' => 'Belgium', 'code' => '0AT'),
		329 => array( 'title' => 'Belize', 'code' => 'lH4'),
		330 => array( 'title' => 'Benin', 'code' => 'I2x'),
		331 => array( 'title' => 'Bhutan', 'code' => 'D9z'),
		332 => array( 'title' => 'Bolgariya', 'code' => 'V3p'),
		333 => array( 'title' => 'Bolivia', 'code' => '8Vs'),
		334 => array( 'title' => 'Bosnia and Herzegovina', 'code' => 'Z1t'),
		335 => array( 'title' => 'Botswana', 'code' => 'Vf3'),
		336 => array( 'title' => 'Brazil', 'code' => '1oU'),
		337 => array( 'title' => 'Brunei', 'code' => '3rE'),
		338 => array( 'title' => 'Burkina Faso', 'code' => 'x8P'),
		339 => array( 'title' => 'Burundi', 'code' => '5qZ'),
		340 => array( 'title' => 'Cambodia', 'code' => 'o8B'),
		341 => array( 'title' => 'Cameroon', 'code' => '3cO'),
		342 => array( 'title' => 'Canada', 'code' => 'P4g'),
		343 => array( 'title' => 'Cape Verde', 'code' => 'R5O'),
		344 => array( 'title' => 'Central African Republic', 'code' => 'kN9'),
		345 => array( 'title' => 'Chad', 'code' => 'V5u'),
		346 => array( 'title' => 'Chile', 'code' => 'wY3'),
		347 => array( 'title' => 'China', 'code' => 'Z1v'),
		348 => array( 'title' => 'Colombia', 'code' => 'a4S'),
		349 => array( 'title' => 'Comoros', 'code' => 'N6k'),
		350 => array( 'title' => 'Congo', 'code' => 'WK0'),
		351 => array( 'title' => 'Costa Rica', 'code' => 'PP7'),
		352 => array( 'title' => 'Cote d\'Ivoire', 'code' => '6PX'),
		353 => array( 'title' => 'Croatia', 'code' => '7KQ'),
		354 => array( 'title' => 'Cuba', 'code' => 'vU2'),
		355 => array( 'title' => 'Cyprys', 'code' => 'Gw4'),
		356 => array( 'title' => 'Czech Republic', 'code' => '1ZY'),
		357 => array( 'title' => 'Democratic Republic of the Congo', 'code' => 'Kv5'),
		358 => array( 'title' => 'Denmark', 'code' => 'Ro2'),
		359 => array( 'title' => 'Djibouti', 'code' => 'MS7'),
		360 => array( 'title' => 'Dominica', 'code' => 'E7U'),
		361 => array( 'title' => 'Dominican Republic', 'code' => 'Eu2'),
		362 => array( 'title' => 'Ecuador', 'code' => 'D90'),
		363 => array( 'title' => 'Egypt', 'code' => '7LL'),
		364 => array( 'title' => 'El Salvador', 'code' => '0zL'),
		365 => array( 'title' => 'Equatorial Guinea', 'code' => 'b8T'),
		366 => array( 'title' => 'Eritrea', 'code' => '8Gl'),
		367 => array( 'title' => 'Estonia', 'code' => 'VJ8'),
		368 => array( 'title' => 'Ethiopia', 'code' => 'ZH1'),
		369 => array( 'title' => 'Fiji', 'code' => 'E1f'),
		370 => array( 'title' => 'Finland', 'code' => 'nM4'),
		371 => array( 'title' => 'France', 'code' => 'E77'),
		372 => array( 'title' => 'Gabon', 'code' => 'R1u'),
		373 => array( 'title' => 'Gambia', 'code' => 'TZ6'),
		374 => array( 'title' => 'Georgia', 'code' => '8Ou'),
		375 => array( 'title' => 'German', 'code' => 'K7e'),
		376 => array( 'title' => 'Ghana', 'code' => '6Mr'),
		377 => array( 'title' => 'Greece', 'code' => 'kY8'),
		378 => array( 'title' => 'Grenada', 'code' => 'yG1'),
		379 => array( 'title' => 'Guatemala', 'code' => 'aE8'),
		380 => array( 'title' => 'Guinea', 'code' => '6Lm'),
		381 => array( 'title' => 'Guinea-Bissau', 'code' => 'I39'),
		382 => array( 'title' => 'Guyana', 'code' => 'Mh5'),
		383 => array( 'title' => 'Haiti', 'code' => 'Qx7'),
		384 => array( 'title' => 'Honduras', 'code' => 'm5Q'),
		385 => array( 'title' => 'Hungary ', 'code' => 'OU2'),
		386 => array( 'title' => 'Iceland', 'code' => 'Ho8'),
		387 => array( 'title' => 'India', 'code' => 'My6'),
		388 => array( 'title' => 'Indonesia', 'code' => 'G0m'),
		389 => array( 'title' => 'Iran', 'code' => 'Vo7'),
		390 => array( 'title' => 'Iraq', 'code' => 'z7I'),
		391 => array( 'title' => 'Ireland', 'code' => '5Tr'),
		392 => array( 'title' => 'Israel', 'code' => '5KS'),
		393 => array( 'title' => 'Italy', 'code' => 'BW7'),
		394 => array( 'title' => 'Jamaica', 'code' => 'u6W'),
		395 => array( 'title' => 'Japan', 'code' => '4YX'),
		396 => array( 'title' => 'Jordan', 'code' => 's2B'),
		397 => array( 'title' => 'Kazakhstan', 'code' => 'QA5'),
		398 => array( 'title' => 'Kenya', 'code' => 'X3y'),
		399 => array( 'title' => 'Kiribati', 'code' => 'l2H'),
		400 => array( 'title' => 'Kosovs', 'code' => 'Pb3'),
		401 => array( 'title' => 'Kuwait', 'code' => 'P5F'),
		402 => array( 'title' => 'Kyrgyzstan', 'code' => 'uP6'),
		403 => array( 'title' => 'Laos', 'code' => 'Qy5'),
		404 => array( 'title' => 'Latvia', 'code' => 'j1D'),
		405 => array( 'title' => 'Lebanon', 'code' => 'Rl2'),
		406 => array( 'title' => 'Lesotho', 'code' => 'lB1'),
		407 => array( 'title' => 'Liberia', 'code' => '9Qw'),
		408 => array( 'title' => 'Libya', 'code' => 'v6I'),
		409 => array( 'title' => 'Liechtenstein', 'code' => '2GH'),
		410 => array( 'title' => 'Lithuania', 'code' => 'uI6'),
		411 => array( 'title' => 'Luxembourg', 'code' => 'EV8'),
		412 => array( 'title' => 'Macedonia', 'code' => '6GV'),
		413 => array( 'title' => 'Madagascar', 'code' => '4tE'),
		414 => array( 'title' => 'Malawi', 'code' => 'O9C'),
		415 => array( 'title' => 'Malaysia', 'code' => 'C9k'),
		416 => array( 'title' => 'Maldives', 'code' => '1Q3'),
		417 => array( 'title' => 'Mali', 'code' => 'Yi5'),
		418 => array( 'title' => 'Malta', 'code' => 'N11'),
		419 => array( 'title' => 'Marshall Islands', 'code' => 'Z3x'),
		420 => array( 'title' => 'Mauritania', 'code' => 'F18'),
		421 => array( 'title' => 'Mauritius', 'code' => 'mH4'),
		422 => array( 'title' => 'Mexico', 'code' => '8Qb'),
		423 => array( 'title' => 'Micronesia', 'code' => 'H6t'),
		424 => array( 'title' => 'Moldova', 'code' => 'FD8'),
		425 => array( 'title' => 'Monaco', 'code' => 't0X'),
		426 => array( 'title' => 'Mongolia', 'code' => 'X8h'),
		427 => array( 'title' => 'Montenegro', 'code' => '61A'),
		428 => array( 'title' => 'Morocco', 'code' => 'M2e'),
		429 => array( 'title' => 'Mozambique', 'code' => 'J7N'),
		430 => array( 'title' => 'Myanmar ', 'code' => 'YB9'),
		431 => array( 'title' => 'Namibia', 'code' => 'r0H'),
		432 => array( 'title' => 'Nauru', 'code' => 'M09'),
		433 => array( 'title' => 'Nepal', 'code' => 'E0c'),
		434 => array( 'title' => 'Netherlands', 'code' => '8jV'),
		435 => array( 'title' => 'New Zealand', 'code' => '0Mi'),
		436 => array( 'title' => 'Nicaragua', 'code' => '5dN'),
		437 => array( 'title' => 'Niger', 'code' => 'Rj0'),
		438 => array( 'title' => 'Nigeria', 'code' => '8oM'),
		439 => array( 'title' => 'North Korea', 'code' => '3Yz'),
		440 => array( 'title' => 'Norvay', 'code' => '4KE'),
		441 => array( 'title' => 'Oman', 'code' => '8NL'),
		442 => array( 'title' => 'Pakistan', 'code' => 'n4T'),
		443 => array( 'title' => 'Palau', 'code' => '8G2'),
		444 => array( 'title' => 'Panama', 'code' => '93O'),
		445 => array( 'title' => 'Papua New Guinea', 'code' => 'FD4'),
		446 => array( 'title' => 'Paraguay', 'code' => 'y5O'),
		447 => array( 'title' => 'Peru', 'code' => '4MJ'),
		448 => array( 'title' => 'Philippines', 'code' => '2qL'),
		449 => array( 'title' => 'Poland ', 'code' => 'j0R'),
		450 => array( 'title' => 'Portugal', 'code' => '0Rq'),
		451 => array( 'title' => 'Qatar', 'code' => 'a8S'),
		452 => array( 'title' => 'Romania', 'code' => 'nC7'),
		453 => array( 'title' => 'Russia', 'code' => 'D1H'),
		454 => array( 'title' => 'Rwanda', 'code' => '8UD'),
		455 => array( 'title' => 'Saint Kitts and Nevis', 'code' => 'X2d'),
		456 => array( 'title' => 'Saint Lucia', 'code' => 'I5e'),
		457 => array( 'title' => 'Saint Vincent and the Grenadines', 'code' => '3Kf'),
		458 => array( 'title' => 'Samoa', 'code' => '54E'),
		459 => array( 'title' => 'San Marino', 'code' => 'K4F'),
		460 => array( 'title' => 'Sao Tome and Principe', 'code' => 'cZ9'),
		461 => array( 'title' => 'Saudi Arabia', 'code' => 'J06'),
		462 => array( 'title' => 'Senegal', 'code' => 'x2O'),
		463 => array( 'title' => 'Serbia', 'code' => 'GC6'),
		464 => array( 'title' => 'Seychelles', 'code' => 'JE6'),
		465 => array( 'title' => 'Sierra Leone', 'code' => 'mS4'),
		466 => array( 'title' => 'Singapore', 'code' => 'O6e'),
		467 => array( 'title' => 'Slovakia', 'code' => 'Y2i'),
		468 => array( 'title' => 'Slovenia', 'code' => 'ZR1'),
		469 => array( 'title' => 'Solomon Islands', 'code' => '0U1'),
		470 => array( 'title' => 'Somalia', 'code' => '3fH'),
		471 => array( 'title' => 'South Africa', 'code' => '7xS'),
		472 => array( 'title' => 'South Korea', 'code' => '0W3'),
		473 => array( 'title' => 'South Sudan', 'code' => 'H4u'),
		474 => array( 'title' => 'Spain', 'code' => 'A5d'),
		475 => array( 'title' => 'Sri Lanka', 'code' => '9JL'),
		476 => array( 'title' => 'Sudan', 'code' => 'Wh1'),
		477 => array( 'title' => 'Suriname', 'code' => '7Rb'),
		478 => array( 'title' => 'Swaziland', 'code' => 'f6L'),
		479 => array( 'title' => 'Sweden', 'code' => 'oZ3'),
		480 => array( 'title' => 'Switzerland', 'code' => '8aW'),
		481 => array( 'title' => 'Syria', 'code' => 'UZ9'),
		// 482 => array( 'title' => 'Taiwan', 'code' => 'Rg9'),
		509 => array( 'title' => 'Taiwan', 'code' => '00T'),
		483 => array( 'title' => 'Tajikistan', 'code' => '7Qa'),
		484 => array( 'title' => 'Tanzania', 'code' => 'VU7'),
		485 => array( 'title' => 'Thailand', 'code' => 'V6r'),
		486 => array( 'title' => 'Timor-Leste', 'code' => '52C'),
		487 => array( 'title' => 'Togo', 'code' => 'HH3'),
		488 => array( 'title' => 'Tonga', 'code' => '8Ox'),
		489 => array( 'title' => 'Trinidad and Tobago', 'code' => 'oZ8'),
		490 => array( 'title' => 'Tunisia', 'code' => 'pD6'),
		491 => array( 'title' => 'Turkey', 'code' => 'YZ9'),
		492 => array( 'title' => 'Turkmenistan', 'code' => 'Tm5'),
		493 => array( 'title' => 'Tuvalu', 'code' => 'u0Y'),
		494 => array( 'title' => 'Uganda', 'code' => 'eJ2'),
		495 => array( 'title' => 'Ukraine', 'code' => '2Mg'),
		496 => array( 'title' => 'United Arab Emirates', 'code' => 'DT3'),
		497 => array( 'title' => 'United Kingdom', 'code' => 'Dw0'),
		498 => array( 'title' => 'United States of America', 'code' => 'R04'),
		499 => array( 'title' => 'Uruguay', 'code' => 'aL9'),
		500 => array( 'title' => 'Uzbekistan', 'code' => 'zJ3'),
		501 => array( 'title' => 'Vanuatu', 'code' => 'D0Y'),
		502 => array( 'title' => 'Vatican City', 'code' => 'FG2'),
		503 => array( 'title' => 'Venezuela', 'code' => 'Eg6'),
		504 => array( 'title' => 'Vietnam', 'code' => 'l2A'),
		505 => array( 'title' => 'Yemen', 'code' => 'YZ0'),
		506 => array( 'title' => 'Zambia', 'code' => '9Be'),
		507 => array( 'title' => 'Zimbabwe', 'code' => '80Y'),
		508 => array( 'title' => 'Hong Kong', 'code' => '00H'),
		// 509 => array( 'title' => 'Taiwan', 'code' => '00T'),
	);


	function onAfterInitialise()
	{
		$this->referrer = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$this->api_key = $this->params->get('api_key', '');
		$this->source_language = $this->params->get( 'source_language', '' );
		$this->target_languages = $this->params->get( 'target_languages', array() );
		$this->style_change_language = $this->params->get( 'style_change_language', array() );
		$this->style_change_flag = $this->params->get( 'style_change_flag', array() );
		$this->style_flag = $this->params->get( 'style_flag', 'rect' );
		$this->style_text = $this->params->get( 'style_text', 'full-text' );
		$this->style_position_vertical = $this->params->get( 'style_position_vertical', 'bottom' );
		$this->style_position_horizontal = $this->params->get( 'style_position_horizontal', 'right' );
		$this->style_indenting_vertical = $this->params->get( 'style_indenting_vertical', '0' );
		$this->style_indenting_horizontal = $this->params->get( 'style_indenting_horizontal', '24' );
		$this->alternate = $this->params->get( 'alternate', 'on' );
		$this->auto_translate = $this->params->get( 'auto_translate', '0' );
		$this->hide_conveythis_logo = $this->params->get( 'hide_conveythis_logo', '0' );		
		$this->style_position_type = $this->params->get( 'style_position_type', 'fixed' );
		$this->style_position_vertical_custom = $this->params->get( 'style_position_vertical_custom', 'bottom' );
		$this->style_selector_id = $this->params->get( 'style_selector_id', '' );

		//
		if($this->auto_translate){
			
			$app = JFactory::getApplication();

			if ($app->isClient('Site')){ // not administration page

				$browserLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

				if (in_array($browserLanguage, $this->target_languages)) {

					session_start();
					if (empty($_SESSION['conveythis-autoredirected'])){
						
						$_SESSION['conveythis-autoredirected'] = true;
						
						$prefix = $this->getPrefix();
						$location = $this->getLocation($prefix, $browserLanguage);
						
						header("Location: ".$location);
						die();
					}
				}
			}
		}

//        echo print_r($this->target_languages, 1);

		if( !empty( $this->target_languages ) )
		{
			$prefix = $this->getPrefix();

			preg_match( '/^(' . preg_quote( $prefix, '/' ) . '('. implode( '|', $this->target_languages ) .')\/).*/', $_SERVER["REQUEST_URI"], $matches );



			if( !empty( $matches ) )
			{
				$this->language_code = $matches[2];

				$tmp = $matches[1];

				$_SERVER['REQUEST_URI'] = $this->customReplace( '/^' . preg_quote( $tmp, '/' ) . '(.*)/', $prefix . '\1', $_SERVER['REQUEST_URI'] );

                $uri = \Joomla\CMS\Uri\Uri::getInstance();

                $uri->setPath( $this->customReplace( '/^' . preg_quote( $tmp, '/' ) . '(.*)/', $prefix . '\1', $uri->getPath() ) );
			}
		}
	}

	function onAfterRender()
	{
		$app = JFactory::getApplication();

		if( $app->isClient('admin') ) return;

		$content = $app->getBody();

		if( !empty( $this->language_code ) )
		{
            $this->site_url = JURI::base();
			$content = $this->translateContent( $content );
		}
		$code = $this->getButton();

		$content = $this->customReplace( '/<\/body(\s+)?>(\s+)?<\/html(\s+)?>(\s+)?$/', $code . '</body\1>\2</html\3>\4', $content );
		$app->setBody( $content );
	}

	function onGetLanguages()
	{
		return $this->languages;
	}

	function onGetFlags()
	{
		return $this->flags;
	}
	
	function DOMinnerHTML(DOMNode $element)
    {
        $innerHTML = "";
        $children  = $element->childNodes;

        foreach ($children as $child)
        {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }
	
	function shouldTranslateWholeTag($element){
        for($i = 0; $i < count($element->childNodes); $i++){
            $child = $element->childNodes->item($i);

            if(in_array(strtoupper($child->nodeName), $this->siblingsAvoidArray)){
                return false;
            }
        }
        return true;
    }
	
	function allowTranslateWholeTag($element){
        for($i = 0; $i < count($element->childNodes); $i++){
            $child = $element->childNodes->item($i);

            if(in_array(strtoupper($child->nodeName), $this->siblingsAllowArray)){
                $outerHTML = $element->ownerDocument->saveHTML($child);

                if(preg_match("/>(\s*[^<>\s]+[\s\S]*?)</", $outerHTML)){
                    return true;
                }else if(strtoupper($child->nodeName) == "BR"){
                    $innerHTML = $this->DOMinnerHTML($element);

                    if(preg_match("/\s*[^<>\s]+\s*<br>\s*[^<>\s]+/i", $innerHTML)){
                        return true;
                    }
                }
            }
        }
        return false;
    }
	
	function isTextNodeExists($element){
        for($i = 0; $i < count($element->childNodes); $i++){
            $child = $element->childNodes->item($i);

            if($child->nodeName == "#text" && trim($child->textContent)){
                return true;
            }
        }
        return false;
    }

	// DOM

	function domRecursiveRead( $doc )
    {
        foreach( $doc->childNodes as $child )
        {
            if( $child->nodeType === 3 )
            {
                $value = trim( $child->textContent );
                // $value = htmlentities($child->textContent, null, 'utf-8');
                // $value = str_ireplace("&nbsp;", " ", $value);
                // $value = trim($value);


                if( !empty( $value ) )
                {
                    if	($child->nextSibling || $child->previousSibling) {

                        if($child->parentNode && $this->allowTranslateWholeTag($child->parentNode) && $this->shouldTranslateWholeTag($child->parentNode)){
                            $value = trim($this->DOMinnerHTML($child->parentNode));
                            $value = preg_replace("/\<!--(.*?)\-->/", "", $value);
                            $this->segments[$value] = $value;
                        }else
                            $this->segments[$value] = $value;
                    }
                    else
                        $this->segments[$value] = $value;
                }
            }
            else
            {
                if( $child->nodeType === 1 )
                {
                    if( $child->hasAttribute('title') )
                    {
                        $attrValue = trim( $child->getAttribute('title') );
                    }

                    if( $child->hasAttribute('alt') )
                    {
                        $attrValue = trim( $child->getAttribute('alt') );
                    }

                    if( $child->hasAttribute('placeholder') )
                    {
                        $attrValue = trim( $child->getAttribute('placeholder') );
                    }

                    if( $child->hasAttribute( 'type' ) )
                    {
                        $attrTypeValue = trim( $child->getAttribute( 'type' ) );

                        if( strcasecmp( $attrTypeValue, 'submit' ) === 0 || strcasecmp( $attrTypeValue, 'reset' ) === 0)
                        {
                            if( $child->hasAttribute( 'value' ) )
                            {
                                $attrValue = trim( $child->getAttribute( 'value' ) );
                            }
                        }
                    }

                    if( !empty( $attrValue ) )
                    {
                        $this->segments[$attrValue] = $attrValue;
                    }

                    if( strcasecmp( $child->nodeName, 'meta' ) === 0 )
                    {
                        if( $child->hasAttribute('name') )
                        {
                            $metaAttributeName = trim( $child->getAttribute('name') );

                            if( strcasecmp( $metaAttributeName, 'description' ) === 0 || strcasecmp( $metaAttributeName, 'keywords' ) === 0 )
                            {
                                if( $child->hasAttribute('content') )
                                {
                                    $metaAttrValue = trim( $child->getAttribute('content') );

                                    if( !empty( $metaAttrValue ) )
                                    {
                                        $this->segments[$metaAttrValue] = $metaAttrValue;
                                    }
                                }
                            }
                        }
                    }

                    if($child->nodeName == 'img'){
                        // if(window.conveythis.translate_media){
                        // // console.log(el["src"]);
                        // apply( el, 'src' )( translate );
                        // }
                        if($this->translate_media){
                            $src = $child->getAttribute("src");
                            $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
                            if(strpos($ext,"?") !== false) $ext = substr($ext, 0, strpos($ext,"?"));

                            if(in_array($ext, $this->imageExt)){
                                // echo $src . "<br>";
                                $this->segments[$src] = $src;
                            }
                        }
                    }


                    $shouldReadChild = true;
                    if($child->nodeName == 'a'){

                        if($this->translate_document){
                            $href = $child->getAttribute("href");
                            $ext = strtolower(pathinfo($href, PATHINFO_EXTENSION));
                            if(strpos($ext,"?") !== false) $ext = substr($ext, 0, strpos($ext,"?"));

                            if(in_array($ext, $this->documentExt)){
                                // echo $href . "<br>";
                                $this->segments[$href] = $href;

                            }
                        }

                        if($this->translate_links) {
                            $href = $child->getAttribute("href");
                            $pageHost = $this->getPageHost($href);
                            $link = parse_url($href);
                            if ((!$pageHost || $pageHost == $this->site_host) && $link['path'] && $link['path'] != '/') {
                                $this->segments[$link['path']] = $link['path'];
                                $this->links[$link['path']] = $link['path'];
                            }
                        }

                        $translateAttr = $child->getAttribute("translate");
                        if($translateAttr && $translateAttr == "no"){
                            // no need to walk inside
                            $shouldReadChild = false;
                        }
                    }

                    if( in_array(strtoupper($child->nodeName), $this->siblingsAllowArray) ){

                        if($child->parentNode){
                            if($this->isTextNodeExists($child->parentNode) && $this->allowTranslateWholeTag($child->parentNode) && $this->shouldTranslateWholeTag($child->parentNode)){
                                // no need to walk inside
                                $shouldReadChild = false;
                            }
                        }
                    }

                    if ($child->hasAttribute('class')) {
                        $class = $child->getAttribute("class");
                        if (strpos($class, 'conveythis-no-translate') !== false) {
                            // no need to walk inside
                            $shouldReadChild = false;
                        }
                    }

                    foreach ($this->exclusion_block_ids as $exclusionBlockId) {
                        if ($child->hasAttribute('id') && $child->getAttribute("id") == $exclusionBlockId) {
                            // no need to walk inside
                            $shouldReadChild = false;
                            break;
                        }
                    }

                    if( strcasecmp( $child->nodeName, 'script' ) !== 0 && strcasecmp( $child->nodeName, 'style' ) !== 0 && $shouldReadChild == true )
                    {
                        $this->domRecursiveRead( $child );
                    }
                }
            }
        }
    }

    function replaceSegments( $output )
    {

        foreach($this->siblingsAvoidArray as $key=>$value){

            $output = preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) use ($value) {

                $segment = $this->searchSegment( $matches[2] );

                if( !empty( $segment ) )
                {
                    return $matches[1] . $segment . $matches[3];
                }
                else
                {
                    // required to correctly find deepest element eg.: <div><div><div>text<a href="link"> link</a></div></div></div>
                    $tempOutput =  preg_replace_callback( '#<'.strtolower($value).'\b[^>]*>(?!<'.strtolower($value).'\b[^>]*>)(?:[\S\s](?!<'.strtolower($value).'\b[^>]*>))*?</'.strtolower($value).'>#', function ( $matches ) use ($value) {

                        if(isset($matches[0])){

                            return preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*?)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) {

                                if (isset($matches[2]) && preg_match("#(<.*>)#", $matches[2])) { // segment should not be plain text
                                    $segment = $this->searchSegment( $matches[2] );

                                    if( !empty( $segment ) )
                                    {
                                        return $matches[1] . $segment . $matches[3];
                                    }
                                    else
                                    {
                                        return $matches[0];
                                    }
                                }else{
                                    return $matches[0];
                                }
                            }, $matches[0]);
                        }else{
                            return null;
                        }

                    }, $matches[0]);

                    return $tempOutput ? $tempOutput : $matches[0];
                }

            }, $output);
        }

        foreach($this->siblingsAvoidArray as $key=>$value){
            $output = preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*)(</'.strtolower($value).'\b[^>]*>)#', function ( $matches ) use ($value) {

                $segment = $this->searchSegment( $matches[2] );

                if( !empty( $segment ) )
                {
                    return $matches[1] . $segment . $matches[3];
                }

                else
                {
                    // required to correctly find deepest element eg.: <div><div><div>text<a href="link"> link</a></div></div></div>
                    $tempOutput =  preg_replace_callback( '#<'.strtolower($value).'\b[^>]*>(?!<'.strtolower($value).'\b[^>]*>)(?:[\S\s](?!<'.strtolower($value).'\b[^>]*>))*?</'.strtolower($value).'>#', function ( $matches ) use ($value) {

                        if(isset($matches[0])){

                            return preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*?)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) {

                                if (isset($matches[2]) && preg_match("#(<.*>)#", $matches[2])) { // segment should not be plain text
                                    $segment = $this->searchSegment( $matches[2] );

                                    if( !empty( $segment ) )
                                    {
                                        return $matches[1] . $segment . $matches[3];
                                    }
                                    else
                                    {
                                        return $matches[0];
                                    }
                                }else{
                                    return $matches[0];
                                }

                            }, $matches[0]);
                        }else{

                            return null;
                        }

                    }, $matches[0]);

                    return $tempOutput ? $tempOutput : $matches[0];
                }

            }, $output);
        }
        //die;
        foreach($this->siblingsAllowArray as $key=>$value){
            $output = preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) use ($value) {

                if( !preg_match( '/translate="no"/', $matches[0] ) )
                {
                    $segment = $this->searchSegment( $matches[2] );

                    if( !empty( $segment ) )
                    {
                        return $matches[1] . $segment . $matches[3];
                    }

                    else
                    {
                        // required to correctly find deepest element eg.: <div><div><div>text<a href="link"> link</a></div></div></div>
                        $tempOutput =  preg_replace_callback( '#<'.strtolower($value).'\b[^>]*>(?!<'.strtolower($value).'\b[^>]*>)(?:[\S\s](?!<'.strtolower($value).'\b[^>]*>))*?</'.strtolower($value).'>#s', function ( $matches ) use ($value) {

                            if(isset($matches[0])){

                                return preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*?)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) {

                                    if (isset($matches[2]) && preg_match("#(<.*>)#", $matches[2])) { // segment should not be plain text
                                        $segment = $this->searchSegment( $matches[2] );

                                        if( !empty( $segment ) )
                                        {
                                            return $matches[1] . $segment . $matches[3];
                                        }
                                        else
                                        {
                                            return $matches[0];
                                        }
                                    }else{
                                        return $matches[0];
                                    }

                                }, $matches[0]);
                            }else{

                                return null;
                            }

                        }, $matches[0]);

                        return $tempOutput ? $tempOutput : $matches[0];
                    }
                }
                else
                {
                    return $matches[0];
                }

            }, $output);
        }
        foreach($this->siblingsAllowArray as $key=>$value){
            $output = preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*)(</'.strtolower($value).'\b[^>]*>)#', function ( $matches ) use ($value) {

                if( !preg_match( '/translate="no"/', $matches[0] ) )
                {
                    $segment = $this->searchSegment( $matches[2] );

                    if( !empty( $segment ) )
                    {
                        return $matches[1] . $segment . $matches[3];
                    }

                    else
                    {
                        // required to correctly find deepest element eg.: <div><div><div>text<a href="link"> link</a></div></div></div>
                        $tempOutput =  preg_replace_callback( '#<'.strtolower($value).'\b[^>]*>(?!<'.strtolower($value).'\b[^>]*>)(?:[\S\s](?!<'.strtolower($value).'\b[^>]*>))*?</'.strtolower($value).'>#s', function ( $matches ) use ($value) {

                            if(isset($matches[0])){

                                return preg_replace_callback( '#(<\s*?'.strtolower($value).'\b[^>]*>)(.*?)(</'.strtolower($value).'\b[^>]*>)#s', function ( $matches ) {

                                    if (isset($matches[2]) && preg_match("#(<.*>)#", $matches[2])) { // segment should not be plain text
                                        $segment = $this->searchSegment( $matches[2] );

                                        if( !empty( $segment ) )
                                        {
                                            return $matches[1] . $segment . $matches[3];
                                        }
                                        else
                                        {
                                            return $matches[0];
                                        }
                                    }else{
                                        return $matches[0];
                                    }

                                }, $matches[0]);
                            }else{

                                return null;
                            }

                        }, $matches[0]);

                        return $tempOutput ? $tempOutput : $matches[0];
                    }
                }
                else
                {
                    return $matches[0];
                }

            }, $output);
        }


        $output = preg_replace_callback( '/>([^<>]+)</', function ( $matches ) {

            $segment = $this->searchSegment( $matches[1] );

            if( !empty( $segment ) )
            {
                return '>' . $segment . '<';
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback('/(content|placeholder|alt|title)(\s+)?=(\s+)?"([^"]+)"/', function ( $matches ) {

            $segment = $this->searchSegment( $matches[4] );

            if( !empty( $segment ) )
            {
                return $matches[1] . '="' . $segment . '"';
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback("/(content|placeholder|alt|title)(\s+)?=(\s+)?'([^']+)'/", function ( $matches ) {

            $segment = $this->searchSegment( $matches[4] );

            if( !empty( $segment ) )
            {
                return $matches[1] . "='" . $segment . "'";
            }

            else
            {
                return $matches[0];
            }

        }, $output);


        $output = preg_replace_callback('/type="(submit|reset)"(\s+)?value=(\s+)?"([^"]+)"/', function ( $matches ) {

            $segment = $this->searchSegment( $matches[4] );

            if( !empty( $segment ) )
            {
                return 'type='.$matches[1].' value="'.esc_html($segment).'"';
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( '/(<(\s+)?a([^<]+)href(\s+)?=(\s+)?)"([^"]+)"(([^>]+)?>)/', function ( $matches ) {

            if( !preg_match( '/translate="no"/', $matches[0] ) && !preg_match( '/\/wp-content\//', $matches[0] ) )
            {
                $temp = $this->replaceLink( $matches[6], $this->language_code );

                if( !empty( $temp ) )
                {
                    return $matches[1] . '"' . $temp . '"' . $matches[7];
                }

                else
                {
                    return $matches[0];
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( "/(<(\s+)?a([^<]+)href(\s+)?=(\s+)?)'([^']+)'(([^>]+)?>)/", function ( $matches ) {

            if( !preg_match( '/translate="no"/', $matches[0] ) && !preg_match( '/\/wp-content\//', $matches[0] ) )
            {
                $temp = $this->replaceLink( $matches[6], $this->language_code );

                if( !empty( $temp ) )
                {
                    return $matches[1] . "'" . $temp . "'" . $matches[7];
                }

                else
                {
                    return $matches[0];
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);


        $output = preg_replace_callback( '/(<(\s+)?img([^<]+)src(\s+)?=(\s+)?)"([^"]+)"/', function ( $matches ) {

            $metaAttrValue = $matches[6];

            if( strpos( $metaAttrValue, '//' ) === false )
            {

                if( strncmp( $metaAttrValue, $this->site_url, strlen( $this->site_url ) ) !== 0 )
                {
                    $newAttrValue = rtrim( $this->site_url, '/' ) . '/' . ltrim( $metaAttrValue, '/' );
                    return $matches[1] . '"' . $newAttrValue . '"';
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        if($this->translate_media){
            $output = preg_replace_callback( '/(<(\s+)?img([^<]+)src(\s+)?=(\s+)?)"([^"]+)"/', function ( $matches ) {

                $metaAttrValue = $matches[6];

                if( !preg_match( '/translate="no"/', $matches[0] ) )
                {

                    $segment = $this->searchSegment( $matches[6] );

                    if( !empty( $segment ) )
                    {
                        return $matches[1] . '"' . $segment . '" srcset=""';
                    }

                    else
                    {
                        return $matches[0];
                    }
                }

                else
                {
                    return $matches[0];
                }


            }, $output);
        }

        if($this->translate_document){
            $output = preg_replace_callback( '/(<(\s+)?a([^<]+)href(\s+)?=(\s+)?)"([^"]+)"/', function ( $matches ) {

                $metaAttrValue = $matches[6];

                if( !preg_match( '/translate="no"/', $matches[0] ) )
                {
                    $segment = $this->searchSegment( $matches[6] );

                    if( !empty( $segment ) )
                    {
                        return $matches[1] . '"' . $segment . '"';
                    }

                    else
                    {
                        return $matches[0];
                    }
                }

                else
                {
                    return $matches[0];
                }

            }, $output);
        }


        $output = preg_replace_callback( '/(<(\s+)?link([^<]+)canonical([^<]+)href(\s+)?=(\s+)?)"([^"]+)"/', function ( $matches ) {

            $temp = $this->replaceLink( $matches[7], $this->language_code );

            if( !empty( $temp ) )
            {
                return $matches[1] . '"' . $temp . '"';
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( "/(<(\s+)?link([^<]+)canonical([^<]+)href(\s+)?=(\s+)?)'([^']+)'/", function ( $matches ) {

            $temp = $this->replaceLink( $matches[7], $this->language_code );

            if( !empty( $temp ) )
            {
                return $matches[1] . "'" . $temp . "'";
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( '/(<(\s+)?article([^<]+)data-permalink(\s+)?=(\s+)?)"([^"]+)"(([^>]+)?>)/', function ( $matches ) {

            if( !preg_match( '/translate="no"/', $matches[0] ) )
            {
                $temp = $this->replaceLink( $matches[6], $this->language_code );

                if( !empty( $temp ) )
                {
                    return $matches[1] . '"' . $temp . '"' . $matches[7];
                }

                else
                {
                    return $matches[0];
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( '/(<(\s+)?form([^<]+)action(\s+)?=(\s+)?)"([^"]+)"/', function ( $matches ) {

            if( !preg_match( '/translate="no"/', $matches[0] ) )
            {
                $temp = $this->replaceLink( $matches[6], $this->language_code );

                if( !empty( $temp ) )
                {
                    return $matches[1] . '"' . $temp . '"';
                }

                else
                {
                    return $matches[0];
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        $output = preg_replace_callback( "/(<(\s+)?form([^<]+)action(\s+)?=(\s+)?)'([^']+)'/", function ( $matches ) {

            if( !preg_match( '/translate="no"/', $matches[0] ) )
            {
                $temp = $this->replaceLink( $matches[6], $this->language_code );

                if( !empty( $temp ) )
                {
                    return $matches[1] . '"' . $temp . '"';
                }

                else
                {
                    return $matches[0];
                }
            }

            else
            {
                return $matches[0];
            }

        }, $output);

        return $output;

    }

	function domRecursiveApply( $doc, $items )
    {
        foreach( $doc->childNodes as $child )
        {
            if( $child->nodeType === 3 )
            {
                $value = $child->textContent;
                $segment = $this->searchSegment( $value, $items );

                if( !empty( $segment ) )
                {
                    $child->textContent = $segment;
                }
            }

            else
            {
                if( $child->nodeType === 1 )
                {
                    if( $child->hasAttribute( 'title' ) )
                    {
                        $attrValue = $child->getAttribute( 'title' );
                        $segment = $this->searchSegment( $attrValue, $items );

                        if( !empty( $segment ) )
                        {
                            $child->setAttribute( 'title', $segment );
                        }
                    }

                    if( $child->hasAttribute( 'alt' ) )
                    {
                        $attrValue = $child->getAttribute( 'alt' );
                        $segment = $this->searchSegment( $attrValue, $items );

                        if( !empty( $segment ) )
                        {
                            $child->setAttribute( 'alt', $segment );
                        }
                    }

                    if( $child->hasAttribute( 'placeholder' ) )
                    {
                        $attrValue = $child->getAttribute( 'placeholder' );
                        $segment = $this->searchSegment( $attrValue, $items );

                        if( !empty( $segment ) )
                        {
                            $child->setAttribute( 'placeholder', $segment );
                        }
                    }

                    if( $child->hasAttribute( 'type' ) )
                    {
                        $attrValue = trim( $child->getAttribute( 'type' ) );

                        if( strcasecmp( $attrValue, 'submit' ) === 0 || strcasecmp( $attrValue, 'reset' ) === 0 )
                        {
                            if( $child->hasAttribute( 'value' ) )
                            {
                                $attrValue = $child->getAttribute( 'value' );
                                $segment = $this->searchSegment( $attrValue, $items );

                                if( !empty( $segment ) )
                                {
                                    $child->setAttribute( 'value', $segment );
                                }
                            }
                        }
                    }

                    if( strcasecmp( $child->nodeName, 'img' ) === 0 )
                    {
                        if( $child->hasAttribute( 'src' ) )
                        {
                            $metaAttrValue = trim( $child->getAttribute( 'src' ) );

                            if( !empty( $metaAttrValue ) )
                            {
                                if( strpos( $metaAttrValue, '//' ) === false )
                                {
                                    if( strncmp( $metaAttrValue, $this->site_url, strlen( $this->site_url ) ) !== 0 )
                                    {
                                        $newAttrValue = rtrim( $this->site_url, '/' ) . '/' . ltrim( $metaAttrValue, '/' );

                                        $child->setAttribute( 'src', $newAttrValue );
                                    }
                                }
                            }
                        }
                    }

                    if( strcasecmp( $child->nodeName, 'a' ) === 0 )
                    {
                        if( $child->hasAttribute( 'href' ) )
                        {
                            $metaAttrValue = trim( $child->getAttribute( 'href' ) );

                            if( !empty( $metaAttrValue ) )
                            {
                                if( $metaAttrValue !== '#' )
                                {
                                    if( $child->hasAttribute( 'translate' ) )
                                    {
                                        $metaAttrValue = trim( $child->getAttribute( 'translate' ) );

                                        if( $metaAttrValue === 'no' )
                                        {

                                        }

                                        else
                                        {
                                            $temp = $this->replaceLink( $metaAttrValue, $this->language_code );
                                            $child->setAttribute( 'href', $temp );
                                        }
                                    }

                                    else
                                    {
                                        $temp = $this->replaceLink( $metaAttrValue, $this->language_code );
                                        $child->setAttribute( 'href', $temp );
                                    }
                                }
                            }
                        }
                    }

                    if( strcasecmp( $child->nodeName, 'meta' ) === 0 )
                    {
                        if( $child->hasAttribute( 'name' ) )
                        {
                            $metaAttributeName = trim( $child->getAttribute( 'name' ) );

                            if( strcasecmp( $metaAttributeName, 'description' ) === 0 || strcasecmp( $metaAttributeName, 'keywords' ) === 0 )
                            {
                                if( $child->hasAttribute( 'content' ) )
                                {
                                    $metaAttrValue = $child->getAttribute( 'content' );
                                    $segment = $this->searchSegment( $metaAttrValue, $items );

                                    if( !empty( $segment ) )
                                    {
                                        $child->setAttribute( 'content', $segment );
                                    }
                                }
                            }
                        }
                    }

                    if( strcasecmp( $child->nodeName, 'script' ) !== 0 && strcasecmp( $child->nodeName, 'style' ) !== 0 )
                    {
                        if( $child->hasAttribute( 'translate' ) )
                        {
                            $metaAttrValue = trim( $child->getAttribute( 'translate' ) );

                            if( $metaAttrValue === 'no' )
                            {

                            }

                            else
                            {
                                $this->domRecursiveApply( $child, $items );
                            }
                        }

                        else
                        {
                            $this->domRecursiveApply( $child, $items );
                        }
                    }
                }
            }
        }
    }

	public function translateContent( $output )
	{
		if( extension_loaded('xml') )
		{
			$doc = $this->domLoad( $output );

			$this->domRecursiveRead( $doc );

			sort( $this->segments );
// 			dd($this->segments);
			
			$response = $this->onSend( 'POST', '/website/translate/', array(
				'referrer' => $this->referrer,
				'source_language' => $this->source_language,
				'target_language' => $this->language_code,
				'segments' => $this->segments,
			));

            $response = $this->checkTranslatedTagsInSegments($response);

			if( !empty( $response ) )
			{
				$this->items = $response;

				$output = $this->replaceSegments( $output );

				//~ $doc = $this->domLoad( $output );

				//~ $this->domRecursiveApply( $doc, $response );

				//~ $output = $doc->saveHTML($doc->documentElement);
			}
		}

		return $output;
	}

    public function checkTranslatedTagsInSegments($segments) {
        if (!empty($segments) && is_array($segments)) {
            foreach ($segments as $idx => &$segment) {
                if (preg_match('/<mstrans:dictionary\b/', $segment['translate_text'])) {
                    $segment['translate_text'] = preg_replace('/<mstrans:dictionary\s+translation\s*=\s*[^>]*>/', '', $segment['translate_text']);
                }
            }
        }

        return $segments;
    }

	public function getLocation( $prefix, $language_code )
	{
		if( $this->source_language == $language_code )
		{
			return $_SERVER["REQUEST_URI"];
		}

		else
		{
			return $this->customReplace( '/^' . preg_quote( $prefix, '/' ) . '(.*)/', $prefix . '' . $language_code . '/' . '\1', $_SERVER["REQUEST_URI"] );
		}
	}
	
	public function getButton()
	{
        $current_language_id = 703;
		$languages = array();

		$prefix = $this->getPrefix();

		if( !empty( $this->language_code ) )
		{
			$current_language_code = $this->language_code;
		}

		else
		{
			$current_language_code = $this->source_language;
		}

		foreach( $this->languages as $id => $language )
		{
			if( $current_language_code == $language['code2'] )
			{
				$location = $this->getLocation( $prefix, $language['code2'] );

				$languages[] = '{"id":"'. $id .'", "location":"'. $location .'", "active":true}';
				
				$current_language_id = $id;
			}
		}

		if( !empty( $this->language_code ) )
		{
			foreach( $this->languages as $id => $language )
			{
				if( $this->source_language == $language['code2'] )
				{
					$location = $this->getLocation( $prefix, $language['code2'] );

					$languages[] = '{"id":"'. $id .'", "location":"'. $location .'", "active":false}';
				}
			}
		}

		if (($key = array_search($this->source_language, $this->target_languages)) !== false) { //remove source_language from target_languages
			unset($this->target_languages[$key]);
		}
		
		foreach( $this->target_languages as $language_code )
		{
			foreach( $this->languages as $id => $language )
			{
				if( $language_code == $language['code2'] && $current_language_code != $language['code2'] )
				{
					$location = $this->getLocation( $prefix, $language['code2'] );

					$languages[] = '{"id":"'. $id .'", "location":"'. $location .'", "active":false}';
				}
			}
		}

//		$source_language_id = 0;
//
//		if( !empty( $this->source_language ) )
//		{
//			foreach( $this->languages as $id => $language )
//			{
//				if( $this->source_language == $language['code2'] )
//				{
//					$source_language_id = $id;
//					break;
//				}
//			}
//		}

		//

//		$i = 0;
//
//		$temp = array();
//
//		while( $i < 5 )
//		{
//			if( !empty( $this->style_change_language[$i] ) )
//			{
//				$temp[] = '"' . $this->style_change_language[$i] . '":"' . $this->style_change_flag[$i] . '"';
//			}
//			$i++;
//		}

//		$change = '{' . implode( ',', $temp ) .'}';
//
//		//
//
//		if($this->style_position_type == 'custom' && $this->style_selector_id != '') {
//			if ($this->style_position_vertical_custom == 'top') {
//				$positionTop = 50;
//				$positionBottom = "null";
//			} else {
//				$positionTop = "null";
//				$positionBottom = 0;
//			}
//
//			$positionLeft  = "null";
//			$positionRight = 25;
//			$styleSelectorId = $this->style_selector_id ?: null;
//		}else{
//			if ($this->style_position_vertical == 'top') {
//				$positionTop = $this->style_indenting_vertical ?: 0;
//				$positionBottom = "null";
//			} else {
//				$positionTop = "null";
//				$positionBottom = $this->style_indenting_vertical ?: 0;
//			}
//			if ($this->style_position_horizontal == 'left') {
//				$positionLeft = $this->style_indenting_horizontal ?: 24;
//				$positionRight = "null";
//			} else {
//				$positionLeft = "null";
//				$positionRight = $this->style_indenting_horizontal ?: 24;
//			}
//			$styleSelectorId = null;
//		}

		//

//		$code = '';


//		$code .= '<script src="' . CONVEYTHIS_JAVASCRIPT_PLUGIN_URL . '/conveythis.js"></script>';
//		$code .= '<script src="' . CONVEYTHIS_JAVASCRIPT_PLUGIN_URL . '/translate.js"></script>';

        $code = '';
		$code .= '<script src="'. CONVEYTHIS_JAVASCRIPT_PLUGIN_URL . '/conveythis-initializer.js"></script>';
        $code .= '<script type="text/javascript">';
        $code .= '
                document.addEventListener("DOMContentLoaded", function(e) {
                    ConveyThis_Initializer.init({
                        api_key: "' . $this->api_key . '",
                        is_joomla: "' . $current_language_id . '",
                        auto_translate: "' . $this->auto_translate . '",
                        languages:[' . implode( ', ', $languages ) . '],
                    })
                });
            ';
        $code .= '</script>';

//		$code .= "\n";
//		$code .= '<script type="text/javascript">';
//		$code .= 'document.addEventListener("DOMContentLoaded", function(e) {';
//		$code .= 'ConveyThis_Initializer.init({';
//		$code .= 'api_key:"' . $this->api_key . '",';
//		$code .= 'auto_translate:' . $this->auto_translate . ',';
//		$code .= 'php_plugin_cur_lang:"' . $current_language_id . '",';
//		$code .= '});';
//		$code .= '});';
//		$code .= '</script>';
//		$code .= "\n";

        /*
         * 		$code .= 'change:' . $change . ',';
		$code .= 'icon:"' . $this->style_flag . '",';
		$code .= 'text:"' . $this->style_text . '",';
		$code .= 'positionTop:' . $positionTop . ',';
		$code .= 'positionBottom:' . $positionBottom . ',';
		$code .= 'positionLeft:' . $positionLeft . ',';
		$code .= 'positionRight:' . $positionRight . ',';
		$code .= 'languages:[' . implode( ', ', $languages ) . '],';
         * 		$code .= 'source_language_id:"' . $source_language_id . '",';
         * 		$code .= 'hide_conveythis_logo:' . $this->hide_conveythis_logo . ',';
         * 		if (isset($styleSelectorId)){
			$code .=	 'selector: "'.$styleSelectorId.'",';
		}
         * */

		return $code;
	}

	/*
	 * 
	 * 
	 * 
	 * */

	function replaceLink( $value )
	{
		$site_url = JURI::base();
		$site_host = parse_url( $site_url, PHP_URL_HOST );
		$site_path = parse_url( $site_url, PHP_URL_PATH );
		$prefix = $this->getPrefix();

//		$patterns = array();
//		$replacements = array();

		$link = parse_url( $value );

		if( ( empty( $link['host'] ) && !empty( $link['path'] ) ) || ( !empty( $link['host'] ) && $link['host'] == $site_host ) )
		{
			if( strncmp( $site_path, $link['path'], strlen( $site_path ) ) === 0 )
			{
				if( stripos( $link['path'], 'wp-admin' ) === false )
				{
					$link['path'] = $this->customReplace( '/^' . preg_quote( $prefix, '/' ) . '(.*)/', $prefix . '' . $this->language_code . '/' . '\1', $link['path'] );

					return $this->unparse_url( $link );
				}
			}
		}
	}

	function unparse_url( $parsed_url )
	{
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}

	function domLoad( $output )
	{
		$doc = new DOMDocument();

		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;

		libxml_use_internal_errors( true );

		$doc->loadHTML( $output );

		libxml_clear_errors();

		return $doc;
	}

	function searchSegment( $value )
    {
        $source_text = html_entity_decode( $value );
        // $source_text = str_ireplace("&nbsp;", " ", $value);
        // $source_text = html_entity_decode(trim($source_text));

        $source_text = trim(preg_replace("/\<!--(.*?)\-->/", "", $source_text));

        if (count($this->segments_hash) && !isset($this->segments_hash[md5($source_text)]))
        {
            return false;
        }

        if (!empty($this->items) && !empty($source_text))
        {
            foreach ($this->items as $item) {
                $source_text2 = isset($item['source_text']) ? trim(html_entity_decode($item['source_text'])) : '';

                if (strcmp($source_text, $source_text2) === 0) {
                    return str_replace($source_text, $item['translate_text'], $source_text);
                }
            }

            $source_text = strip_tags(mb_strtolower($source_text, 'UTF-8'));
            foreach ($this->items as $item) {
                $source_text2 = isset($item['source_text']) ? html_entity_decode($item['source_text']) : '';
                if (strcmp($source_text, strip_tags(mb_strtolower($source_text2, 'UTF-8'))) === 0) {
                    return str_replace($source_text, $item['translate_text'], $source_text);
                }
            }
        }
	}

	function onSend( $request_method, $request_uri, $query = array() )
	{
		$ch = curl_init();

		if( in_array( $request_method, array( 'PUT', 'POST', 'DELETE', 'PATCH' ) ) )
		{
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $query ) );
		}

		else
		{
			$request_uri .= '?' . http_build_query( $query );
		}

		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_method );
		curl_setopt( $ch, CURLOPT_URL, CONVEYTHIS_API_URL . $request_uri );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Accept: text/html',
			'Content-Type: text/html',
			'X-Api-Key: ' . $this->api_key
		));

		$response = curl_exec( $ch );

		curl_close($ch);

		//~ echo $response;
		//~ return;

		if( !empty( $response ) )
		{
			$data = json_decode( $response, true );

			if( !empty( $data ) )
			{
				if( $data['status'] == 'success' )
				{
					return $data['data'];
				}

				else
				{
					if( !empty( $response['message'] ) )
					{
						$message = JText::_( $response['message'] );

						if( strpos( $message, '#' ) )
						{
							$message = $this->customReplace( '/#/', '<a target="_blank" href="https://www.conveythis.com/dashboard/pricing/">change plan</a>', $message );
						}

						return (object) array(
							'error' => $message,
						);
					}
				}
			}
		}
	}

	function customReplace( $patterns, $replacements, $data )
	{
		return preg_replace( $patterns, $replacements, $data );
	}

	function getPrefix()
	{
		if( strpos( php_sapi_name(), 'cgi' ) !== false && !ini_get('cgi.fix_pathinfo') && !empty( $_SERVER['REQUEST_URI'] ) )
		{
			$script_name = $_SERVER['PHP_SELF'];
		}

		else
		{
			$script_name = $_SERVER['SCRIPT_NAME'];
		}
		$script_name = $this->customReplace( array("/'/", '/"/', '/</', '/>/'), array('%27', '%22', '%3C', '%3E'), $script_name );

		return rtrim( dirname( $script_name ), '/\\' ).'/';
	}
}
