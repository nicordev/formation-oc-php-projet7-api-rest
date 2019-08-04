<?php

namespace App\Tests\Controller;


use App\Entity\Product;
use App\Response\DeleteProductResponse;
use App\Tests\HelperTest\HelperTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    use HelperTestTrait;

    public function setUp()
    {
        $this->fullSetUp();
    }

    public function tearDown()
    {
        $this->fullTearDown();
    }

    public function testGetProductAction()
    {
        $this->client->request(
            'GET',
            "/api/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testUserToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $product = $this->serializer->deserialize($response->getContent(), Product::class, 'json');
        $this->checkEntity($product, $this->testProduct);
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
            "/api/admin/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json",
                $this->keyHeaderToken => $this->testAdminToken
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
            "/api/admin/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testAdminToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteProductResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testProduct);
    }
}