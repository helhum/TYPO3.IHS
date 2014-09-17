<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Domain\Model\Advisory;

class AdvisoryController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Repository\AdvisoryRepository
	 */
	protected $advisoryRepository;

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
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Advisory $newAdvisory
	 * @return void
	 */
	public function createAction(Advisory $newAdvisory) {
		$this->advisoryRepository->add($newAdvisory);
		$this->addFlashMessage('Created a new advisory.');
		$this->redirect('index');
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