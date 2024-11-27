<?php

/**
 * @file plugins/generic/volumesForm/classes/Volume.php
 *
 * Data object representing a Volume.
 */

namespace APP\plugins\generic\volumesForm\classes;


use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\core\DataObject;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\submission\Collector;
use PKP\submission\PKPSubmission;

class Volume extends DataObject
{
	private array $published_parts = [];

    private array $parts = [];
    //
	// Get/set methods
	//

    /**
     * Get all parts as submission
     *
     * @return array
     */
    public function getParts(): array
    {
        $parts = $this->parts;
        if(empty($parts)){
            $parts = $this->loadParts();
            $this->parts = $parts;
        }

        return $parts;
    }

    /**
     * Get all published parts as publications
     *
     * @param string|null $orderOption
     *
     * @return array
     */
    public function getPublishedParts(?string $orderOption=null): array
    {
        if ($orderOption) {
            $publishedParts = $this->loadPublishedParts($orderOption);
            $this->published_parts = $publishedParts;
        } else {
            $publishedParts = $this->published_parts;
            if(empty($publishedParts)){
                $publishedParts = $this->loadPublishedParts();
                $this->published_parts = $publishedParts;
            }
        }

        return $publishedParts;
    }


    /**
     * Get context ID.
     *
     * @return int|null
     */
	public function getContextId(): ?int
	{
		return $this->getData('contextId');
	}

    /**
     * Set context ID.
     *
     * @param $contextId int
     *
     * @return Volume
     */
	public function setContextId(int $contextId): self
	{
		$this->setData('contextId', $contextId);
        return $this;
	}

    /**
     * Get name.
     *
     * @return string|null
     */
	public function getPPN(): ?string
	{
		return $this->getData('ppn');
	}

    /**
     * Set name.
     *
     * @param string $ppn
     *
     * @return Volume
     */
	public function setPPN(string $ppn): self
	{
		$this->setData('ppn', $ppn);
        return $this;
	}


    /**
     * Get localized title of the volume.
     *
     * @return string|null
     */
	public function getLocalizedTitle(): ?string
	{
		return $this->getLocalizedData('title');
	}

    /**
     * Get localized description of the volume.
     *
     * @return string|null
     */
	public function getLocalizedDescription(): ?string
	{
		return $this->getLocalizedData('description');
	}

    /**
     * Get title of volume.
     *
     * @param string|null $locale string
     *
     * @return string|array|null
     */
	public function getTitle(?string $locale): string|array|null
	{
		return $this->getData('title', $locale);
	}

    /**
     * Return the combined title and prefix for all locales
     *
     * @param  string $format Define the return data format as text or html
     *
     * @return array
     */
    public function getTitles(string $format = 'text'): array
    {
        $allTitles = $this->getData('title');
        $return = [];
        foreach ($allTitles as $locale => $title) {
            if (!$title) {
                continue;
            }
            $return[$locale] = $this->getTitle($locale);
        }
        return $return;
    }

    /**
     * Set title of volume.
     *
     * @param string|array $title  string
     * @param string|null  $locale string
     *
     * @return Volume
     */
	public function setTitle(string|array $title, ?string $locale): self
	{
		$this->setData('title', $title, $locale);
        return $this;
	}

    /**
     * Get description of volume.
     *
     * @param string|null $locale
     *
     * @return string|array|null
     */
	public function getDescription(?string $locale): string|array|null
	{
		return $this->getData('description', $locale);
	}

    /**
     * Set description of volume.
     *
     * @param string|array $description string
     * @param string|null  $locale      string
     *
     * @return Volume
     */
	public function setDescription(string|array $description, ?string $locale): self
	{
		$this->setData('description', $description, $locale);
        return $this;
	}

    /**
     * Get path to volume (in URL).
     *
     * @return string|null
     */
	public function getPath(): ?string
	{
		return $this->getData('path');
	}

    /**
     * Set path to volume (in URL).
     *
     * @param $path string
     *
     * @return Volume
     */
	public function setPath(string $path): self
	{
		$this->setData('path', $path);
        return $this;
	}

    /**
     * Get the option how the books in this volume should be sorted,
     * in the form: concat(sortBy, sortDir).
     *
     * @return string|null
     */
	public function getSortOption(): ?string
	{
		return $this->getData('sortOption');
	}

