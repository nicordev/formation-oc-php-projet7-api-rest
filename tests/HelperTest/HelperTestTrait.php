<?php

namespace App\Tests\HelperTest;


use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Helper\DatabaseHandler;
use App\Helper\LoginCredentials;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

trait HelperTestTrait
{
    protected $client;
    protected $testProduct;
    protected $testCustomer;
    protected $testUser;
    protected $testAdmin;
    /**
     * @var Serializer
     */
    protected $serializer;
    protected $userEmail = "user@test.com";
    protected $adminEmail = "admin@test.com";
    protected $password = "pwdSucks!0";
    protected $hashedPassword = '$2y$13$qACYre5/bO7y2jW4n8S.m.Es6vjYpz7x8XBhZxBvckcr.VoC5cvqq'; // pwdSucks!0
    protected $productModel = "Test-Model";
    protected $productBrand = "Test-Brand";
    protected $customerName = "test-customer-name";
    protected $customerEmail = "customer@test.com";

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
        if (!$this->testCustomer) {
            $this->testCustomer = $this->createTestCustomer();
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
            $this->deleteEntity($this->testProduct); // doctrine detached entity cannot be removed
            $this->testProduct = null;
        }
        if ($this->testCustomer) {
            $this->deleteEntity($this->testCustomer);
            $this->testCustomer = null;
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
     * Send a login request and get the token
     *
     * @param string $login
     * @param string $password
     * @return
     */
    protected function login(string $login, string $password)
    {
        $body = new class ($login, $password) {
            public $username;
            public $password;

            public function __construct(string $username, string $password)
            {
                $this->username = $username;
                $this->password = $password;
            }
        };
        $this->client->request(
            'POST',
            "/api/login_check",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json"
            ],
            $this->serializer->serialize($body, 'json')
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseObject = json_decode($this->client->getResponse()->getContent());

        return $responseObject->token;
    }
    
    /**
     * Create a test product
     *
     * @return mixed
     */
    protected function createTestProduct()
    {
        $product = new Product();
        $product->setModel($this->productModel);
        $product->setBrand($this->productBrand);
        $product->setPrice(100);
        $product->setQuantity(1351);

        return $this->saveEntity($product);
    }

    /**
     * Create a test customer
     *
     * @return mixed
     */
    protected function createTestCustomer()
    {
        $customer = new Customer();
        $customer->setName($this->customerName);
        $customer->setEmail($this->customerEmail);
        $customer->setAddress("test customer address");
        $customer->setSurname("test-customer-surname");

        return $this->saveEntity($customer);
    }

    /**
     * Create a test user
     *
     * @return mixed
     */
    protected function createTestUser()
    {
        $user = new User();

        $user->setName("test-user-name")
            ->setEmail($this->userEmail)
            ->setRoles(["ROLE_USER"])
            ->setPassword($this->hashedPassword);

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

        $user->setName("test-admin-name")
            ->setEmail($this->adminEmail)
            ->setRoles(["ROLE_USER", "ROLE_ADMIN"])
            ->setPassword($this->hashedPassword);

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
     * @throws \Exception
     */
    protected function deleteEntity($entity)
    {
        $manager = $this->client->getContainer()->get('doctrine')->getManager();

        try {
            $manager->remove($entity);
            $manager->flush();
        } catch (\Exception $e) {
            echo "\nException: " . $e->getCode() . " " . $e->getMessage() . "\n";
            $database = DatabaseHandler::getInstance("mysql", "ocp7_bilemo_api", "root", "");
            $table = $database->getTableNameFromEntity($entity);
            $database->delete($table, "id = {$entity->getId()}");
        }

        return $entity;
    }
}