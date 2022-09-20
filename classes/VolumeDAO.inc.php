<?php

/**
 * @file plugins/generic/volumeForm/classes/VolumeDAO.inc.php
 *
 * Operations for retrieving and modifying volume objects.
 */
import('lib.pkp.classes.db.DAO');
import('plugins.generic.volumesForm.classes.Volume');

class VolumeDAO extends DAO
{
	/**
	 * Retrieve a volume by ID.
	 * @param $volumeId int
	 * @param $contextId int optional
	 * @return Volume|null
	 */
	function getById($volumeId, $contextId = null)
	{
		$params = [(int) $volumeId];
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT	*
			FROM	volumes
			WHERE	volume_id = ?
			' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Retrieve all volumes for a press.
	 * @return DAOResultFactory containing volumes.
	 */
	function getByPressId($pressId, $rangeInfo = null)
	{
		return $this->getByContextId($pressId, $rangeInfo);
	}

	/**
	 * @copydoc PKPSectionDAO::getByContextId()
	 */
	function getByContextId($contextId, $rangeInfo = null)
	{
		$result = $this->retrieveRange(
			'SELECT * FROM volumes WHERE context_id = ?',
			[(int) $contextId],
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	
	/**
	 * Retrieve a volume by path.
	 * @param $path string
	 * @param $pressId int
	 * @return Volume|null
	 */
	function getByPath($path, $pressId) {
		$result = $this->retrieve(
			'SELECT * FROM volumes WHERE path = ? AND context_id = ?',
			[(string) $path, (int) $pressId]
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get the ID of the last inserted volume.
	 * @return int
	 */
	function getInsertId()
	{
		return $this->_getInsertId('volumes', 'volume_id');
	}

	/**
	 * Insert a Volume.
	 * @param $volume Volume.
	 * @return int Inserted volume ID.
	 */
	function insertObject($volume)
	{
		$this->update(
			'INSERT INTO volumes(volume_id, context_id, path, image) VALUES (?, ?, ?, ?)',
			array(
				(int) $volume->getId(),
				(int) $volume->getContextId(),
				(string) $volume->getPath(),
				serialize($volume->getImage() ? $volume->getImage() : array()),
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
	function updateObject($volume)
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
				serialize($volume->getImage() ? $volume->getImage() : array()),
				(int) $volume->getId()
			)
		);
		$this->updateLocaleFields($volume);
	}

	/**
	 * Delete a volume by ID.
	 * @param $volumeId int
	 */
	function deleteById($volumeId)
	{
		// Delete in both tables.
		$this->update(
			'DELETE FROM volumes WHERE volume_id = ?',
			[(int) $volumeId]
		);
		$this->update(
			'DELETE FROM volume_settings WHERE volume_id = ?',
			[(int) $volumeId]
		);
	}
	/**
	 * Delete a volume object.
	 * @param $volume Volume
	 */
	function deleteObject($volume)
	{
		$this->deleteById($volume->getId());
	}
	/**
	 * Generate a new volume object.
	 * @return Volume
	 */
	function newDataObject()
	{
		return new Volume();
	}
	/**
	 * Return a new volume object from a given row.
	 * @return Volume
	 */
	function _fromRow($row)
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
	function getLocaleFieldNames()
	{
		return ['title', 'description'];
	}


	/**
	 * Get a list of additional fields 
	 * to save in volume_settings table.
	 * @return array
	 */
	function getAdditionalFieldNames()
	{
		return array_merge(
			parent::getAdditionalFieldNames(),
			['sortOption', 'ppn']
		);
	}
	/**
	 * Update the settings for this object.
	 * @param $volume object
	 */
	function updateLocaleFields($volume)
	{
		$this->updateDataObjectSettings(
			'volume_settings',
			$volume,
			array('volume_id' => $volume->getId())
		);
	}

	/**
	 * Check if a volume exists with a specified path.
	 * @param $path the path for the volume
	 * @return boolean
	 */
	function volumeExistsByPath($path)
	{
		$result = $this->retrieve(
			'SELECT COUNT(*) AS row_count FROM volumes WHERE path = ?',
			[$path]
		);
		$row = $result->current();
		return $row ? (bool) $row->row_count : false;
	}
}
