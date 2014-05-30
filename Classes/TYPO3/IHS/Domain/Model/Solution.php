<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Solution {

	/**
	 * @var Issue
	 * @ORM\ManyToOne(inversedBy="solutions")
	 */
	protected $issue;

	/**
	 * @var string
	 * @ORM\Column(nullable=true)
	 */
	protected $fixedInVersion;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var Collection<Link>
	 * @ORM\ManyToMany
	 */
	protected $links;

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $fixedInVersion
	 */
	public function setFixedInVersion($fixedInVersion) {
		$this->fixedInVersion = $fixedInVersion;
	}

	/**
	 * @return string
	 */
	public function getFixedInVersion() {
		return $this->fixedInVersion;
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 */
	public function setIssue($issue) {
		$this->issue = $issue;
	}

	/**
	 * @return \TYPO3\IHS\Domain\Model\Issue
	 */
	public function getIssue() {
		return $this->issue;
	}

	/**
	 * @param mixed $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return mixed
	 */
	public function getLinks() {
		return $this->links;
	}



}