<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
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

    /**
     * @Get(
     *     path = "/users",
     *     name = "user_list"
     * )
     * @Rest\QueryParam(
     *     name = "page",
     *     requirements = "\d+",
     *     default = 1,
     *     description = "Page number"
     * )
     * @Rest\QueryParam(
     *     name = "itemsPerPage",
     *     requirements = "\d+",
     *     default = 5,
     *     description = "Number of items per page"
     * )
     */
    public function getUsersAction(
        UserRepository $repository,
        int $page = 1,
        int $itemsPerPage = 5
    ) {
        $paginatedUsers = $repository->getPage($page, $itemsPerPage);
        $users = $paginatedUsers[UserRepository::KEY_PAGING_ENTITIES];

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($users),
            "product_list",
            [
                "page" => $page,
                "itemsPerPage" => $itemsPerPage
            ],
            $page,
            $itemsPerPage,
            $paginatedUsers[UserRepository::KEY_PAGING_COUNT]
        );

        $view = $this->view($paginatedRepresentation, Response::HTTP_OK);

        return $this->handleView($view);
    }
}
