<?php

namespace App\Tests\Controller;


use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Tests\TestHelperTrait\UnitTestHelperTrait;
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
    use UnitTestHelperTrait;

    public function testGetCustomerAction()
    {
        $customer = $this->createCustomer();
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
        $controller = $this->createCustomerController(new User());
        $page = 1;
        $quantity = 5;

        $repository = $this->prophesize(CustomerRepository::class);
        $repository->getPage($page, $quantity, null, ["user_id" => null])->shouldBeCalled();

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
        $customer = $this->createCustomer();
        $modifiedCustomer = $this->createCustomer(
            null,
            "test-modified-name",
            "test-modified-surname",
            "modified.customer@test.com",
            "test-modified-address"
        );
        $user = new User();
        $controller = $this->createCustomerController($user);
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
        $this->checkCustomer($modifiedCustomer, $responseCustomer, false);
    }

    public function testDeleteCustomerAction()
    {
        $customer = $this->createCustomer();
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
            $this->setId($customer, $id);
        }

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