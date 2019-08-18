<?php

namespace App\Tests\Controller;


use App\Controller\UserController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserVoter;
use App\Tests\TestHelperTrait\UnitTestHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UserControllerTest extends TestCase
{
    use UnitTestHelperTrait;

    public function testGetUserAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController($user, $user, UserVoter::READ);

        $response = $controller->getUserAction($user);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $this->checkUser($user, $responseUser);
    }

    public function testGetUsersAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController($user, null, UserVoter::LIST);
        $page = 1;
        $quantity = 5;

        $repository = $this->prophesize(UserRepository::class);
        $repository->getPage($page, $quantity)->shouldBeCalled();

        $response = $controller->getUsersAction(
            $repository->reveal(),
            $page,
            $quantity
        );
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    public function testCreateUserAction()
    {
        $user = $this->createUser();
        $controller = $this->createUserController($user, null, UserVoter::CREATE);
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
        $controller = $this->createUserController($user, $user, UserVoter::UPDATE);
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
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $modifiedUser->setPassword("encoded-password");
        $this->checkUser($modifiedUser, $responseUser, false);
    }

    public function testDeleteUserAction()
    {
        $user = $this->createUser();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($user)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createUserController($user, $user, UserVoter::DELETE);

        $response = $controller->deleteUserAction($user, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    // Private

    private function createUserController(?User $user = null, $entity = null, ?string $voterAction = null)
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new UserController();
        $controller->setViewHandler($viewHandler);

        if ($user) {
            $controller->setContainer($this->createSecurityContainerMock($user, $entity, $voterAction)->reveal());
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