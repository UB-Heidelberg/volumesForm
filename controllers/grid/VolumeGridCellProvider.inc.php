<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridCellProvider.inc.php
 *
 * Class for a cell provider to display information about volumes.
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class VolumeGridCellProvider extends GridCellProvider
{

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     * @param $row GridRow
     * @param $column GridColumn
     * @return array
     */
    function getTemplateVarsFromRowColumn($row, $column)
    {
        // Get volume and column id.
        $volume = $row->getData();
        $columnId = $column->getId();
        assert(!empty($columnId));

        switch ($columnId) {

                // Just one column.
            case 'volume':
                return array('label' => $volume['title']);
        }
    }
}
