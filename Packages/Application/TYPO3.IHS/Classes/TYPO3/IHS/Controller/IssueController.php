<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\IHS\Domain\Factory\AdvisoryFactory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\IHS\Domain\Repository\ProductRepository;

class IssueController extends ActionController {

	use ArgumentMappingTrait;

	protected $supportedFormats = array("html", "json");

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
	 * @param string $search
	 * @return void
	 */
	public function indexAction($search = NULL) {
		$issues = $this->getSearchResults($search);
		$quickFilterHasSolution = '[{"has solution":"yes"}]';
		$quickFilterHasAdvisory = '[{"has advisory":"yes"}]';

		$this->view->assign('issues', $issues);
		$this->view->assign('quickFilterHasSolution', $quickFilterHasSolution);
		$this->view->assign('quickFilterHasAdvisory', $quickFilterHasAdvisory);
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
		$this->persistenceManager->persistAll();

		$this->redirect('edit', 'advisory', NULL, array('advisory' => $advisory));
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function newAction(\TYPO3\IHS\Domain\Model\Advisory $advisory = NULL) {
		if ($advisory) {
			$this->view->assign('advisory', $advisory);
		}

		$products = $this->productRepository->findAll();
		$this->view->assign('products', $products);
		$this->view->assign('solutionsAvailable', FALSE);
	}

	/**
	 * Initialize property mapping configuration
	 */
	protected function initializeCreateAction() {
		$this->allowMappingForArgumentAndCollectionProperty('newIssue', 'affectedVersions');
		$this->allowMappingForArgumentAndCollectionProperty('newIssue', 'links');
		$this->allowMappingForArgumentAndCollectionProperty('newIssue', 'solutions', 'fixedInVersions');
		$this->allowMappingForArgumentAndCollectionProperty('newIssue', 'solutions', 'links', TRUE);
		$this->allowCreationForArgumentAndProperty('newIssue', 'vulnerabilityType');
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
	 * @Flow\IgnoreValidation(argumentName="issue")
	 */
	public function editAction(Issue $issue) {
		$products = $this->productRepository->findAll();
		$this->view->assign('products', $products);
		$this->view->assign('issue', $issue);
		$this->view->assign('solutionsAvailable', TRUE);
	}

	/**
	 * Initialize property mapping configuration
	 */
	protected function initializeUpdateAction() {
		$this->allowMappingForArgumentAndCollectionProperty('issue', 'affectedVersions');
		$this->allowMappingForArgumentAndCollectionProperty('issue', 'links');
		$this->allowMappingForArgumentAndCollectionProperty('issue', 'solutions', 'fixedInVersions');
		$this->allowMappingForArgumentAndCollectionProperty('issue', 'solutions', 'links', TRUE);
		$this->allowCreationForArgumentAndProperty('issue', 'vulnerabilityType');
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
	 * @Flow\IgnoreValidation(argumentName="issue")
	 */
	public function deleteAction(Issue $issue) {
		$this->issueRepository->remove($issue);
		$this->addFlashMessage('Deleted a issue.');
		$this->redirect('index');
	}

	/**
	 * @param string $searchRequest
	 * @return Object
	 */
	protected function getSearchResults($searchRequest) {
		$searchRequestAsArray = array();
		if ($searchRequest) {
			foreach(json_decode($searchRequest, true) as $key => $value) {
				$searchRequestAsArray[key($value)] = $value[key($value)];
			}
		}

		$issues = $this->issueRepository->findBySearchRequest($searchRequestAsArray);

		return $issues;
	}

	/**
	 * returns all VulnerabilityTypes as json
	 *
	 * @param string $searchTerm
	 * @return json $types
	 */
	public function getVulnerabilityTypesAsJSONAction($searchTerm = NULL) {
		$vulnerabilityTypes = $this->issueRepository->findAllVulnerabilityTypes($searchTerm);

		$types = array();
		foreach($vulnerabilityTypes as $vulnerabilityType) {
			array_push($types, $vulnerabilityType->getValue());
		}

		return json_encode($types);

	}
}