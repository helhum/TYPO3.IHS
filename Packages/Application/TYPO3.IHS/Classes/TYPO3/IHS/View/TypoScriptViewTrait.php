<?php
namespace TYPO3\IHS\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Neos\Domain\Service\ContentContext;

/**
 * Trait TypoScriptViewTrait
 *
 * @package TYPO3\IHS\View
 */
trait TypoScriptViewTrait {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\ContentContextFactory
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	protected function initializeObject() {
		$this->defaultViewObjectName = 'TYPO3\TypoScript\View\TypoScriptView';
	}

	/**
	 * @param ViewInterface $view
	 */
	protected function initializeView(ViewInterface $view) {
		$contentContext = $this->buildContextFromWorkspaceName('live');
		$siteNode = $contentContext->getCurrentSiteNode();
		$currentNode = $contentContext->getNode($this->currentNodePath);

		$this->view->assignMultiple(array(
			'node' => $currentNode,
			'documentNode' => $currentNode,
			'site' => $siteNode,
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

	/**
	 * Overwritten method that returns FALSE in case of validation errors to avoid standard error message
	 * Validation message will still be shown
	 */
	protected function getErrorFlashMessage() {
		return FALSE;
	}
}