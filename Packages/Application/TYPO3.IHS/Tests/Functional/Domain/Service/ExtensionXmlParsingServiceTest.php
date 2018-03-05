<?php
namespace TYPO3\IHS\Tests\Functional\Domain\Service;

use Neos\Flow\Tests\FunctionalTestCase;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\Domain\Service\ExtensionXMLParsingService;

class ExtensionXmlParsingServiceTest extends FunctionalTestCase {

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var ExtensionXMLParsingService
	 */
	protected $parsingService;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;

	public function setUp() {
		parent::setUp();
		$this->parsingService = $this->objectManager->get('TYPO3\IHS\Domain\Service\ExtensionXMLParsingService');
		$this->productRepository = $this->objectManager->get('TYPO3\IHS\Domain\Repository\ProductRepository');
	}

	/**
	 * @test
	 */
	public function simpleImportWorks() {
		$this->parsingService->setXmlFile(__DIR__ . '/Fixtures/AFewExtensions.xml');
		$this->parsingService->createXmlReader();
		$this->parsingService->parseXML();

		$this->persistenceManager->persistAll();

		/** @var $sampleProduct Product */
		$sampleProduct = $this->productRepository->findOneByShortName('a1_teasermenu');

		$this->assertNotNull($sampleProduct);
		$this->assertSame(ProductType::CMS_EXTENSION, $sampleProduct->getType()->getValue());
	}

	/**
	 * @test
	 */
	public function reimportingUpdatesProducts() {
		$this->parsingService->setXmlFile(__DIR__ . '/Fixtures/AFewExtensions.xml');
		$this->parsingService->createXmlReader();
		$this->parsingService->parseXML();

		$this->persistenceManager->persistAll();

		$this->parsingService->setXmlFile(__DIR__ . '/Fixtures/AFewExtensions.xml');
		$this->parsingService->createXmlReader();
		$this->parsingService->parseXML();

		$this->persistenceManager->persistAll();

		/** @var $sampleProduct Product */
		$this->assertSame(1, $this->productRepository->findByShortName('a1_teasermenu')->count());
	}

	/**
	 * @test
	 */
	public function importWithMultipleVersions() {
		$this->parsingService->setXmlFile(__DIR__ . '/Fixtures/ExtensionWithMultipleVersions.xml');
		$this->parsingService->createXmlReader();
		$this->parsingService->parseXML();

		$this->persistenceManager->persistAll();

		$this->parsingService->setXmlFile(__DIR__ . '/Fixtures/ExtensionWithMultipleVersions.xml');
		$this->parsingService->createXmlReader();
		$this->parsingService->parseXML();

		$this->persistenceManager->persistAll();

		/** @var $product Product */
		$product = $this->productRepository->findOneByShortName('a1_ttnews');
		$this->assertNotNull($product);
		$this->assertSame(6, $product->getVersions()->count());
	}

} 