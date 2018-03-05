<?php
namespace TYPO3\IHS\Controller;

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

use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use TYPO3\IHS\View\FusionViewTrait;

/**
 * Class AuthenticationController
 */
class LoginController extends AbstractAuthenticationController {

	use FusionViewTrait;

	/**
	 * @var string
	 */
	protected $currentNodePath = '/sites/securitytypo3org/securitybulletins';

	/**
	 * Renders the login form
	 */
	public function indexAction() {
	}

	/**
	 * Redirect to Advisory Controller after logout
	 */
	public function logoutAction() {
		parent::logoutAction();
		$this->redirect('index', 'Advisory', 'TYPO3.IHS');
	}

	/**
	 * Redirects to Advisory Controller after login
	 *
	 * @param \Neos\Flow\Mvc\ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
	 * @return string
	 */
	protected function onAuthenticationSuccess(\Neos\Flow\Mvc\ActionRequest $originalRequest = NULL) {
		$this->redirect('Index', 'Advisory', 'TYPO3.IHS');
	}
}