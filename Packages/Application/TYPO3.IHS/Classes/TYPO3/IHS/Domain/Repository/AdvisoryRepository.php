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
class AdvisoryRepository extends Repository {

	/**
	 * @param Product $product
	 * @param string $year
	 * @return int
	 */
	public function countByProductAndYear(Product $product, $year) {
		$query = $this->createQuery();
		$beginning = \DateTime::createFromFormat('d.m.Y H:i', '1.1.' . $year . ' 00:01');
		$end = \DateTime::createFromFormat('d.m.Y H:i', '31.12.' . $year . ' 23:59');
		$constraints = array();

		$constraints[] =
			$query->logicalAnd(
				$query->greaterThanOrEqual('creationDate', $beginning),
				$query->lessThanOrEqual('creationDate', $end)
			);
		$constraints[] =
			$query->logicalOr(
				$query->equals('issues.product.type', $product->getType()),
				$query->like('identifier', '%'.$product->getType().'%')
			);

		return $query->matching($query->logicalAnd($constraints))->count();
	}

	/**
	 *
	 *
	 * @param array $searchRequest
	 * @param boolean $published
	 * @return Object
	 */
	public function findBySearchRequest($searchRequest, $isAuthenticatedUser = FALSE) {
		$term = FALSE;
		$vulnerabilityType = FALSE;
		$productType = FALSE;
		$product = FALSE;
		$hasIssue = NULL;

		if (array_key_exists("text", $searchRequest)) {
			$term = $searchRequest["text"];
		}

		if (array_key_exists("vulnerability type", $searchRequest)) {
			$vulnerabilityType = $searchRequest["vulnerability type"];
		}

		if (array_key_exists("product type", $searchRequest)) {
			$productType = $searchRequest["product type"];
		}

		if (array_key_exists("product", $searchRequest)) {
			$product = $searchRequest["product"];
		}

		if (array_key_exists("has issue", $searchRequest)) {
			if ($searchRequest["has issue"] == "yes") {
				$hasIssue = TRUE;
			} elseif ($searchRequest["has issue"] == "no") {
				$hasIssue = FALSE;
			}
		}

		$query = $this->createQuery();
		// workaround: query should have a getQueryBuilder() method.
		$qb = ObjectAccess::getProperty($query, 'queryBuilder', TRUE);

		$qb->leftJoin('e.issues', 'i');

		if ($term) {
			$qb
				->andWhere(
					$qb->expr()->orX(
						$qb->expr()->like('e.title', ':term'),
						$qb->expr()->like('e.identifier', ':term'),
						$qb->expr()->like('e.description', ':term'),
						$qb->expr()->like('i.abstract', ':term')
					)
				)
				->setParameter('term', "%$term%");
		}

		if ($product OR $productType) {
			$qb->leftJoin('i.product', 'p');
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

		if ($vulnerabilityType) {
			$qb
				->join('i.vulnerabilityType', 't')
				->andWhere('t.value = :vulnerabilityType')
				->setParameter('vulnerabilityType', $vulnerabilityType);
		}

		if ($hasIssue === TRUE) {
			$qb
				->groupBy('e')
				->having('COUNT(i) > 0');
		} elseif ($hasIssue === FALSE) {
			$qb
				->groupBy('e')
				->having('COUNT(i) = 0');
		}

		if ($isAuthenticatedUser === FALSE) {
			$qb
				->andWhere('e.published = :published')
				->setParameter('published', TRUE);
		}

		$qb->addOrderBy('e.published', 'ASC');
		$qb->addOrderBy('e.publishingDate', 'DESC');
		$qb->addOrderBy('e.creationDate', 'DESC');
		return $query->execute();
	}

	/**
	 *
	 *
	 * @return Object
	 */
	public function findPublished() {
		$query = $this->createQuery();
		$query->matching(
			$query->equals('published', TRUE)
		);

		$query->setOrderings(array(
				'publishingDate' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING,
			)
		);

		return $query->execute();
	}

	/**
	 *
	 *
	 * @return Object
	 */
	public function findAllOrdered() {
		$query = $this->createQuery();
		$query->setOrderings(array(
				'published' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
				'publishingDate' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING,
				'creationDate' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
			)
		);

		return $query->execute();
	}

}