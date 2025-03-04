<?php

/**
 * Kunena Component
 *
 * @package       Kunena.Framework
 * @subpackage    HTML
 *
 * @copyright     Copyright (C) 2008 - 2024 Kunena Team. All rights reserved.
 * @license       https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          https://www.kunena.org
 **/

namespace Kunena\Forum\Site\Service\Html;

\defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Kunena\Forum\Libraries\Forum\Category\KunenaCategoryHelper;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class KunenaForum
 *
 * @since   Kunena 6.0
 */
class Kunenaforum
{
    /**
     * @param   string  $name       name
     * @param   mixed   $parent     parent
     * @param   array   $options    options
     * @param   array   $params     params
     * @param   null    $attribs    attribs
     * @param   string  $key        key
     * @param   string  $text       text
     * @param   array   $selected   selected
     * @param   bool    $idtag      idtag
     * @param   bool    $translate  translate
     *
     * @return  string|boolean
     *
     * @since   Kunena 6.0
     *
     * @throws  null
     */
    public static function categorylist($name, $parent, $options = [], $params = [], $attribs = null, $key = 'value', $text = 'text', $selected = [], $idtag = false, $translate = false)
    {
        $preselect     = isset($params['preselect']) ? (bool) ($params['preselect'] && $params['preselect'] != 'false') : true;
        $unpublished   = isset($params['unpublished']) ? (bool) $params['unpublished'] : 0;
        $sections      = isset($params['sections']) ? (bool) $params['sections'] : 0;
        $ordering      = isset($params['ordering']) ? (string) $params['ordering'] : 'ordering';
        $direction     = isset($params['direction']) && $params['direction'] == 'desc' ? -1 : 1;
        $action        = isset($params['action']) ? (string) $params['action'] : 'read';
        $levels        = isset($params['levels']) ? (int) $params['levels'] : 10;
        $topleveltxt   = isset($params['toplevel']) ? $params['toplevel'] : false;
        $catid         = isset($params['catid']) ? (int) $params['catid'] : 0;
        $hide_lonely   = isset($params['hide_lonely']) ? (bool) $params['hide_lonely'] : 0;
        $returnOptions = isset($params['return_options']) ? (bool) $params['return_options'] : 0;

        $params                = [];
        $params['ordering']    = $ordering;
        $params['direction']   = $direction;
        $params['unpublished'] = $unpublished;
        $params['action']      = $action;
        $params['selected']    = $catid;

        if ($catid) {
            $category = KunenaCategoryHelper::get($catid);

            if (!$category->getParent()->isAuthorised($action) && !KunenaUserHelper::getMyself()->isAdmin()) {
                $categories = KunenaCategoryHelper::getParents($catid, $levels, $params);
            }
        }

        $channels = [];

        if (!isset($categories)) {
            if (!\is_array($parent)) {
                $parent = [$parent];
            }

            $categories = [];
            $channels   = [];

            foreach ($parent as $p) {
                $channels_local = [];
                $category       = KunenaCategoryHelper::get($p);
                $children       = KunenaCategoryHelper::getChildren($p, $levels, $params);

                if ($params['action'] == 'topic.create') {
                    $channels_local = $category->getChannels();

                    if (empty($children) && !isset($channels_local[$category->id])) {
                        $category = KunenaCategoryHelper::get();
                    }

                    foreach ($channels_local as $id => $channel) {
                        if (!$id || $category->id == $id || isset($children[$id]) || !$channel->isAuthorised($action)) {
                            unset($channels_local[$id]);
                        }
                    }
                }

                $categories += $category->id > 0 ? [$category->id => $category] + $children : $children;

                if (!empty($channels_local)) {
                    $channels += $channels_local;
                }
            }

            if ($hide_lonely && \count($categories) + \count($channels) <= 1) {
                return false;
            }
        }

        if (!\is_array($options)) {
            $options = [];
        }

        if ($selected === false || $selected === null) {
            $selected = [];
        } elseif (!\is_array($selected)) {
            $selected = [(string) $selected];
        }

        if ($topleveltxt) {
            $me        = KunenaUserHelper::getMyself();
            $disabled  = ($action == 'admin' && !$me->isAdmin());
            $options[] = HTMLHelper::_('select.option', '0', Text::_($topleveltxt), 'value', 'text', $disabled);

            if ($preselect && empty($selected) && !$disabled) {
                $selected[] = 0;
            }

            $toplevel = 1;
        } else {
            $toplevel = -KunenaCategoryHelper::get($parent)->level;
        }

        foreach ($categories as $category) {
            $disabled = !$category->isAuthorised($action) || (!$sections && $category->isSection());

            if ($preselect && empty($selected) && !$disabled) {
                $selected[] = $category->id;
            }

            $options[] = HTMLHelper::_('select.option', $category->id, str_repeat('- ', $category->level + $toplevel) . ' ' . $category->name, 'value', 'text', $disabled);
        }

        $disabled = false;

        foreach ($channels as $category) {
            if ($preselect && empty($selected)) {
                $selected[] = $category->id;
            }

            $options[] = HTMLHelper::_('select.option', $category->id, '+ ' . $category->getParent()->name . ' / ' . $category->name, 'value', 'text', $disabled);
        }

        reset($options);

        if ($returnOptions) {
            return $options;
        }

        if (\is_array($attribs)) {
            $attribs = ArrayHelper::toString($attribs);
        }

        $id = $name;

        if ($idtag) {
            $id = $idtag;
        }

        $id = str_replace('[', '', $id);
        $id = str_replace(']', '', $id);

        $html = '';

        if (!empty($options)) {
            $html .= '<select name="' . $name . '" id="' . $id . '" ' . $attribs . '>';
            $html .= HTMLHelper::_('select.options', $options, $key, $text, $selected, $translate);
            $html .= '</select>';
        }

        return $html;
    }

