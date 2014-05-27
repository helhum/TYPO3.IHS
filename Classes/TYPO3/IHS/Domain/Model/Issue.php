<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Issue {

	/**
	 * @var string
	 * @Flow\Identity
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
	 */
	protected $identifier;

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=512 })
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $abstract;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $vulnerabilityType;

	/**
	 * @var string
	 */
	protected $reporter;

	/**
	 * @var Product
	 * @ORM\OneToOne
	 */
	protected $product;

	/**
	 * @var string
	 */
	protected $affectedVersions;

	/**
	 * @var string
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $CVE;

	/**
	 * @var string
	 */
	protected $CVSS;

	/**
	 * @var Link
	 * @ORM\ManyToMany
	 */
	protected $links;

	/**
	 * @param mixed $CVE
	 */
	public function setCVE($CVE) {
		$this->CVE = $CVE;
	}

	/**
	 * @return mixed
	 */
	public function getCVE() {
		return $this->CVE;
	}

	/**
	 * @param mixed $CVSS
	 */
	public function setCVSS($CVSS) {
		$this->CVSS = $CVSS;
	}

	/**
	 * @return mixed
	 */
	public function getCVSS() {
		return $this->CVSS;
	}

	/**
	 * @param mixed $abstract
	 */
	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	/**
	 * @return mixed
	 */
	public function getAbstract() {
		return $this->abstract;
	}

	/**
	 * @param mixed $affectedVersions
	 */
	public function setAffectedVersions($affectedVersions) {
		$this->affectedVersions = $affectedVersions;
	}

	/**
	 * @return mixed
	 */
	public function getAffectedVersions() {
		return $this->affectedVersions;
	}

	/**
	 * @param mixed $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return mixed
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $issueIdentifier
	 */
	public function setIssueIdentifier($issueIdentifier) {
		$this->issueIdentifier = $issueIdentifier;
	}

	/**
	 * @return string
	 */
	public function getIssueIdentifier() {
		return $this->issueIdentifier;
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Link $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return \TYPO3\IHS\Domain\Model\Link
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
	 * @param mixed $reporter
	 */
	public function setReporter($reporter) {
		$this->reporter = $reporter;
	}

	/**
	 * @return mixed
	 */
	public function getReporter() {
		return $this->reporter;
	}

	/**
	 * @param mixed $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return mixed
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param mixed $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return mixed
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param mixed $vulnerabilityType
	 */
	public function setVulnerabilityType($vulnerabilityType) {
		$this->vulnerabilityType = $vulnerabilityType;
	}

	/**
	 * @return mixed
	 */
	public function getVulnerabilityType() {
		return $this->vulnerabilityType;
	}



}