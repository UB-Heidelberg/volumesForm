<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/form/VolumeForm.inc.php
 *
 * Form for adding/editing a volume.
 *
 */

import('lib.pkp.classes.form.Form');
import('classes.file.PublicFileManager');

class VolumeForm extends Form
{

	/** @var VolumeFormPlugin */
	var $plugin;

	/** @var The context ID of the volume being edited */
	var $contextId;

	/** @var The volume ID */
	var $volumeId;


	/**
	 * Constructor.
	 * @param $contextId Context id.
	 * @param $volumeId Volume id.
	 */
	function __construct($volumesFormPlugin, $contextId, $volumeId = null)
	{
		parent::__construct('editVolumeForm.tpl');

		// Set vars.
		$this->plugin = $volumesFormPlugin;
		$this->contextId = $contextId;
		$this->volumeId = $volumeId;
		$request = Application::get()->getRequest();
		$user = $request->getUser();
		$this->userId = $user->getId();

		// Validation checks for this form.
		$form = $this;
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'plugins.generic.volumesForm.volumeNameRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'path', 'required', 'grid.volume.pathAlphaNumeric', '/^[a-zA-Z0-9\/._-]+$/'));
		$this->addCheck(new FormValidatorCustom(
			$this,
			'path',
			'required',
			'grid.volume.pathExists',
			function ($path) use ($form, $contextId) {
				$volumeDao = DAORegistry::getDAO('VolumeDAO');
				return !$volumeDao->volumeExistsByPath($path, $contextId) || ($form->getData('oldPath') != null && $form->getData('oldPath') == $path);
			}
		));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the volume ID.
	 * @return int volumeId
	 */
	function getVolumeId()
	{
		return $this->volumeId;
	}

	/**
	 * Set the volume ID for this section.
	 * @param $volumeId int
	 */
	function setVolumeId($volumeId)
	{
		$this->volumeId = $volumeId;
	}

	/**
	 * Get the context id.
	 * @return int contextId
	 */
	function getContextId()
	{
		return $this->contextId;
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames()
	{
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		return $volumeDao->getLocaleFieldNames();
	}

	/**
	 * @see Form::initData()
	 */
	function initData()
	{
		// Get volume.
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
		$volume = $volumeDao->getById($this->getVolumeId(), $this->getContextId());

		// Init data.
		if ($volume) {
			$this->setData('title', $volume->getTitle(null));
			$this->setData('description', $volume->getDescription(null));
			$this->setData('path', $volume->getPath());
			$this->setData('image', $volume->getImage());
			$this->setData('ppn', $volume->getPPN());

			// Get sort options of the monographs.
			$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
			$sortOption = $volume->getSortOption() ? $volume->getSortOption() : $submissionDao->getDefaultSortOption();
			$this->setData('sortOption', $sortOption);
		}
	}

	/**
	 * @see Form::validate()
	 */
	function validate($callHooks = true)
	{
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			import('lib.pkp.classes.file.TemporaryFileManager');
			$temporaryFileManager = new TemporaryFileManager();
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /* @var $temporaryFileDao TemporaryFileDAO */
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $this->userId);
			if (
				!$temporaryFile ||
				!($this->_imageExtension = $temporaryFileManager->getImageExtension($temporaryFile->getFileType())) ||
				!($this->_sizeArray = getimagesize($temporaryFile->getFilePath())) ||
				$this->_sizeArray[0] <= 0 || $this->_sizeArray[1] <= 0
			) {
				$this->addError('temporaryFileId', __('form.invalidImage'));
				return false;
			}
		}
		return parent::validate($callHooks);
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData()
	{
		// Read user-entered values.
		$this->readUserVars(array('title', 'path', 'description', 'temporaryFileId', 'ppn', 'sortOption'));

		// For path duplicate checking; excuse the current path.
		if ($volumeId = $this->getVolumeId()) {
			$volumeDao = DAORegistry::getDAO('VolumeDAO');
			$volume = $volumeDao->getById($volumeId, $this->getContextId());
			$this->setData('oldPath', $volume->getPath());
		}
	}

	/**
	 * Display the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false)
	{
		// Pass necessary vars to template.
		$templateMgr = TemplateManager::getManager($request);
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$templateMgr->assign('volumeId', $this->getVolumeId());
		$templateMgr->assign('sortOptions', $submissionDao->getSortSelectOptions());
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs)
	{
		// Fetch volume ID and volume DAO.
		$volumeId = $this->getVolumeId();
		$volumeDao = DAORegistry::getDAO('VolumeDAO');

		// Get a volume object to edit or create.
		if ($volumeId) {
			$volume = $volumeDao->getById($this->volumeId, $this->contextId);
		} else {
			$volume = $volumeDao->newDataObject();
			$volume->setContextId($this->contextId);
		}

		// Set the editable properties of the volume object.
		$volume->setTitle($this->getData('title'), null);
		$volume->setDescription($this->getData('description'), null);
		$volume->setPath($this->getData('path'));
		$volume->setPPN($this->getData('ppn'));
		$volume->setSortOption($this->getData('sortOption'));

		// Update or insert the volume object
		if ($volumeId) {
			$volumeDao->updateObject($volume);
		} else {
			$volumeId = $volumeDao->insertObject($volume);
		}

		// Handle the image upload if there was one.
		if ($temporaryFileId = $this->getData('temporaryFileId')) {

			// Fetch the temporary file storing the uploaded library file.
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $this->userId);
			$temporaryFilePath = $temporaryFile->getFilePath();

			// Use the public directory to store the image (FIXME: templates cannot access wlb/files/ due to cataloghandler.)
			$publicFileManager = new PublicFileManager();
			$basePath = $publicFileManager->getContextFilesPath($this->contextId) . '/volumes/';

			// Delete the old file if it exists.
			$oldSetting = $volume->getImage();
			if ($oldSetting) {
				$publicFileManager->deleteByPath($basePath . $oldSetting['thumbnailName']);
				$publicFileManager->deleteByPath($basePath . $oldSetting['name']);
			}

			// The following variables were fetched in validation.
			assert($this->_sizeArray && $this->_imageExtension);

			// Generate the surrogate images.
			switch ($this->_imageExtension) {
				case '.jpg':
					$image = imagecreatefromjpeg($temporaryFilePath);
					break;
				case '.png':
					$image = imagecreatefrompng($temporaryFilePath);
					break;
				case '.gif':
					$image = imagecreatefromgif($temporaryFilePath);
					break;
				default:
					$image = null; // Suppress warning
			}
			assert($image);

			// Calculate ratio and size for thumbnail.
			$context = Application::get()->getRequest()->getContext();
			$coverThumbnailsMaxWidth = $context->getSetting('coverThumbnailsMaxWidth');
			$coverThumbnailsMaxHeight = $context->getSetting('coverThumbnailsMaxHeight');
			$thumbnailFilename = $volume->getId() . '-volume-thumbnail' . $this->_imageExtension;
			$xRatio = min(1, ($coverThumbnailsMaxWidth ? $coverThumbnailsMaxWidth : 100) / $this->_sizeArray[0]);
			$yRatio = min(1, ($coverThumbnailsMaxHeight ? $coverThumbnailsMaxHeight : 100) / $this->_sizeArray[1]);
			$ratio = min($xRatio, $yRatio);
			$thumbnailWidth = round($ratio * $this->_sizeArray[0]);
			$thumbnailHeight = round($ratio * $this->_sizeArray[1]);
			$thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
			imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $this->_sizeArray[0], $this->_sizeArray[1]);

			// Copy the new file over.
			$filename = $volume->getId() . '-volume' . $this->_imageExtension;
			$publicFileManager->copyFile($temporaryFile->getFilePath(), $basePath . $filename);

			switch ($this->_imageExtension) {
				case '.jpg':
					imagejpeg($thumbnail, $basePath . $thumbnailFilename);
					break;
				case '.png':
					imagepng($thumbnail, $basePath . $thumbnailFilename);
					break;
				case '.gif':
					imagegif($thumbnail, $basePath . $thumbnailFilename);
					break;
			}
			imagedestroy($thumbnail);
			imagedestroy($image);

			$volume->setImage(array(
				'name' => $filename,
				'width' => $this->_sizeArray[0],
				'height' => $this->_sizeArray[1],
				'thumbnailName' => $thumbnailFilename,
				'thumbnailWidth' => $thumbnailWidth,
				'thumbnailHeight' => $thumbnailHeight,
				'uploadName' => $temporaryFile->getOriginalFileName(),
				'dateUploaded' => Core::getCurrentDate(),
			));

			// Clean up the temporary file
			import('lib.pkp.classes.file.TemporaryFileManager');
			$temporaryFileManager = new TemporaryFileManager();
			$temporaryFileManager->deleteById($temporaryFileId, $this->userId);
		}

		// Update volume object to store image information.
		$volumeDao->updateObject($volume);
		parent::execute(...$functionArgs);
		return $volume;
	}
}
