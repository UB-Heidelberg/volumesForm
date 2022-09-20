<?php

/**
 * @file plugins/generic/volumesForm/VolumesFormPlugin.inc.php
 *
 * @brief Add Volumes to the submission metadata.
 *
 */
import('lib.pkp.classes.plugins.GenericPlugin');
use \PKP\components\forms\FieldSelect;

class VolumesFormPlugin extends GenericPlugin
{
	/**
	 * @copydoc Plugin::getName()
	 */
	function getName()
	{
		return 'VolumesFormPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName()
	{
		return __('plugins.generic.volumesForm.title');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription()
	{
		return __('plugins.generic.volumesForm.description');
	}
	
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null)
	{
		$success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled($mainContextId)) {

			// Get custom DAO.
			import('plugins.generic.volumesForm.classes.VolumeDAO');
			$volumeDao = new VolumeDAO();
			DAORegistry::registerDAO('VolumeDAO', $volumeDao);

			// Show volume grid in publication and submission workflow.
			HookRegistry::register('TemplateResource::getFilename', array($this, '_overridePluginTemplates'));

			// Load grid handler.
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));

			// Handler for custom volume page in frontend.
			HookRegistry::register('LoadHandler', array($this, 'setupVolumePageHandler'));

			// Load JS for grid handler.
			HookRegistry::register('TemplateManager::display', array($this, 'addGridhandlerJs'));

			// Extend the publication schema to store data.
			HookRegistry::register('Schema::get::publication', array($this, 'addToSchema'));

			// We need the volume field in three forms: normal submission, metadataform, quicksubmit form.
			HookRegistry::register('submissionsubmitstep1form::display', array($this, 'addToStep1'));
			HookRegistry::register('quicksubmitform::display', array($this, 'addToStep1'));
			HookRegistry::register('quicksubmitform::AdditionalItems', array($this, 'addToQuicksubmit'));
			HookRegistry::register('Form::config::before', array($this, 'addToCatalogEntry'));

			// Init the new field.
			HookRegistry::register('submissionsubmitstep1form::initdata', array($this, 'metadataInitData'));
			HookRegistry::register('quicksubmitform::initdata', array($this, 'metadataInitData'));

			// Hook for readUserVars: consider the new field entries.
			HookRegistry::register('submissionsubmitstep1form::readuservars', array($this, 'metadataReadUserVars'));
			HookRegistry::register('quicksubmitform::readuservars', array($this, 'metadataReadUserVars'));

			// Hook for execute: consider the new fields in the publication settings.
			HookRegistry::register('Publication::add', array($this, 'metadataExecute'));
			HookRegistry::register('quicksubmitform::execute', array($this, 'metadataExecute'));

