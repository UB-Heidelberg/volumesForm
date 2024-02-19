<?php

/**
 * @file plugins/generic/volumesForm/VolumesFormPlugin.php
 *
 * @brief Add Volumes to the submission metadata.
 *
 */

namespace APP\plugins\generic\volumesForm;


use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\plugins\generic\volumesForm\classes\Volume;
use APP\plugins\generic\volumesForm\classes\VolumeDAO;
use APP\plugins\generic\volumesForm\controllers\grid\VolumeGridHandler;
use APP\press\Press;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Exception;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\oai\OAIRecord;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\submission\Collector;
use PKP\submission\PKPSubmission;
use PKP\template\PKPTemplateManager;
use PKPString;

class VolumesFormPlugin extends GenericPlugin
{
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string|array|null
	{
		return __('plugins.generic.volumesForm.title');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string|array|null
	{
		return __('plugins.generic.volumesForm.description');
	}

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null): bool
	{
        $success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled()) {

			// Get custom DAO.
			$volumeDao = new VolumeDAO();
			DAORegistry::registerDAO('VolumeDAO', $volumeDao);

			// Show volume grid in publication and submission workflow.
			Hook::add('TemplateResource::getFilename', [$this, '_overridePluginTemplates']);

			// Load grid handler.
            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);

			// Handler for custom volume page in frontend.
			Hook::add('LoadHandler', [$this, 'setupVolumePageHandler']);

			// Load JS for grid handler.
			Hook::add('TemplateManager::display', [$this, 'addGridhandlerJs']);

			// Extend the publication schema to store data.
			Hook::add('Schema::get::publication', [$this, 'addToSchema']);

			// We need the volume field in three forms: normal submission, metadataform, quicksubmit form.
			Hook::add('submissionsubmitstep1form::display', [$this, 'addToStep1']);
			Hook::add('quicksubmitform::display', [$this, 'addToStep1']);
			Hook::add('quicksubmitform::AdditionalItems', [$this, 'addToQuicksubmit']);
			Hook::add('Form::config::before', [$this, 'addToCatalogEntry']);

			// Init the new field.
			Hook::add('submissionsubmitstep1form::initdata', [$this, 'metadataInitData']);
			Hook::add('quicksubmitform::initdata', [$this, 'metadataInitData']);

			// Hook for readUserVars: consider the new field entries.
			Hook::add('submissionsubmitstep1form::readuservars', [$this, 'metadataReadUserVars']);
			Hook::add('quicksubmitform::readuservars', [$this, 'metadataReadUserVars']);

			// Hook for execute: consider the new fields in the publication settings.
			Hook::add('Publication::add', [$this, 'metadataExecute']);
			Hook::add('quicksubmitform::execute', [$this, 'metadataExecute']);

            // Hook for add volume data to frontend book or chapter page
            Hook::add('CatalogBookHandler::book', $this->changeBookTemplateData(...));
            Hook::add('Templates::Catalog::Book::Details', $this->displayVolumeEnhancement(...));

            // Hook for add volume data to series page
            Hook::add('TemplateManager::display', [$this, 'showVolumePartsInSeries']);

            // Hook for citation change in CSL-Plugin
            Hook::add('CitationStyleLanguage::citation', [$this, 'changeCitationData']);

            // Hook for OAI Record
            Hook::add('OAIDAO::_returnRecordFromRow', [$this, 'changeOaiData']);

