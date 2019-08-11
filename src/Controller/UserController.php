<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\ViolationsTrait;
use App\Repository\UserRepository;
use App\Response\DeleteUserResponse;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UserController extends AbstractFOSRestController
{
    use ViolationsTrait;

    /**
     * @Get(
     *     path = "/api/users/{id}",
     *     name = "user_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @Get(
     *     path = "/api/users/{name}",
     *     name = "user_show_name"
     * )
     * @View()
     */
    public function getUserAction(User $user)
    {
        return $this->view($user, Response::HTTP_OK);
    }

    /**
     * @Get(
     *     path = "/api/users",
     *     name = "user_list"
     * )
     * @Rest\QueryParam(
     *     name = "page",
     *     requirements = "\d+",
     *     default = 1,
     *     description = "Page number"
     * )
     * @Rest\QueryParam(
     *     name = "quantity",
     *     requirements = "\d+",
     *     default = 5,
     *     description = "Number of items per page"
     * )
     * @View()
     */
    public function getUsersAction(
        UserRepository $repository,
        int $page = 1,
        int $quantity = 5
    ) {
        $paginatedUsers = $repository->getPage($page, $quantity);
        $users = $paginatedUsers[UserRepository::KEY_PAGING_ENTITIES];

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($users),
            "product_list",
            [
                "page" => $page,
                "quantity" => $quantity
            ],
            $page,
            $quantity,
            $paginatedUsers[UserRepository::KEY_PAGING_COUNT]
        );

        return $this->view($paginatedRepresentation, Response::HTTP_OK);
    }

    /**
     * @Post(
     *     "/api/users",
     *     name = "user_create"
     * )
     * @ParamConverter(
     *     "newUser",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "Create"}
     *     }
     * )
     * @View()
     */
    public function createUserAction(
        User $newUser,
        EntityManagerInterface $manager,
        ConstraintViolationListInterface $violations,
        UserPasswordEncoderInterface $encoder
    ) {
        $this->handleViolations($violations);

        $encoded = $encoder->encodePassword($newUser, $newUser->getPassword());
        $newUser->setPassword($encoded);
        $manager->persist($newUser);
        $manager->flush();

        return $this->view($newUser, Response::HTTP_CREATED);
    }

    /**
     * @Post(
     *     "/api/users/{id}",
     *     name = "user_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedUser", converter="fos_rest.request_body")
     * @View()
     */
    public function editUserAction(
        User $user,
        User $modifiedUser,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ) {
        if ($modifiedUser->getName() !== null) {
            $user->setName($modifiedUser->getName());
        }
        if ($modifiedUser->getEmail() !== null) {
            $user->setEmail($modifiedUser->getEmail());
        }
        if (!empty($modifiedUser->getPassword())) {
            $encoded = $encoder->encodePassword($modifiedUser, $modifiedUser->getPassword());
            $user->setPassword($encoded);
        }
        if (!empty($modifiedUser->getRoles() !== null)) {
            $user->setRoles($modifiedUser->getRoles());
        }

        $manager->flush();

        return $this->view($user, Response::HTTP_ACCEPTED);
    }

    /**
     * @Delete(
     *     "/api/users/{id}",
     *     name = "user_delete"
     * )
     */
    public function deleteUserAction(User $user, EntityManagerInterface $manager)
    {
        $manager->remove($user);
        $manager->flush();
        $view = $this->view(new DeleteUserResponse($user), Response::HTTP_OK);

        return $this->handleView($view);
    }
}
