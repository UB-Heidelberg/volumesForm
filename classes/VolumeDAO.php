<?php

/**
 * @file plugins/generic/volumeForm/classes/VolumeDAO.php
 *
 * Operations for retrieving and modifying volume objects.
 */

namespace APP\plugins\generic\volumesForm\classes;


use PKP\db\DAO;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\submission\Collector;

class VolumeDAO extends DAO
{
    public const ORDERBY_VOLUME_POSITION = 'volumePosition';

    /**
     * Compile the sort orderBy and orderDirection into an option
     * used in forms
     */
    protected function getSortOption(string $sortBy, string $sortDir): string
    {
        return $sortBy . '-' . $sortDir;
    }

    /**
     * Get an array of sort options used in forms when configuring
     * how published submissions are displayed
     */
    public function getSortSelectOptions(): array
    {
        return [
            $this->getSortOption(Collector::ORDERBY_TITLE, Collector::ORDER_DIR_ASC)            => __('catalog.sortBy.titleAsc'),
            $this->getSortOption(Collector::ORDERBY_TITLE, Collector::ORDER_DIR_DESC)           => __('catalog.sortBy.titleDesc'),
            $this->getSortOption(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_ASC)   => __('catalog.sortBy.datePublishedAsc'),
            $this->getSortOption(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_DESC)  => __('catalog.sortBy.datePublishedDesc'),
            $this->getSortOption( self::ORDERBY_VOLUME_POSITION, Collector::ORDER_DIR_ASC )     => __('catalog.sortBy.volumePostionAsc'),
            $this->getSortOption( self::ORDERBY_VOLUME_POSITION, Collector::ORDER_DIR_DESC )     => __('catalog.sortBy.volumePostionDesc'),
        ];
    }

    /**
     * Get the default sort option used in forms when configuring
     * how published submissions are displayed
     *
     * @see self::getSortSelectOptions()
     */
    public function getDefaultSortOption(): string
    {
        return $this->getSortOption(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_DESC);
    }

