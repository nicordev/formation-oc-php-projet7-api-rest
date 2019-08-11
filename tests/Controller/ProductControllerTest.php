<?php

namespace App\Tests\Controller;


use App\Controller\ProductController;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandler;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Tests\ProjectTemplateEngine;

class ProductControllerTest extends TestCase
{
    public function testGetProductAction()
    {
        $product = $this->createMockedProduct();
        $controller = $this->createProductController();

        $response = $controller->getProductAction($product);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseProduct = $response->getData();
        $this->assertEquals($product->getId(), $responseProduct->getId());
        $this->assertEquals($product->getModel(), $responseProduct->getModel());
        $this->assertEquals($product->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($product->getPrice(), $responseProduct->getPrice());
        $this->assertEquals($product->getQuantity(), $responseProduct->getQuantity());
    }

    public function testEditProductAction()
    {
        $product = $this->createMockedProduct();
        $modifiedProduct = $this->createMockedProduct(
            22,
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
        $responseProduct = $response->getData();
        $this->assertEquals($modifiedProduct->getModel(), $responseProduct->getModel());
        $this->assertEquals($modifiedProduct->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($modifiedProduct->getQuantity(), $responseProduct->getQuantity());
        $this->assertEquals($modifiedProduct->getPrice(), $responseProduct->getPrice());
    }

    public function testDeleteProductAction()
    {
        $product = $this->createMockedProduct();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($product)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createProductController();

        $response = $controller->deleteProductAction($product, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    // Private

    private function createProductController()
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new ProductController();
        $controller->setViewHandler($viewHandler);

        return $controller;
    }

    private function createMockedProduct(
        int $id = 77,
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
}