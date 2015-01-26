<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\IHS\Domain\Factory\AdvisoryFactory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Model\Link;
use TYPO3\IHS\Domain\Model\Solution;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\View\TypoScriptViewTrait;

class IssueController extends ActionController {

	use ArgumentMappingTrait;
	use TypoScriptViewTrait;

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
	 * @var string
	 */
	protected $currentNodePath = '/sites/securitytypo3org/securitybulletins/issues';

	/**
	 * @param string $search
	 * @return void
	 */
	public function indexAction($search = '[{"has advisory":"no"}]') {
		$issues = $this->getSearchResults($search);

		$quickFilters = array();

		$quickFilters[0]['name'] = 'Has No Advisory';
		$quickFilters[0]['filter'] = '[{"has advisory":"no"}]';
		$quickFilters[0]['active'] = FALSE;

		$quickFilters[1]['name'] = 'Has Advisory';
		$quickFilters[1]['filter'] = '[{"has advisory":"yes"}]';
		$quickFilters[1]['active'] = FALSE;

		$quickFilters[2]['name'] = 'Has Solution';
		$quickFilters[2]['filter'] = '[{"has solution":"yes"}]';
		$quickFilters[2]['active'] = FALSE;

		foreach ($quickFilters as $key => $quickFilter) {
			if ($quickFilter['filter'] == $search) {
				$quickFilters[$key]['active'] = TRUE;
			}
		}

		$this->view->assign('issues', $issues);
		$this->view->assign('quickFilters', $quickFilters);
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

		//check if solutions have versions that are currently not set in the parent issue
		$affectedVersions = $issue->getAffectedVersions();
		$solutions = $issue->getSolutions();

		$affectedVersionsTemp = array();
		foreach ($affectedVersions as $affectedVersion) {
			$affectedVersionsTemp[$affectedVersion->getVersionNumber()] = $affectedVersion->getVersionNumber();
		}

		foreach ($solutions as $solution) {
			$currentFixedInVersions = $solution->getFixedInVersions();
			$newFixedInVersions = array();
			foreach($currentFixedInVersions as $fixedInVersion) {
				if (isset($affectedVersionsTemp[$fixedInVersion->getVersionNumber()])) {
					array_push($newFixedInVersions, $fixedInVersion);
				}
			}

			$solution->setFixedInVersions($newFixedInVersions);
		}

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
	 * @param string $term
	 * @return string $types
	 */
	public function getVulnerabilityTypesAsJSONAction($term = NULL) {
		$vulnerabilityTypes = $this->issueRepository->findAllVulnerabilityTypes($term);

		$types = array();
		foreach($vulnerabilityTypes as $vulnerabilityType) {
			array_push($types, $vulnerabilityType->getValue());
		}

		return json_encode($types);
	}

	/**
	 * @param Issue $issue
	 * @param Solution $solution
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeSolutionAction(Issue $issue, Solution $solution) {
		$issue->getSolutions()->removeElement($solution);
		$this->issueRepository->update($issue);

		$this->redirectToUri($_SERVER['HTTP_REFERER']);
	}

	protected function initializeRemoveSolutionLinkAction() {
		$this->arguments['link']->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)->allowAllProperties();
	}

	/**
	 * @param Solution $solution
	 * @param Link $link
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeSolutionLinkAction(Solution $solution, Link $link) {
		$solution->removeLink($link);
		$issue = $solution->getIssue();
		$issue->getSolutions()->add($solution);
		$this->issueRepository->update($issue);

		$this->redirectToUri($_SERVER['HTTP_REFERER']);
	}

	protected function initializeRemoveLinkAction() {
		$this->arguments['link']->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)->allowAllProperties();
	}

	/**
	 * @param Issue $issue
	 * @param Link $link
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeLinkAction(Issue $issue, Link $link) {
		$issue->removeLink($link);
		$this->issueRepository->update($issue);

		$this->redirectToUri($_SERVER['HTTP_REFERER']);
	}

}