<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Media\Domain\Model\Asset;

/**
 * @Flow\ValueObject
 */
class Link {

	/**
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=128 })
	 * @var string
	 */
	protected $title;

	/**
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=512 })
	 * @ORM\Column(type="text")
	 * @var string
	 */
	protected $description;

	/**
	 * @ORM\Column(type="integer")
	 * @var integer
	 */
	protected $sortKey;

	/**
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=3, "maximum"=512 })
	 * @ORM\Column(nullable=true)
	 * @var string
	 */
	protected $uri;

	/**
	 * @ORM\Column(nullable=true)
	 * @ORM\ManyToOne
	 * @var Asset
	 */
	protected $asset;

	/**
	 * @param string $title
	 * @param string $description
	 * @param string $uri
	 * @param integer $sortKey
	 * @param Asset $asset
	 */
	public function __construct($uri, $title = '', $description = '', $sortKey = 0, Asset $asset = NULL) {
		$this->uri = $uri;
		$this->title = $title;
		$this->description = $description;
		$this->sortKey = $sortKey;
		$this->asset = $asset;
	}

	/**
	 * @return Asset
	 */
	public function getAsset() {
		return $this->asset;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return integer
	 */
	public function getSortKey() {
		return $this->sortKey;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
	}
}