<?php

/**
 * @file plugins/generic/volumesForm/classes/Volume.inc.php
 *
 * Data object representing a Volume.
 */
class Volume extends DataObject
{
	//
	// Get/set methods
	//
	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId()
	{
		return $this->getData('contextId');
	}
	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId)
	{
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get name.
	 * @return string
	 */
	function getPPN()
	{
		return $this->getData('ppn');
	}

	/**
	 * Set name.
	 * @param $PPN string
	 */
	function setPPN($ppn)
	{
		return $this->setData('ppn', $ppn);
	}


	/**
	 * Get localized title of the volume.
	 * @return string
	 */
	function getLocalizedTitle()
	{
		return $this->getLocalizedData('title');
	}

	/**
	 * Get localized decription of the volume.
	 * @return string
	 */
	function getLocalizedDescription()
	{
		return $this->getLocalizedData('description');
	}

	/**
	 * Get title of volume.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale)
	{
		return $this->getData('title', $locale);
	}

	/**
	 * Set title of volume.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale)
	{
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get description of volume.
	 *  @param $locale
	 * @return string
	 */
	function getDescription($locale)
	{
		return $this->getData('description', $locale);
	}

	/**
	 * Set description of volume.
	 * @param $description string
	 * @param $locale string
	 */
	function setDescription($description, $locale)
	{
		return $this->setData('description', $description, $locale);
	}

	/**
	 * Get path to volume (in URL).
	 * @return string
	 */
	function getPath()
	{
		return $this->getData('path');
	}

	/**
	 * Set path to volume (in URL).
	 * @param $path string
	 */
	function setPath($path)
	{
		return $this->setData('path', $path);
	}

	/**
	 * Get the option how the books in this volume should be sorted,
	 * in the form: concat(sortBy, sortDir).
	 * @return string
	 */
	function getSortOption()
	{
		return $this->getData('sortOption');
	}

	/**
	 * Set the option how the books in this volume should be sorted,
	 * in the form: concat(sortBy, sortDir).
	 * @param $sortOption string
	 */
	function setSortOption($sortOption)
	{
		return $this->setData('sortOption', $sortOption);
	}

	/**
	 * Get the image.
	 * @return array
	 */
	function getImage()
	{
		return $this->getData('image');
	}

	/**
	 * Set the image.
	 * @param $image array
	 */
	function setImage($image)
	{
		return $this->setData('image', $image);
	}

	function getPressId()
	{
		return $this->getContextId();
	}

	/**
	 * Set ID of press.
	 * @param $pressId int
	 */
	function setPressId($pressId)
	{
		return $this->setContextId($pressId);
	}
}
