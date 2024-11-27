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
use Illuminate\Support\Facades\DB;
use PKP\core\DataObject;
use PKP\plugins\PluginRegistry;
use PKP\submission\Collector;
use PKP\submission\PKPSubmission;

class Volume extends DataObject
{
	private array $published_publications_sorted = [];

    private array $all_publications_sorted = [];

    private array $all_submissions = [];
    //
	// Get/set methods
	//

    /**
     * Get all parts as submission
     *
     * @return array
     */
    public function getSubmissions(): array
    {
        $parts = $this->all_submissions;
        if(empty($parts)){
            $parts = $this->loadParts();
            $this->all_submissions = $parts;
        }

        return $parts;
    }

    /**
     * Get all parts as sorted publications
     *
     * @param string|null $orderOption
     *
     * @return array
     */
    public function getParts(?string $orderOption=null): array
    {
        if ($orderOption) {
            $parts = $this->sortParts($orderOption);
            $this->all_publications_sorted = $parts;
        } else {
            $parts = $this->all_publications_sorted;
            if(empty($parts)){
                $parts = $this->sortParts($orderOption);
                $this->all_publications_sorted = $parts;
            }
        }

        return $parts;
    }

    /**
     * Get all published parts sorted as publications
     *
     * @param string|null $orderOption
     *
     * @return array
     */
    public function getPublishedParts(?string $orderOption=null): array
    {
        if ($orderOption) {
            $publishedParts = $this->loadPublishedParts($orderOption);
            $this->published_publications_sorted = $publishedParts;
        } else {
            $publishedParts = $this->published_publications_sorted;
            if(empty($publishedParts)){
                $publishedParts = $this->loadPublishedParts();
                $this->published_publications_sorted = $publishedParts;
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

    private function sortParts( ?string $orderOption=null ): array
    {
        $submissions = $this->getSubmissions();

        // Sort option.
        if (!$orderOption) {
            $orderOption = $this->getSortOption() ? $this->getSortOption() : Collector::ORDERBY_DATE_PUBLISHED . '-' . SORT_DIRECTION_DESC;
        }
        [$orderBy, $orderDir] = explode('-', $orderOption);

        // Get all published submissions which are part of a certain volume.
        $publishedPublications = [];
        $hdSortCataloguePlugin = PluginRegistry::getPlugin('generic', 'hdsortcatalogueplugin');

        if($orderBy === VolumeDAO::ORDERBY_VOLUME_POSITION) {
            $positions = [];
            $publications = [];
            foreach ($submissions as $submission) {
                /** @var Publication $publication */
                $publication = $submission->getCurrentPublication();
                $positions[$submission->getId()]  = $publication->getData('volumePosition');
                $publications[$submission->getId()] = $publication;
            }
            asort($positions);
            foreach ($positions as $key => $value) {
                $positions[$key] = $publications[$key];
            }
            $publishedPublications = $positions;

            if($orderDir === Collector::ORDER_DIR_DESC){
                $publishedPublications = array_reverse($publishedPublications);
            }
        } elseif ($orderBy === Collector::ORDERBY_DATE_PUBLISHED) {
            $positions = [];
            $publications = [];
            foreach ($submissions as $submission) {
                /** @var Publication $publication */
                $publication = $submission->getCurrentPublication();

                if ( $hdSortCataloguePlugin && $hdSortCataloguePlugin->getEnabled() ) {
                    $date = $publication->getData('hdSortCatalogue::sortDate') ?: $publication->getData('datePublished');
                } else {
                    $date = $publication->getData('datePublished');
                }

                $positions[$submission->getId()]  = $date;
                $publications[$submission->getId()] = $publication;
            }
            asort($positions);
            foreach ($positions as $key => $value) {
                $positions[$key] = $publications[$key];
            }
            $publishedPublications = $positions;

            if($orderDir === Collector::ORDER_DIR_DESC){
                $publishedPublications = array_reverse($publishedPublications);
            }
        } elseif ($orderBy === Collector::ORDERBY_TITLE) {
            $positions = [];
            $publications = [];
            foreach ($submissions as $submission) {
                /** @var Publication $publication */
                $publication = $submission->getCurrentPublication();
                $title = $publication->getLocalizedTitle();
                if ($title !== NULL) {
                    $positions[$submission->getId()]  = $title;
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

    private function getPublicationIds(): array
    {
        $volume_id = $this->getId();
        $publicationIds = [];
        $sql = "SELECT publication_id FROM publication_settings WHERE setting_name = 'volumeId' AND setting_value = ?";
        $params = [$this->getId()];
        $result = DB::select(DB::raw($sql)->getValue(), $params);
        foreach ($result as $object) {
            $publicationIds[] = $object->publication_id;
        }

        return $publicationIds;
    }


    private function loadParts(): array
    {
        $publicationIds = $this->getPublicationIds();
        $allPublications = [];
        foreach ($publicationIds as $publicationId) {
            $allPublications[] = Repo::publication()->get($publicationId);
        }

        $allSubmissions = [];
        foreach ($allPublications as $publication) {
            $submission = Repo::submission()->get($publication->getData('submissionId'));
            if ($submission->getData('contextId') === $this->getContextId()) {
                if (!array_key_exists($submission->getId(), $allSubmissions)) {
                    $allSubmissions[$submission->getId()] = $submission;
                }
            }
        }

        return $allSubmissions;
    }

    private function loadPublishedParts( ?string $orderOption=null ): array {
        $submissions = $this->sortParts($orderOption);
        /** @var Submission $submission */
        return array_filter($submissions, function($submission) { return $submission->getData('status') === PKPSubmission::STATUS_PUBLISHED; });
    }
}
