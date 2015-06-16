<?php
namespace TYPO3\IHS\Tests\Unit\ViewHelpers\Format;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Nicole Cordes <typo3@cordes.co>
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

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\IHS\Domain\Model\ProductVersion;
use TYPO3\IHS\ViewHelpers\Format\VersionViewHelper;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for VersionViewHelper
 */
class VersionViewHelperTest extends UnitTestCase {

	/**
	 * @var VersionViewHelper
	 */
	protected $versionViewHelper;

	public function setUp() {
		$this->versionViewHelper = $this->getMock('TYPO3\\IHS\\ViewHelpers\\Format\\VersionViewHelper', array('dummy'));
	}

	/**
	 * @return array
	 */
	public function dataProvider() {
		return array(
			'One version' => array(
				array(
					'4.0.0',
				),
				'4.0.0',
			),
			'Multiple versions' => array(
				array(
					'4.0.0',
					'4.1.0',
					'4.2.0',
					'4.3.0',
				),
				'4.0.0, 4.1.0, 4.2.0, 4.3.0',
			),
			'Versions in row' => array(
				array(
					'4.0.0',
					'4.0.1',
					'4.0.2',
					'4.0.3',
				),
				'4.0.0 - 4.0.3',
			),
			'Versions in multiple-row' => array(
				array(
					'4.0.0',
					'4.0.1',
					'4.1.0',
					'4.2.0',
					'4.2.1',
					'4.2.2',
				),
				'4.0.0 - 4.0.1, 4.1.0, 4.2.0 - 4.2.2',
			),
			'Many different versions' => array(
				array(
					'4.0.0',
					'4.0.1',
					'4.0.2',
					'4.0.3',
					'4.0.5',
					'4.1.0',
					'4.1.3',
					'4.1.4',
					'4.1.5',
					'4.1.6',
					'4.1.7',
					'4.1.8',
					'4.1.9',
					'4.1.10',
					'4.2.5',
					'4.6.3',
					'5.5.7',
					'5.5.8',
					'6.0.0',
				),
				'4.0.0 - 4.0.3, 4.0.5, 4.1.0, 4.1.3 - 4.1.10, 4.2.5, 4.6.3, 5.5.7 - 5.5.8, 6.0.0',
			),
		);
	}

	/**
	 * @param array $versionArray
	 * @param string $expectedString
	 * @test
	 * @dataProvider dataProvider
	 */
	public function renderGeneratesExpectedVersionStrings($versionArray, $expectedString) {
		$versionCollection = $this->convertVersionArrayToCollection($versionArray);
		$this->assertEquals($expectedString, $this->versionViewHelper->render($versionCollection));
	}

	/**
	 * @param array $versionArray
	 * @return ArrayCollection
	 */
	protected function convertVersionArrayToCollection($versionArray) {
		$collectionArray = array();
		foreach ($versionArray as $version) {
			$collectionArray[] = new ProductVersion($version);
		}
		unset($version);

		return new ArrayCollection($collectionArray);
	}
}