    /**
     * Retrieve a volume by ID.
     *
     * @param int      $volumeId
     * @param int|null $contextId optional
     *
     * @return Volume|null
     */
	public function getById(int $volumeId, ?int $contextId = null): Volume|null
	{
		$params = [$volumeId];
		if ($contextId) {$params[] = $contextId;}

		$result = $this->retrieve(
			'SELECT	*
			FROM	volumes
			WHERE	volume_id = ?
			' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);

        if ($result) {
            $row = $result->current();
            return $row ? $this->_fromRow((array) $row) : null;
        }

        return null;
	}

    /**
     * Retrieve all volumes for a press.
     *
     * @param int                $pressId
     * @param DBResultRange|null $rangeInfo
     *
     * @return DAOResultFactory containing volumes.
     */
	public function getByPressId(int $pressId, ?DBResultRange $rangeInfo = null): DAOResultFactory
	{
		return $this->getByContextId($pressId, $rangeInfo);
	}

    /**
     * @param int                $contextId
     * @param DBResultRange|null $rangeInfo
     *
     * @return DAOResultFactory
     */
	public function getByContextId(int $contextId, ?DBResultRange $rangeInfo = null): DAOResultFactory
	{
		$result = $this->retrieveRange(
			'SELECT * FROM volumes WHERE context_id = ?',
			[$contextId],
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

    /**
     * @param int                $seriesId
     * @param DBResultRange|null $rangeInfo
     *
     * @return DAOResultFactory
     */
    public function getBySeriesId(int $seriesId, ?DBResultRange $rangeInfo = null): DAOResultFactory
    {
        $result = $this->retrieveRange(
            'SELECT * FROM volumes JOIN volume_settings ON volumes.volume_id=volume_settings.volume_id WHERE  volume_settings.setting_name = "seriesId" AND volume_settings.setting_value = ?',
            [$seriesId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }


	/**
	 * Retrieve a volume by path.
     *
	 * @param $path string
	 * @param $pressId int
     *
	 * @return Volume|null
	 */
	public function getByPath(string $path, int $pressId): Volume|null
    {
		$result = $this->retrieve(
			'SELECT * FROM volumes WHERE path = ? AND context_id = ?',
			[$path, $pressId]
		);

        if ($result) {
            $row = $result->current();
            return $row ? $this->_fromRow((array) $row) : null;
        }

        return null;
	}

	/**
	 * Insert a Volume.
	 * @param $volume Volume.
	 * @return int Inserted volume ID.
	 */
	public function insertObject(Volume $volume): int
	{
		$this->update(
			'INSERT INTO volumes(volume_id, context_id, path, image) VALUES (?, ?, ?, ?)',
			array(
				(int) $volume->getId(),
				(int) $volume->getContextId(),
				(string) $volume->getPath(),
				serialize($volume->getImage()),
			)
		);
		$volume->setId($this->getInsertId());
		$this->updateLocaleFields($volume);
		return $volume->getId();
	}

	/**
	 * Update the database with a volume object.
	 * @param $volume Volume
	 */
	public function updateObject(Volume $volume): void
	{
		$this->update(
			'UPDATE	volumes
			SET	context_id = ?,
			path = ?,
			image = ?
			WHERE volume_id = ?',
			array(
                (int) $volume->getContextId(),
                (string) $volume->getPath(),
                serialize($volume->getImage()),
                (int) $volume->getId()
			)
		);
		$this->updateLocaleFields($volume);
	}

	/**
	 * Delete a volume by ID.
	 * @param $volumeId int
	 */
	public function deleteById(int $volumeId): void
	{
		// Delete in both tables.
		$this->update(
			'DELETE FROM volumes WHERE volume_id = ?',
			[$volumeId]
		);
		$this->update(
			'DELETE FROM volume_settings WHERE volume_id = ?',
			[$volumeId]
		);
	}

	/**
	 * Delete a volume object.
	 * @param $volume Volume
	 */
	public function deleteObject(Volume $volume): void
	{
        $this->deleteById($volume->getId());
	}

	/**
	 * Generate a new volume object.
	 * @return Volume
	 */
	public function newDataObject(): Volume
	{
		return new Volume();
	}

    /**
     * Return a new volume object from a given row.
     *
     * @param array $row
     *
     * @return Volume
     */
	public function _fromRow(array $row): Volume
	{
		$volume = $this->newDataObject();
		$volume->setId($row['volume_id']);
		$volume->setContextId($row['context_id']);
		$volume->setImage(unserialize($row['image']));
		$volume->setPath($row['path']);
		$this->getDataObjectSettings('volume_settings', 'volume_id', $row['volume_id'], $volume);
		return $volume;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	public function getLocaleFieldNames(): array
	{
		$localeFieldNames = parent::getLocaleFieldNames();
        $localeFieldNames[] = 'title';
        $localeFieldNames[] = 'description';
        return $localeFieldNames;
	}

	/**
	 * Get a list of additional fields
	 * to save in volume_settings table.
	 * @return array
	 */
	public function getAdditionalFieldNames(): array
	{
		$additionalFieldNames = parent::getAdditionalFieldNames();
        $additionalFieldNames[] = 'ppn';
        $additionalFieldNames[] = 'isbn13';
        $additionalFieldNames[] = 'isbn10';
        $additionalFieldNames[] = 'sortOption';
        $additionalFieldNames[] = 'courseOfPublication';

        return $additionalFieldNames;
	}

    /**
     * Update the settings for this object.
     *
     * @param Volume $volume
     */
	public function updateLocaleFields(Volume $volume): void
	{
		$this->updateDataObjectSettings(
			'volume_settings',
			$volume,
			array('volume_id' => $volume->getId())
		);
	}

    /**
     * Check if a volume exists with a specified path.
     *
     * @param string $path the path for the volume
     *
     * @return bool
     */
	public function volumeExistsByPath(string $path): bool
	{
		$result = $this->retrieve(
			'SELECT COUNT(*) AS row_count FROM volumes WHERE path = ?',
			[$path]
		);

        if ($result) {
            $row = $result->current();
            return $row && $row->row_count > 0;
        }

        return false;
	}
}
