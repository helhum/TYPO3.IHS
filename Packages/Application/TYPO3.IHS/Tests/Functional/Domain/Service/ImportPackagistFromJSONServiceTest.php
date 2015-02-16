<?php
namespace TYPO3\IHS\Tests\Functional\Domain\Service;

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\Domain\Service\ImportPackagistFromJSONService;

class ImportPackagistFromJSONServiceTest extends FunctionalTestCase {

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var ImportPackagistFromJSONService
	 */
	protected $importService;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;

	public function setUp() {
		parent::setUp();
		$this->importService = $this->objectManager->get('TYPO3\IHS\Domain\Service\ImportPackagistFromJSONService');
		$this->productRepository = $this->objectManager->get('TYPO3\IHS\Domain\Repository\ProductRepository');
	}

	/**
	 * @test
	 */
	public function simpleImportWorks() {
		$this->importService->createOrUpdateProduct('name long', 'name_short', 'EXT', __DIR__ . '/Fixtures/PackagistExample.json');

		$this->persistenceManager->persistAll();

		/** @var $sampleProduct Product */
		$sampleProduct = $this->productRepository->findOneByShortName('name_short');

		$this->assertNotNull($sampleProduct);
		$this->assertSame(ProductType::CMS_EXTENSION, $sampleProduct->getType()->getValue());
		$this->assertSame(6, $sampleProduct->getVersions()->count());
	}

	/**
	 * @test
	 */
	public function reimportingUpdatesProducts() {
		$this->importService->createOrUpdateProduct('name long', 'name_short', 'EXT', __DIR__ . '/Fixtures/PackagistExample.json');

		$this->persistenceManager->persistAll();

		$this->assertSame(1, $this->productRepository->findByShortName('name_short')->count());
		$this->assertSame(6, $this->productRepository->findOneByShortName('name_short')->getVersions()->count());
	}
}