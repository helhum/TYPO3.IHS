<?php
namespace TYPO3\IHS\ViewHelpers\Format;

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
 * Class HtmlspecialcharsViewHelper
 *
 * @package Neos\FluidAdaptor\ViewHelpers\Format
 */
class MarkdownViewHelper extends AbstractViewHelper {

    protected $escapeChildren = false;

    /**
     * @var bool
     */
	protected $escapeOutput = false;

	/**
	 * Transforms markdown to HTML using Parsedown PHP library.
	 *
	 * @param string $value string to format
	 * @return string the altered string
	 * @see http://parsedown.org/
	 */
	public function render($value = NULL) {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}

		if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
			return $value;
		}

		$parsedown = new \Parsedown();
		return $parsedown->text($value);
	}

}
