<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Domain\Model\Product;

class ProductController extends ActionController {

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

	/**
	 * @param \TYPO3\IHS\Domain\Model\Product $product
	 * @return void
	 */
	public function showAction(Product $product) {
		$this->view->assign('product', $product);
	}

	/**
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Product $newProduct
	 * @return void
	 */
	public function createAction(Product $newProduct) {
		$this->productRepository->add($newProduct);
		$this->addFlashMessage('Created a new product.');
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Product $product
	 * @return void
	 */
	public function editAction(Product $product) {
		$this->view->assign('product', $product);
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Product $product
	 * @return void
	 */
	public function updateAction(Product $product) {
		$this->productRepository->update($product);
		$this->addFlashMessage('Updated the product.');
		$this->redirect('index');
	}

	/**
	 * @param \TYPO3\IHS\Domain\Model\Product $product
	 * @return void
	 */
	public function deleteAction(Product $product) {
		$this->productRepository->remove($product);
		$this->addFlashMessage('Deleted a product.');
		$this->redirect('index');
	}

}