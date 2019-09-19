<?php

namespace App\Tests;


use App\Entity\Product;
use App\Repository\PaginatedRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    /**
     * @var array
     */
    private $entities = [];

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get("doctrine")
            ->getManager()
        ;
        $this->fillDatabase();
    }

    public function testGetPage_firstPage()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_COUNT = 100;
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_PAGES_COUNT = 20;
            public const EXPECTED_CURRENT_PAGE = 1;
            public const EXPECTED_NEXT_PAGE = 2;
            public const EXPECTED_PREVIOUS_PAGE = 1;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE
        );

        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, count($page[PaginatedRepository::KEY_PAGING_ENTITIES]));
        $this->assertEquals($test::EXPECTED_ITEMS_COUNT, $page[PaginatedRepository::KEY_PAGING_ITEMS_COUNT]);
        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, $page[PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE]);
        $this->assertEquals($test::EXPECTED_PAGES_COUNT, $page[PaginatedRepository::KEY_PAGING_PAGES_COUNT]);
        $this->assertEquals($test::EXPECTED_CURRENT_PAGE, $page[PaginatedRepository::KEY_PAGING_CURRENT_PAGE]);
        $this->assertEquals($test::EXPECTED_NEXT_PAGE, $page[PaginatedRepository::KEY_PAGING_NEXT_PAGE]);
        $this->assertEquals($test::EXPECTED_PREVIOUS_PAGE, $page[PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE]);
    }

    public function testGetPage_lastPage()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_COUNT = 100;
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_PAGES_COUNT = 20;
            public const EXPECTED_CURRENT_PAGE = 20;
            public const EXPECTED_NEXT_PAGE = 20;
            public const EXPECTED_PREVIOUS_PAGE = 19;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE
        );

        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, count($page[PaginatedRepository::KEY_PAGING_ENTITIES]));
        $this->assertEquals($test::EXPECTED_ITEMS_COUNT, $page[PaginatedRepository::KEY_PAGING_ITEMS_COUNT]);
        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, $page[PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE]);
        $this->assertEquals($test::EXPECTED_PAGES_COUNT, $page[PaginatedRepository::KEY_PAGING_PAGES_COUNT]);
        $this->assertEquals($test::EXPECTED_CURRENT_PAGE, $page[PaginatedRepository::KEY_PAGING_CURRENT_PAGE]);
        $this->assertEquals($test::EXPECTED_NEXT_PAGE, $page[PaginatedRepository::KEY_PAGING_NEXT_PAGE]);
        $this->assertEquals($test::EXPECTED_PREVIOUS_PAGE, $page[PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE]);
    }

    public function testGetPage_middlePage()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_COUNT = 100;
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_PAGES_COUNT = 20;
            public const EXPECTED_CURRENT_PAGE = 10;
            public const EXPECTED_NEXT_PAGE = 11;
            public const EXPECTED_PREVIOUS_PAGE = 9;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE
        );

        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, count($page[PaginatedRepository::KEY_PAGING_ENTITIES]));
        $this->assertEquals($test::EXPECTED_ITEMS_COUNT, $page[PaginatedRepository::KEY_PAGING_ITEMS_COUNT]);
        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, $page[PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE]);
        $this->assertEquals($test::EXPECTED_PAGES_COUNT, $page[PaginatedRepository::KEY_PAGING_PAGES_COUNT]);
        $this->assertEquals($test::EXPECTED_CURRENT_PAGE, $page[PaginatedRepository::KEY_PAGING_CURRENT_PAGE]);
        $this->assertEquals($test::EXPECTED_NEXT_PAGE, $page[PaginatedRepository::KEY_PAGING_NEXT_PAGE]);
        $this->assertEquals($test::EXPECTED_PREVIOUS_PAGE, $page[PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE]);
    }

    public function testGetPage_firstPage_search()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_COUNT = 10;
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_PAGES_COUNT = 2;
            public const EXPECTED_CURRENT_PAGE = 1;
            public const EXPECTED_NEXT_PAGE = 2;
            public const EXPECTED_PREVIOUS_PAGE = 1;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE,
            null,
            null,
            ["model" => "model-t10"],
            false // $exactSearch to false
        );

        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, count($page[PaginatedRepository::KEY_PAGING_ENTITIES]));
        $this->assertEquals($test::EXPECTED_ITEMS_COUNT, $page[PaginatedRepository::KEY_PAGING_ITEMS_COUNT]);
        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, $page[PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE]);
        $this->assertEquals($test::EXPECTED_PAGES_COUNT, $page[PaginatedRepository::KEY_PAGING_PAGES_COUNT]);
        $this->assertEquals($test::EXPECTED_CURRENT_PAGE, $page[PaginatedRepository::KEY_PAGING_CURRENT_PAGE]);
        $this->assertEquals($test::EXPECTED_NEXT_PAGE, $page[PaginatedRepository::KEY_PAGING_NEXT_PAGE]);
        $this->assertEquals($test::EXPECTED_PREVIOUS_PAGE, $page[PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE]);
    }

    public function testGetPage_firstPage_exactSearch()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_COUNT = 10;
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_PAGES_COUNT = 2;
            public const EXPECTED_CURRENT_PAGE = 1;
            public const EXPECTED_NEXT_PAGE = 2;
            public const EXPECTED_PREVIOUS_PAGE = 1;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE,
            null,
            null,
            ["brand" => "brand-t10"],
            true // $exactSearch to true
        );

        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, count($page[PaginatedRepository::KEY_PAGING_ENTITIES]));
        $this->assertEquals($test::EXPECTED_ITEMS_COUNT, $page[PaginatedRepository::KEY_PAGING_ITEMS_COUNT]);
        $this->assertEquals($test::EXPECTED_ITEMS_PER_PAGE, $page[PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE]);
        $this->assertEquals($test::EXPECTED_PAGES_COUNT, $page[PaginatedRepository::KEY_PAGING_PAGES_COUNT]);
        $this->assertEquals($test::EXPECTED_CURRENT_PAGE, $page[PaginatedRepository::KEY_PAGING_CURRENT_PAGE]);
        $this->assertEquals($test::EXPECTED_NEXT_PAGE, $page[PaginatedRepository::KEY_PAGING_NEXT_PAGE]);
        $this->assertEquals($test::EXPECTED_PREVIOUS_PAGE, $page[PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE]);
    }

    public function testGetPage_firstPage_exactSearch_notFound()
    {
        $test = new class () {
            public const EXPECTED_ITEMS_PER_PAGE = 5;
            public const EXPECTED_CURRENT_PAGE = 1;
        };
        $repository = $this->entityManager->getRepository(Product::class);
        $page = $repository->getPage(
            $test::EXPECTED_CURRENT_PAGE,
            $test::EXPECTED_ITEMS_PER_PAGE,
            null,
            null,
            ["brand" => "unknownBrand"],
            true // $exactSearch to true
        );

        $this->assertNull($page);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->emptyTable("product");
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }

    private function fillDatabase()
    {
        for ($i = 0, $j = 0, $count = 100; $i < $count; $i++) {
            if ($i % 10 === 0) {
                $j++;
            }
            $tag = "t$j";
            $entity = (new Product())
                ->setModel("model-$tag-$i")
                ->setBrand("brand-$tag")
                ->setQuantity($i * 100)
                ->setPrice($i * 10)
                ->setDetail(["detail$i", "detail1", "detail2"])
                ->setCreatedAt(new \DateTime())
            ;
            $this->entities[] = $entity;
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    private function emptyTable(string $table)
    {
        $rawSql = "DELETE FROM {$table}";
        $statement = $this->entityManager->getConnection()->prepare($rawSql);
        $statement->execute();
    }
}