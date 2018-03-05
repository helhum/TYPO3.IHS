<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\ValueObject
 */
class ProductVersion {

	/**
	 * A version like "3.12.1" is stored as "3012001" integer
	 *
	 * @var int
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $versionNumber;

	/**
	 * @param int|string $versionNumber
	 */
	public function __construct($versionNumber) {
		if (is_string($versionNumber)) {
			$versionNumber = self::getVersionNumberFromHumanReadableVersion($versionNumber);
		}
		$this->versionNumber = $versionNumber;
	}

	/**
	 * @return int
	 */
	public function getVersionNumber() {
		return $this->versionNumber;
	}

	/**
	 * @return string
	 */
	public function getHumanReadableVersionNumber() {
		$versionNumberString = str_pad($this->versionNumber, 9, '0', STR_PAD_LEFT);
		$parts = array(
			substr($versionNumberString, 0, 3),
			substr($versionNumberString, 3, 3),
			substr($versionNumberString, 6, 3)
		);
		return intval($parts[0]) . '.' . intval($parts[1]) . '.' . intval($parts[2]);
	}

	/**
	 * @param string $versionString
	 * @return int
	 */
	static public function getVersionNumberFromHumanReadableVersion($versionString) {
		$versionParts = explode('.', $versionString);
		return (int)(((int)$versionParts[0] . str_pad((int)(isset($versionParts[1]) ? $versionParts[1] : ''), 3, '0', STR_PAD_LEFT)) . str_pad((int)(isset($versionParts[2]) ? $versionParts[2] : ''), 3, '0', STR_PAD_LEFT));
	}
}