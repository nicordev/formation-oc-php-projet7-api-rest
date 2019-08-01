<?php

namespace App\tests\Controller;

require dirname(__DIR__) . "/HelperTest/HelperTestTrait.php";

use App\Entity\Product;
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
        $this->checkJsonProduct($product, true);
    }

    public function testDeleteAction()
    {
        $id = $this->testProduct->getId();
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
        $responseData = $this->serializer->deserialize($response->getContent(), "array", "json");
        $this->checkJsonProduct($responseData["deleted_entity"]);
    }

    // Private

    private function checkJsonProduct($product, bool $checkValues = false)
    {
        if (is_array($product)) {
            $this->assertArrayHasKey("id", $product);
            $this->assertArrayHasKey("model", $product);
            $this->assertArrayHasKey("brand", $product);
            $this->assertArrayHasKey("price", $product);
            $this->assertArrayHasKey("quantity", $product);

            $this->assertEquals($this->testProduct->getModel(), $product["model"]);
            $this->assertEquals($this->testProduct->getBrand(), $product["brand"]);
            $this->assertEquals($this->testProduct->getPrice(), $product["price"]);
            $this->assertEquals($this->testProduct->getQuantity(), $product["quantity"]);
        } else {
            $this->assertInstanceOf(Product::class, $product);
            if ($checkValues) {
                $this->assertEquals($this->testProduct->getModel(), $product->getModel());
                $this->assertEquals($this->testProduct->getBrand(), $product->getBrand());
                $this->assertEquals($this->testProduct->getPrice(), $product->getPrice());
                $this->assertEquals($this->testProduct->getQuantity(), $product->getQuantity());
            }
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