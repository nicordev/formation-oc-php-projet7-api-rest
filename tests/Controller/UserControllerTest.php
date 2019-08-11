<?php

namespace App\Tests\Controller;


use App\Controller\UserController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UserControllerTest extends TestCase
{
    public function testGetUserAction()
    {
        $user = $this->createMockedUser();
        $controller = $this->createUserController();

        $response = $controller->getUserAction($user);
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $this->checkUser($user, $responseUser);
    }

    public function testGetUsersAction()
    {
        $controller = $this->createUserController();
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
        $user = $this->createMockedUser();
        $controller = $this->createUserController();
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
        $user = $this->createMockedUser();
        $modifiedUser = $this->createMockedUser(
            22,
            "test-modified-name",
            "modified.user@test.com",
            "test-modified-password",
            ["ROLE_USER", "ROLE_ADMIN"]
        );
        $controller = $this->createUserController();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method("flush");
        $manager->expects($this->never())
            ->method("persist");
        $manager->expects($this->never())
            ->method("remove");
        $encoder = $this->prophesize(UserPasswordEncoderInterface::class);
        $encoder
            ->encodePassword($user, $modifiedUser->getPassword())
            ->willReturn("encoded-password")
            ->shouldBeCalled()
        ;

        $response = $controller->editUserAction($user, $modifiedUser, $manager, $encoder->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);

        $responseUser = $response->getData();
        $this->checkUser($user, $responseUser);
    }

    public function testDeleteUserAction()
    {
        $user = $this->createMockedUser();
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->remove($user)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();
        $controller = $this->createUserController();

        $response = $controller->deleteUserAction($user, $manager->reveal());
        $this->assertObjectHasAttribute("statusCode", $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(View::class, $response);
    }

    // Private

    private function createUserController()
    {
        $viewHandler = $this->createMock(ViewHandler::class);
        $controller = new UserController();
        $controller->setViewHandler($viewHandler);

        return $controller;
    }

    private function createMockedUser(
        int $id = 77,
        string $name = "test-user-name",
        string $email = "user@test.com",
        string $password = "test-password",
        array $roles = ["ROLE_USER"]
    ) {
        $mockedUser = $this->createMock(User::class);
        $mockedUser->method("getId")
            ->willReturn($id);
        $mockedUser->method("getName")
            ->willReturn($name);
        $mockedUser->method("getEmail")
            ->willReturn($email);
        $mockedUser->method("getPassword")
            ->willReturn($password);
        $mockedUser->method("getRoles")
            ->willReturn($roles);
        $mockedUser->method("setPassword");

        return $mockedUser;
    }

    private function checkUser($expectedUser, $responseUser)
    {
        $this->assertEquals($expectedUser->getId(), $responseUser->getId());
        $this->assertEquals($expectedUser->getName(), $responseUser->getName());
        $this->assertEquals($expectedUser->getEmail(), $responseUser->getEmail());
        $this->assertEquals($expectedUser->getPassword(), $responseUser->getPassword());
        $this->assertEquals($expectedUser->getRoles(), $responseUser->getRoles());
    }
}