<?php

namespace App\Tests\Controller;


use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Entity\User;
use App\Helper\Cache\Cache;
use App\Repository\CustomerRepository;
use App\Repository\PaginatedRepository;
use App\Security\CustomerVoter;
use App\Tests\TestHelperTrait\UnitTestHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CustomerControllerTest extends TestCase
{
    use UnitTestHelperTrait;

    public function testGetCustomerAction()
    {
        $customer = $this->createCustomer();
        $controller = $this->createCustomerController(new User(), $customer, CustomerVoter::READ);

        $response = $controller->getCustomerAction($customer);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($customer, $responseCustomer);
    }

    public function testGetCustomerAction_accessDenied()
    {
        $customer = $this->createCustomer();
        $controller = $this->createCustomerController(
            new User(),
            $customer,
            CustomerVoter::READ,
            false
        );

        $this->expectException(AccessDeniedException::class);
        $controller->getCustomerAction($customer);
    }

    public function testGetCustomersAction()
    {
        $user = new User();
        $this->setId($user, 123);
        $controller = $this->createCustomerController($user);
        $page = 1;
        $quantity = 5;

        // Fake customers
        $customersCount = 15;
        $customers = (function () use ($customersCount) {
            $customers = [];

            for ($i = 0; $i < $customersCount; $i++) {
                $customers[] = (new Customer())
                    ->setName("n$i")
                    ->setSurname("s$i")
                    ->setEmail("e$i")
                    ->setAddress("a$i")
                    ->setUser((new User())->setName("u$i"))
                ;
            }

            return $customers;
        })();

        $repository = $this->prophesize(CustomerRepository::class);
        $requestedProperties = [
            "id",
            "name",
            "surname",
            "email",
            "address"
        ];
        $repository->getPage(
                $page,
                $quantity,
                $requestedProperties,
                null,
                ["user" => $user->getId()]
            )
            ->willReturn([
                PaginatedRepository::KEY_PAGING_ENTITIES => $customers,
                PaginatedRepository::KEY_PAGING_PAGES_COUNT => 3,
                PaginatedRepository::KEY_PAGING_ITEMS_COUNT => $customersCount,
                PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE => 5,
                PaginatedRepository::KEY_PAGING_CURRENT_PAGE => 1,
                PaginatedRepository::KEY_PAGING_NEXT_PAGE => 2,
                PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE => 1
            ])
            ->shouldBeCalled();

        $response = $controller->getCustomersAction(
            $repository->reveal(),
            $page,
            $quantity
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseContent = $response->getData();
        $this->assertInstanceOf(PaginatedRepresentation::class, $responseContent);
        $inline = $responseContent->getInline();
        $this->assertInstanceOf(CollectionRepresentation::class, $inline);
        $resources = $inline->getResources();
        $this->assertEquals($customersCount, count($resources));

        for ($i = 0; $i < $customersCount; $i++) {
            $this->assertInstanceOf(Customer::class, $resources[$i]);
            $this->assertEquals("n$i", $resources[$i]->getName());
            $this->assertEquals("s$i", $resources[$i]->getSurname());
            $this->assertEquals("e$i", $resources[$i]->getEmail());
            $this->assertEquals("a$i", $resources[$i]->getAddress());
            $this->assertInstanceOf(User::class, $resources[$i]->getUser());
            $this->assertEquals("u$i", $resources[$i]->getUser()->getName());
        }
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
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method("invalidateTags")
            ->with([CustomerController::TAG_CACHE_LIST])
        ;

        $response = $controller->createCustomerAction(
            $customer,
            $manager,
            $violations,
            $cache
        );
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
        $controller = $this->createCustomerController(
            $user,
            $customer,
            CustomerVoter::UPDATE,
            true
        );
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method("invalidateTags")
            ->with([CustomerController::TAG_CACHE_LIST])
        ;

        $response = $controller->editCustomerAction(
            $customer,
            $modifiedCustomer,
            $manager,
            $cache
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseCustomer = $response->getData();
        $this->checkCustomer($modifiedCustomer, $responseCustomer, false);
    }

    public function testEditCustomerAction_accessDenied()
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
        $controller = $this->createCustomerController(
            $user,
            $customer,
            CustomerVoter::UPDATE,
            false
        );
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->never())
            ->method("invalidateTags")
            ->with([CustomerController::TAG_CACHE_LIST])
        ;

        $this->expectException(AccessDeniedException::class);
        $controller->editCustomerAction(
            $customer,
            $modifiedCustomer,
            $manager,
            $cache
        );
    }

    public function testDeleteCustomerAction()
    {
        $customer = $this->createCustomer();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($customer)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createCustomerController(
            new User,
            $customer,
            CustomerVoter::DELETE,
            true
        );
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method("invalidateTags")
            ->with([CustomerController::TAG_CACHE_LIST])
        ;

        $response = $controller->deleteCustomerAction(
            $customer,
            $manager->reveal(),
            $cache
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    public function testDeleteCustomerAction_accessDenied()
    {
        $customer = $this->createCustomer();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($customer)->shouldNotBeCalled();
        $manager->flush()->shouldNotBeCalled();
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->never())
            ->method("invalidateTags")
            ->with([CustomerController::TAG_CACHE_LIST])
        ;
        $controller = $this->createCustomerController(
            new User,
            $customer,
            CustomerVoter::DELETE,
            false
        );

        $this->expectException(AccessDeniedException::class);
        $controller->deleteCustomerAction(
            $customer,
            $manager->reveal(),
            $cache
        );
    }

    // Private

    /**
     * Create a CustomerController instance with a ViewHandler. Optionally: a user to use Controller::getUser()
     *
     * @param User|null $user
     * @param null $entity
     * @param string|null $voterAction
     * @param bool $isGranted
     * @return CustomerController
     */
    private function createCustomerController(
        ?User $user = null,
        $entity = null,
        ?string $voterAction = null,
        bool $isGranted = true
    ) {
        $controller = new CustomerController();
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller->setViewHandler($viewHandler);

        if ($user) {
            $controller->setContainer(
                $this->createSecurityContainerMock(
                    $user,
                    $entity,
                    $voterAction,
                    $isGranted
                )->reveal()
            );
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
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
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