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

//        $product = $this->serializer->deserialize($response->getContent(), Product::class, 'json'); // Not working
        $product = json_decode($response->getContent());
        $this->checkJsonProduct($product);
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
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $product = json_decode($response->getContent());
        $this->checkJsonProduct($product);
    }

    // Private

    private function checkJsonProduct($product)
    {
        $this->assertObjectHasAttribute("id", $product);
        $this->assertObjectHasAttribute("model", $product);
        $this->assertObjectHasAttribute("brand", $product);
        $this->assertObjectHasAttribute("price", $product);
        $this->assertObjectHasAttribute("quantity", $product);

        $this->assertEquals($this->testProduct->getModel(), $product->model);
        $this->assertEquals($this->testProduct->getBrand(), $product->brand);
        $this->assertEquals($this->testProduct->getPrice(), $product->price);
        $this->assertEquals($this->testProduct->getQuantity(), $product->quantity);
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