<?php

/**
 * @file plugins/generic/volumesForm/VolumePageHandler.php
 *
 * @class VolumePageHandler
 *
 * @brief Handle volume page in the frontend.
 */

namespace APP\plugins\generic\volumesForm;

use APP\author\Author;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\monograph\ChapterDAO;
use APP\plugins\generic\volumesForm\classes\VolumeDAO;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;
use PKP\userGroup\UserGroup;

class VolumePageHandler extends Handler
{

	/** @var VolumesFormPlugin The plugin */
	protected VolumesFormPlugin $plugin;

	/**
	 * Constructor
	 */
	public function __construct(VolumesFormPlugin $plugin)
	{
		parent::__construct();
        $this->plugin = $plugin;
	}


    /**
     * View the content of a volume.
     *
     * @param $args    array [
     * @option string Volume path
     * @option int Page number if available
     * ];
     * @param $request PKPRequest
     *
     * @return void
     */
	public function volume(array $args, PKPRequest $request): void
	{

		// Set up basic template + fetch template manager.
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		// Pagination
		$page = isset($args[1]) ? (int) $args[1] : 1;

		// Get context and Daos.
		$context = $request->getContext();
		$contextId = $context->getId();
        /** @var VolumeDAO $volumeDao */
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
        /** @var ChapterDAO $chapterDao */
        $chapterDao = DAORegistry::getDAO('ChapterDAO');

		// Check if volume exist by path.
		$volumePath = $args[0];
		if (!$volumePath || !$volumeDao->volumeExistsByPath($volumePath)) {
            $request->getDispatcher()->handle404();
        }

        // Get volume by path.
        $volume = $volumeDao->getByPath($volumePath, $contextId);
        $volumeId = $volume->getData('id');


        // Serve 404 or a preview if all parts are unpublished
        if (!$volume->hasPublishedParts() ) {
            // Serve 404 if all parts are unpublished and no user is logged in
            $user = $request->getUser();
            if (!$user) {
                $request->getDispatcher()->handle404();
            }

            // Serve 404 if all parts are unpublished and we have a user logged in but the user does not have access to preview at least one part
            $userCanPreview = false;
            /** @var Submission $submission */
            foreach ($volume->getParts() as $submission) {
                if (Repo::submission()->canPreview($user, $submission)) {
                    $userCanPreview = true;
                    break;
                }
            }
            if (!$userCanPreview) {
                $request->getDispatcher()->handle404();
            }
        }


        // Get all published submissions which are part of a certain volume. Also collect editors, authors and series.
        $publishedPublications = $volume->getPublishedParts();
        $publishedSubmissions = [];
        $editorNames = [];
        $authorNames = [];
        $authorsGroups = $this->plugin->getAuthorGroups($context->getId());
        $editorsGroups = $this->plugin->getEditorGroups($context->getId());
        $authorUserGroups = Repo::userGroup()->getCollector()->filterByRoleIds([Role::ROLE_ID_AUTHOR])->filterByContextIds([$context->getId()])->getMany()->remember();
        $seriesArray = [];

        /** @var Publication $publication */
        foreach ($publishedPublications as $publication) {
            //Series
            $seriesId = $publication->getData('seriesId');
            $seriesPosition = $publication->getData('seriesPosition');
            if ($seriesId) {
                if (!array_key_exists($seriesId, $seriesArray)) {
                    $seriesArray[$seriesId] = [];
                }
                if ($seriesPosition) {
                    $seriesArray[$seriesId][] = $seriesPosition;
                }
            }

            //Authors & Editors
            $publicationAuthors = $publication->getData('authors');
            /** @var Author $publicationAuthor */
            foreach ($publicationAuthors as $publicationAuthor) {
                $userGroupId = $publicationAuthor->getUserGroupId();
                $hdEnhancedRolesPlugin = PluginRegistry::getPlugin('generic', 'hdenhancedrolesplugin');
                if ($hdEnhancedRolesPlugin && $hdEnhancedRolesPlugin->getEnabled() ) {
                    /** @var UserGroup $userGroup */
                    $userGroup = Repo::userGroup()->get($userGroupId, $contextId);
                    if ($userGroup->getData('hdEnhancedRoles::showAsEditor')) {
                        $editorNames[] = $publicationAuthor->getFullName();
                    }
                    if ($userGroup->getData('hdEnhancedRoles::showAsAuthor')) {
                        $authorNames[] = $publicationAuthor->getFullName();
                    }
                } else {
                    switch (true) {
                        case in_array($userGroupId, $editorsGroups):
                            $editorNames[] = $publicationAuthor->getFullName();
                            break;
                        case in_array($userGroupId, $authorsGroups):
                            $authorNames[] = $publicationAuthor->getFullName();
                            break;
                        default:
                            break;
                    }
                }
            }
            //Submissions
            $publishedSubmissions[] = Repo::submission()->get($publication->getData('submissionId'));
        }

        // Series
        foreach ($seriesArray as $seriesId => $positions) {
            $positions = array_unique($positions);
            sort($positions);
            $positions = implode(", ", $positions);
            $seriesData = [
                'series' => Repo::section()->get((int) $seriesId, $contextId),
                'positions' => $positions,
            ];
            $seriesArray[$seriesId] = $seriesData;
        }

        //Authors & Editors
        $editorNames = array_unique($editorNames);
        $authorNames = array_unique($authorNames);
        $pureAuthorNames = [];
        foreach ( $authorNames as $authorName ) {
            if (!in_array($authorName, $editorNames)) {
                $pureAuthorNames[] = $authorName;
            }
        }
        $authorNames = $pureAuthorNames;

        // Page items and pagination settings.
        $count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
        $offset = $page > 1 ? ($page - 1) * $count : 0;
        $total = $volume->countPublishedParts();
        $publishedSubmissions = array_slice($publishedSubmissions, $offset, $count);
        $this->_setupPaginationTemplate($request, count($publishedSubmissions), $page, $count, $offset, $total);


        $authorUserGroups = Repo::userGroup()->getCollector()->filterByRoleIds([Role::ROLE_ID_AUTHOR])->filterByContextIds([$context->getId()])->getMany()->remember();

        // Cover
        $basePath = $request->getBaseUrl() .'/'. $this->plugin->getPluginPath() . '/cover/';
        $imageInfo = $volume->getImage();

        // Assign necessary vars to template.
        $templateMgr->assign('volume', $volume)
            ->assign('volumeTitle', $volume->getLocalizedTitle())
            ->assign('volumeDescription', $volume->getLocalizedDescription())
            ->assign('volumePath', $volumePath)
            ->assign('seriesArray', $seriesArray)
            ->assign('editors', $editorNames)
            ->assign('authors', $authorNames)
            ->assign('total', $total)
            ->assign('publishedSubmissions', $publishedSubmissions)
            ->assign('authorUserGroups', $authorUserGroups)
            ->assign( 'volumeCover', $basePath . $volume->getImage()['name'] )
            ->assign( 'volumeCoverThumbnail', $basePath . $volume->getImage()['thumbnailName'] )
            ->display($this->plugin->getTemplateResource('/frontend/catalogVolumes.tpl'));
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
	protected function _setupPaginationTemplate(PKPRequest $request, int $submissionsCount, int $page, int $count, int $offset, int $total): void
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
}
