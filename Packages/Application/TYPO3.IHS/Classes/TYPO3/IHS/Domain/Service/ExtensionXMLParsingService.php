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

use Neos\Flow\Annotations as Flow;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductVersion;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\Domain\Service\Exception\FileNotFoundException;
use TYPO3\IHS\Log\ImportLoggerInterface;

/**
 * Extension XML Parser
 *
 * @Flow\Scope("singleton")
 */
class ExtensionXMLParsingService {
	protected $xmlFile;

	/**
	 * @var $product Product
	 */
	protected $product;

	/**
	 * @var $productVersions ProductVersion
	 */
	protected $productVersions;

	/**
	 * @var \XMLReader
	 */
	protected $xmlReader;

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
	 * @var integer
	 */
	protected $numberOfImportedProducts;

	public function __construct() {
		$this->requiredPhpExtensions = 'xmlreader';
	}

	/**
	 * sets the current xml-file to parse
	 *
	 * @return void
	 */
	public function setXmlFile($file) {
		$this->xmlFile = $file;
	}

	/**
	 * creates new instance of php XMLReader
	 *
	 * @return void
	 */
	public function createXmlReader() {
		$this->xmlReader = new \XMLReader();
	}

	/**
	 * parsing of the xml file
	 *
	 * @return void
	 */
	public function parseXML() {
		if (!file_exists($this->xmlFile)) {
			throw new FileNotFoundException(sprintf('The file "%s" was not found.', $this->xmlFile), 1413194297);
		}

		$this->numberOfImportedProducts = 0;
		$this->xmlReader->open($this->xmlFile, 'utf-8');

		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == \XMLReader::ELEMENT) {
				$this->startElement($this->xmlReader->name);
			} else {
				if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT) {
					$this->endElement($this->xmlReader->name);
				} else {
					continue;
				}
			}
		}
		$this->xmlReader->close();
		$this->importLogger->log('Imported ' . $this->numberOfImportedProducts . ' TYPO3 Extensions.', LOG_INFO);
	}

	/**
	 * Method is invoked when parser accesses start tag of an element.
	 *
	 * @param string $elementName element name at parser's current position
	 * @return void
	 */
	protected function startElement($elementName) {
		switch ($elementName) {
			case 'extension':
				$shortName = $this->xmlReader->getAttribute('extensionkey');
				/** @var $existingProduct Product */
				$existingProduct = $this->productRepository->findOneByShortName($shortName);

				if ($existingProduct) {
					$this->product = $existingProduct;
					$this->productVersions = $existingProduct->getVersions();
				} else {
					$this->product = new Product();
					$this->productVersions = new \Doctrine\Common\Collections\ArrayCollection();
				}

				$productType = new ProductType(ProductType::CMS_EXTENSION);
				$this->product->setType($productType);
				$this->product->setShortName($shortName);
				break;
			case 'title':
				$this->product->setName($this->getElementValue($elementName));
				break;
			case 'version':
				$productVersion = new ProductVersion($this->xmlReader->getAttribute('version'));
				if (!$this->product->hasVersion($productVersion)) {
					$this->productVersions->add($productVersion);
				}
				break;
		}
	}

	/**
	 * Method is invoked when parser accesses end tag of an element.
	 *
	 * @param string $elementName: element name at parser's current position
	 * @return void
	 */
	protected function endElement($elementName) {
		switch ($elementName) {
			case 'extension':
				$this->product->setVersions($this->productVersions);
				$this->persistProduct();
				break;
		}
	}

	/**
	 * Method returns the value of an element at XMLReader's current
	 * position.
	 *
	 * Method will read until it finds the end of the given element.
	 * If element has no value, method returns NULL.
	 *
	 * @param string  $elementName: name of element to retrieve it's value from
	 * @return string  an element's value if it has a value, otherwise NULL
	 */
	protected function getElementValue(&$elementName) {
		$value = NULL;
		if (!$this->xmlReader->isEmptyElement) {
			$value = '';
			while ($this->xmlReader->read()) {
				if ($this->xmlReader->nodeType == \XMLReader::TEXT || $this->xmlReader->nodeType == \XMLReader::CDATA || $this->xmlReader->nodeType == \XMLReader::WHITESPACE || $this->xmlReader->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE) {
					$value .= $this->xmlReader->value;
				} else {
					if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name === $elementName) {
						break;
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Method creates or updates a product
	 *
	 * @return void
	 */
	protected function persistProduct() {
		$this->importLogger->log("Importing: " . $this->product->getShortName() . " Title: " . $this->product->getName(), LOG_DEBUG);
		if ($this->product->getName()) {
			$this->productRepository->addOrUpdate($this->product);
			$this->numberOfImportedProducts++;
		}
	}
}