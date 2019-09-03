<?php

namespace App\Tests\Controller;


use App\Controller\UserController;
use App\Entity\Customer;
use App\Entity\User;
use App\Repository\PaginatedRepository;
use App\Repository\UserRepository;
use App\Security\UserVoter;
use App\Tests\TestHelperTrait\UnitTestHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UserControllerTest extends TestCase
{
    use UnitTestHelperTrait;

    public function testGetUserAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::READ,
            true
        );

        $response = $controller->getUserAction($user);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $this->checkUser($user, $responseUser);
    }

    public function testGetUserAction_accessDenied()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::READ,
            false
        );

        $this->expectException(AccessDeniedException::class);
        $controller->getUserAction($user);
    }

    public function testGetUsersAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            null,
            UserVoter::LIST,
            true
        );
        $page = 1;
        $quantity = 5;

        // Fake users
        $usersCount = 15;
        $users = (function () use ($usersCount) {
            $users = [];

            for ($i = 0; $i < $usersCount; $i++) {
                $users[] = (new User())
                    ->setName("n$i")
                    ->setRoles(["ROLE_TEST"])
                    ->setEmail("e$i")
                    ->setPassword("p$i")
                    ->addCustomer((new Customer)->setName("c$i"))
                ;
            }

            return $users;
        })();

        $repository = $this->prophesize(UserRepository::class);
        $repository->getPage(
            $page,
            $quantity,
            [
                "id",
                "name",
                "email",
                "roles"
            ]
        )
            ->willReturn([
                PaginatedRepository::KEY_PAGING_ENTITIES => $users,
                PaginatedRepository::KEY_PAGING_PAGES_COUNT => 3,
                PaginatedRepository::KEY_PAGING_ITEMS_COUNT => $usersCount,
                PaginatedRepository::KEY_PAGING_ITEMS_PER_PAGE => 5,
                PaginatedRepository::KEY_PAGING_CURRENT_PAGE => 1,
                PaginatedRepository::KEY_PAGING_NEXT_PAGE => 2,
                PaginatedRepository::KEY_PAGING_PREVIOUS_PAGE => 1
            ])
            ->shouldBeCalled();

        $response = $controller->getUsersAction(
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
        $this->assertEquals($usersCount, count($resources));

        for ($i = 0; $i < $usersCount; $i++) {
            $this->assertInstanceOf(User::class, $resources[$i]);
            $this->assertEquals("n$i", $resources[$i]->getName());
            $this->assertEquals("e$i", $resources[$i]->getEmail());
            $this->assertEquals("p$i", $resources[$i]->getPassword());
            $this->assertEquals(["ROLE_TEST", "ROLE_USER"], $resources[$i]->getRoles());
            $this->assertInstanceOf(Customer::class, $resources[$i]->getCustomers()[0]);
            $this->assertEquals("c$i", $resources[$i]->getCustomers()[0]->getName());
        }
    }

    public function testGetUsersAction_accessDenied()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            null,
            UserVoter::LIST,
            false
        );
        $page = 1;
        $quantity = 5;
        $repository = $this->prophesize(UserRepository::class);
        $repository->getPage($page, $quantity)
            ->shouldNotBeCalled();

        $this->expectException(AccessDeniedException::class);
        $controller->getUsersAction(
            $repository->reveal(),
            $page,
            $quantity
        );
    }

    public function testCreateUserAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            null,
            UserVoter::CREATE,
            true
        );
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist($user)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $encoder = $this->prophesize(UserPasswordEncoderInterface::class);
        $encoder
            ->encodePassword($user, $user->getPassword())
            ->willReturn("encoded-password")
            ->shouldBeCalled()
        ;

        $response = $controller->createUserAction($user, $manager->reveal(), $violations, $encoder->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $this->checkUser($user, $responseUser);
    }

    public function testCreateUserAction_accessDenied()
    {
        $user = $this->createUser();
        $controller = $this->createUserController(
            $user,
            null,
            UserVoter::CREATE,
            false
        );
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist($user)->shouldNotBeCalled();
        $manager->flush()->shouldNotBeCalled();
        $encoder = $this->prophesize(UserPasswordEncoderInterface::class);
        $encoder
            ->encodePassword($user, $user->getPassword())
            ->shouldNotBeCalled()
        ;

        $this->expectException(AccessDeniedException::class);
        $controller->createUserAction($user, $manager->reveal(), $violations, $encoder->reveal());
    }

    public function testEditUserAction()
    {
        $user = $this->createUser();
        $modifiedUser = $this->createUser(
            null,
            "test-modified-name",
            "modified.user@test.com",
            "test-modified-password",
            ["ROLE_USER", "ROLE_ADMIN"]
        );
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::UPDATE,
            true
        );
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");
        $encoder = $this->prophesize(UserPasswordEncoderInterface::class);
        $encoder
            ->encodePassword($modifiedUser, $modifiedUser->getPassword())
            ->willReturn("encoded-password")
            ->shouldBeCalled()
        ;

        $response = $controller->editUserAction($user, $modifiedUser, $manager, $encoder->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $modifiedUser->setPassword(null);
        $this->checkUser($modifiedUser, $responseUser, false);
    }

    public function testEditUserAction_accessDenied()
    {
        $user = $this->createUser();
        $modifiedUser = $this->createUser(
            null,
            "test-modified-name",
            "modified.user@test.com",
            "test-modified-password",
            ["ROLE_USER", "ROLE_ADMIN"]
        );
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::UPDATE,
            false
        );
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");
        $encoder = $this->prophesize(UserPasswordEncoderInterface::class);
        $encoder
            ->encodePassword($modifiedUser, $modifiedUser->getPassword())
            ->shouldNotBeCalled()
        ;

        $this->expectException(AccessDeniedException::class);
        $controller->editUserAction($user, $modifiedUser, $manager, $encoder->reveal());
    }

    public function testDeleteUserAction()
    {
        $user = $this->createUser();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($user)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::DELETE,
            true
        );

        $response = $controller->deleteUserAction($user, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    public function testDeleteUserAction_accessDenied()
    {
        $user = $this->createUser();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($user)->shouldNotBeCalled();
        $manager->flush()->shouldNotBeCalled();
        $controller = $this->createUserController(
            $user,
            $user,
            UserVoter::DELETE,
            false
        );

        $this->expectException(AccessDeniedException::class);
        $controller->deleteUserAction($user, $manager->reveal());
    }

    // Private

    private function createUserController(
        ?User $user = null,
        $entity = null,
        ?string $voterAction = null,
        bool $isGranted = true
    )
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new UserController();
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

    private function createUser(
        ?int $id = null,
        string $name = "test-user-name",
        string $email = "user@test.com",
        string $password = "test-password",
        array $roles = ["ROLE_USER"]
    ) {
        $user = (new User())
            ->setName($name)
            ->setEmail($email)
            ->setPassword($password)
            ->setRoles($roles)
        ;

        if ($id) {
            $this->setId($user, $id);
        }

        return $user;
    }

    private function checkUser($expectedUser, $responseUser, bool $checkId = true)
    {
        if ($checkId) {
            $this->assertEquals($expectedUser->getId(), $responseUser->getId());
        }
        $this->assertEquals($expectedUser->getName(), $responseUser->getName());
        $this->assertEquals($expectedUser->getEmail(), $responseUser->getEmail());
        $this->assertEquals($expectedUser->getPassword(), $responseUser->getPassword());
        $this->assertEquals($expectedUser->getRoles(), $responseUser->getRoles());
    }
}