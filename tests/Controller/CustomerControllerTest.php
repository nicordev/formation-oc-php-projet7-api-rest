<?php

namespace App\Tests\Controller;


use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CustomerControllerTest extends TestCase
{
    public function testGetCustomerAction()
    {
        $customer = $this->createStubCustomer();
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
        $customer = $this->createCustomer();
        $user = new User();
        $controller = $this->createCustomerController($user);

        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method("persist")
            ->with($this->callback(function (Customer $customer) use ($user) {
                $this->assertSame($user, $customer->getUser());

                return true;
            }))
        ;
        $manager
            ->expects($this->once())
            ->method("flush")
        ;

        $response = $controller->createCustomerAction($customer, $manager, $violations);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($customer, $responseCustomer);
    }

    public function testEditCustomerAction()
    {
        $customer = $this->createStubCustomerFromProphecy();
        $customer->setName("test-modified-name")->will(function () {
            $this->getName()->willReturn("test-modified-name");
            return $this;
        });
        $customer->setSurname("test-modified-surname")->will(function () {
            $this->getSurname()->willReturn("test-modified-surname");
            return $this;
        });
        $customer->setEmail("modified.customer@test.com")->will(function () {
            $this->getEmail()->willReturn("modified.customer@test.com");
            return $this;
        });
        $customer->setAddress("test-modified-address")->will(function () {
            $this->getAddress()->willReturn("test-modified-address");
            return $this;
        });

        $modifiedCustomer = $this->createStubCustomer(
            null,
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

        $response = $controller->editCustomerAction($customer->reveal(), $modifiedCustomer, $manager);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($modifiedCustomer, $responseCustomer, false);
    }

    public function testDeleteCustomerAction()
    {
        $customer = $this->createStubCustomer();
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

    /**
     * Create a CustomerController instance with a ViewHandler. Optionally: a user to use Controller::getUser()
     *
     * @param User|null $user
     * @return CustomerController
     */
    private function createCustomerController(?User $user = null)
    {
        $controller = new CustomerController();
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller->setViewHandler($viewHandler);

        if ($user) {
            $tokenMock = $this->prophesize(TokenInterface::class);
            $tokenMock
                ->getUser()
                ->willReturn($user)
            ;
            $tokenStorageMock = $this->prophesize(TokenStorageInterface::class);
            $tokenStorageMock
                ->getToken()
                ->willReturn($tokenMock)
            ;
            $containerMock = $this->prophesize(ContainerInterface::class);
            $containerMock
                ->has('security.token_storage')
                ->willReturn(true)
            ;
            $containerMock
                ->get('security.token_storage')
                ->willReturn($tokenStorageMock)
            ;
            $controller->setContainer($containerMock->reveal());
        }

        return $controller;
    }

    private function createCustomer(
        ?int $id = null,
        string $name = "test-customer-name",
        string $surname = "test-customer-surname",
        string $email = "customer@test.com",
        string $address = "test-customer-address"
    )
    {
        $customer = (new Customer())
            ->setName($name)
            ->setSurname($surname)
            ->setEmail($email)
            ->setAddress($address)
        ;

        if ($id) {
            $reflectionCustomer = new \ReflectionObject($customer);
            $reflectionId = $reflectionCustomer->getProperty("id");
            $reflectionId->setAccessible(true);
            $reflectionId->setValue($customer, $id);
        }

        return $customer;
    }

    private function createStubCustomer(
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
        $mockedCustomer->method("getUser")
            ->willReturn(
                (new User())->setName("user-name")
                    ->setEmail("user@mail.com")
                    ->setPassword("user-password")
                    ->setRoles(["ROLE_USER"])
            );

        return $mockedCustomer;
    }

    public function createStubCustomerFromProphecy(
        ?int $id = 77,
        string $name = "test-customer-name",
        string $surname = "test-customer-surname",
        string $email = "customer@test.com",
        string $address = "test-customer-address"
    ) {
        $customer = $this->prophesize(Customer::class);
        $customer->getId()->willReturn($id);
        $customer->getName()->willReturn($name);
        $customer->getSurname()->willReturn($surname);
        $customer->getEmail()->willReturn($email);
        $customer->getAddress()->willReturn($address);

        return $customer;
    }

    private function checkCustomer($expectedCustomer, $responseCustomer, bool $checkId = true)
    {
        if ($checkId) {
            $this->assertEquals($expectedCustomer->getId(), $responseCustomer->getId());
        }
        $this->assertEquals($expectedCustomer->getName(), $responseCustomer->getName());
        $this->assertEquals($expectedCustomer->getSurname(), $responseCustomer->getSurname());
        $this->assertEquals($expectedCustomer->getEmail(), $responseCustomer->getEmail());
        $this->assertEquals($expectedCustomer->getAddress(), $responseCustomer->getAddress());
    }
}