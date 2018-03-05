<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;

class StandardController extends \Neos\Flow\Mvc\Controller\ActionController {

	/**
	 * @return void
	 */
	public function redirectAction() {
		$this->redirect('index', 'Advisory');
	}

}