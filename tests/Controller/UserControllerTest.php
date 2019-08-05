<?php

namespace App\Tests\Controller;


use App\Entity\User;
use App\Response\DeleteUserResponse;
use App\Tests\HelperTest\HelperTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
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

    public function testGetUserAction()
    {
        $this->client->request(
            'GET',
            "/api/users/{$this->testUser->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testUserToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $user = $this->serializer->deserialize($response->getContent(), User::class, 'json');
        $this->checkEntity($user, $this->testUser, true);
    }

    public function testEditUserAction()
    {
        $modifiedUser = new User();
        $modifiedUser->setName("test-modified-name")
            ->setEmail("modified.user@test.com")
            ->setPassword("modified.password")
            ->setRoles(["ROLE_USER", "ROLE_ADMIN"]);
        $body = $this->serializer->serialize($modifiedUser, "json");

        $this->client->request(
            'POST',
            "/api/users/{$this->testUser->getId()}",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json",
                $this->keyHeaderToken => $this->testUserToken
            ],
            $body
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseUser = $this->serializer->deserialize($response->getContent(), User::class, "json");
        $this->assertEquals($modifiedUser->getName(), $responseUser->getName());
        $this->assertEquals($modifiedUser->getEmail(), $responseUser->getEmail());
        $this->assertEquals($modifiedUser->getPassword(), $responseUser->getPassword());
        $this->assertEquals($modifiedUser->getRoles(), $responseUser->getRoles());
        $this->assertEquals($modifiedUser->getApiToken(), $responseUser->getApiToken());
    }

    public function testDeleteAction()
    {
        $this->client->request(
            'DELETE',
            "/api/users/{$this->testUser->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testAdminToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteUserResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testUser, true);
    }
}