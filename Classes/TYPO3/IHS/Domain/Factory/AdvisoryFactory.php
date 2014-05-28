<?php
namespace TYPO3\IHS\Domain\Factory;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
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
use TYPO3\IHS\Domain\Model\Advisory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\Flow\Annotations as Flow;

/**
 * Class AdvisoryFactory
 * @Flow\Scope("singleton")
 */
class AdvisoryFactory {

	/**
	 * @var AdvisoryRepository
	 * @Flow\inject
	 */
	protected $advisoryRepository;

	public function createFromIssue(Issue $issue) {
		$now = new \DateTime();

		$count = $this->advisoryRepository->countByProductAndYear($issue->getProduct(), $now->format('Y'));

		$advisory = new Advisory();
		$advisory->addIssue($issue);
		$advisory->setIdentifier($this->generateIdentifier($issue->getProduct(), $now->format('Y'), $count));
		$advisory->setTitle($advisory->getIdentifier());

		return $advisory;

	}

	protected function generateIdentifier(Product $product, $year, $count) {
		$count++;
		$identifierPattern = 'TYPO3-%s-SA-%s-%s';
		if ($product->getType()->equals(ProductType::TYPO3_PRODUCT)) {
			$productPart = str_replace('TYPO3 ', '', $product->getName());
		} elseif ($product->getType()->equals(ProductType::CMS_EXTENSION)) {
			$productPart = 'EXT';
		} elseif ($product->getType()->equals(ProductType::FLOW_PACKAGE)) {
			$productPart = 'PACK';
		} else {
			$productPart = 'PSA';
		}

		return sprintf(
			$identifierPattern,
			$productPart,
			$year,
			str_pad((string)$count, 3, '0', STR_PAD_LEFT)
		);
	}
} 