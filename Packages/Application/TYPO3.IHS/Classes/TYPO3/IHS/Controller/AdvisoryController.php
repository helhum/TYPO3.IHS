<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Security\Context;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\IHS\Domain\Model\Advisory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\IHS\Domain\Repository\IssueRepository;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\View\TypoScriptViewTrait;

class AdvisoryController extends ActionController {

	use ArgumentMappingTrait;
	use TypoScriptViewTrait;

	/**
	 * @Flow\Inject
	 * @var AdvisoryRepository
	 */
	protected $advisoryRepository;

	/**
	 * @Flow\Inject
	 * @var IssueRepository
	 */
	protected $issueRepository;

	/**
	 * @Flow\Inject
	 * @var ProductRepository
	 */
	protected $productRepository;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * A list of IANA media types which are supported by this controller
	 *
	 * @var array
	 */
	protected $supportedMediaTypes = array('text/html', 'text/xml');


	/**
	 * @var string
	 */
	protected $currentNodePath = '/sites/securitytypo3org/securitybulletins';

	/**
	 * @param string $search
	 * @return void
	 */
	public function indexAction($search = NULL) {
		$advisories = $this->getSearchResults($search);

		$this->view->assign('advisories', $advisories);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function showAction(Advisory $advisory) {
		$this->view->assign('advisory', $advisory);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function editAction(Advisory $advisory) {
		$products = $this->productRepository->findAll();

		$this->view->assign('advisory', $advisory);
		$this->view->assign('products', $products);
	}

	protected function initializeUpdateAction() {
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'affectedVersions');
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'vulnerabilityType', NULL, TRUE);
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'links', NULL, TRUE);
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'solutions', NULL, TRUE);
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'solutions', 'fixedInVersions');
		$this->allowMappingForArgumentAndCollectionProperty('advisory', 'issues', 'solutions', 'links', TRUE);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function updateAction(Advisory $advisory) {
		$this->advisoryRepository->update($advisory);
		$this->addFlashMessage('Updated the advisory.');
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

		if ($this->securityContext->hasRole('AuthenticatedUser')) {
			if (count($searchRequestAsArray) > 0) {
				$advisories = $this->advisoryRepository->findBySearchRequest($searchRequestAsArray, FALSE);
			} else {
				$advisories = $this->advisoryRepository->findAllOrdered();
			}
		} else {
			if (count($searchRequestAsArray) > 0) {
				$advisories = $this->advisoryRepository->findBySearchRequest($searchRequestAsArray, TRUE);
			} else {
				$advisories = $this->advisoryRepository->findPublished();
			}
		}

		return $advisories;
	}

	/**
	 * @return void
	 */
	public function rssFeedAction() {
		$currentDate = new \DateTime();
		$this->view->assign('currentDate', $currentDate);
		$advisories = $this->advisoryRepository->findPublished();
		$this->view->assign('advisories', $advisories);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function publishAction(Advisory $advisory) {
		$advisory->setPublished(TRUE);
		$advisory->setPublishingDate(new \DateTime());
		$this->advisoryRepository->update($advisory);
		$this->persistenceManager->persistAll();

		$this->addFlashMessage('Published Advisory. You can now change the publishingdate if you want.');

		$this->redirect('show', 'advisory', NULL, array('advisory' => $advisory));
	}

	/**
	 * @param Advisory $advisory
	 * @param Issue $issue
	 * @Flow\IgnoreValidation("$issue")
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeIssueAction(Advisory $advisory, Issue $issue) {
		$issue->setAdvisory(NULL);
		$this->issueRepository->update($issue);

		$this->addFlashMessage('Removed the issue.');

		$this->redirect('edit', 'advisory', NULL, array('advisory' => $advisory));
	}
}