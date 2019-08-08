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
        $product = $this->createMock(Product::class);
        $product->method("getId")
            ->willReturn(1);
        $product->method("getModel")
            ->willReturn("test-model");
        $product->method("getBrand")
            ->willReturn("test-brand");
        $product->method("getPrice")
            ->willReturn(9999);
        $product->method("getQuantity")
            ->willReturn(8888);
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new ProductController();
        $controller->setViewHandler($viewHandler);
        $response = $controller->getProductAction($product);

        $this->assertObjectHasAttribute("test", $response);
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