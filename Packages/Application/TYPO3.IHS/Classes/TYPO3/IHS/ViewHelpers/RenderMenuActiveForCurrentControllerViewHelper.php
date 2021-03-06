<?php
namespace TYPO3\IHS\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Michael Berhorst <michael.berhorst@sandstorm-media.de>
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


use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
/**
 * Class AccountViewHelper
 */
class RenderMenuActiveForCurrentControllerViewHelper extends AbstractViewHelper {

	/**
	 * Returns class 'active' when given controllerName matches the current active controller
	 *
	 * @param string $controllerName
	 * @return string
	 */
	public function render($controllerName) {
		if ($controllerName == $this->controllerContext->getRequest()->getControllerName()) {
			return "active";
		}
		return '';
	}
}