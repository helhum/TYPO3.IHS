<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\IHS\Domain\Factory\AdvisoryFactory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\IHS\Domain\Repository\ProductRepository;

class IssueController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var ProductRepository
	 */
	protected $productRepository;

	/**
	 * @Flow\Inject
	 * @var AdvisoryRepository
	 */
	protected $advisoryRepository;

	/**
	 * @Flow\Inject
	 * @var AdvisoryFactory
	 */
	protected $advisoryFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Repository\IssueRepository
	 */
	protected $issueRepository;

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('issues', $this->issueRepository->findAll());
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 * @return void
	 */
	public function showAction(Issue $issue) {
		$this->view->assign('issue', $issue);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 * @return void
	 */
	public function createAdvisoryAction(Issue $issue) {
		$advisory = $this->advisoryFactory->createFromIssue($issue);
		$this->advisoryRepository->add($advisory);
		$issue->setAdvisory($advisory);
		$this->issueRepository->update($issue);
		$this->redirect('index', 'advisory');
	}

	/**
	 * @return void
	 */
	public function newAction() {
		$products = $this->productRepository->findAll();
		$this->view->assign('products', $products);
	}

	protected function initializeCreateAction() {
		/** @var PropertyMappingConfiguration $mappingConfiguration */
		$mappingConfiguration = $this->arguments['newIssue']->getPropertyMappingConfiguration();
		$mappingConfiguration->forProperty('links.*')
			->allowAllProperties()
			->setTypeConverterOption(
				'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
				\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
				TRUE
			);
		$mappingConfiguration->forProperty('solutions.*')
			->allowAllProperties()
			->setTypeConverterOption(
				'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
				\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
				TRUE
			);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $newIssue
	 * @return void
	 */
	public function createAction(Issue $newIssue) {
		$this->issueRepository->add($newIssue);
		$this->addFlashMessage('Created a new issue.');
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 * @return void
	 */
	public function editAction(Issue $issue) {
		$products = $this->productRepository->findAll();
		$this->view->assign('products', $products);
		$this->view->assign('issue', $issue);
	}

	protected function initializeUpdateAction() {
		/** @var PropertyMappingConfiguration $mappingConfiguration */
		$mappingConfiguration = $this->arguments['issue']->getPropertyMappingConfiguration();
		$mappingConfiguration->forProperty('links.*')
			->allowAllProperties()
			->setTypeConverterOption(
				'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
				\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
				TRUE
			);
		$mappingConfiguration->forProperty('solutions.*')
			->allowAllProperties()
			->setTypeConverterOption(
				'TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter',
				\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
				TRUE
			);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 * @return void
	 */
	public function updateAction(Issue $issue) {
		$this->issueRepository->update($issue);
		$this->addFlashMessage('Updated the issue.');
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Issue $issue
	 * @return void
	 */
	public function deleteAction(Issue $issue) {
		$this->issueRepository->remove($issue);
		$this->addFlashMessage('Deleted a issue.');
		$this->redirect('index');
	}

}