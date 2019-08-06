<?php

namespace App\Tests\Controller;


use App\Entity\Customer;
use App\Response\DeleteCustomerResponse;
use App\Tests\HelperTest\HelperTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends WebTestCase
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

    public function testGetCustomerAction()
    {
        // Anonymous
        $this->client->request(
            'GET',
            "/api/customers/{$this->testCustomer->getId()}"
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // As user
        $token = $this->login($this->userEmail, $this->password);
        $this->client->request(
            'GET',
            "/api/customers/{$this->testCustomer->getId()}",
            [],
            [],
            [
                "Authorization" => "BEARER $token"
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $customer = $this->serializer->deserialize($response->getContent(), Customer::class, 'json');
        $this->checkEntity($customer, $this->testCustomer, true);
    }

    public function testEditCustomerAction()
    {
        $modifiedCustomer = new Customer();
        $modifiedCustomer->setName("test-modified-name");
        $modifiedCustomer->setEmail("modified.customer@test.com");
        $modifiedCustomer->setSurname("test-modified-surname");
        $modifiedCustomer->setAddress("test-modified-address");
        $body = $this->serializer->serialize($modifiedCustomer, "json");

        $this->client->request(
            'POST',
            "/api/customers/{$this->testCustomer->getId()}",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json"
            ],
            $body
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseCustomer = $this->serializer->deserialize($response->getContent(), Customer::class, "json");
        $this->assertEquals($modifiedCustomer->getName(), $responseCustomer->getName());
        $this->assertEquals($modifiedCustomer->getEmail(), $responseCustomer->getEmail());
        $this->assertEquals($modifiedCustomer->getSurname(), $responseCustomer->getSurname());
        $this->assertEquals($modifiedCustomer->getAddress(), $responseCustomer->getAddress());
    }

    public function testDeleteCustomerAction()
    {
        $this->client->request(
            'DELETE',
            "/api/customers/{$this->testCustomer->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testUserToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteCustomerResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testCustomer, true);
    }
}