    /**
     * Creates link pointing to a Kunena page
     *
     * @param   mixed   $uri         Kunena URI, either as string, Joomla\CMS\Uri\Uri or array
     * @param   string  $content     content
     * @param   string  $title       Link title
     * @param   string  $class       Link class
     * @param   string  $rel         Link relationship, see: http://www.w3.org/TR/html401/types.html#type-links
     * @param   mixed   $attributes  Tag attributes as: 'accesskey="a" lang="en"' or array('accesskey'=>'a',
     *                               'lang'=>'en')
     *
     * @return  string
     *
     * @since   Kunena 6.0
     *
     * @throws Exception
     * @throws null
     */
    public static function link($uri, string $content, $title = '', $class = '', $rel = '', $attributes = ''): string
    {
        if (!empty($uri)) {
            if (\is_string($uri) && $uri[0] == '/') {
                $list['href'] = $uri;
            } else {
                $list['href'] = KunenaRoute::_($uri);
            }
        } else {
            $list['href'] = KunenaRoute::_($uri);
        }

        if ($title) {
            $list['title'] = htmlspecialchars($title);
        }

        if ($class) {
            $list['class'] = htmlspecialchars($class);
        }

        if ($rel) {
            $list['rel'] = htmlspecialchars($rel);
        }

        if (\is_array($attributes)) {
            $list += $attributes;
        }

        // Parse attributes
        $attr = [];

        foreach ($list as $key => $value) {
            $attr[] = "{$key}=\"{$value}\"";
        }

        if (!empty($attributes) && !\is_array($attributes)) {
            $attr[] = (string) $attributes;
        }

        $attributes = implode(' ', $attr);

        return "<a {$attributes}>{$content}</a>";
    }

    /**
     * @param   string  $name         name
     * @param   mixed   $options      options
     * @param   array   $selected     selected
     * @param   null    $class_input  input
     *
     * @return  string
     *
     * @since   Kunena 6.0
     */
    public static function checklist(string $name, $options, $selected = [], $class_input = null): string
    {
        if ($selected !== true && !\is_array($selected)) {
            $selected = (array) $selected;
        }

        $html   = [];
        $html[] = '<ul class="checklist">';

        foreach ($options as $item) {
            // Setup  the variable attributes.
            $eid     = "checklist_{$name}_{$item}";
            $checked = $selected === true || \in_array($item, $selected) ? ' checked="checked"' : '';

            // Build the HTML for the item.
            $html[] = '	<li>';
            $html[] = '		<input type="checkbox" name="' . $name . '[]" value="' . $item . '" id="' . $eid . '" class="' . $class_input . '"';
            $html[] = '			' . $checked . ' />';
            $html[] = '		<label for="' . $eid . '">';
            $html[] = '			' . $item;
            $html[] = '		</label>';
            $html[] = '	</li>';
        }

        $html[] = '</ul>';

        if ($selected === true) {
            $html[] = '<input type="hidden" name="' . $name . '_all" value="' . implode(',', $options) . '" />';
        }

        return implode("\n", $html);
    }

    /**
     * Method to load the Sortable script and make table sortable
     *
     * @param   string   $tableId                 DOM id of the table
     * @param   string   $formId                  DOM id of the form
     * @param   string   $sortDir                 Sort direction
     * @param   string   $saveOrderingUrl         Save ordering url, ajax-load after an item dropped
     * @param   boolean  $proceedSaveOrderButton  Set whether a save order button is displayed
     * @param   boolean  $nestedList              Set whether the list is a nested list
     *
     * @return  string
     *
     * @since   Kunena 6.1
     *
     */
    public static function sortablelist($tableId, $formId, $sortDir = 'asc', $saveOrderingUrl = null, $proceedSaveOrderButton = true, $nestedList = false): string
    {
        // Note: $i is required but has to be an optional argument in the function call due to argument order
        if ($saveOrderingUrl === null) {
            return false;
        }

        Factory::getApplication()->getDocument()->addScript(Uri::root() . 'media/kunena/core/js/jquery.ui.core.js');
        Factory::getApplication()->getDocument()->addScript(Uri::root() . 'media/kunena/core/js/jquery.ui.sortable.js');
        Factory::getApplication()->getDocument()->addScript(Uri::root() . 'media/kunena/core/js/sortablelist.js');

        // Attach sortable to document
        Factory::getApplication()->getDocument()->addScriptDeclaration(
            "
		jQuery(document).ready(function ($){
			var sortableList = new $.JSortableList('#"
                . $tableId . " tbody','" . $formId . "','" . $sortDir . "' , '" . $saveOrderingUrl . "','','" . $nestedList . "');
		});
	"
        );

        return true;
    }
}
