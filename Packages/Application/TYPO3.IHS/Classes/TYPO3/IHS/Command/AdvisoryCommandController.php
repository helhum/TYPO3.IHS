<?php
namespace TYPO3\IHS\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\IHS\Domain\Model\Advisory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Model\Link;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\Solution;
use TYPO3\IHS\Domain\Repository\AdvisoryRepository;
use TYPO3\IHS\Domain\Repository\IssueRepository;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class AdvisoryCommandController extends CommandController {

	/**
	 * @Flow\inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\inject
	 * @var AdvisoryRepository
	 */
	protected $advisoryRepository;

	/**
	 * @Flow\inject
	 * @var IssueRepository
	 */
	protected $issueRepository;

	/**
	 * @Flow\inject
	 * @var ProductRepository
	 */
	protected $productRepository;

	/**
	 * @Flow\inject
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var Advisory
	 */
	protected $currentAdvisory;

	/**
	 * @var Issue
	 */
	protected $currentIssue;

	/**
	 * @var \stdClass
	 */
	protected $currentObject;

	/**
	 * @var string
	 */
	protected $currentObjectType;

	/**
	 * @var Product
	 */
	protected $currentProduct;

	/**
	 * @var Solution
	 */
	protected $currentSolution;

	/**
	 * @var \stdClass
	 */
	protected $validatedObject;

	/**
	 * @var string
	 * @Flow\Inject(setting="import.advisory")
	 */
	protected $settings;

	/**
	 * Imports all advisories
	 */
	public function importAllCommand() {
		$feed = new \SimplePie();
		$feed->enable_cache(FALSE);
		$feed->set_feed_url('https://typo3.org/?id=199&type=101');
		$success = $feed->init();
		$feed->handle_content_type();
		$this->outputLine(count($feed->get_items()));

		foreach ($feed->get_items() as $item) {
			$this->importCommand($item->get_link());
		}
	}

	/**
	 * Imports a single advisory
	 *
	 * @param string $singleAdvisoryUrl
	 */
	public function importCommand($singleAdvisoryUrl) {
		$html = file_get_contents($singleAdvisoryUrl);
		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();
		$xpath = new \DOMXPath($dom);
		$article = $xpath->query("//article")->item(0);

		$this->parseHeader($xpath, $article);

		$link = new Link($singleAdvisoryUrl, 'Original URL');
		$this->currentAdvisory->addLink($link);

		// CONTENT
		$bodyElements = $xpath->query('./div[2]//p[position()>1]|./div[2]//h2', $article);
		foreach ($bodyElements as $element) {
			$elementHtml = $element->ownerDocument->saveHTML($element);
			foreach ($this->settings['mappings'] as $mapping) {
				if (preg_match($mapping['regex'], $elementHtml, $matches)) {
					if ($mapping['object'] != $this->currentObjectType) {
						$this->changeCurrentObject($mapping);
					}

					$this->mapMatches($matches, $mapping);
					break;
				}
			}
		}

		$this->outputLine('=====================================');
		$this->outputLine('');

		$this->advisoryRepository->add($this->currentAdvisory);
	}

	/**
	 * Parse the header with all needed information for the Advisory
	 *
	 * @param $xpath
	 * @param $article
	 */
	protected function parseHeader($xpath, $article) {
		$this->currentAdvisory = new Advisory();
		$this->currentObjectName = 'advisory';

		$heading = $xpath->query('//h1', $article)->item(0)->nodeValue;
		$this->currentAdvisory->setIdentifier(trim(substr($heading, 0, strpos($heading, ':'))));
		if ($this->advisoryRepository->findByIdentifier($this->currentAdvisory->getIdentifier())) {
			$this->outputLine(sprintf('Advisory %s already exists!', $this->currentAdvisory->getIdentifier()));
			return;
		}

		$this->currentAdvisory->setTitle(trim(substr($heading, strpos($heading, ':') + 1, strlen($heading))));
		$this->currentAdvisory->setDescription($xpath->query('./div[2]//p[1]', $article)->item(0)->nodeValue);

		$this->outputLine(sprintf('Importing advisory %s', $this->currentAdvisory->getIdentifier()));

		$header = $xpath->query('./div[1]//p[2]', $article)->item(0);
		// Author
		preg_match('/(?:(?:Author:\s+)([\w\s]+)|(?:Author:\s+<a.+>\s+)([\w\s\.]+)(?:<\/a>))/', $header->ownerDocument->saveHTML($header), $matches);
		if (!empty($matches)) {
			$author = (empty($matches[1])) ? $matches[2] : $matches[1];
			$this->currentAdvisory->setAuthor($author);
		}

		// Category
		preg_match('/(?:.+Category:\s+ <a.+>)(.+)(?:<\/a>.+)/', $header->ownerDocument->saveHTML($header), $matches);
		$category = $matches[1];
		switch ($category) {
			case 'TYPO3 Extension':
				if (preg_match('/.+(\(([\w\d_]+)\))/', $this->currentAdvisory->getTitle(), $matches)) {
					$this->currentProduct = $this->productRepository->findOneByShortName($matches[2]);
				}
			break;
			case 'TYPO3 CMS':
			case 'TYPO3 Flow':
				$this->currentProduct = $this->productRepository->findOneByName($category);
			break;
		}
	}

	/**
	 * Switch up the $currentObject when neccessary
	 *
	 * @param array $mapping
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	protected function changeCurrentObject($mapping) {
		if ($mapping) {
			switch ($mapping['object']) {
				case 'advisory':
					$this->currentObject = $this->currentAdvisory;
					break;
				case 'issue':
					if ($this->isSolutionValid() && $this->isIssueValid(TRUE)) {
						$this->currentSolution->setIssue($this->currentIssue);
						$this->currentIssue->getSolutions()->add($this->currentSolution);
					}

					$this->currentIssue = new Issue();
					$this->currentObject = $this->currentIssue;
					if ($this->currentProduct) {
						$this->currentIssue->setProduct($this->currentProduct);
					}
				break;
				case 'solution':
					$this->currentIssue->setTitle(sprintf('%s in %s', $this->currentIssue->getVulnerabilityType(), ($this->currentProduct != NULL) ? $this->currentProduct->getName() : ''));
					if ($this->isIssueValid()) {
						$this->currentAdvisory->addIssue($this->currentIssue);
						$this->currentIssue->setAdvisory($this->currentAdvisory);

						$this->issueRepository->add($this->currentIssue);
					} else {
						$this->outputLine('Issue could not be created.');
					}

					$this->currentSolution = new Solution();
					$this->currentObject = $this->currentSolution;
				break;
			}

			$this->currentObjectType = ($mapping['object'] != 'keep' && $mapping['object'] != 'skip') ? $mapping['object'] : $this->currentObjectType;
		}
	}

	/**
	 * Check if Issue is valid and set the advisory
	 * Also adds it to the repository
	 *
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	protected function finalizeIssue() {
		$this->currentIssue->setTitle(sprintf('%s in %s', $this->currentIssue->getVulnerabilityType(), ($this->currentProduct != NULL) ? $this->currentProduct->getName() : ''));
		if ($this->isIssueValid() && $this->persistenceManager->isNewObject($this->currentIssue)) {
			$this->currentAdvisory->addIssue($this->currentIssue);
			$this->currentIssue->setAdvisory($this->currentAdvisory);

			$this->issueRepository->add($this->currentIssue);
		} else {
			$this->outputLine('Issue could not be created.');
		}
	}

	/**
	 * Check if Solution is valid and set the issue
	 */
	protected function finalizeSolution() {
		if ($this->isSolutionValid()) {
			$this->currentSolution->setIssue($this->currentIssue);
			$this->currentIssue->getSolutions()->add($this->currentSolution);
		}
	}

	/**
	 * Map the found values to the corresponding objects and properties
	 *
	 * @param array $matches
	 * @param array $mapping
	 */
	protected function mapMatches($matches, $mapping) {
		$value = $matches[$mapping['match']];
		switch ($mapping['property']) {
			case 'publishingDate':
				try {
					$date = new \DateTime($value);
					$this->currentObject->setPublishingDate($date);
				} catch (\Exception $e) {
					$this->outputLine(sprintf('%s is not a valid date.', $value));
				}
			break;
			case 'vulnerabilityType':
				$this->currentObject->setVulnerabilityType($value);
			break;
			case 'cvss':
				$this->currentObject->setCVSS($value);
			break;
			case 'cve':
				$this->currentObject->setCVE($value);
			break;
			case 'abstract':
				$this->currentObject->setAbstract($value);
			break;
			case 'description':
				$this->currentObject->setDescription(sprintf('%s <p>%s</p>', $this->currentObject->getDescription(), $value));
			break;
			case 'product':
				$this->currentProduct = $this->productRepository->findOneByShortName($value);
				$this->currentIssue->setProduct($this->currentProduct);
			break;
			default:
				$this->outputLine('Couldn\'t match a line. Please double check');
			break;
		}
	}

	/**
	 * Checks if the Issue is valid
	 * Certain fields will be checked by filled with empty strings (CVE, CVSS, Abstract)
	 *
	 * @return boolean
	 */
	protected function isIssueValid($silent = FALSE) {
		$result = TRUE;
		$abstract = $this->currentIssue->getAbstract();
		$errors = array();

		if (empty($abstract)) {
			$result = FALSE;
			$errors[] = 'Issue without Abstract found.';
		}

		$cve = $this->currentIssue->getCVE();
		if (empty($cve)) {
			$this->currentIssue->setCVE('');
			$errors[] = 'Issue without CVE found.';
		}

		$product = $this->currentIssue->getProduct();
		if (empty($product)) {
			$result = FALSE;
			$errors[] = 'Issue without Product found.';
		}

		$vulnerabilityType = $this->currentIssue->getVulnerabilityType();
		if (empty($vulnerabilityType)) {
			$result = FALSE;
			$errors[] = 'Issue without Vulnerability Type found.';
		}

		$cvss = $this->currentIssue->getCVSS();
		if (empty($cvss)) {
			$this->currentIssue->setCVSS('');
			$errors[] = 'Issue without CVSS found.';
		}

		if (!$silent) {
			foreach ($errors as $error) {
				$this->outputLine($error);
			}
		}


		return $result;
	}

	/**
	 * Checks if the Solution is valid
	 *
	 * @return boolean
	 */
	protected function isSolutionValid() {
		$result = TRUE;
		if ($this->currentSolution != NULL) {
			$abstract = $this->currentSolution->getAbstract();
			if (empty($abstract)) {
				$result = FALSE;
				$this->outputLine('Solution without Abstract found.');
			}
		} else {
			$result = FALSE;
		}

		return $result;
	}
}