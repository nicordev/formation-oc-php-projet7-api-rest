<?php

namespace App\Tests\Controller;


use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Response\DeleteCustomerResponse;
use App\Tests\HelperTest\HelperTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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

        $responseCustomer = $response->getData();
        $this->checkCustomer($customer, $responseCustomer);
    }

    public function testGetCustomersAction()
    {
        $controller = $this->createCustomerController();
        $page = 1;
        $quantity = 5;

        $repository = $this->prophesize(CustomerRepository::class);
        $repository->getPage($page, $quantity)->shouldBeCalled();

        $response = $controller->getCustomersAction(
            $repository->reveal(),
            $page,
            $quantity
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    public function testCreateCustomerAction()
    {
        $customer = $this->createMockedCustomer();
        $controller = $this->createCustomerController();
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist($customer)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();

        $response = $controller->createCustomerAction($customer, $manager->reveal(), $violations);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($customer, $responseCustomer);
    }

    public function testEditCustomerAction()
    {
        $customer = $this->createMockedCustomer();
        $modifiedCustomer = $this->createMockedCustomer(
            22,
            "test-modified-name",
            "test-modified-surname",
            "modified.customer@test.com",
            "test-modified-address"
        );
        $controller = $this->createCustomerController();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");

        $response = $controller->editCustomerAction($customer, $modifiedCustomer, $manager);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($customer, $responseCustomer);
    }

    public function testDeleteCustomerAction()
    {
        $customer = $this->createMockedCustomer();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($customer)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createCustomerController();

        $response = $controller->deleteCustomerAction($customer, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
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
        ?int $id = 77,
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

    private function checkCustomer($expectedCustomer, $responseCustomer)
    {
        $this->assertEquals($expectedCustomer->getId(), $responseCustomer->getId());
        $this->assertEquals($expectedCustomer->getName(), $responseCustomer->getName());
        $this->assertEquals($expectedCustomer->getSurname(), $responseCustomer->getSurname());
        $this->assertEquals($expectedCustomer->getEmail(), $responseCustomer->getEmail());
        $this->assertEquals($expectedCustomer->getAddress(), $responseCustomer->getAddress());
    }
}