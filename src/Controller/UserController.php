<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\ViolationsTrait;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UserController extends AbstractFOSRestController
{
    use ViolationsTrait;

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

    /**
     * @Post(
     *     "/users",
     *     name = "user_create"
     * )
     * @ParamConverter(
     *     "newUser",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "Create"}
     *     }
     * )
     */
    public function createAction(User $newUser, EntityManagerInterface $manager, ConstraintViolationListInterface $violations)
    {
        $this->handleViolations($violations);

        $manager->persist($newUser);
        $manager->flush();
        $view = $this->view(
            $newUser,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl(
                'product_show_id',
                ['id' => $newUser->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )]
        );

        return $this->handleView($view);
    }
}
