<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
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
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	protected $supportedFormats = array("html", "json");

	/**
	 * @return void
	 */
	public function indexAction() {
		//get products counted by type
		$products = $this->productRepository->findAll();

		$productsByType = array();
		foreach($products as $product) {
			/** @var $product Product */
			$type = $product->getType()->getValue();
			if (!array_key_exists($type, $productsByType)) {
				$productsByType[$type] = 1;
			} else {
				$productsByType[$type]++;
			}
		}
		$this->view->assign('productsByType', $productsByType);
	}

	/**
	 * returns all products as json matching a given termn
	 *
	 * @param string $term
	 * @param boolean $withIssue
	 * @param string $productType
	 * @return json $result
	 */
	public function getProductsAsJSONAction($term, $withIssue = FALSE, $productType = NULL) {
		if ($withIssue) {
			$products = $this->productRepository->findByTermAndHasIssue($term, $productType);
		} else {
			$products = $this->productRepository->findByTerm($term, $productType);
		}


		$result = array();
		$i = 0;
		foreach($products as $product) {
			$identifier = $this->persistenceManager->getIdentifierByObject($product);
			array_push($result, array('id' => $identifier, 'label' => $product->getType() . '::' . $product->getName() . ' ('. $product->getShortName() . ')', 'value' => $product->getName()));
			$i++;
			if ($i == 10) {
				break;
			}
		}

		return json_encode($result);
	}

	/**
	 * returns all versions to a product as json
	 *
	 * @param string $identifier
	 * @return json $result
	 */
	public function getProductVersionsAsJSONAction($identifier) {
		$product = $this->productRepository->findByIdentifier($identifier);
		$productVersions = $product->getVersions();
		$result = array();
		foreach($productVersions as $version) {
			$identifier = $this->persistenceManager->getIdentifierByObject($version);
			array_push($result, array('id' => $identifier, 'label' => $version->getHumanReadableVersionNumber(), 'value' => $version->getHumanReadableVersionNumber()));
		}

		return json_encode($result);
	}

	/**
	 * returns all types as json
	 *
	 * @return json $result
	 */
	public function getProductTypesAsJSONAction() {
		$types = array();

		array_push($types, array('value' => ProductType::CMS_EXTENSION, 'label' => "EXT::CMS EXTENSION" ));
		array_push($types, array('value' => ProductType::TYPO3_PRODUCT, 'label' => "TYPO3::TYPO3 PRODUCT" ));
		array_push($types, array('value' => ProductType::FLOW_PACKAGE, 'label' => "PACK::FLOW PACKAGE" ));

		return json_encode($types);
	}

}