<?php

namespace App\Tests\Controller;


use App\Controller\ProductController;
use App\Entity\Product;
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
        $id = 7777;
        $model = "test-model";
        $brand = "test-brand";
        $price = 9999;
        $quantity = 8888;

        $product = $this->createMock(Product::class);
        $product->method("getId")
            ->willReturn($id);
        $product->method("getModel")
            ->willReturn($model);
        $product->method("getBrand")
            ->willReturn($brand);
        $product->method("getPrice")
            ->willReturn($price);
        $product->method("getQuantity")
            ->willReturn($quantity);
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new ProductController();
        $controller->setViewHandler($viewHandler);
        $response = $controller->getProductAction($product);

        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseProduct = $response->getData();
        $this->assertEquals($id, $responseProduct->getId());
        $this->assertEquals($model, $responseProduct->getModel());
        $this->assertEquals($brand, $responseProduct->getBrand());
        $this->assertEquals($price, $responseProduct->getPrice());
        $this->assertEquals($quantity, $responseProduct->getQuantity());
    }

    public function testEditProductAction()
    {
        $modifiedProduct = new Product();
        $modifiedProduct->setModel("test-modified-model");
        $modifiedProduct->setBrand("test-modified-brand");
        $modifiedProduct->setQuantity(9999);
        $modifiedProduct->setPrice(9999);
        $body = $this->serializer->serialize($modifiedProduct, "json");

        $this->client->request(
            'POST',
            "/api/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json"
            ],
            $body
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseProduct = $this->serializer->deserialize($response->getContent(), Product::class, "json");
        $this->assertEquals($modifiedProduct->getModel(), $responseProduct->getModel());
        $this->assertEquals($modifiedProduct->getBrand(), $responseProduct->getBrand());
        $this->assertEquals($modifiedProduct->getQuantity(), $responseProduct->getQuantity());
        $this->assertEquals($modifiedProduct->getPrice(), $responseProduct->getPrice());
    }

    public function testDeleteProductAction()
    {
        $this->client->request(
            'DELETE',
            "/api/products/{$this->testProduct->getId()}"
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteProductResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testProduct);
    }
}