			// Load stylesheet for volume pages.
			$request = Application::get()->getRequest();
			$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/less/volumes.less';
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addStyleSheet('volumeStyles', $url, [
				'contexts' => 'frontend',
				'priority' => STYLE_SEQUENCE_CORE,
			]);
		}
		return $success;
	}
	
	/**
	 * Permit requests to the volume grid handler.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params)
	{
		$component = &$params[0];
		if ($component == 'plugins.generic.volumesForm.controllers.grid.VolumeGridHandler') {
			import($component);
			VolumeGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Handle frontend view in catalog.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	public function setupVolumePageHandler($hookName, $params) {
		$page = $params[0];
		$op = $params[1]; 
		if ($page === 'catalog' && $op == 'volume') {
			define('HANDLER_CLASS', 'VolumePageHandler');
			$this->import('VolumePageHandler');
			VolumePageHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Extend the context entity's schema with an the new field values.
	 * Save values in publication_settings table.
	 * @param $hookName string
	 * @param $args array
	 */
	public function addToSchema($hookName, $args)
	{
		// Fetch schema.
		$schema = $args[0];

		// Store in publication_settings table. 
		$schema->properties->volumeId = (object) [
			'type' => 'string',
			'apiSummary' => true,
			'multilingual' => false,
			'validation' => ['nullable']
		];
		return false;
	}

	function addToStep1($hookName, $params)
	{

		// Fetch template manager.
		$request = PKPApplication::get()->getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$contextId = $request->getContext()->getId();

		// Get volumes for this context and assign options.
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volumeOptions = [];
		$volumes =  $volumeDao->getByContextId($contextId);
		$volumeTitlesArray = $volumes->toAssociativeArray();
		foreach ($volumeTitlesArray as $volume) {
			$volumeOptions[$volume->getId()] = $volume->getLocalizedTitle();
		}
		$volumeOptions = ['' => __('submission.submit.selectVolume')] + $volumeOptions;
		$templateMgr->assign('volumeOptions', $volumeOptions);
	}

	/**
	 * Add volume field to quicksubmit form.
	 * @param $hookName string
	 * @param $params array
	 */
	public function addToQuicksubmit($hookName, $params)
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
	 * @param $form FormComponent
	 */
	public function addToCatalogEntry($hookName, $form)
	{

		// Only modify the catalog entry form.
		if (!defined('FORM_CATALOG_ENTRY') || $form->id !== FORM_CATALOG_ENTRY) {
			return;
		}

		// Don't do anything at the site-wide level, only dependent context.
		$context = Application::get()->getRequest()->getContext();
		if (!$context) {
			return;
		}

		// Get current publication.
		$path = parse_url($form->action)['path'];
		if (!$path) return;
		$args = explode('/', $path);
		$publicationId = 0;
		if ($key = array_search('publications', $args)) {
			if (array_key_exists($key + 1, $args)) {
				$publicationId = intval($args[$key + 1]);
			}
		}
		if (!$publicationId) return;
		$publication = Services::get('publication')->get($publicationId);
		if (!$publication) return;

		// Volume options.
		$request = PKPApplication::get()->getRequest();
		$contextId = $request->getContext()->getId();
		$volumeOptions = [['value' => '', 'label' => '']];
		$result = DAORegistry::getDAO('VolumeDAO')->getByContextId($contextId);
		while (!$result->eof()) {
			$volume = $result->next();
			$volumeOptions[] = [
				'value' => (int) $volume->getId(),
				'label' => $volume->getLocalizedTitle(),
			];
		}

		// Add a field to the form.
		$form->addField(new FieldSelect('volumeId', [
			'label' => __('volume.volume'),
			'value' => $publication->getData('volumeId'),
			'options' => $volumeOptions,
		]), [FIELD_POSITION_AFTER, 'seriesPosition']);

		return false;
	}

	/**
	 * Initialize data in the metadata forms.
	 * @param $hookName string
	 * @param $args array
	 */
	function metadataInitData($hookName, $args)
	{
		// Get form.
		$form = $args[0];

		// Get publication.
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publicationId = $form->submission->getData('currentPublicationId');
		$publication = $publicationDao->getById($publicationId);

		// Initialize Data.
		$form->setData('volumeId', $publication->getData('volumeId'));
	}

	/**
	 * Get user-entered values.
	 * @param $hookName string
	 * @param $args array
	 */
	public function metadataReadUserVars($hookName, $args)
	{
		// Get user variables.
		$userVars = &$args[1];
		$userVars[] = 'volumeId';
	}

	/**
	 * Save additional fields in the forms upon execution.
	 * @param $hookName string
	 * @param $args array
	 */
	public function metadataExecute($hookName, $args)
	{
		// Get Daos and context.
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$context = Application::get()->getRequest()->getContext();

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
		} else {

			// Fetch publication and get volume ID (by reference).
			$publication = &$args[0];
			$publicationId = $publication->getData('id');
			$publication = $publicationDao->getById($publicationId);
			$request = &$args[1];
			$volumeId = $request->getUserVars()['volumeId'];
		}

		// Set data.
		if ($publication && $volumeId) {
			$publication->setData('volumeId', $volumeId);

			// Update pub object.
			$publicationDao->updateObject($publication);
		}
		return false;
	}

	/**
	 * Add custom gridhandlerJS for backend
	 */
	function addGridhandlerJs($hookName, $params)
	{
		$templateMgr = $params[0];
		$request = $this->getRequest();
		$gridHandlerJs = $this->getJavaScriptURL($request, false) . DIRECTORY_SEPARATOR . 'VolumeGridHandler.js';
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
	function getInstallMigration()
	{
		$this->import('VolumeSchemaMigration');
		return new VolumeSchemaMigration();
	}

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL()
	{
		return Application::get()->getRequest()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
	}
}
