<?php

namespace App\Tests\Controller;


use App\Controller\ProductController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ProductControllerTest extends TestCase
{
    public function testGetProductAction()
    {
        $product = $this->createStubProduct();
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

    public function testGetProductsAction_defaultValues()
    {
        $controller = $this->createProductController();
        $property = "price";
        $order = "asc";
        $search = null;
        $exact = "true";
        $page = 1;
        $quantity = 5;

        $repository = $this->prophesize(ProductRepository::class);
        $exactValue = $exact !== "false";
        $repository->getPage(
            $page,
            $quantity,
            [$property => strtoupper($order)],
            null,
            $exactValue
        )->shouldBeCalled();

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
    }

    public function testCreateProductAction()
    {
        $product = $this->createStubProduct();
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
        $product = $this->createStubProductFromProphecy();
        $product->setModel("test-modified-model")->will(function () {
            $this->getModel()->willReturn("test-modified-model");
            return $this;
        });
        $product->setBrand("test-modified-brand")->will(function () {
            $this->getBrand()->willReturn("test-modified-brand");
            return $this;
        });
        $product->setPrice(222)->will(function () {
            $this->getPrice()->willReturn(222);
            return $this;
        });
        $product->setQuantity(2222)->will(function () {
            $this->getQuantity()->willReturn(2222);
            return $this;
        });
        $modifiedProduct = $this->createStubProduct(
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

        $response = $controller->editProductAction($product->reveal(), $modifiedProduct, $manager);
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
        $product = $this->createStubProduct();
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

    private function createStubProduct(
        ?int $id = 77,
        string $model = "test-model", 
        string $brand = "test-brand", 
        int $price = 888, 
        int $quantity = 9999
    ) {
        $mockedProduct = $this->createMock(Product::class);
        $mockedProduct->method("getId")
            ->willReturn($id);
        $mockedProduct->method("getModel")
            ->willReturn($model);
        $mockedProduct->method("getBrand")
            ->willReturn($brand);
        $mockedProduct->method("getPrice")
            ->willReturn($price);
        $mockedProduct->method("getQuantity")
            ->willReturn($quantity);

        return $mockedProduct;
    }

    public function createStubProductFromProphecy(
        ?int $id = 77,
        string $model = "test-model",
        string $brand = "test-brand",
        int $price = 888,
        int $quantity = 9999
    ) {
        $product = $this->prophesize(Product::class);
        $product->getId()->willReturn($id);
        $product->getModel()->willReturn($model);
        $product->getBrand()->willReturn($brand);
        $product->getPrice()->willReturn($price);
        $product->getQuantity()->willReturn($quantity);

        return $product;
    }
}