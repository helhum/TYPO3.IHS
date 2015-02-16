<?php
namespace TYPO3\IHS\Command;

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

/**
 * The Product Command Controller
 *
 * @Flow\Scope("singleton")
 */
class ProductCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Service\ExtensionXMLParsingService
	 */
	protected $extensionXMLParser;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Service\ImportPackagistFromJSONService
	 */
	protected $packagistImporter;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\IHS\Domain\Service\ImportTypo3FromJSONService
	 */
	protected $typo3Importer;

	/**
	 * Parse a certain xml file with a list of extensions from the root path
	 *
	 * @param string $source path to the source xml file
	 * @return void
	 */
	public function importCommand($source) {
		$this->outputLine('Parsed '. $source);
		$this->extensionXMLParser->setXmlFile($source);
		$this->extensionXMLParser->createXmlReader();
		$this->extensionXMLParser->parseXML();
	}

	/**
	 * Creates new product with name, shortName and versions from external json
	 *
	 * @param string $name
	 * @param string $shortName
	 * @param string $type
	 * @param string $packagistUrl
	 * @return void
	 */
	public function importPackagistCommand($name, $shortName, $type, $packagistUrl) {
		$this->outputLine('creating:  '. $name.' '.$shortName.' '.$type.' '.$packagistUrl);

		$this->packagistImporter->createProduct($name, $shortName, $type, $packagistUrl);
	}

	/**
	 * Creates oder extends typo3 with versions given from external json
	 *
	 * @param string $urlToJSON
	 * @return void
	 */
	public function importTypo3Command($urlToJSON) {
		$this->outputLine('creating: typo3 '.$urlToJSON);

		$this->typo3Importer->createOrUpdateTypo3($urlToJSON);
	}
}
