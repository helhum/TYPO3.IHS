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

use TYPO3\Flow\Annotations as Flow;

/**
 * Creates typo3 with versions given from get.typo3.org
 *
 * @Flow\Scope("singleton")
 */
class ImportTypo3FromJSONService extends AbstractProductImporter {


	/**
	 * Creates typo3 with versions given from get.typo3.org
	 *
	 * @param string $urlToJSON
	 * @return void
	 */
	public function createOrUpdateTypo3($urlToJSON) {
		parent::createOrUpdateProductAbstract('TYPO3 CMS', 'CMS', 'TYPO3');

		$content = file_get_contents($urlToJSON);
		$versionsArray = json_decode($content, true);
		$versions = $this->getVersions($versionsArray);

		parent::parseVersionFromJSON($versions);
		parent::persistProduct();
	}

	/**
	 * Gets all Version-Keys
	 *
	 * @param array $versionsArray
	 * @return array
	 */
	protected function getVersions($versionsArray)
	{
		$versions = array();
		foreach($versionsArray as $majorRelease) {
			if (is_array($majorRelease) AND array_key_exists('releases', $majorRelease)) {
				$versions = array_merge($versions, $majorRelease['releases']);
			}
		}
		return array_keys($versions);
	}


}