			// Load stylesheet for volume pages.
			$request = Application::get()->getRequest();
			$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/less/volumes.less';
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addStyleSheet('volumeStyles', $url, [
                'contexts' => 'frontend',
                'priority' => PKPTemplateManager::STYLE_SEQUENCE_CORE,
			]);
		}
		return $success;
	}

    /**
     * Permit requests to the volume grid handler.
     *
     * @param $hookName string The name of the hook being invoked
     * @param array $params
     *
     * @return bool
     */
	public function setupGridHandler(string $hookName, array $params): bool
	{
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component == 'plugins.generic.volumesForm.controllers.grid.VolumeGridHandler') {
            $componentInstance = new VolumeGridHandler($this);
            return true;
        }
        return false;
	}

	/**
	 * Handle frontend view in catalog.
	 * @param $hookName string The name of the hook being invoked
	 * @param $params array The parameters to the invoked hook
	 */
	public function setupVolumePageHandler(string $hookName, array $params) : bool
    {
		$page = $params[0];
		$op = $params[1];
        $handler =& $params[3];
		if ($page === 'catalog' && $op == 'volume') {
            $handler = new VolumePageHandler($this);
			return true;
		}
		return false;
	}

    /**
     * Extend the context entity's schema with an the new field values.
     * Save values in publication_settings table.
     *
     * @param $hookName string
     * @param $args     array
     *
     * @return bool
     */
	public function addToSchema(string $hookName, array $args): bool
	{
		// Fetch schema.
		$schema = $args[0];

		// Store in publication_settings table.
		$schema->properties->volumeId = (object) [
			'type' => 'string',
			'apiSummary' => true,
			'multilingual' => false,
			'validation' => ['nullable'],
		];
        $schema->properties->volumePosition = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'multilingual' => false,
            'validation' => ['nullable'],
        ];

		return false;
	}

	function addToStep1(string $hookName, array $params): bool
	{

		// Fetch template manager.
		$request = PKPApplication::get()->getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$contextId = $request->getContext()->getId();

		// Get volumes for this context and assign options.
        /** @var VolumeDAO $volumeDao */
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volumeOptions = [];
		$volumes =  $volumeDao->getByContextId($contextId);
		$volumeTitlesArray = $volumes->toAssociativeArray();
		foreach ($volumeTitlesArray as $volume) {
			$volumeOptions[$volume->getId()] = $volume->getLocalizedTitle();
		}
		$volumeOptions = ['' => __('submission.submit.selectVolume')] + $volumeOptions;
		$templateMgr->assign('volumeOptions', $volumeOptions);
        return false;
	}

    /**
     * Add volume field to quicksubmit form.
     *
     * @param $hookName string
     * @param $params   array
     *
     * @return bool
     */
	public function addToQuicksubmit(string $hookName, array $params): bool
	{

		// Get smarty and template.
		$smarty = $params[1];
		$template = $params[2];

		// Add volume field to quicksubmit form.
		$template .= $smarty->display($this->getTemplateResource('/submission/form/volumeField.tpl'));
		return false;
	}


    /**
     * Extend catalog entry form in the publication settings.
     * with a volume field.
     *
     * @param $hookName string
     * @param $form     FormComponent
     *
     * @return bool
     */
	public function addToCatalogEntry(string $hookName, FormComponent $form): bool
	{

		// Only modify the catalog entry form.
		if (!defined('FORM_CATALOG_ENTRY') || $form->id !== FORM_CATALOG_ENTRY) {
			return false;
		}

		// Don't do anything at the site-wide level, only dependent context.
		$context = Application::get()->getRequest()->getContext();
		if (!$context) {
			return false;
		}

		// Get current publication.
		$path = parse_url($form->action)['path'];
		if (!$path) return false;
		$args = explode('/', $path);
		$publicationId = 0;
		if ($key = array_search('publications', $args)) {
			if (array_key_exists($key + 1, $args)) {
				$publicationId = intval($args[$key + 1]);
			}
		}
		if (!$publicationId) return false;
		$publication = Repo::publication()->get($publicationId);
		if (!$publication) return false;

		// Volume options.
		$request = PKPApplication::get()->getRequest();
		$contextId = $request->getContext()->getId();
		$volumeOptions = [['value' => '', 'label' => '']];
        /** @var VolumeDAO $volumeDAO */
        $volumeDAO = DAORegistry::getDAO('VolumeDAO');
		$result = $volumeDAO->getByContextId($contextId);
		while (!$result->eof()) {
			$volume = $result->next();
			$volumeOptions[] = [
				'value' => (int) $volume->getId(),
				'label' => $volume->getLocalizedTitle(),
			];
		}

		// Add fields to the form.
		$form->addField(new FieldSelect('volumeId', [
			'label' => __('volume.volume'),
			'value' => $publication->getData('volumeId'),
			'options' => $volumeOptions,
		]), [FIELD_POSITION_AFTER, 'seriesPosition'])
            ->addField(new FieldText('volumePosition', [
            'label' => __('volume.volumePosition'),
            'description' => __('submission.submit.seriesPosition.description'),
            'value' => $publication->getData('volumePosition'),
        ]), [FIELD_POSITION_AFTER, 'volumeId']);

		return false;
	}

    /**
     * Initialize data in the metadata forms.
     *
     * @param $hookName string
     * @param $args     array
     *
     * @return bool
     */
	function metadataInitData(string $hookName, array $args): bool
	{
		// Get form.
		$form = $args[0];

		// Get publication.
		$publication = Repo::publication()->get($form->submission->getData('currentPublicationId'));

		// Initialize Data.
		$form->setData('volumeId', $publication->getData('volumeId'));
        $form->setData('volumePosition', $publication->getData('volumePosition'));
        return false;
	}

    /**
     * Get user-entered values.
     *
     * @param $hookName string
     * @param $args     array
     *
     * @return bool
     */
	public function metadataReadUserVars(string $hookName, array $args): bool
	{
		// Get user variables.
		$userVars = &$args[1];
		$userVars[] = 'volumeId';
        $userVars[] = 'volumePosition';
        return false;
	}

    /**
     * Save additional fields in the forms upon execution.
     *
     * @param $hookName string
     * @param $args     array
     *
     * @return bool
     */
	public function metadataExecute(string $hookName, array $args): bool
	{
		//context.
        $request = Application::get()->getRequest();
		$context = $request->getContext();

		// Get current publication depending on form.
		if (get_class($args[0]) === 'QuickSubmitForm') {
			$submissionsIterator = Services::get('submission')->getMany([
				'contextId' => $context->getId(),
			]);
			foreach ($submissionsIterator as $submission) {
				$publication = $submission->getCurrentPublication();
			}

			// Get volume ID from form.
			$volumeId = $args[0]->getData('volumeId');
            $volumePosition = ''; //TODO
		} else {
			// Fetch publication and get volume ID (by reference).
			/** @var Publication $publication */
            $publication =& $args[0];
			$volumeId = $request->getUserVars()['volumeId'];
            $volumePosition = $request->getUserVars()['volumePosition'];
		}

		// Set data.
		if (isset($publication) && $volumeId) {
			$publication->setData('volumeId', $volumeId);

			// Update pub object.
			Repo::publication()->edit(
                $publication,
                [
                    'volumeId' => $volumeId,
                    'volumePosition' => $volumePosition,
                ]
            );
		}
		return false;
	}

	/**
	 * Add custom gridhandlerJS for backend
	 */
	public function addGridhandlerJs(string $hookName, array $params): bool
	{
		$templateMgr = $params[0];
		$gridHandlerJs = $this->getJavaScriptURL() . DIRECTORY_SEPARATOR . 'VolumeGridHandler.js';
		$templateMgr->addJavaScript(
			'VolumeGridHandlerJs',
			$gridHandlerJs,
			array('contexts' => 'backend')
		);
		return false;
	}


	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	public function getInstallMigration(): VolumeSchemaMigration
    {
		return new VolumeSchemaMigration();
	}

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	public function getJavaScriptURL(): string
	{
		return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
	}

    public function changeBookTemplateData($hookName, $params): bool
    {
        $request = $params[0];
        /** @var Submission $submission */
        $submission =& $params[1];
        /** @var Publication $publication */
        $publication =& $params[2];
        $chapter =& $params[3];

        if($publication->getData('volumeId')){
            /** @var VolumeDAO $volumeDao */
            $volumeDao = DAORegistry::getDAO('VolumeDAO');
            $volume = $volumeDao->getById((int) $publication->getData('volumeId'));
            if($volume){
                try {
                    $templateMgr = TemplateManager::getManager($request);
                } catch (Exception) {
                    return false;
                }
                $templateMgr->assign('volume', $volume);
                $templateMgr->assign('locale', Locale::getLocale());

                //Title
                $publicationTitles = $publication->getTitles();
                $volumeTitles = $volume->getTitles();
                foreach( $publicationTitles as $locale => $title ) {
                    $volumeTitle = $volumeTitles[$locale];
                    if($volumePostion = $publication->getData('volumePosition')){
                        $volumeTitle = PKPString::concatTitleFields([$volumeTitle, $volumePostion]);
                    }
                    $title = PKPString::concatTitleFields([$volumeTitle, $title]);
                    $publication->setData('title', $title, $locale);
                }

                //Series
                if($volume->getData('seriesId') && !$publication->getData('seriesId')){
                    $publication->setData('seriesId', (int) $volume->getData('seriesId') );
                    $series = Repo::section()->get($publication->getData('seriesId'), $submission->getData('contextId'));
                    $templateMgr->assign('series', $series);
                }
            }
        }
        return false;
    }

    public function changeCitationData($hookName, $params): bool
    {
        $citationData =& $params[0];
        /** @var Submission $submisson */
        $submission =& $params[2];
        //params[3]: $issue (in OMP immer null)
        /** @var Press $context */
        $context = $params[4];
        /** @var Publication $publication */
        $publication =& $params[5];
        $locale = Locale::getLocale();

        if($publication->getData('volumeId')){
            /** @var VolumeDAO $volumeDao */
            $volumeDao = DAORegistry::getDAO('VolumeDAO');
            $volume = $volumeDao->getById((int) $publication->getData('volumeId'));

            //Title
            $title = $citationData->title;
            $fullTitle = $volume->getTitle($locale);
            if($publication->getData('volumePosition')){
                $fullTitle .= ', ' . $publication->getData('volumePosition');
            }
            $citationData->title = PKPString::concatTitleFields([$fullTitle, $title]);

            //Series
            if($volume->getData('seriesId') && !$publication->getData('seriesId')){
                $seriesId = $volume->getData('seriesId');
                $series = $seriesId ? Repo::section()->get($seriesId) : null;
                if ($series) {
                    $citationData->{'collection-title'} = htmlspecialchars(trim($series->getLocalizedFullTitle()));
                    if($publication->getData('seriesPosition')){
                        $citationData->{'collection-number'} = htmlspecialchars($publication->getData('seriesPosition'));
                    } elseif ($volume->getData('seriesPosition')){
                        $citationData->{'collection-number'} = htmlspecialchars($volume->getData('seriesPosition'));
                    }
                    $citationData->{'collection-editor'} = htmlspecialchars($series->getEditorsString());
                    $onlineISSN = $series->getOnlineISSN();
                    if (!empty($onlineISSN)) {
                        $citationData->serialNumber[] = htmlspecialchars($onlineISSN);
                    }
                    $printISSN = $series->getPrintISSN();
                    if (!empty($printISSN)) {
                        $citationData->serialNumber[] = htmlspecialchars($printISSN);
                    }
                }
            }
            //Publisher
            if($volume->getData('publisher')){
                $citationData->publisher = htmlspecialchars($volume->getData('publisher'));
            }
            //Location
            if($volume->getData('location')){
                $citationData->{'publisher-place'} = $volume->getData('location');
            }
            //ISBN
            $serialNumber =& $citationData->serialNumber;
            if($volume->getData('isbn10')){
                $serialNumber[] = htmlspecialchars($volume->getData('isbn10'));
            }
            if($volume->getData('isbn13')){
                $serialNumber[] = htmlspecialchars($volume->getData('isbn13'));
            }
        }

        return false;
    }

    public function displayVolumeEnhancement($hookName, $params): bool
    {
        $smarty =& $params[1];
        $output =& $params[2];

        $output .= $smarty->fetch($this->getTemplateResource('bookDetailsVolumeEnhancement.tpl'));

        return FALSE;
    }

    public function getEditorGroups(int $contextId): array
    {
        return $this->getSetting($contextId, 'groupEditor') ?? [];
    }

    public function getAuthorGroups(int $contextId): array
    {
        return $this->getSetting($contextId, 'groupAuthor') ?? [];
    }

    /**
     * @see Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);

        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic',
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $linkAction);

        return $actions;
    }

    /**
     * @see Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') == 'settings') {
            $form = new VolumesFormSettingsForm($this);

            if ($request->getUserVar('save')) {
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new JSONMessage(true);
                }
            }

            $form->initData();
            return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function showVolumePartsInSeries($hookName, $args): bool
    {
        $request = Application::get()->getRequest();
        $page = $request->getRequestedPage();
        $op = $request->getRequestedOp();

        if ($page === 'catalog' && $op === 'series') {
            /** @var TemplateManager $templateMgr */
            $templateMgr =& $args[0];
            $template =& $args[1];
            $output =& $args[2];
            $context = $request->getContext();
            $series = $templateMgr->tpl_vars['series']->value;
            /** @var VolumeDAO $volumeDao */
            $volumeDao = DAORegistry::getDAO('VolumeDAO');
            $volumes = $volumeDao->getBySeriesId((int) $series->getId())->toArray();

            if (count($volumes) > 0) {
                $orderOption = $series->getSortOption() ? $series->getSortOption() : Collector::ORDERBY_DATE_PUBLISHED . '-' . Collector::ORDER_DIR_DESC;
                [$orderBy, $orderDir] = explode('-', $orderOption);

                $count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                if ($templateMgr->tpl_vars['prevPage']->value !== null) {
                    $pageNumber = $templateMgr->tpl_vars['prevPage']->value + 1;
                } else {
                    $pageNumber = 1;
                }
                $offset = $pageNumber > 1 ? ($pageNumber - 1) * $count : 0;

                $publishedSubmissions = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$context->getId()])
                    ->filterBySeriesIds([$series->getId()])
                    ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                    ->orderBy($orderBy, $orderDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC')
                    ->orderByFeatured()
                    ->getMany()
                    ->toArray();

                $seriesOrder = $series->getData('sortOption');
                $countWithoutVolumes = count($publishedSubmissions);

                /** @var Volume $volume */
                foreach ($volumes as $volume) {
                    $publishedPublications = [];
                    $publishedPublications = $volume->getPublishedParts($seriesOrder);
                    foreach ($publishedPublications as $publishedPublication) {
                        $submissionId = (int) $publishedPublication->getData('submissionId');
                        if (!array_key_exists($submissionId, $publishedSubmissions)) {
                            $publishedSubmissions[$submissionId] = Repo::submission()->get($submissionId);
                        }
                    }
                }
                $countWithVolumes = count($publishedSubmissions);

                //sort
                if ($countWithVolumes > $countWithoutVolumes) {
                    [$orderBy, $orderDir] = explode('-', $seriesOrder);
                    switch ($orderBy) {
                        case Collector::ORDERBY_DATE_PUBLISHED:
                            $publishedSubmissions = $this->sortByDatePublished($publishedSubmissions, $orderDir);
                            break;
                        case Collector::ORDERBY_TITLE:
                            $publishedSubmissions = $this->sortByTitle($publishedSubmissions, $orderDir);
                            break;
                        case 'seriesPosition':
                            $publishedSubmissions = $this->sortBySeriesPosition($publishedSubmissions, $orderDir);
                            break;
                        default:
                            break;
                    }
                }

                $total = count($publishedSubmissions);
                $publishedSubmissions = array_slice($publishedSubmissions, $offset, $count );
                $submissionsCount = count($publishedSubmissions);
                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + $submissionsCount);

                $templateMgr->assign('publishedSubmissions', $publishedSubmissions)
                    ->assign('volumes', $volumes)
                    ->assign('total', $total)
                    ->assign('showingStart', $showingStart)
                    ->assign('showingEnd', $showingEnd);
            }


        }

        return false;
    }

    private function sortBySeriesPosition(array $submissions, string $orderDir): array
    {
        $positions = [];
        /** @var VolumeDAO $volumeDao */
        $volumeDao = DAORegistry::getDAO('VolumeDAO');
        /** @var Submission $submission */
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if ($publication->getData('seriesPosition')) {
                $positions[$submission->getId()] = $publication->getData('seriesPosition');
            } elseif ($publication->getData('volumeId')) {
                $volume = $volumeDao->getById($publication->getData('volumeId'));
                if ($volume->getData('seriesPosition')) {
                    $positions[$submission->getId()] = $publication->getData('seriesPosition');
                }
            } else {
                $positions[$submission->getId()] = '';
            }
        }
        asort($positions);
        foreach ($positions as $key => $value) {
            $positions[$key] = $submissions[$key];
        }
        $submissions = $positions;

        if($orderDir === Collector::ORDER_DIR_DESC){
            $submissions = array_reverse($submissions);
        }

        return $submissions;
    }

    private function sortByTitle(array $submissions, string $orderDir): array
    {
        $positions = [];
        /** @var Submission $submission */
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $positions[$submission->getId()] = $publication->getLocalizedTitle();
        }
        asort($positions);
        foreach ($positions as $key => $value) {
            $positions[$key] = $submissions[$key];
        }
        $submissions = $positions;

        if($orderDir === Collector::ORDER_DIR_DESC){
            $submissions = array_reverse($submissions);
        }

        return $submissions;
    }

    private function sortByDatePublished(array $submissions, string $orderDir): array
    {
        $positions = [];
        /** @var Submission $submission */
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $positions[$submission->getId()] = $publication->getData('datePublished');
        }
        asort($positions);
        foreach ($positions as $key => $value) {
            $positions[$key] = $submissions[$key];
        }
        $submissions = $positions;

        if($orderDir === Collector::ORDER_DIR_DESC){
            $submissions = array_reverse($submissions);
        }

        return $submissions;
    }

    public function changeOaiData($hookName, $args): bool
    {
        /** @var OAIRecord $oairecord */
        $record = $args[0];

        /** @var Submission $submission */
        $submission = $record->getData('monograph');
        $publicationFormat = $record->getData('publicationFormat');
        $publicationFormat->setData('entryKey', 'UPS');
        $publication = $submission->getCurrentPublication();
        if ($publication->getData('volumeId')) {
            /** @var VolumeDAO $volumeDao */
            $volumeDao = DAORegistry::getDAO('VolumeDAO');
            $volume = $volumeDao->getById($publication->getData('volumeId'));

            //Title
            $publicationTitles = $publication->getTitles();
            $volumeTitles = $volume->getTitles();
            foreach ($publicationTitles as $locale => $title) {
                $volumeTitle = $volumeTitles[$locale];
                if($volumePostion = $publication->getData('volumePosition')){
                    $volumeTitle = PKPString::concatTitleFields([$volumeTitle, $volumePostion]);
                }
                $title = PKPString::concatTitleFields([$volumeTitle, $title]);
                $publication->setData('title', $title, $locale);
            }
        }

        return false;
    }
}