    /**
     * Set the option how the books in this volume should be sorted,
     * in the form: concat(sortBy, sortDir).
     *
     * @param $sortOption string
     *
     * @return Volume
     */
	public function setSortOption(string $sortOption): self
	{
		$this->setData('sortOption', $sortOption);
        return $this;
	}

    /**
     * Get the image.
     *
     * @return array
     */
	public function getImage(): array
	{
        $image =  $this->getData('image');
		return $image ?: [];
	}

    /**
     * Set the image.
     *
     * @param $image array
     *
     * @return Volume
     */
	public function setImage(array $image): self
	{
		$this->setData('image', $image);
        return $this;
	}

    /**
     * @return int|null
     */
    public function getPressId(): ?int
	{
		return $this->getContextId();
	}

    /**
     * Set ID of press.
     *
     * @param $pressId int
     *
     * @return Volume
     */
	public function setPressId(int $pressId): self
	{
		$this->setContextId($pressId);
        return $this;
	}

    private function loadPublishedParts( ?string $orderOption=null ): array
    {
        // Sort option.
        if (!$orderOption) {
            $orderOption = $this->getSortOption() ? $this->getSortOption() : Collector::ORDERBY_DATE_PUBLISHED . '-' . SORT_DIRECTION_DESC;
        }
        [$orderBy, $orderDir] = explode('-', $orderOption);

        // Get all published submissions which are part of a certain volume.
        $publishedPublications = [];
        $hdSortCataloguePlugin = PluginRegistry::getPlugin('generic', 'hdsortcatalogueplugin');

        if($orderBy === VolumeDAO::ORDERBY_VOLUME_POSITION) {
            $submissions = Repo::submission()
                ->getCollector()
                ->filterByContextIds([$this->getContextId()])
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->getMany();
            $positions = [];
            $publications = [];
            foreach ($submissions as $submission) {
                $publication = $submission->getCurrentPublication();
                if ((string) $publication->getData('volumeId') === (string) $this->getId()) {
                    $positions[$submission->getId()]  = $publication->getData('volumePosition');
                    $publications[$submission->getId()] = $publication;
                }
            }
            asort($positions);
            foreach ($positions as $key => $value) {
                $positions[$key] = $publications[$key];
            }
            $publishedPublications = $positions;

            if($orderDir === Collector::ORDER_DIR_DESC){
                $publishedPublications = array_reverse($publishedPublications);
            }
        } elseif ($hdSortCataloguePlugin && $hdSortCataloguePlugin->getEnabled() && $orderBy === Collector::ORDERBY_DATE_PUBLISHED) {
            $submissions = Repo::submission()
                ->getCollector()
                ->filterByContextIds([$this->getContextId()])
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->getMany();
            $positions = [];
            $publications = [];
            foreach ($submissions as $submission) {
                /** @var Publication $publication */
                $publication = $submission->getCurrentPublication();
                if ((string) $publication->getData('volumeId') === (string) $this->getId()) {
                    $date = $publication->getData('hdSortCatalogue::sortDate') ?: $publication->getData('datePublished');
                    $positions[$submission->getId()]  = $date;
                    $publications[$submission->getId()] = $publication;
                }
            }
            asort($positions);
            foreach ($positions as $key => $value) {
                $positions[$key] = $publications[$key];
            }
            $publishedPublications = $positions;

            if($orderDir === Collector::ORDER_DIR_DESC){
                $publishedPublications = array_reverse($publishedPublications);
            }
        } else {
            $submissions = Repo::submission()
                ->getCollector()
                ->filterByContextIds([$this->getContextId()])
                ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
                ->orderBy($orderBy, $orderDir)
                ->getMany();
            foreach ($submissions as $submission) {
                $publication = $submission->getCurrentPublication();
                if ((string) $publication->getData('volumeId') === (string) $this->getId()) {
                    $publishedPublications[] = $publication;
                }
            }
        }

        return $publishedPublications;
    }

    public function countPublishedParts(): int
    {
        return count($this->getPublishedParts());
    }

    public function hasPublishedParts(): bool
    {
        return $this->countPublishedParts() > 0;
    }

    private function loadParts(): array
    {
        $submissions = [];
        $allSubmissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$this->getContextId()])
            ->getMany();

        /** @var Submission $submission */
        foreach ($allSubmissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if ($publication && (string) $publication->getData('volumeId') === (string) $this->getId()) {
                $submissions[] = $submission;
            }
        }

        return $submissions;
    }
}
