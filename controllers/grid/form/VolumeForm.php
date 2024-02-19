<?php

/**
 * @file plugins/generic/volumesForm/controllers/grid/form/VolumeForm.php
 *
 * Form for adding/editing a volume.
 *
 */

namespace APP\plugins\generic\volumesForm\controllers\grid\form;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\volumesForm\classes\Volume;
use APP\plugins\generic\volumesForm\classes\VolumeDAO;
use APP\plugins\generic\volumesForm\VolumesFormPlugin;
use APP\section\Section;
use APP\template\TemplateManager;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\file\ContextFileManager;
use PKP\file\TemporaryFileDAO;
use PKP\file\TemporaryFileManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorLocale;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorRegExp;

class VolumeForm extends Form
{

	/** @var VolumesFormPlugin */
	private VolumesFormPlugin $plugin;

	/** @var int $contextId The context ID of the volume being edited */
	private int $contextId;

	/** @var int|null $volumeId The volume ID */
	private ?int $volumeId;

    /** @var int $userId */
    private int $userId;

    /** @var false|string $imageExtension */
    private false|string $imageExtension;

    /** @var false|array $sizeArray */
    private false|array $sizeArray;


	/**
	 * Constructor.
	 * @param int $contextId Context id.
	 * @param int|null $volumeId Volume id.
	 */
	public function __construct(VolumesFormPlugin $volumesFormPlugin, int $contextId, ?int $volumeId = null)
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
                /** @var VolumeDAO $volumeDao */
				$volumeDao = DAORegistry::getDAO('VolumeDAO');
				return !$volumeDao->volumeExistsByPath($path) || ($form->getData('oldPath') !== null && $form->getData('oldPath') === $path);
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
	 * @return int|null volumeId
	 */
	public function getVolumeId(): int|null
	{
		return $this->volumeId;
	}

    /**
     * Set the volume ID for this section.
     *
     * @param $volumeId int
     *
     * @return VolumeForm
     */
	public function setVolumeId(int $volumeId): self
	{
		$this->volumeId = $volumeId;
        return $this;
	}

