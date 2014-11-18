<?php
namespace TYPO3\IHS\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\IHS\Domain\Model\Advisory;
use TYPO3\IHS\Domain\Model\Issue;
use TYPO3\IHS\Domain\Model\Link;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\Solution;
use TYPO3\IHS\Domain\Model\VulnerabilityType;
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
	 * Resets all advisories, issues and solutions
	 *
	 * @param boolean $confirmation
	 */
	public function resetCommand($confirmation = FALSE) {
		if ($confirmation) {
			$this->issueRepository->removeAll();
			$this->advisoryRepository->removeAll();
		} else {
			$this->outputLine('You need to confirm this reset.');
		}
	}

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
		$this->currentObject = NULL;
		$this->currentObjectType = '';
		$this->currentAdvisory = NULL;
		$this->currentIssue = NULL;
		$this->currentSolution = NULL;
		$this->currentProduct = NULL;

		$html = str_replace('&nbsp;', ' ', file_get_contents($singleAdvisoryUrl));
		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();
		$xpath = new \DOMXPath($dom);
		$article = $xpath->query("//article")->item(0);

		$this->parseHeader($xpath, $article);

		if ($this->persistenceManager->isNewObject($this->currentAdvisory)) {
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
						continue 2;
					}
				}
			}

			$this->advisoryRepository->add($this->currentAdvisory);
		} else {
			$this->advisoryRepository->update($this->currentAdvisory);
		}

		$this->outputLine('=====================================');
		$this->outputLine('');
	}

	/**
	 * Parse the header with all needed information for the Advisory
	 *
	 * @param $xpath
	 * @param $article
	 */
	protected function parseHeader($xpath, $article) {
		$this->currentObjectName = 'advisory';
		$heading = $xpath->query('//h1', $article)->item(0)->nodeValue;
		$identifier = trim(substr($heading, 0, strpos($heading, ':')));

		$this->currentAdvisory = $this->advisoryRepository->findByIdentifier($identifier);
		if ($this->currentAdvisory === NULL) {
			$this->outputLine('Importing advisory %s', array($identifier));
			$this->currentAdvisory = new Advisory();
			$this->currentAdvisory->setIdentifier($identifier);
		} else {
			$this->outputLine('Updating advisory %s', array($identifier));
		}
		$this->currentObject = $this->currentAdvisory;

		$this->currentAdvisory->setTitle(trim(substr($heading, strpos($heading, ':') + 1, strlen($heading))));
		$this->currentAdvisory->setDescription($xpath->query('./div[2]//p[1]', $article)->item(0)->nodeValue);

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

		// Publishing Date
		$publishingDate = $xpath->query('./div[1]//p[1]', $article)->item(0)->nodeValue;
		$date = new \DateTime($publishingDate);
		$this->currentAdvisory->setPublishingDate($date);
		$this->currentAdvisory->setCreationDate($date);
		$this->currentAdvisory->setPublished(TRUE);
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
					$this->currentIssue->setCreationDate($this->currentAdvisory->getCreationDate());
					if ($this->currentProduct) {
						$this->currentIssue->setProduct($this->currentProduct);
					}
				break;
				case 'solution':
					if ($this->currentIssue) {
						$this->currentIssue->setTitle(sprintf('%s in %s', ($this->currentIssue->getVulnerabilityType() !== NULL) ? $this->currentIssue->getVulnerabilityType()->getValue() : '', ($this->currentProduct != NULL) ? $this->currentProduct->getName() : ''));
						if ($this->isIssueValid()) {
							$this->currentAdvisory->addIssue($this->currentIssue);
							$this->currentIssue->setAdvisory($this->currentAdvisory);

							$this->issueRepository->add($this->currentIssue);
							$this->outputLine('Issue could be created.');
						} else {
							$this->outputLine('Issue could not be created.');
						}

						$this->currentSolution = new Solution();
						$this->currentObject = $this->currentSolution;
					}
				break;
			}

			$this->currentObjectType = ($mapping['object'] != 'keep' && $mapping['object'] != 'skip') ? $mapping['object'] : $this->currentObjectType;
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
			case 'vulnerabilityType':
				$this->currentObject->setVulnerabilityType(new VulnerabilityType(trim($value)));
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
			$this->currentIssue->setAbstract('');
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
		if ($this->currentIssue->getVulnerabilityType() === NULL) {
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