<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Domain\Model\Advisory;
use TYPO3\IHS\Domain\Repository\IssueRepository;

class AdvisoryController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Repository\AdvisoryRepository
	 */
	protected $advisoryRepository;

	/**
	 * @Flow\Inject
	 * @var IssueRepository
	 */
	protected $issueRepository;

	/**
	 * A list of IANA media types which are supported by this controller
	 *
	 * @var array
	 */
	protected $supportedMediaTypes = array('application/json', 'text/html');

	/**
	 * @param string $search
	 * @return void
	 */
	public function indexAction($search = null) {
		if ($search) {
			$advisories = $this->getSearchResults($search);
		} else {
			$advisories = $this->advisoryRepository->findAll();
		}
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
		$this->view->assign('advisory', $advisory);
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
	 * @return void
	 */
	public function searchAction($searchRequest) {
		$advisories = $this->getSearchResults($searchRequest);

		$this->view->assign('advisories', $advisories);
	}

	/**
	 * @param string $searchRequest
	 * @return Object
	 */
	protected function getSearchResults($searchRequest) {
		$searchRequestAsArray = array();
		foreach(json_decode($searchRequest, true) as $key => $value) {
			$searchRequestAsArray[key($value)] = $value[key($value)];
		}

		if (count($searchRequestAsArray) > 0) {
			$advisories = $this->advisoryRepository->findBySearchRequest($searchRequestAsArray);
		} else {
			$advisories = $this->advisoryRepository->findAll();
		}

		return $advisories;
	}
}