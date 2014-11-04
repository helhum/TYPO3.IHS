<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;

/**
 * @Flow\Scope("singleton")
 */
class IssueRepository extends Repository {

	/**
	 *
	 *
	 * @param array $searchRequest
	 * @return Object
	 */
	public function findBySearchRequest($searchRequest) {
		$term = FALSE;
		$productType = FALSE;
		$hasSolution = NULL;
		$hasAdvisory = NULL;
		$product = FALSE;

		if (array_key_exists("text", $searchRequest)) {
			$term = $searchRequest["text"];
		}

		if (array_key_exists("product type", $searchRequest)) {
			$productType = $searchRequest["product type"];
		}

		if (array_key_exists("product", $searchRequest)) {
			$product = $searchRequest["product"];
		}

		if (array_key_exists("has solution", $searchRequest)) {
			if ($searchRequest["has solution"] == "yes") {
				$hasSolution = TRUE;
			} else {
				$hasSolution = FALSE;
			}
		}

		if (array_key_exists("has advisory", $searchRequest)) {
			if ($searchRequest["has advisory"] == "yes") {
				$hasAdvisory = TRUE;
			} elseif ($searchRequest["has advisory"] == "no") {
				$hasAdvisory = FALSE;
			}
		}

		// use doctrine query builder for more flexibility
		$qb = $this->createQueryBuilder('customIhsQuery');

		$qb
			->select('i')
			->from('TYPO3\IHS\Domain\Model\Issue', 'i');

		if ($term) {
			$qb
				->andWhere(
					$qb->expr()->orX(
						$qb->expr()->like('i.title', ':term'),
						$qb->expr()->like('i.abstract', ':term'),
						$qb->expr()->like('i.description', ':term')
					)
				)
				->setParameter('term', "%$term%");
		}

		if ($productType OR $product) {
			$qb->join('i.product', 'p');
		}

		if ($productType) {
			$qb
				->join('p.type', 't')
				->andWhere('t.value = :productType')
				->setParameter('productType', $productType);
		}

		if ($product) {
			$qb
				->andWhere('p.name = :productName')
				->setParameter('productName', $product);
		}

		if ($hasSolution === TRUE) {
			$qb
				->leftJoin('i.solutions', 's')
				->groupBy('i')
				->having('COUNT(s) > 0');
		} elseif ($hasSolution === FALSE) {
			$qb
				->leftJoin('i.solutions', 's')
				->groupBy('i')
				->having('COUNT(s) = 0');
		}

		if ($hasAdvisory === TRUE) {
			$qb->andWhere($qb->expr()->isNotNull('i.advisory'));
		} elseif ($hasAdvisory === FALSE) {
			$qb->andWhere($qb->expr()->isNull('i.advisory'));
		}

		$qb->orderBy('i.creationDate', 'DESC');


		return $qb->getQuery()->execute();
	}

	/**
	 *
	 *
	 * @return Object
	 */
	public function findAllOrdered() {
		$qb = $this->createQueryBuilder('customIhsQuery');

		$qb
			->select('i')
			->from('TYPO3\IHS\Domain\Model\Issue', 'i');

		$qb->andWhere($qb->expr()->isNull('i.advisory'));
		$qb->orderBy('i.creationDate', 'DESC');

		return $qb->getQuery()->execute();
	}
}