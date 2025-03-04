<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing export layout API endpoint.
 */
trait ExportTrait
{
	public function export()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['GET', 'DELETE', 'PUT', 'PATCH'], $method);

		if ($method === 'POST') {
			$this->exportLayout();
		}
	}

	/**
	 * Export template layout.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function exportLayout()
	{
		$user = Factory::getUser();
		$authorised = $user->authorise('core.edit', 'com_sppagebuilder');
		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');

		$model = $this->getModel('Editor');

		$pageId = $this->getInput('pageId', '', 'STRING');
		$isSeoChecked = $this->getInput('isSeoChecked', '', 'STRING');

		if(empty($pageId)) {
			$this->sendResponse(['message' => 'Page Id missing.'], 400);
		}

		if ($canEditOwn && !empty($pageId)) {
			JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');

			$item_info  = SppagebuilderModelPage::getPageInfoById($pageId);

			$canEditOwn = $item_info->created_by == $user->id;
		}

		if (!$authorised && !$canEditOwn) {
			die('Restricted Access');
		}

		$content = $model->getPageContent($pageId);

		if (empty($content)) {
			$this->sendResponse(['message' => 'Requesting page not found!'], 404);
		}

		$content = ApplicationHelper::preparePageData($content);

		$seoSettings = [];

		if ($isSeoChecked) {
			$seoSettings = [
				'og_description' => isset($content->og_description) ? $content->og_description : '',
				'og_image' => '',
				'og_title' => isset($content->og_title) ? $content->og_title : '',
				'meta_description' => isset($content->attribs) && isset($content->attribs->meta_description) ?  $content->attribs->meta_description : '',
				'meta_keywords' => isset($content->attribs) && isset($content->attribs->meta_keywords) ?  $content->attribs->meta_keywords : '',
				'og_type' => isset($content->attribs) && isset($content->attribs->og_type) ?  $content->attribs->og_type : '',
				'robots' => isset($content->attribs) && isset($content->attribs->robots) ?  $content->attribs->robots : '',
				'seo_spacer' => isset($content->attribs) && isset($content->attribs->seo_spacer) ?  $content->attribs->seo_spacer : '',
				'author' => isset($content->attribs) && isset($content->attribs->author) ?  $content->attribs->author : '',
			];
		}

		$pageContent = (object) [
			'template' => isset($content->content) ? $content->content : $content->text,
			'css' => isset($content->css) ? $content->css : '',
			'seo' => json_encode($seoSettings),
			'title' => $content->title,
			'language' => isset($content->language) ? $content->language : '*',
		];

		if (isset($content->extension_view) && $content->extension_view === 'popup') {
			$pageContent->attribs = isset($content->attribs) ? json_encode($content->attribs) : '';
			$pageContent->type = 'popup';
		}


		$this->sendResponse($pageContent);
	}
}
