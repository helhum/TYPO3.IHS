<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Solution {

	/**
	 * @var Issue
	 * @ORM\ManyToOne(inversedBy="solutions")
	 */
	protected $issue;

	/**
	 * @var Collection<ProductVersion>
	 * @ORM\ManyToMany(cascade={"persist"})
	 * @ORM\OrderBy({"versionNumber" = "ASC"})
	 */
	protected $fixedInVersions;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $abstract;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $description;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $author;

	/**
	 * @var Collection<Link>
	 * @ORM\ManyToMany(cascade={"persist"})
	 */
	protected $links;

	/**
	 * @param string $abstract
	 */
	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	/**
	 * @return string
	 */
	public function getAbstract() {
		return $this->abstract;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param Issue $issue
	 */
	public function setIssue($issue) {
		$this->issue = $issue;
	}

	/**
	 * @return Issue
	 */
	public function getIssue() {
		return $this->issue;
	}

	/**
	 * @param mixed $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return mixed
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param Collection<ProductVersion> $fixedInVersions
	 */
	public function setFixedInVersions($fixedInVersions) {
		$this->fixedInVersions = $fixedInVersions;
	}

	/**
	 * @return Collection<ProductVersion>
	 */
	public function getFixedInVersions() {
		return $this->fixedInVersions;
	}

	/**
	 * @param string $author
	 */
	public function setAuthor($author) {
		$this->author = $author;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}
}