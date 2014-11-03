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
				$query->equals('published', FALSE)
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
	public function findBySearchRequest($searchRequest, $published = TRUE) {
		$term = FALSE;
		$category = FALSE;
		$vulnerabilityType = FALSE;

		if (array_key_exists("text", $searchRequest)) {
			$term = $searchRequest["text"];
		}

		if (array_key_exists("category", $searchRequest)) {
			$category = $searchRequest["category"];
		}

		if (array_key_exists("vulnerability type", $searchRequest)) {
			$vulnerabilityType = $searchRequest["vulnerability type"];
		}

		$query = $this->createQuery();
		$constraints = array();

		if ($term) {
			$constraints[] =
				$query->logicalOr(
					$query->like('title', '%' . $term . '%'),
					$query->like('description', '%' . $term . '%'),
					$query->like('identifier', '%' . $term . '%'),
					$query->like('issues.abstract', '%' . $term . '%')
				);
		}

		if ($category) {
			$constraints[] =
				$query->equals('issues.product.type.value', $category);
		}

		if ($vulnerabilityType) {
			$constraints[] =
				$query->equals('issues.vulnerabilityType', $vulnerabilityType);
		}

		if ($published === TRUE) {
			$constraints[] =
				$query->equals('published', $published);
		}

		$query->setOrderings(array(
				'published' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
				'publishingDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING,
				'creationDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
			)
		);

		return $query->matching($query->logicalAnd($constraints))->execute();
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
				'publishingDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING,
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
				'published' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
				'publishingDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING,
				'creationDate' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
			)
		);

		return $query->execute();
	}

}