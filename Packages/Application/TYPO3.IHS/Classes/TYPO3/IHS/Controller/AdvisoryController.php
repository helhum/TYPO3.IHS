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
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('advisories', $this->advisoryRepository->findAll());
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
	 * @param \TYPO3\IHS\Domain\Model\Advisory $advisory
	 * @return void
	 */
	public function deleteAction(Advisory $advisory) {
		$this->advisoryRepository->remove($advisory);
		$this->addFlashMessage('Deleted a advisory.');
		$this->redirect('index');
	}

}