<?php

/**
 * @file plugins/generic/volumesForm/VolumePageHandler.inc.php
 *
 * @class VolumePageHandler
 *
 * @brief Handle volume page in the frontend.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.submission.PKPSubmission');
import('classes.core.Services');

class VolumePageHandler extends Handler
{

	/** @var VolumesFormPlugin The plugin */
	static $plugin;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Provide the VolumesFormPlugin to the handler.
	 * @param $plugin WLBThemePlugin
	 */
	static function setPlugin($plugin)
	{
		self::$plugin = $plugin;
	}

	/**
	 * View the content of a volume.
	 * @param $args array [
	 *		@option string Volume path
	 *		@option int Page number if available
	 * ]
	 * @param $request PKPRequest
	 */
	function volume($args, $request)
	{

		// Set up basic template + fetch template manager.
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		// Pagination
		import('lib.pkp.classes.submission.PKPSubmission');
		import('classes.core.Services');
		$page = isset($args[1]) ? (int) $args[1] : 1;

		// Get context and Daos.
		$context = $request->getContext();
		$contextId = $context->getId();
		$volumeDao = DAORegistry::getDAO('VolumeDAO');

		// Check if volume exist by path.
		$volumePath = $args[0];
		if (!$volumeDao->volumeExistsByPath($volumePath)) {
			$publishedSubmissions = [];
		} else {

			// Get volume by path.
			$volume = $volumeDao->getByPath($volumePath, $contextId);
			$volumeId = $volume->getData('id');

			// Sort option.
			$orderOption = $volume->getSortOption() ? $volume->getSortOption() : ORDERBY_DATE_PUBLISHED . '-' . SORT_DIRECTION_DESC;
			list($orderBy, $orderDir) = explode('-', $orderOption);

			// Page items and pagination settings.
			$count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
			$offset = $page > 1 ? ($page - 1) * $count : 0;
			$publishedSubmissions = [];

			// Get all published submissions which are part of a certain volume.
			$params = array(
				'contextId' => $context->getId(),
				'orderBy' => $orderBy,
				'orderDirection' => $orderDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC',
				'status' => STATUS_PUBLISHED,
			);
			$submissionsIterator = iterator_to_array(Services::get('submission')->getMany($params));
			foreach ($submissionsIterator as $submission) {
				$publication = $submission->getCurrentPublication();
				if ((string) $publication->getData('volumeId') === (string) $volumeId) {
					$publishedSubmissions[] = $submission;
				}
			}
			$total = count($publishedSubmissions);
			$publishedSubmissions = array_slice($publishedSubmissions, $offset, $count);
			$this->_setupPaginationTemplate($request, count($publishedSubmissions), $page, $count, $offset, $total);


			// Assign necessary vars to template.
			$templateMgr->assign('volumeTitle', $volume->getLocalizedTitle());
			$templateMgr->assign('volumeDescription', $volume->getLocalizedDescription());
			$templateMgr->assign('volumePath', $volumePath);
			$templateMgr->assign('total', $total);
			$templateMgr->assign('publishedSubmissions', $publishedSubmissions);
			return $templateMgr->display(self::$plugin->getTemplateResource('catalogVolumes.tpl'));
		}
	}

	/**
	 * Assign the pagination template variables
	 * @param $request PKPRequest
	 * @param $submissionsCount int Number of monographs being shown
	 * @param $page int Page number being shown
	 * @param $count int Max number of monographs being shown
	 * @param $offset int Starting position of monographs
	 * @param $total int Total number of monographs available
	 */
	protected function _setupPaginationTemplate($request, $submissionsCount, $page, $count, $offset, $total)
	{
		$showingStart = $offset + 1;
		$showingEnd = min($offset + $count, $offset + $submissionsCount);
		$nextPage = $total > $showingEnd ? $page + 1 : null;
		$prevPage = $showingStart > 1 ? $page - 1 : null;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'showingStart' => $showingStart,
			'showingEnd' => $showingEnd,
			'total' => $total,
			'nextPage' => $nextPage,
			'prevPage' => $prevPage,
		));
	}

	/**
	 * Serve the thumbnail for a volume.
	 */
	function thumbnail($args, $request)
	{
		$press = $request->getPress();
		$type = $request->getUserVar('type');
		$id = $request->getUserVar('id');
		$imageInfo = array();
		$path = null;

		switch ($type) {
			case 'volume':
				$path = '/volume/';
				$volumeDao = DAORegistry::getDAO('VolumeDAO'); /* @var $seriesDao SeriesDAO */
				$volume = $volumeDao->getById($id, $press->getId());
				if ($volume) {
					$imageInfo = $volume->getImage();
				}
				break;
			default:
				fatalError('invalid type specified');
				break;
		}

		if ($imageInfo) {
			import('lib.pkp.classes.file.ContextFileManager');
			$pressFileManager = new ContextFileManager($press->getId());
			$pressFileManager->downloadByPath($pressFileManager->getBasePath() . $path . $imageInfo['thumbnailName'], null, true);
		}
	}
}
