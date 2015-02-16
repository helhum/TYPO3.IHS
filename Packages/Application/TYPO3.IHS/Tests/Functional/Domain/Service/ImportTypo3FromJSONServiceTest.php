<?php
namespace TYPO3\IHS\Tests\Functional\Domain\Service;

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\IHS\Domain\Model\Product;
use TYPO3\IHS\Domain\Model\ProductType;
use TYPO3\IHS\Domain\Repository\ProductRepository;
use TYPO3\IHS\Domain\Service\ImportTypo3FromJSONService;

class ImportTypo3FromJSONServiceTest extends FunctionalTestCase {

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var ImportTypo3FromJSONService
	 */
	protected $importService;

	/**
	 * @var ProductRepository
	 */
	protected $productRepository;

	public function setUp() {
		parent::setUp();
		$this->importService = $this->objectManager->get('TYPO3\IHS\Domain\Service\ImportTypo3FromJSONService');
		$this->productRepository = $this->objectManager->get('TYPO3\IHS\Domain\Repository\ProductRepository');
	}

	/**
	 * @test
	 */
	public function importWorks() {
		$this->importService->createOrUpdateTypo3( __DIR__ . '/Fixtures/getTypo3JSON.json');

		$this->persistenceManager->persistAll();

		/** @var $typo3 Product */
		$typo3 = $this->productRepository->findOneByShortName('CMS');

		$this->assertNotNull($typo3);
		$this->assertSame(ProductType::TYPO3_PRODUCT, $typo3->getType()->getValue());
		$this->assertSame(3, $typo3->getVersions()->count());
	}

	/**
	 * @test
	 */
	public function reimportingUpdatesTypo3() {
		$this->importService->createOrUpdateTypo3( __DIR__ . '/Fixtures/getTypo3JSON.json');

		$this->persistenceManager->persistAll();

		$this->assertSame(1, $this->productRepository->findByShortName('CMS')->count());
		$this->assertSame(3, $this->productRepository->findOneByShortName('CMS')->getVersions()->count());
	}
} 