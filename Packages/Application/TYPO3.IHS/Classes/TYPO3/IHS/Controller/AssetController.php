<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\Asset;

class AssetController extends ActionController {

	protected $defaultViewObjectName = 'TYPO3\\Flow\\Mvc\\View\\JsonView';

	/**
	 * @Flow\Inject
	 * @var \Neos\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @var \Neos\Flow\Mvc\View\JsonView
	 */
	protected $view;

	/**
	 * @param string $term
	 * @return void
	 */
	public function getAssetsAsJsonAction($term) {
		$array = array();
		$assets = $this->assetRepository->findBySearchTermOrTags($term);
		/** @var Asset $asset */
		foreach ($assets as $asset) {
			$array[] = array(
				'id' => $asset->getIdentifier(),
				'label' => $asset->getLabel(),
			);
		}
		$this->view->setVariablesToRender(array('assets'));
		$this->view->assign('assets', $array);
	}

}