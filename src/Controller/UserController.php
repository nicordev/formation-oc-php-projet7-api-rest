<?php

namespace App\Controller;

use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

class UserController extends AbstractFOSRestController
{
    /**
     * @Get(
     *     path = "/users/{id}",
     *     name = "user_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @Get(
     *     path = "/users/{name}",
     *     name = "user_show_name"
     * )
     */
    public function getUserAction(User $user)
    {
        $view = $this->view($user, Response::HTTP_OK);

        return $this->handleView($view);
    }
}
