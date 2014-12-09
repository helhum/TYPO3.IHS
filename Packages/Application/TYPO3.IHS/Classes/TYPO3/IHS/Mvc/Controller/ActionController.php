<?php
namespace TYPO3\IHS\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\ContentContext;

/**
 * Class ActionController
 *
 * @package TYPO3\IHS\Mvc\Controller
 */
class ActionController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	protected $nodeRepository;

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\TypoScript\View\TypoScriptView';

	/**
	 * @var \TYPO3\TypoScript\View\TypoScriptView
	 */
	protected $view;

	/**
	 * @Flow\Inject
	 * @var ContentContextFactory
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @param \TYPO3\Flow\Mvc\View\ViewInterface $view
	 */
	protected function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view) {
		$contentContext = $this->buildContextFromWorkspaceName('live');
		$currentNode = $contentContext->getCurrentSiteNode();

		$this->view->assignMultiple(array(
			'node' => $currentNode,
			'documentNode' => $currentNode,
			'site' => $currentNode,
		));
	}

	/**
	 * @param string $workspaceName
	 * @param array $dimensions
	 * @return ContentContext
	 */
	protected function buildContextFromWorkspaceName($workspaceName, array $dimensions = NULL) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);

		if ($dimensions !== NULL) {
			$contextProperties['dimensions'] = $dimensions;
		}

		$currentDomain = $this->domainRepository->findOneByActiveRequest();

		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
		}

		return $this->contextFactory->create($contextProperties);
	}

}