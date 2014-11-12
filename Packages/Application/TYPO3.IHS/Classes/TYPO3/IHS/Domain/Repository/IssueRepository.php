<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;

/**
 * @Flow\Scope("singleton")
 */
class IssueRepository extends Repository {

	/**
	 * @Flow\Inject
	 * @var PersistenceManager
	 */
	protected $persistenceManager;

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
		$vulnerabilityType = FALSE;

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
			} else {
				$hasAdvisory = FALSE;
			}
		}

		if (array_key_exists("vulnerability type", $searchRequest)) {
			$vulnerabilityType = $searchRequest["vulnerability type"];
		}

		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);

		if ($term) {
			$qb
				->andWhere(
					$qb->expr()->orX(
						$qb->expr()->like('e.title', ':term'),
						$qb->expr()->like('e.abstract', ':term'),
						$qb->expr()->like('e.description', ':term')
					)
				)
				->setParameter('term', "%$term%");
		}

		if ($productType OR $product) {
			$qb->join('e.product', 'p');
		}

		if ($productType) {
			$qb
				->join('p.type', 'pt')
				->andWhere('pt.value = :productType')
				->setParameter('productType', $productType);
		}

		if ($product) {
			$qb
				->andWhere('p.shortName = :product')
				->setParameter('product', $product);
		}

		if ($hasSolution === TRUE) {
			$qb
				->leftJoin('e.solutions', 's')
				->groupBy('e')
				->having('COUNT(s) > 0');
		} elseif ($hasSolution === FALSE) {
			$qb
				->leftJoin('e.solutions', 's')
				->groupBy('e')
				->having('COUNT(s) = 0');
		}

		if ($hasAdvisory === TRUE) {
			$qb->andWhere($qb->expr()->isNotNull('e.advisory'));
		} elseif ($hasAdvisory === FALSE) {
			$qb->andWhere($qb->expr()->isNull('e.advisory'));
		}

		if ($vulnerabilityType) {
			$qb
				->join('e.vulnerabilityType', 't')
				->andWhere('t.value = :vulnerabilityType')
				->setParameter('vulnerabilityType', $vulnerabilityType);
		}

		$qb->orderBy('e.creationDate', 'DESC');
		return $q->execute();
	}

	/**
	 *
	 *
	 * @return Object
	 */
	public function findAllOrdered() {
		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);

		//$qb->andWhere($qb->expr()->isNull('e.advisory'));
		$qb->orderBy('e.creationDate', 'DESC');

		return $q->execute();
	}

	/**
	 *
	 * @param string $term
	 * @return Object
	 */
	public function findAllVulnerabilityTypes($term) {
		$q = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		/** @var $qb \Doctrine\ORM\QueryBuilder **/
		$qb = ObjectAccess::getProperty($q, 'queryBuilder', TRUE);
		$qb
			->resetDQLParts()
			->select('t')
			->from('TYPO3\IHS\Domain\Model\VulnerabilityType', 't')
		;

		if ($term) {
			$qb
				->andWhere(
					$qb->expr()->like('t.value', ':term')
				)
				->setParameter('term', "%$term%");
		}

		$qb->orderBy('t.value', 'ASC');

		return $q->execute();
	}
}