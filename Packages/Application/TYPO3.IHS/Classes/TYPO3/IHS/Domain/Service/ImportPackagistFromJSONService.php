<?php
namespace TYPO3\IHS\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductVersion;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\Domain\Service\Exception\NoValidProductTypeException;
use TYPO3\IHS\Log\ImportLoggerInterface;

/**
 * Create product with name, shortName and type with versions from packagist
 *
 * @Flow\Scope("singleton")
 */
class ImportPackagistFromJSONService {
	/**
	 * @var $product Product
	 */
	protected $product;

	/**
	 * @var $productVersions ProductVersion
	 */
	protected $productVersions;

	/**
	 * @Flow\Inject
	 * @var ProductRepository
	 */
	protected $productRepository;

	/**
	 * @Flow\Inject
	 * @var ImportLoggerInterface
	 */
	protected $importLogger;

	/**
	 * Creates new product with versions from packagistJSON
	 *
	 * @param string $name
	 * @param string $shortName
	 * @param string $type
	 * @param string $packagistUrl
	 * @return void
	 */
	public function createProduct($name, $shortName, $type, $packagistUrl) {
		/** @var $existingProduct Product */
		$existingProduct = $this->productRepository->findOneByShortName($shortName);

		if ($existingProduct) {
			$this->product = $existingProduct;
			$this->productVersions = $existingProduct->getVersions();
		} else {
			$this->product = new Product();
			$this->productVersions = new \Doctrine\Common\Collections\ArrayCollection();
		}

		$type = strtoupper($type);
		if ($type != ProductType::CMS_EXTENSION AND $type != ProductType::FLOW_PACKAGE AND $type != ProductType::TYPO3_PRODUCT) {
			throw new NoValidProductTypeException(sprintf('The type "%s" is not valid. Valid types are: EXT, TYPO3, PACK.', $type), 1413364625);
		}

		$productType = new ProductType($type);
		$this->product->setType($productType);
		$this->product->setName($name);
		$this->product->setShortName($shortName);

		$this->parseVersionFromJSON($packagistUrl);
		$this->persistProduct();
	}

	/**
	 * Gets and adds all versions from json file
	 *
	 * @param string $packagistUrl
	 * @return void
	 */
	protected function parseVersionFromJSON($packagistUrl) {
		$content = file_get_contents($packagistUrl);
		$json = json_decode($content, true);
		$versions = $this->getVersions($json['packages']);

		foreach ($versions as $version) {
			if($this->isValidVersion($version)) {
				$productVersion = new ProductVersion($version);
				if (!$this->product->hasVersion($productVersion)) {
					$this->productVersions->add($productVersion);
				}
			} else {
				$this->importLogger->log("Version not imported due to invalid format: " . $this->product->getShortName() . " Version: " . $version, LOG_DEBUG);
			}
		}
	}

	/**
	 * Gets all Version-Keys from packages array
	 *
	 * @param array $packages
	 * @return array
	 */
	protected function getVersions($packages)
	{
		$versions = array();
		foreach($packages as $version) {
			$versions = array_merge($versions, $version);
		}
		return array_keys($versions);
	}

	/**
	 * Removes version that are not stored
	 *
	 * @param string $versionString
	 * @return boolean
	 */
	protected function isValidVersion($versionString) {
		if (!is_numeric($versionString[0])) {
			return false;
		}

		if (strpos($versionString,'-') !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Creates or updates a product
	 *
	 * @return array
	 */
	protected function persistProduct() {
		$this->importLogger->log("Importing: " . $this->product->getShortName() . " Title: " . $this->product->getName(), LOG_DEBUG);
		$this->product->setVersions($this->productVersions);

		if ($this->product->getName()) {
			$this->productRepository->addOrUpdate($this->product);
		}
	}

}