<?php
namespace TYPO3\IHS\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Neos\Flow\Annotations as Flow;

/**
 * Create product with name, shortName and type with versions from packagist
 *
 * @Flow\Scope("singleton")
 */
class ImportPackagistFromJSONService extends AbstractProductImporter {
	/**
	 * Creates new product with versions from packagistJSON
	 *
	 * @param string $name
	 * @param string $shortName
	 * @param string $type
	 * @param string $packagistUrl
	 * @return void
	 */
	public function createOrUpdateProduct($name, $shortName, $type, $packagistUrl) {
		parent::createOrUpdateProductAbstract($name, $shortName, $type);

		$content = file_get_contents($packagistUrl);
		$versionsArray = json_decode($content, true);
		$versions = $this->getVersions($versionsArray['packages']);

		parent::parseVersionFromJSON($versions);
		parent::persistProduct();
	}
}