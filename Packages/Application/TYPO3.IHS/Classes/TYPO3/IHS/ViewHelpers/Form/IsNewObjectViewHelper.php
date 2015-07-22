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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class IsNewObjectViewHelper
 * @package TYPO3\IHS\ViewHelpers
 */
class IsNewObjectViewHelper extends AbstractViewHelper {
	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 *
	 * @param object $object
	 * @return boolean
	 */
	public function render($object) {
		$isNewObject = $this->persistenceManager->isNewObject($object);
		return $isNewObject;
	}

} 