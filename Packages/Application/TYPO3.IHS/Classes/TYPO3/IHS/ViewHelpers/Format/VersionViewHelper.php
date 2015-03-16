<?php
namespace TYPO3\IHS\ViewHelpers\Format;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Tim Kandel <tim@kandel.io>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use Doctrine\Common\Collections\Collection;
use TYPO3\IHS\Domain\Model\ProductVersion;

/**
 * Class AccountViewHelper
 */
class VersionViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @param Collection<ProductVersion> $value
	 * @return string
	 */
	public function render(Collection $value = NULL) {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}

		/** @var ProductVersion $version */
		$mapper = function($version) { return $version->getHumanReadableVersionNumber(); };
		$versions = array_map($mapper, $value->toArray());

		$lastVersion = NULL;
		$results = array();
		$groupVersions = false;
		for ($i = 0; $i < count($versions); $i++) {
			$version  = explode('.', $versions[$i]);
			if ($i + 1 < count($versions)) {
				$nextVersion = explode('.', $versions[$i+1]);
				// if major and minor version number are identical, use an array to group the versions
				if ($version[0] == $nextVersion[0] && $version[1] == $nextVersion[1] && $version[2] + 1 == $nextVersion[2]) {
					if ($groupVersions) {
						$results[count($results) - 1][1] = $versions[$i + 1];
					} else {
						$results[] = array(0 => $versions[$i], 1 => $versions[$i + 1]);
						$groupVersions = true;
					}
				} else {
					if (!$groupVersions) {
						$results[] = $versions[$i];
					}
					$groupVersions = false;
				}
			}
		}

		$mapper = function($version) { if (is_array($version)) { return implode(' - ', $version); } else { return $version; } };
		$results = array_map($mapper, $results);
		return implode(', ', $results);
	}
}