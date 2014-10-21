<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Advisory {

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var string
	 * @Flow\Identity
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="NONE")
	 * @ORM\Column(unique=true)
	 */
	protected $identifier;

	/**
	 * @var Collection<Issue>
	 * @ORM\OneToMany(mappedBy="advisory")
	 */
	protected $issues;

	/**
	 * @var \DateTime
	 * @ORM\Column(nullable=true)
	 * @Flow\Validate(type="DateTime")
	 */
	protected $publishingDate;

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=512 })
	 */
	protected $title;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $abstract = '';

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $description = '';

	/**
	 * @var Collection<Link>
	 * @ORM\ManyToMany(cascade={"persist"})
	 */
	protected $links;


	public function __construct() {
		$this->issues = new ArrayCollection();
		$this->creationDate = new \DateTime();
	}

	/**
	 * Calculates the severity based on CVSS Scores of related issues
	 *
	 * @return string
	 */
	public function getOverallSeverity() {
		foreach ($this->issues as $issue) {
			// TODO: Do calculation
		}
		return 'Medium';
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDate() {
		return $this->creationDate;
	}

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
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $issues
	 */
	public function setIssues($issues) {
		$this->issues = $issues;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getIssues() {
		return $this->issues;
	}

	/**
	 * @param Issue $issue
	 */
	public function addIssue(Issue $issue) {
		$this->issues->add($issue);
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param \DateTime $publishingDate
	 */
	public function setPublishingDate($publishingDate) {
		$this->publishingDate = $publishingDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getPublishingDate() {
		return $this->publishingDate;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
}