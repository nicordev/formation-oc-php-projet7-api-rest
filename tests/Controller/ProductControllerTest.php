<?php

namespace App\Tests\Controller;


use App\Entity\Product;
use App\Response\DeleteProductResponse;
use App\Tests\HelperTest\HelperTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
        // Anonymous
        $this->client->request(
            'GET',
            "/api/products/{$this->testProduct->getId()}"
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // As user
        $token = $this->login($this->userEmail, $this->password);
        $this->client->request(
            'GET',
            "/api/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                "Authorization" => "BEARER $token"
            ]
        );
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