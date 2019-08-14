<?php

namespace App\Tests\Controller;


use App\Controller\ProductController;
use App\Entity\Product;
use App\Repository\PaginatedRepository;
use App\Repository\ProductRepository;
use App\Tests\TestHelperTrait\UnitTestHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Hateoas\Representation\PaginatedRepresentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ProductControllerTest extends TestCase
{
    use UnitTestHelperTrait;

    public function testGetProductAction()
    {
        $product = $this->createProduct();
        $controller = $this->createProductController();

        $response = $controller->getProductAction($product);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseProduct = $response->getData();
        $this->assertEquals($product->getId(), $responseProduct->getId());
        $this->assertEquals($product->getModel(), $responseProduct->getModel());
        $this->assertEquals($product->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($product->getPrice(), $responseProduct->getPrice());
        $this->assertEquals($product->getQuantity(), $responseProduct->getQuantity());
    }

    public function testGetProductsAction()
    {
        $controller = $this->createProductController();
        $property = "price";
        $order = "asc";
        $search = null;
        $exact = "true";
        $page = 1;
        $quantity = 5;
        $products = (function () {
            $products = [];

            for ($i = 0; $i < 5; $i++) {
                $products[] = (new Product())
                    ->setModel("p$i")
                    ->setBrand("b$i")
                    ->setQuantity($i * 10)
                    ->setPrice($i * 10)
                ;
            }

            return $products;
        })();

        $repository = $this->prophesize(ProductRepository::class);
        $exactValue = $exact !== "false";
        $repository->getPage(
            $page,
            $quantity,
            [$property => strtoupper($order)],
            null,
            $exactValue
        )->willReturn([
            PaginatedRepository::KEY_PAGING_ENTITIES => $products,
            PaginatedRepository::KEY_PAGING_PAGES_COUNT => 1,
            PaginatedRepository::KEY_PAGING_CURRENT_PAGE => 1,
            PaginatedRepository::KEY_PAGING_NEXT_PAGE => 2,
            PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE => 1
        ])->shouldBeCalled();

        $response = $controller->getProductsAction(
            $repository->reveal(),
            $property,
            $order,
            $search,
            $exact,
            $page,
            $quantity
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseContent = $response->getData();
        $this->assertInstanceOf(PaginatedRepresentation::class, $responseContent);
        /*
         * TODO: check response content
         */
    }

    public function testCreateProductAction()
    {
        $product = $this->createProduct();
        $controller = $this->createProductController();
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist($product)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();

        $response = $controller->createProductAction($product, $manager->reveal(), $violations);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseProduct = $response->getData();
        $this->assertEquals($product->getId(), $responseProduct->getId());
        $this->assertEquals($product->getModel(), $responseProduct->getModel());
        $this->assertEquals($product->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($product->getPrice(), $responseProduct->getPrice());
        $this->assertEquals($product->getQuantity(), $responseProduct->getQuantity());
    }

    public function testEditProductAction()
    {
        $product = $this->createProduct();
        $modifiedProduct = $this->createProduct(
            null,
            "test-modified-model",
            "test-modified-brand",
            222,
            2222
        );
        $controller = $this->createProductController();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");

        $response = $controller->editProductAction($product, $modifiedProduct, $manager);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseProduct = $response->getData();
        $this->assertEquals($modifiedProduct->getModel(), $responseProduct->getModel());
        $this->assertEquals($modifiedProduct->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($modifiedProduct->getQuantity(), $responseProduct->getQuantity());
        $this->assertEquals($modifiedProduct->getPrice(), $responseProduct->getPrice());
    }

    public function testDeleteProductAction()
    {
        $product = $this->createProduct();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($product)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createProductController();

        $response = $controller->deleteProductAction($product, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    // Private

    private function createProductController()
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new ProductController();
        $controller->setViewHandler($viewHandler);

        return $controller;
    }

    private function createProduct(
        ?int $id = 77,
        string $model = "test-model",
        string $brand = "test-brand",
        int $price = 888,
        int $quantity = 9999
    ) {
        $product = (new Product())
            ->setModel($model)
            ->setBrand($brand)
            ->setPrice($price)
            ->setQuantity($quantity)
        ;

        if ($id) {
            $this->setId($product, $id);
        }

        return $product;
    }
}