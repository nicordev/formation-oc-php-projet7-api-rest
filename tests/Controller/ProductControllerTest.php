<?php

namespace App\tests\Controller;

require dirname(__DIR__) . "/HelperTest/HelperTestTrait.php";

use App\Entity\Product;
use App\Response\DeleteProductResponse;
use App\tests\HelperTest\HelperTestTrait;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    use HelperTestTrait;

    private $client;
    private $testProduct;
    /**
     * @var Serializer
     */
    private $serializer;

    public function setUp()
    {
        if (!$this->client) {
            $this->client = static::createClient();
        }
        if (!$this->testProduct) {
            $this->testProduct = $this->createTestProduct();
        }
        if (!$this->serializer) {
            $this->serializer = SerializerBuilder::create()->build();
        }
    }

    public function tearDown()
    {
        if ($this->testProduct) {
            $this->deleteEntity($this->testProduct);
            $this->testProduct = null;
        }
    }

    public function testGetProductAction()
    {
        $this->client->request(
            'GET',
            "/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $product = $this->serializer->deserialize($response->getContent(), Product::class, 'json');
        $this->checkProduct($product);
    }

    public function testDeleteAction()
    {
        $this->client->request(
            'DELETE',
            "/products/{$this->testProduct->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteProductResponse::class, "json");
        $this->checkProduct($responseContentObject->entity);
    }

    // Private

    /**
     * Check if an object is a Product and can check if its values are the same than the test object's values
     *
     * @param $product
     * @param bool $checkValues
     */
    private function checkProduct($product, bool $checkValues = true)
    {
        $this->assertInstanceOf(Product::class, $product);
        if ($checkValues) {
            $this->assertEquals(true, $product == $this->testProduct);
        }
    }

    /**
     * Create a test product
     *
     * @return mixed
     */
    private function createTestProduct()
    {
        $product = new Product();

        $product->setModel("Test-Model");
        $product->setBrand("Test-Brand");
        $product->setPrice(100);
        $product->setQuantity(1351);

        return $this->saveEntity($product);
    }
}