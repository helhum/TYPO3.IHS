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
class Product {
	const TYPE_CMS_EXTENSION = 'EXT';
	const TYPE_TYPO3_PRODUCT = 'TYPO3';
	const TYPE_FLOW_PACKAGE = 'PACK';

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=128 })
	 */
	protected $name;

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=128 })
	 */
	protected $shortName;

	/**
	 * @var ProductType
	 * @ORM\ManyToOne(cascade={"persist"})
	 *
	 */
	protected $type;

	/**
	 * @var Collection<ProductVersion>
	 * @ORM\ManyToMany(cascade={"persist", "remove"})
	 * @ORM\OrderBy({"versionNumber" = "ASC"})
	 */
	protected $versions;

	/**
	 * @var Collection<Issue>
	 * @ORM\OneToMany(mappedBy="product")
	 */
	protected $issues;

	public function __construct() {
		$this->versions = new ArrayCollection();
		$this->issues = new ArrayCollection();
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getQualifiedName() {
		return $this->getType()->getValue() . ' :: ' . $this->getShortName();
	}

	/**
	 * @return string
	 */
	public function getNameAndShortName() {
		return $this->getName() . ' (' . $this->getShortName() . ')';
	}

	/**
	 * @param ProductType $type
	 */
	public function setType($type) {
		$this->type = new ProductType((string)$type);
	}

	/**
	 * @return ProductType
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param mixed $shortName
	 */
	public function setShortName($shortName) {
		$this->shortName = $shortName;
	}

	/**
	 * @return mixed
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * @param \Doctrine\Common\Collections\Collection $versions
	 */
	public function setVersions($versions) {
		$this->versions = $versions;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getVersions() {
		return $this->versions;
	}

	/**
	 * @param ProductVersion $productVersion
	 * @return boolean
	 */
	public function hasVersion(ProductVersion $productVersion) {
		foreach ($this->versions as $version) {
			/* @var $version ProductVersion */
			if ($version->getVersionNumber() == $productVersion->getVersionNumber()) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @param ProductVersion $productVersion
	 */
	public function addVersion(ProductVersion $productVersion) {
		$this->versions->add($productVersion);
	}

	/**
	 * @param ProductVersion $productVersion
	 */
	public function removeVersion($productVersion) {
		$this->versions->removeElement($productVersion);
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getIssues() {
		return $this->issues;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->getType() . '::' . $this->getNameAndShortName();
	}
}