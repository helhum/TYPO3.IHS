<?php
namespace TYPO3\IHS\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\IHS\Controller\Mapping\ArgumentMappingTrait;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Model\ProductVersion;
use TYPO3\IHS\Mvc\Controller\ActionController;

class ProductController extends ActionController {

	use ArgumentMappingTrait;

	protected $supportedFormats = array("html", "json");

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

	/**
	 * @var string
	 */
	protected $currentNodePath = '/sites/securitytypo3org/securitybulletins/products';

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
	 * @param string $productType
	 * @param boolean $withIssue
	 * @return string $result
	 */
	public function getProductsAsJSONAction($term, $productType = NULL, $withIssue = FALSE) {
		$products = $this->productRepository->findByTerm($term, $productType, $withIssue);
		$productsWithMatchingShortName = $this->productRepository->findByTermMatchingShortName($term, $productType, $withIssue);

		$result = array();
		$existingProducts = array();
		$i = 0;
		foreach($productsWithMatchingShortName as $product) {
			$identifier = $this->persistenceManager->getIdentifierByObject($product);
			array_push($result, array('id' => $identifier, 'label' => $product->getType() . '::' . $product->getNameAndShortName(), 'value' => $product->getShortName()));

			$existingProducts[$identifier] = true;
			$i++;
		}

		foreach($products as $product) {
			$identifier = $this->persistenceManager->getIdentifierByObject($product);

			if (!array_key_exists($identifier, $existingProducts)) {
				array_push($result, array('id' => $identifier, 'label' => $product->getType() . '::' . $product->getNameAndShortName(), 'value' => $product->getShortName()));
				$i++;
			}

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
	 * @return string $result
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
	 * @return string $types
	 */
	public function getProductTypesAsJSONAction() {
		$types = array();

		array_push($types, array('value' => ProductType::CMS_EXTENSION, 'label' => "EXT::CMS EXTENSION" ));
		array_push($types, array('value' => ProductType::TYPO3_PRODUCT, 'label' => "TYPO3::TYPO3 PRODUCT" ));
		array_push($types, array('value' => ProductType::FLOW_PACKAGE, 'label' => "PACK::FLOW PACKAGE" ));

		return json_encode($types);
	}

	/**
	 * Returns empty form field for adding new versions to a product
	 *
	 * @return void
	 */
	public function  newVersionAction() {

	}

	/**
	 * Adds versions to given product
	 *
	 * @param string $versions
	 * @param string $productIdentifier
	 * @return string $response
	 */
	public function  createVersionAction($versions, $productIdentifier) {
		$response = array();
		$createdVersions = array();

		/** @var $product Product */
		$product = $this->productRepository->findByIdentifier($productIdentifier);

		$i = 0;
		if ($product) {
			$versions = json_decode($versions);
			foreach($versions as $version) {
				$productVersion = new ProductVersion($version);
				if (!$product->hasVersion($productVersion)) {
					$product->addVersion($productVersion);
					$identifier = $this->persistenceManager->getIdentifierByObject($productVersion);
					$createdVersions[$i]['identifier'] = $identifier;
					$createdVersions[$i]['versionAsString'] = $version;

					$i++;
				}
			}

			$this->productRepository->update($product);
			$this->persistenceManager->persistAll();

			if ($i > 0) {
				$response['message'] = 'Version(s) has been created.';
			} else {
				$response['message'] = 'No new version has been created.';
			}
			$response['status'] = 'success';
			$response['createdVersions'] = $createdVersions;
		} else {
			$response['message'] = 'No product could be found.';
			$response['status'] = 'error';
		}

		return json_encode($response);
	}

	/**
	 * Removes version from given product
	 *
	 * @param string $versionIdentifier
	 * @param string $productIdentifier
	 *
	 * @return string $response
	 */
	public function deleteVersionAction($versionIdentifier, $productIdentifier) {
		$response = array();

		/** @var $product Product */
		$product = $this->productRepository->findByIdentifier($productIdentifier);
		$product->removeVersion($versionIdentifier);

		$this->productRepository->update($product);
		$this->persistenceManager->persistAll();

		$response['status'] = 'success';
		return json_encode($response);
	}
}