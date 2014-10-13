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
	 * Parse a certain xml file with a list of extensions from the root path
	 *
	 * @param string $source path to the source xml file
	 * @return void
	 */
	public function importCommand($source) {
		$this->extensionXMLParser->setXmlFile($source);
		$this->extensionXMLParser->createXmlReader();
		$this->extensionXMLParser->parseXML();
		$this->outputLine('Parsed '. $source);
	}
}
