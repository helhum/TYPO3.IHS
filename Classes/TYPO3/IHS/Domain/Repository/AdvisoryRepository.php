<?php
namespace TYPO3\IHS\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class AdvisoryRepository extends Repository {

	// add customized methods here

	public function findByProductAndYear($product, $year) {
		$query = $this->createQuery();
		$beginning = \DateTime::createFromFormat('d.m.Y', '1.1.' . $year);
		$end = \DateTime::createFromFormat('d.m.Y', '31.12.' . $year);
		$constraints = array();

		$constraints[] = $query->equals('issues.product', $product);
		$constraints[] = $query->greaterThanOrEqual('publishDate', $beginning);
		$constraints[] = $query->lessThanOrEqual('publishDate', $end);

		return $query->matching($query->logicalAnd($constraints))->execute();
	}

	public function countByProductAndYear($product, $year) {
		$query = $this->createQuery();
		$beginning = \DateTime::createFromFormat('d.m.Y', '1.1.' . $year);
		$end = \DateTime::createFromFormat('d.m.Y', '31.12.' . $year);
		$constraints = array();

		$constraints[] = $query->equals('issues.product', $product);
		$constraints[] = $query->logicalOr(
			$query->logicalAnd(
				$query->greaterThanOrEqual('publishingDate', $beginning),
				$query->lessThanOrEqual('publishingDate', $end)
			),
			$query->equals('publishingDate', NULL)
		);

		return $query->matching($query->logicalAnd($constraints))->count();
	}

}