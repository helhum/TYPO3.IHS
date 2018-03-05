<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use TYPO3\IHS\Domain\Model\Product;
use Neos\Utility\ObjectAccess;

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

	/**
	 * Finds products matching a given term
	 *
	 * @param string $term
	 * @param string $productType
	 * @param boolean $hasIssue
	 * @return Products
	 */
	public function findByTerm($term, $productType, $hasIssue = FALSE) {

		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);

		$qb
			->andWhere(
				$qb->expr()->orX(
					$qb->expr()->like('e.name', ':term'),
					$qb->expr()->like('e.shortName', ':term')
				)
			)
			->setParameter('term', "%$term%");

		if ($productType) {
			$qb
				->leftJoin('e.type', 't')
				->andWhere('t.value = :productType')
				->setParameter('productType', $productType);
		}

		if ($hasIssue) {
			$qb
				->join('e.issues', 'i')
				->groupBy('e')
				->having('COUNT(i) > 0');
		}

		return $q->execute();
	}

	/**
	 * Finds products by shortName matching a given term
	 *
	 * @param string $term
	 * @param string $productType
	 * @param boolean $hasIssue
	 * @return Products
	 */
	public function findByTermMatchingShortName($term, $productType, $hasIssue = FALSE) {
		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);

		$qb
			->andWhere('e.shortName = :term')
			->setParameter('term', $term);

		if ($productType) {
			$qb
				->leftJoin('e.type', 't')
				->andWhere('t.value = :productType')
				->setParameter('productType', $productType);
		}

		if ($hasIssue) {
			$qb
				->join('e.issues', 'i')
				->groupBy('e')
				->having('COUNT(i) > 0');
		}

		return $q->execute();
	}

}