	/**
	 * Get the context id.
	 * @return int contextId
	 */
	public function getContextId(): int
	{
		return $this->contextId;
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * Get all locale field names
	 */
	public function getLocaleFieldNames(): array
	{
        return DAORegistry::getDAO('VolumeDAO')
            ->getLocaleFieldNames();
	}

	/**
	 * @see Form::initData()
	 */
	public function initData(): void
	{
		// Get volume.
        /** @var VolumeDAO $volumeDao */
		$volumeDao = DAORegistry::getDAO('VolumeDAO');
        if ($this->getVolumeId()) {
            $volume = $volumeDao->getById($this->getVolumeId(), $this->getContextId());
        } else {
            $volume = null;
        }

		// Init data.
		if ($volume) {
            $this->setData( 'volume', $volume );
			$this->setData('title', $volume->getTitle(null));
			$this->setData('description', $volume->getDescription(null));
			$this->setData('path', $volume->getPath());
			$this->setData('image', $volume->getImage());
			$this->setData('ppn', $volume->getPPN());
            $this->setData('isbn13', $volume->getData('isbn13'));
            $this->setData('isbn10', $volume->getData('isbn10'));
            $this->setData('seriesId', $volume->getData('seriesId'));
            $this->setData('seriesPosition', $volume->getData('seriesPosition'));
            $this->setData('publisher', $volume->getData('publisher'));
            $this->setData('location', $volume->getData('location'));
            $this->setData('courseOfPublication', $volume->getData('courseOfPublication'));

			// Get sort options of the monographs.
			$sortOption = $volume->getSortOption()
                ?: $volumeDao->getDefaultSortOption();
			$this->setData('sortOption', $sortOption);
		}
	}

	/**
	 * @see Form::validate()
	 */
	public function validate($callHooks = true)
	{
		if ($temporaryFileId = $this->getData('temporaryFileId')) {
			$temporaryFileManager = new TemporaryFileManager();
            /* @var $temporaryFileDao TemporaryFileDAO */
			$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $this->userId);
			if (
				!$temporaryFile ||
				!($this->imageExtension = $temporaryFileManager->getImageExtension($temporaryFile->getFileType())) ||
				!($this->sizeArray = getimagesize($temporaryFile->getFilePath())) ||
				$this->sizeArray[0] <= 0 || $this->sizeArray[1] <= 0
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
	public function readInputData(): void
	{
		// Read user-entered values.
		$this->readUserVars(
            array(
                'title',
                'path',
                'description',
                'temporaryFileId',
                'ppn',
                'isbn13',
                'isbn10',
                'sortOption',
                'seriesId',
                'seriesPosition',
                'publisher',
                'location',
                'courseOfPublication'
            )
        );

		// For path duplicate checking; excuse the current path.
		if ($volumeId = $this->getVolumeId()) {
            /** @var VolumeDAO $volumeDao */
			$volumeDao = DAORegistry::getDAO('VolumeDAO');
			$volume = $volumeDao->getById($volumeId, $this->getContextId());
			$this->setData('oldPath', $volume->getPath());
		}
	}

	/**
	 * Display the form.
	 * @copydoc Form::fetch()
	 */
	public function fetch($request, $template = null, $display = false): null|string
	{
        /** @var VolumeDAO $volumeDao */
        $volumeDao = DAORegistry::getDAO('VolumeDAO');

        // Pass necessary vars to template.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('volumeId', $this->getVolumeId());
		$templateMgr->assign(
            'sortOptions',
            $volumeDao->getSortSelectOptions()
        );

        $context = Application::get()->getRequest()->getContext();

        $seriesOptions = Repo::section()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->map(fn (Section $series) => $series->getLocalizedTitle())
            ->toArray();
        $seriesOptions[0] = '';
        asort($seriesOptions);

        $templateMgr->assign(
            'seriesOptions',
            $seriesOptions
        );
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	public function execute(...$functionArgs): Volume
	{
		// Fetch volume ID and volume DAO.
		$volumeId = $this->getVolumeId();
		/** @var VolumeDAO $volumeDao */
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
		$volume->setPPN($this->getData('ppn'));
		$volume->setSortOption($this->getData('sortOption'));
        $volume->setPath($this->getData('path'));
        $volume->setData('isbn13', $this->getData('isbn13'));
        $volume->setData('isbn10', $this->getData('isbn10'));
        $volume->setData('seriesId', $this->getData('seriesId'));
        $volume->setData('seriesPosition', $this->getData('seriesPosition'));
        $volume->setData('publisher', $this->getData('publisher'));
        $volume->setData('location', $this->getData('location'));
        $volume->setData('courseOfPublication', $this->getData('courseOfPublication'));

		// Update or insert the volume object
		if ($volumeId) {
			$volumeDao->updateObject($volume);
		} else {
			$volumeDao->insertObject($volume);
		}

        // Handle the image upload if there was one.
        if ($temporaryFileId = $this->getData('temporaryFileId')) {
            // Fetch the temporary file storing the uploaded library file
            $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
            /** @var TemporaryFileDAO $temporaryFileDao */

            $temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $this->userId);
            $temporaryFilePath = $temporaryFile->getFilePath();
            $pressFileManager = new ContextFileManager($this->contextId);
            $basePath = Core::getBaseDir().'/'. $this->plugin->getPluginPath() . '/cover/';

            // Delete the old file if it exists
            $oldSetting = $volume->getImage();
            if ($oldSetting) {
                $pressFileManager->deleteByPath($basePath . $oldSetting['thumbnailName']);
                $pressFileManager->deleteByPath($basePath . $oldSetting['name']);
            }

            // The following variables were fetched in validation
            assert($this->sizeArray && $this->imageExtension);

            // Generate the surrogate image.
            $image = match ($this->imageExtension) {
                '.jpg' => imagecreatefromjpeg($temporaryFilePath),
                '.png' => imagecreatefrompng($temporaryFilePath),
                '.gif' => imagecreatefromgif($temporaryFilePath),
                default => null,
            };
            assert($image);

            $context = Application::get()->getRequest()->getContext();
            $coverThumbnailsMaxWidth = $context->getSetting('coverThumbnailsMaxWidth');
            $coverThumbnailsMaxHeight = $context->getSetting('coverThumbnailsMaxHeight');

            $thumbnailFilename = $volume->getId() . '-volume-thumbnail' . $this->imageExtension;
            $xRatio = min(1, $coverThumbnailsMaxWidth / $this->sizeArray[0]);
            $yRatio = min(1, $coverThumbnailsMaxHeight / $this->sizeArray[1]);

            $ratio = min($xRatio, $yRatio);

            $thumbnailWidth = round($ratio * $this->sizeArray[0]);
            $thumbnailHeight = round($ratio * $this->sizeArray[1]);
            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $this->sizeArray[0], $this->sizeArray[1]);

            // Copy the new file over
            $filename = $volume->getId() . '-volume' . $this->imageExtension;
            $pressFileManager->copyFile($temporaryFile->getFilePath(), $basePath . $filename);

            switch ($this->imageExtension) {
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

            $volume->setImage([
                                  'name' => $filename,
                                  'width' => $this->sizeArray[0],
                                  'height' => $this->sizeArray[1],
                                  'thumbnailName' => $thumbnailFilename,
                                  'thumbnailWidth' => $thumbnailWidth,
                                  'thumbnailHeight' => $thumbnailHeight,
                                  'uploadName' => $temporaryFile->getOriginalFileName(),
                                  'dateUploaded' => Core::getCurrentDate(),
                              ]);

            // Clean up the temporary file
            $temporaryFileManager = new TemporaryFileManager();
            $temporaryFileManager->deleteById($temporaryFileId, $this->userId);
        }

		// Update volume object to store image information.
		$volumeDao->updateObject($volume);
		parent::execute(...$functionArgs);
		return $volume;
	}
}
