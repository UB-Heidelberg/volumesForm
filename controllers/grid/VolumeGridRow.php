<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridRow.php
 *
 * Handle volume grid row requests.
 */

namespace APP\plugins\generic\volumesForm\controllers\grid;

use APP\core\Application;
use APP\plugins\generic\volumesForm\classes\VolumeDAO;
use PKP\controllers\grid\GridRow;
use PKP\db\DAORegistry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class VolumeGridRow extends GridRow
{
	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	public function initialize($request, $template = null): void
	{
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			$router = $request->getRouter();
			$actionArgs = ['volumeId' => $rowId];

			// Create the "edit" action.
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
            $context = Application::get()->getRequest()->getContext();
            /** @var VolumeDAO $volumeDao */
            $volumeDao = DAORegistry::getDAO('VolumeDAO');
            $volume = $volumeDao->getById($rowId, $context->getId());
            if(!$volume->hasPublishedParts()){
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
}
