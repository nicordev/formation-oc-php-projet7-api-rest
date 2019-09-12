<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\Cache\CacheTool;
use App\Helper\HeaderGenerator;
use App\Helper\ViolationsTrait;
use App\Repository\UserRepository;
use App\Security\UserVoter;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Swagger\Annotations as SWG;

class UserController extends AbstractFOSRestController
{
    use ViolationsTrait;

    public const CACHE_EXPIRATION = "+10 minutes";
    public const TAG_CACHE_LIST = "user_list";

    /**
     * Get the profile of a user.
     *
     * If you are not admin, you'll have access to your profile only.
     *
     * @Get(
     *     path = "/api/users/{id}",
     *     name = "user_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Return the detail of a user"
     * )
     */
    public function getUserAction(User $user)
    {
        $this->denyAccessUnlessGranted(UserVoter::READ, $user);

        $headers = HeaderGenerator::generateShowHeaders(
            self::CACHE_EXPIRATION,
            $user
        );

        return $this->view($user, Response::HTTP_OK ,$headers);
    }

    /**
     * Get the list of all registered users (admin only)
     *
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
     * @SWG\Response(
     *     response = 200,
     *     description = "Return the list of all users (admin only)"
     * )
     */
    public function getUsersAction(
        UserRepository $repository,
        int $page = 1,
        int $quantity = 5
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LIST);

        $requestedProperties = [
            "id",
            "name",
            "email",
            "roles"
        ];
        $paginatedUsers = $repository->getPage(
            $page,
            $quantity,
            $requestedProperties
        );

        if (!$paginatedUsers) {
            return $this->view(null, Response::HTTP_NO_CONTENT);
        }

        $users = $paginatedUsers[UserRepository::KEY_PAGING_ENTITIES];

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($users),
            "user_list",
            [
                "page" => $page,
                "quantity" => $quantity
            ],
            $page,
            $quantity,
            $paginatedUsers[UserRepository::KEY_PAGING_PAGES_COUNT]
        );

        $headers = HeaderGenerator::generateListHeaders(
            self::CACHE_EXPIRATION,
            "User"
        );

        return $this->view($paginatedRepresentation, Response::HTTP_OK, $headers);
    }

    /**
     * Create a user (admin only)
     *
     * @Post(
     *     "/api/users",
     *     name = "user_create"
     * )
     * @ParamConverter(
     *     "newUser",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "user_create"}
     *     }
     * )
     * @View()
     * @SWG\Response(
     *     response = 201,
     *     description = "Create a user (admin only)"
     * )
     */
    public function createUserAction(
        User $newUser,
        EntityManagerInterface $manager,
        ConstraintViolationListInterface $violations,
        UserPasswordEncoderInterface $encoder,
        CacheTool $cacheTool
    ) {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        $this->handleViolations($violations);

        $encoded = $encoder->encodePassword($newUser, $newUser->getPassword());
        $newUser->setPassword($encoded);
        if ($newUser->getRoles() === ["ROLE_USER"]) {
            $newUser->setRoles(["ROLE_USER"]);
        }
        $manager->persist($newUser);
        $manager->flush();
        $newUser->setPassword(null);
        // Cache
        $cacheTool->invalidateTags([self::TAG_CACHE_LIST]);

        return $this->view($newUser, Response::HTTP_CREATED);
    }

    /**
     * Modify a user
     *
     * If you are not admin, you'll be able to modify your profile only.
     *
     * @Post(
     *     "/api/users/{id}",
     *     name = "user_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedUser", converter="fos_rest.request_body")
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Update the current user"
     * )
     */
    public function editUserAction(
        User $user,
        User $modifiedUser,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder,
        CacheTool $cacheTool
    ) {
        $this->denyAccessUnlessGranted(UserVoter::UPDATE, $user);

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
        $user->setPassword(null);
        // Cache
        $cacheTool->invalidateTags([self::TAG_CACHE_LIST]);

        return $this->view($user, Response::HTTP_OK);
    }

    /**
     * Delete a user account
     *
     * If you are not admin, you'll be able to delete your profile only.
     *
     * @Delete(
     *     "/api/users/{id}",
     *     name = "user_delete"
     * )
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Delete the current user"
     * )
     */
    public function deleteUserAction(
        User $user,
        EntityManagerInterface $manager,
        CacheTool $cacheTool
    )
    {
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $id = $user->getId();
        $manager->remove($user);
        $manager->flush();
        // Cache
        $cacheTool->invalidateTags([self::TAG_CACHE_LIST]);

        return $this->view(null, Response::HTTP_OK);
    }
}
