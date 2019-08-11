<?php

namespace App\Tests\Controller;


use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Response\DeleteCustomerResponse;
use App\Tests\HelperTest\HelperTestTrait;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends TestCase
{
    public function testGetCustomerAction()
    {
        $customer = $this->createMockedCustomer();
        $controller = $this->createCustomerController();

        $response = $controller->getCustomerAction($customer);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseProduct = $response->getData();
        $this->assertEquals($customer->getId(), $responseProduct->getId());
        $this->assertEquals($customer->getName(), $responseProduct->getName());
        $this->assertEquals($customer->getSurname(), $responseProduct->getSurname());
        $this->assertEquals($customer->getEmail(), $responseProduct->getEmail());
        $this->assertEquals($customer->getAddress(), $responseProduct->getAddress());
    }

    public function testCreateCustomerAction()
    {
        $newCustomer = new Customer();
        $newCustomer->setName("new-customer-test-name")
            ->setSurname("new-customer-test-surname")
            ->setEmail("new.customer@test.com")
            ->setAddress("new-customer-test-address");
        $body = $this->serializer->serialize($newCustomer, "json");

        // Anonymous
        $this->client->request(
            'POST',
            "/api/customers",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json"
            ],
            $body
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // As user
        $token = $this->login($this->userEmail, $this->password);
        $this->client->request(
            'POST',
            "/api/customers",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json",
                "Authorization" => "BEARER $token"
            ],
            $body
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseCustomer = $this->serializer->deserialize($response->getContent(), Customer::class, "json");
        $this->checkEntity($responseCustomer, $newCustomer);
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
            "/api/customers/{$this->testCustomer->getId()}"
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteCustomerResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testCustomer, true);
    }

    // Private

    private function createCustomerController()
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new CustomerController();
        $controller->setViewHandler($viewHandler);

        return $controller;
    }

    private function createMockedCustomer(
        int $id = 77,
        string $name = "test-customer-name",
        string $surname = "test-customer-surname",
        string $email = "customer@test.com",
        string $address = "test-customer-address"
    ) {
        $mockedCustomer = $this->createMock(Customer::class);
        $mockedCustomer->method("getId")
            ->willReturn($id);
        $mockedCustomer->method("getName")
            ->willReturn($name);
        $mockedCustomer->method("getSurname")
            ->willReturn($surname);
        $mockedCustomer->method("getEmail")
            ->willReturn($email);
        $mockedCustomer->method("getAddress")
            ->willReturn($address);

        return $mockedCustomer;
    }
}