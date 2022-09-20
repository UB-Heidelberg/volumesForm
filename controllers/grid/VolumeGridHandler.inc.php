<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridHandler.inc.php
 *
 * @class VolumeGridHandler
 *
 * @brief Handle volume grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.volumesForm.controllers.grid.VolumeGridRow');
import('plugins.generic.volumesForm.controllers.grid.VolumeGridCellProvider');

class VolumeGridHandler extends GridHandler
{

	static $plugin;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'fetchRow', 'addVolume', 'editVolume', 'updateVolume', 'deleteVolume', 'uploadImage')
		);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */

	function authorize($request, &$args, $roleAssignments) {
		if ($request->getContext()) {
			import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
			$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		} else {
			import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
			$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		}
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Getters/Setters
	//
	/**
	 * Set the Volume form plugin.
	 * @param $plugin VolumesFormPlugin
	 */
	static function setPlugin($plugin)
	{
		self::$plugin = $plugin;
	}


	//
	// Overridden template methods
	//

	/**
	 * Configure the grid.
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args = null)
	{
		parent::initialize($request, $args);
		$press = $request->getPress();

		// Load locale components.
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);

		// Set the grid title.
		$this->setTitle('plugins.generic.volumesForm.volumeTitle');
		$this->setEmptyRowText('plugins.generic.volumesForm.noneCreated');

		// Get the items and add the data to the grid.
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volumeIterator = $volumeDao->getByPressId($press->getId());

		$gridData = array();
		while ($volume = $volumeIterator->next()) {

			$volumeId = $volume->getId();
			$gridData[$volumeId] = array(
				'title' => $volume->getLocalizedTitle(),
			);
		}

		$this->setGridDataElements($gridData);

		// Add grid-level actions
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addVolume',
				new AjaxModal(
					$router->url($request, null, null, 'addVolume', null, array('gridId' => $this->getId())),
					__('grid.action.addVolume'),
					'modal_manage'
				),
				__('grid.action.addVolume'),
				'add_item'
			)
		);

		$volumeGridCellProvider = new VolumeGridCellProvider();

		// Columns
		$this->addColumn(new GridColumn(
			'volume',
			'plugins.generic.volumesForm.volumeTitle',
			null,
			'controllers/grid/gridCell.tpl',
			$volumeGridCellProvider
		));
	}

	//
	// Overridden methods from GridHandler
	//

	// /**
	//  * @copydoc GridHandler::addFeatures()
	//  */
	// function initFeatures($request, $args) {
	// 	import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
	// 	return array(new OrderGridItemsFeature());
	// }

	/**
	 * @copydoc GridHandler::getJSHandler()
	 */
	public function getJSHandler()
	{
		return '$.pkp.plugins.generic.volumesForm.VolumeGridHandler';
	}

	/**
	 * Get the list of "publish data changed" events.
	 * Used to update the site context switcher upon create/delete.
	 * @return array
	 */
	function getPublishChangeEvents()
	{
		return array('updateSidebar');
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return VolumeGridRow
	 */
	function getRowInstance()
	{
		return new VolumeGridRow();
	}

	//
	// Public Volume Grid Actions
	//
	/**
	 * An action to add a new volume.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addVolume($args, $request)
	{
		// Calling editVolume with an empty ID will add
		// a new volume.
		return $this->editVolume($args, $request);
	}

	/**
	 * An action to edit a volume.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editVolume($args, $request)
	{
		// Check if volume exists.
		$volumeId = isset($args['volumeId']) ? $args['volumeId'] : null;
		$contextId = $request->getContext()->getId();
		$this->setupTemplate($request);

		// Initialize volume form.
		import('plugins.generic.volumesForm.controllers.grid.form.VolumeForm');
		$volumeForm = new VolumeForm(self::$plugin, $contextId, $volumeId);
		$volumeForm->initData();
		return new JSONMessage(true, $volumeForm->fetch($request));
	}

	/**
	 * Update a volume.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateVolume($args, $request)
	{
		// Update the volume and validate before.
		$volumeId = $request->getUserVar('volumeId');
		$contextId = $request->getContext()->getId();

		import('plugins.generic.volumesForm.controllers.grid.form.VolumeForm');
		$volumeForm = new VolumeForm(self::$plugin, $contextId, $volumeId);
		$volumeForm->readInputData();

		if ($volumeForm->validate()) {

			// Save.
			$volumeForm->execute();
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($request->getUser()->getId());
			return DAO::getDataChangedEvent($volumeForm->getVolumeId());
		} else {

			// Present errors.
			$json = new JSONMessage(true, $volumeForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete a volume.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteVolume($args, $request)
	{
		$press = $request->getPress();
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volume = $volumeDao->getById($request->getUserVar('volumeId'), $press->getId());
		if (isset($volume)) {
			$volumeDao->deleteObject($volume);
			return DAO::getDataChangedEvent($volume->getId());
		} else {
			AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
			return new JSONMessage(false, __('manager.setup.errorDeletingItem'));
		}
	}

	/**
	 * Handle file uploads for cover/image art for a volume.
	 * @param $request PKPRequest
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	function uploadImage($args, $request)
	{
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
		if ($temporaryFile) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
			return $json;
		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}
}
