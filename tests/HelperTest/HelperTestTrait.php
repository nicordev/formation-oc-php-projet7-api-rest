<?php

namespace App\Tests\HelperTest;


use App\Entity\Product;
use App\Entity\User;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

trait HelperTestTrait
{
    protected $client;
    protected $testProduct;
    protected $testUser;
    protected $testAdmin;
    /**
     * @var Serializer
     */
    protected $serializer;

    protected $keyHeaderToken = "HTTP_X-AUTH-TOKEN";
    protected $testUserToken = "test_user_token";
    protected $testAdminToken = "test_admin_token";

    /**
     * Generate a test product, a test user and a test admin
     */
    protected function fullSetUp()
    {
        if (!$this->client) {
            $this->client = static::createClient();
        }
        if (!$this->testProduct) {
            $this->testProduct = $this->createTestProduct();
        }
        if (!$this->testUser) {
            $this->testUser = $this->createTestUser();
        }
        if (!$this->testAdmin) {
            $this->testAdmin = $this->createTestAdmin();
        }
        if (!$this->serializer) {
            $this->serializer = SerializerBuilder::create()->build();
        }
    }

    /**
     * Delete every test entities
     */
    public function fullTearDown()
    {
        if ($this->testProduct) {
            $this->deleteEntity($this->testProduct);
            $this->testProduct = null;
        }
        if ($this->testUser) {
            $this->deleteEntity($this->testUser);
            $this->testUser = null;
        }
        if ($this->testAdmin) {
            $this->deleteEntity($this->testAdmin);
            $this->testAdmin = null;
        }
    }
    
    /**
     * Create a test product
     *
     * @return mixed
     */
    protected function createTestProduct()
    {
        $product = new Product();
        $product->setModel("Test-Model");
        $product->setBrand("Test-Brand");
        $product->setPrice(100);
        $product->setQuantity(1351);

        return $this->saveEntity($product);
    }

    /**
     * Create a test user
     *
     * @return mixed
     */
    protected function createTestUser()
    {
        $user = new User();

        $user->setName("test-user-name");
        $user->setEmail("user@test.com");
        $user->setPassword("test-user-password");
        $user->setApiToken($this->testUserToken);
        $user->setRoles(["ROLE_USER"]);

        return $this->saveEntity($user);
    }

    /**
     * Create a test user with admin role
     *
     * @return mixed
     */
    protected function createTestAdmin()
    {
        $user = new User();

        $user->setName("test-admin-name");
        $user->setEmail("admin@test.com");
        $user->setPassword("test-admin-password");
        $user->setApiToken($this->testAdminToken);
        $user->setRoles(["ROLE_USER", "ROLE_ADMIN"]);

        return $this->saveEntity($user);
    }

    /**
     * Check if an object is an instance of the same class than the expected entity and can check if its values are the same than the expected object's values
     *
     * @param $entity
     * @param $expectedEntity
     * @param bool $checkValues
     */
    protected function checkEntity($entity, $expectedEntity, bool $checkValues = true)
    {
        $this->assertInstanceOf(get_class($expectedEntity), $entity);
        if ($checkValues) {
            $this->assertEquals(true, $entity == $expectedEntity);
        }
    }

    /**
     * Fetch an entity from the database
     *
     * @param string $entityClassName
     * @param string $criteria
     * @param $value
     * @return mixed
     */
    protected function getEntity(string $entityClassName, string $criteria, $value)
    {
        return $this->client->getContainer()->get('doctrine')->getRepository($entityClassName)->findOneBy([
            $criteria => $value
        ]);
    }

    /**
     * Save an entity in the database
     *
     * @param $entity
     * @return mixed
     */
    protected function saveEntity($entity)
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $entity;
    }

    /**
     * Delete an entity in the database
     *
     * @param $entity
     * @return mixed
     */
    protected function deleteEntity($entity)
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();
        $manager->remove($entity);
        $manager->flush();

        return $entity;
    }
}