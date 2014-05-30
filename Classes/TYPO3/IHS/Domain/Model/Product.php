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
	 * @ORM\ManyToMany(cascade={"persist"})
	 * @ORM\OrderBy({"versionNumber" = "ASC"})
	 */
	protected $versions;

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
}