<?php
namespace TYPO3\IHS\Controller\Mapping;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;

/**
 * Class ArgumentMappingHelper
 */
trait ArgumentMappingTrait {

	/**
	 * Helper method, to allow creation and mapping for collection properties
	 *
	 * @param string $argumentName Argument Name of the to be mapped object
	 * @param string $property Property which must be a collection of objects of the to be mapped object
	 */
	protected function allowMappingForArgumentAndCollectionProperty($argumentName, $property, $subProperty = NULL, $subSubProperty = NULL, $subPropertyIsCollection = FALSE) {
		/** @var PropertyMappingConfiguration $mappingConfiguration */
		$mappingConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();
		$mappingConfiguration->allowAllProperties();
		$mappingConfiguration->forProperty($property)->allowAllProperties();

		// TODO: This is ugly but necessary because specific property configuration currently takes precedence
		$requestArgument = $this->request->getArgument($argumentName);
		if (isset($requestArgument[$property]) && is_array($requestArgument[$property])) {
			foreach (array_keys($requestArgument[$property]) as $propertyIndex) {
				if (!is_int($propertyIndex)) {
					// This is obviously not a collection, so skip
					continue;
				}
				$mappingConfiguration->forProperty($property . '.' . $propertyIndex)
					->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)
					->allowAllProperties();

				if ($subProperty !== NULL) {
					$mappingConfiguration->forProperty($property . '.' . $propertyIndex . '.' . $subProperty)->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)->allowAllProperties();

					if (($subSubProperty != NULL || $subPropertyIsCollection) && isset($requestArgument[$property][$propertyIndex][$subProperty]) && is_array($requestArgument[$property][$propertyIndex][$subProperty])) {
						foreach (array_keys($requestArgument[$property][$propertyIndex][$subProperty]) as $subPropertyIndex) {
							if (!is_int($subPropertyIndex)) {
								// This is obviously not a collection, so skip
								continue;
							}
							$mappingConfiguration->forProperty($property . '.' . $propertyIndex . '.' . $subProperty . '.' . $subPropertyIndex)
								->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)
								->allowAllProperties();

							if ($subSubProperty !== NULL) {
								$mappingConfiguration->forProperty($property . '.' . $propertyIndex . '.' . $subProperty . '.' . $subPropertyIndex . '.' . $subSubProperty)->allowAllProperties();
								if ($subPropertyIsCollection && isset($requestArgument[$property][$propertyIndex][$subProperty][$subPropertyIndex][$subSubProperty]) && is_array($requestArgument[$property][$propertyIndex][$subProperty][$subPropertyIndex][$subSubProperty])) {
									foreach (array_keys($requestArgument[$property][$propertyIndex][$subProperty][$subPropertyIndex][$subSubProperty]) as $subSubPropertyIndex) {
										if (!is_int($subSubPropertyIndex)) {
											// This is obviously not a collection, so skip
											continue;
										}
										$mappingConfiguration->forProperty($property . '.' . $propertyIndex . '.' . $subProperty . '.' . $subPropertyIndex . '.' . $subSubProperty . '.' . $subSubPropertyIndex)
											->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE)
											->allowAllProperties();
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Helper method to allow object creation for a specified argument and property
	 *
	 * @param string $argumentName
	 * @param string $property
	 */
	protected function allowCreationForArgumentAndProperty($argumentName, $property) {
		/** @var PropertyMappingConfiguration $mappingConfiguration */
		$mappingConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();
		$mappingConfiguration->forProperty($property)->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}
}