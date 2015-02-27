<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\ValueObject
 */
class Link {

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=3, "maximum"=512 })
	 */
	protected $uri;

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=128 })
	 */
	protected $title;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=512 })
	 */
	protected $description;

	/**
	 * @var integer
	 * @ORM\Column(type="integer")
	 */
	protected $sortKey;


	/**
	 * @param string $title
	 * @param string $description
	 * @param string $uri
	 * @param integer $sortKey
	 */
	public function __construct($uri, $title = '', $description = '', $sortKey = 0) {
		$this->uri = $uri;
		$this->title = $title;
		$this->description = $description;
		$this->sortKey = $sortKey;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
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

	/**
	 * @return integer
	 */
	public function getSortKey() {
		return $this->sortKey;
	}
}