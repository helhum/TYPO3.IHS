<?php
namespace TYPO3\IHS\ViewHelpers\Form;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ReadablePropertyPathViewHelper
 * @package TYPO3\IHS\ViewHelpers
 */
class ReadablePropertyPathViewHelper extends AbstractViewHelper {
	/**
	 *
	 * @param string $propertyPath
	 * @return string
	 */
	public function render($propertyPath) {
		$readablePropertyPath = str_replace('.', ' ', $propertyPath);
		$readablePropertyPath = preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/', ' $0', $readablePropertyPath);
		return ucfirst($readablePropertyPath);
	}
} 