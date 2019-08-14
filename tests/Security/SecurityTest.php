<?php

namespace App\Tests\Controller;


use App\Tests\TestHelperTrait\FunctionalTestHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityTest extends WebTestCase
{
    use FunctionalTestHelperTrait;

    public function setUp()
    {
        $this->basicSetUp();
        if (!$this->testUser) {
            $this->testUser = $this->createTestUser();
        }
    }

    public function tearDown()
    {
        if ($this->testUser) {
            $this->deleteEntity($this->testUser);
            $this->testUser = null;
        }
    }

    public function testGetProductsAction_Anonymous()
    {
        $this->client->request(
            'GET',
            "/api/products/"
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testLogin()
    {
        $body = new class ($this->userEmail, $this->password) {
            public $username;
            public $password;

            public function __construct(string $username, string $password)
            {
                $this->username = $username;
                $this->password = $password;
            }
        };
        $this->client->request(
            'POST',
            "/api/login_check",
            [],
            [],
            [
                "CONTENT_TYPE" => "application/json"
            ],
            $this->serializer->serialize($body, 'json')
        );
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseObject = json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasAttribute("token", $responseObject);
    }
}