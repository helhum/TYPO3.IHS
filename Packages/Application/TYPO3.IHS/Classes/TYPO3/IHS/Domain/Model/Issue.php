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
class Issue {

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

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
	protected $abstract;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $description;

	/**
	 * @var VulnerabilityType
	 * @Flow\Validate(type="NotEmpty")
	 * @ORM\ManyToOne(cascade={"persist"})
	 */
	protected $vulnerabilityType;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $reporter;

	/**
	 * @var Product
	 * @Flow\Validate(type="NotEmpty")
	 * @ORM\ManyToOne(cascade={"persist"})
	 */
	protected $product;

	/**
	 * @var Collection<ProductVersion>
	 * @ORM\ManyToMany(cascade={"persist"})
	 * @ORM\OrderBy({"versionNumber" = "ASC"})
	 */
	protected $affectedVersions;

	/**
	 * @var string
	 */
	protected $state = '';

	/**
	 * @var string
	 */
	protected $CVE;

	/**
	 * @var string
	 */
	protected $CVSS;

	/**
	 * @var Advisory
	 * @ORM\ManyToOne(inversedBy="issues")
	 */
	protected $advisory;

	/**
	 * @var Collection<Solution>
	 * @ORM\OneToMany(cascade={"persist"},mappedBy="issue")
	 */
	protected $solutions;

	/**
	 * @var Collection<Link>
	 * @ORM\ManyToMany(cascade={"persist"})
	 */
	protected $links;

	public function __construct() {
		$this->creationDate = new \DateTime();
		$this->solutions = new ArrayCollection();
	}

	public function getSeverity() {
		// TODO: calculate severity from CVSS
		return 'Medium';
	}

	/**
	 * @param string $CVE
	 */
	public function setCVE($CVE) {
		$this->CVE = $CVE;
	}

	/**
	 * @return string
	 */
	public function getCVE() {
		return $this->CVE;
	}

	/**
	 * @param string $CVSS
	 */
	public function setCVSS($CVSS) {
		$this->CVSS = $CVSS;
	}

	/**
	 * @return string
	 */
	public function getCVSS() {
		return $this->CVSS;
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
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 */
	public function setAdvisory($advisory) {
		$this->advisory = $advisory;
	}

	/**
	 * @return \TYPO3\IHS\Domain\Model\Advisory
	 */
	public function getAdvisory() {
		return $this->advisory;
	}

	/**
	 * @param Collection<ProductVersion> $fixedInVersions
	 */
	public function setAffectedVersions($affectedVersions) {
		$this->affectedVersions = $affectedVersions;
	}

	/**
	 * @return Collection<ProductVersion>
	 */
	public function getAffectedVersions() {
		return $this->affectedVersions;
	}

	/**
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate($creationDate) {
		$this->creationDate = $creationDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDate() {
		return $this->creationDate;
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
	 * @param \TYPO3\IHS\Domain\Model\Product $product
	 */
	public function setProduct($product) {
		$this->product = $product;
	}

	/**
	 * @return \TYPO3\IHS\Domain\Model\Product
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * @param string $reporter
	 */
	public function setReporter($reporter) {
		$this->reporter = $reporter;
	}

	/**
	 * @return string
	 */
	public function getReporter() {
		return $this->reporter;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection<Solution> $solutions
	 */
	public function setSolutions($solutions) {
		/** @var Solution $solution */
		foreach ($solutions as $solution) {
			$solution->setIssue($this);
		}
		$this->solutions = $solutions;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection<Solution>
	 */
	public function getSolutions() {
		return $this->solutions;
	}

	/**
	 * @param Solution $solution
	 */
	public function addSolution(Solution $solution) {
		$this->solutions->add($solution);
	}

	/**
	 * @param string $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return string
	 */
	public function getState() {
		return $this->state;
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

	/**
	 * @param VulnerabilityType $vulnerabilityType
	 */
	public function setVulnerabilityType(VulnerabilityType $vulnerabilityType) {
		$this->vulnerabilityType = $vulnerabilityType;
	}

	/**
	 * @return VulnerabilityType
	 */
	public function getVulnerabilityType() {
		return $this->vulnerabilityType;
	}

}