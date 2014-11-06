<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\Flow\Reflection\ObjectAccess;

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
	 * @return Products
	 */
	public function findByTerm($term, $productType) {

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

		return $q->execute();
	}

	/**
	 * Finds products matching a given term and are connected with an issue
	 *
	 * @param string $term
	 * @param string $productType
	 * @return Products
	 */
	public function findByTermAndHasIssue($term, $productType) {

		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);

		$qb
			->join('e.issues', 'i')
			->groupBy('e')
			->having('COUNT(i) > 0')
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

		return $q->execute();
	}
}