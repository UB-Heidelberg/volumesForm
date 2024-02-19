<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridHandler.php
 *
 * @class VolumeGridHandler
 *
 * @brief Handle volume grid requests.
 */

namespace APP\plugins\generic\volumesForm\controllers\grid;


use APP\notification\NotificationManager;
use APP\plugins\generic\volumesForm\classes\VolumeDAO;
use APP\plugins\generic\volumesForm\controllers\grid\form\VolumeForm;
use APP\plugins\generic\volumesForm\VolumesFormPlugin;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\core\PKPRequest;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\file\TemporaryFileManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;

class VolumeGridHandler extends GridHandler
{
	/** @var VolumesFormPlugin $plugin */
    private VolumesFormPlugin $plugin;

	/**
	 * Constructor
	 */
	public function __construct(VolumesFormPlugin $plugin)
	{
        parent::__construct();
        $this->plugin = $plugin;
		$this->addRoleAssignment(
			[Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
			['fetchGrid', 'fetchRow', 'addVolume', 'editVolume', 'updateVolume', 'deleteVolume', 'uploadImage']
		);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	public function authorize($request, &$args, $roleAssignments)
    {
		if ($request->getContext()) {
			$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		} else {
			$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		}
		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Overridden template methods
	//

	/**
	 * Configure the grid.
	 * @copydoc Gridhandler::initialize()
	 */
	public function initialize($request, $args = null): void
	{
		parent::initialize($request, $args);
		$press = $request->getPress();

		// Set the grid title.
		$this->setTitle('plugins.generic.volumesForm.volumeTitle');
		$this->setEmptyRowText('plugins.generic.volumesForm.noneCreated');

		// Get the items and add the data to the grid.
        /** @var VolumeDAO $volumeDao */
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volumeIterator = $volumeDao->getByPressId($press->getId());

		$gridData = array();
		while ($volume = $volumeIterator->next()) {
			$volumeId = $volume->getId();
			$gridData[$volumeId] = ['title' => $volume->getLocalizedTitle(),];
		}

		$this->setGridDataElements($gridData);

		// Add grid-level actions
		$router = $request->getRouter();
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
	public function getJSHandler(): string
	{
		return '$.pkp.plugins.generic.volumesForm.VolumeGridHandler';
	}

	/**
	 * Get the list of "publish data changed" events.
	 * Used to update the site context switcher upon create/delete.
	 * @return array
	 */
	public function getPublishChangeEvents(): array
	{
		return ['updateSidebar'];
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return VolumeGridRow
	 */
	public function getRowInstance(): VolumeGridRow
	{
		return new VolumeGridRow();
	}

	//
	// Public Volume Grid Actions
	//
    /**
     * An action to add a new volume.
     *
     * @param $args    array
     * @param $request PKPRequest
     *
     * @return JSONMessage
     */
	public function addVolume(array $args, PKPRequest $request): JSONMessage
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
	public function editVolume(array $args, PKPRequest $request): JSONMessage
	{
		// Check if volume exists.
		$volumeId = $args['volumeId'] ?? null;
		$contextId = $request->getContext()->getId();
		$this->setupTemplate($request);

		// Initialize volume form.
		$volumeForm = new VolumeForm($this->plugin, $contextId, $volumeId);
		$volumeForm->initData();
		return new JSONMessage(true, $volumeForm->fetch($request));
	}

	/**
	 * Update a volume.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	public function updateVolume(array $args, PKPRequest $request): JSONMessage
	{
		// Update the volume and validate before.
		$volumeId = (int) $request->getUserVar('volumeId');
		$contextId = $request->getContext()->getId();

		$volumeForm = new VolumeForm($this->plugin, $contextId, $volumeId);
		$volumeForm->readInputData();

		if ($volumeForm->validate()) {

			// Save.
			$volumeForm->execute();
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification($request->getUser()->getId());
			return DAO::getDataChangedEvent($volumeForm->getVolumeId());
		} else {

			// Present errors.
            return new JSONMessage(true, $volumeForm->fetch($request));
		}
	}

	/**
	 * Delete a volume.
	 *
	 * @param $args    array
	 * @param $request PKPRequest
	 *
	 * @return JSONMessage JSON object
	 */
	public function deleteVolume(array $args, PKPRequest $request): JSONMessage
	{
		$press = $request->getPress();
		/** @var VolumeDAO $volumeDao */
        $volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volume = $volumeDao->getById($request->getUserVar('volumeId'), $press->getId());
		if (isset($volume) && !$volume->hasPublishedParts()) {
			$volumeDao->deleteObject($volume);
			return DAO::getDataChangedEvent($volume->getId());
		} else {
			return new JSONMessage(false, __('manager.setup.errorDeletingItem'));
		}
	}

	/**
	 * Handle file uploads for cover/image art for a volume.
	 * @param $request PKPRequest
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	public function uploadImage(array $args, PKPRequest $request): JSONMessage
	{
		$user = $request->getUser();

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
