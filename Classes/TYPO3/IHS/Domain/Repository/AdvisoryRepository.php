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
class AdvisoryRepository extends Repository {

	/**
	 * @param Product $product
	 * @param string $year
	 * @return int
	 */
	public function countByProductAndYear(Product $product, $year) {
		$query = $this->createQuery();
		$beginning = \DateTime::createFromFormat('d.m.Y H:i', '1.1.' . $year . ' 00:01');
		$end = \DateTime::createFromFormat('d.m.Y', '31.12.' . $year . ' 23:59');
		$constraints = array();

		$constraints[] = $query->equals('issues.product.type', $product->getType());
		$constraints[] =
			$query->logicalOr(
				$query->logicalAnd(
					$query->greaterThanOrEqual('publishingDate', $beginning),
					$query->lessThanOrEqual('publishingDate', $end)
				),
				$query->equals('publishingDate', NULL)
			);

		return $query->matching($query->logicalAnd($constraints))->count();
	}

}