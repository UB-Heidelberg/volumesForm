<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/VolumeGridCellProvider.php
 *
 * Class for a cell provider to display information about volumes.
 */

namespace APP\plugins\generic\volumesForm\controllers\grid;

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridRow;

class VolumeGridCellProvider extends GridCellProvider
{
    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     * @param $row GridRow
     * @param $column GridColumn
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column): array
    {
        // Get volume and column id.
        $volume = $row->getData();
        $columnId = $column->getId();
        assert(!empty($columnId));

        // Just one column.
        if ($columnId === 'volume') {
            return ['label' => $volume['title']];
        }

        return [];
    }
}
