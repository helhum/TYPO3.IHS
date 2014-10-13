<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\IHS\Domain\Model\Product;

/**
 * @Flow\Scope("singleton")
 */
class ProductRepository extends Repository {

	public function addOrUpdate(Product $product) {
		if ($this->persistenceManager->isNewObject($product)) {
			$this->add($product);
		} else {
			$this->update($product);
		}
	}
}