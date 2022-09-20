<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridRow.inc.php
 *
 * Handle volume grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class VolumeGridRow extends GridRow
{

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null)
	{
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			$router = $request->getRouter();
			$actionArgs = array(
				'volumeId' => $rowId
			);

			// Create the "edit" action.
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editVolume',
					new AjaxModal(
						$router->url($request, null, null, 'editVolume', null, $actionArgs),
						__('grid.action.edit'),
						'modal_edit',
						true
					),
					__('grid.action.edit'),
					'edit'
				)
			);

			// Create the "delete" action.
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteVolume',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteVolume', null, $actionArgs),
						'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}
