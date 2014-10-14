<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;

class ProductController extends ActionController {

	use ArgumentMappingTrait;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Repository\ProductRepository
	 */
	protected $productRepository;

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('products', $this->productRepository->findAll());
	}

}