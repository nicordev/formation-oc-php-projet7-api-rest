<?php

namespace App\Tests\Controller;


use App\Entity\User;
use App\Response\DeleteUserResponse;
use App\Tests\HelperTest\HelperTestTrait;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    use HelperTestTrait;

    private $client;
    private $testUser;
    /**
     * @var Serializer
     */
    private $serializer;

    public function setUp()
    {
        if (!$this->client) {
            $this->client = static::createClient();
        }
        if (!$this->testUser) {
            $this->testUser = $this->createTestUser();
        }
        if (!$this->serializer) {
            $this->serializer = SerializerBuilder::create()->build();
        }
    }

    public function tearDown()
    {
        if ($this->testUser) {
            $this->deleteEntity($this->testUser);
            $this->testUser = null;
        }
    }

    public function testGetUserAction()
    {
        $this->client->request(
            'GET',
            "/api/users/{$this->testUser->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $user = $this->serializer->deserialize($response->getContent(), User::class, 'json');
        $this->checkEntity($user, $this->testUser, true);
    }

    public function testDeleteAction()
    {
        $this->client->request(
            'DELETE',
            "/api/users/{$this->testUser->getId()}",
            [],
            [],
            [
                $this->keyHeaderToken => $this->testToken
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseContentObject = $this->serializer->deserialize($response->getContent(), DeleteUserResponse::class, "json");
        $this->checkEntity($responseContentObject->entity, $this->testUser, true);
    }

    // Private

    /**
     * Create a test user
     *
     * @return mixed
     */
    private function createTestUser()
    {
        $user = new User();

        $user->setName("test-name");
        $user->setEmail("test@email.com");
        $user->setPassword("test-password");
        $user->setApiToken($this->testToken);
        $user->setRoles(["ROLE_USER"]);

        return $this->saveEntity($user);
    }
}