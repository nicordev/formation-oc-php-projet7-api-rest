<?php

namespace App\Controller;

use App\Annotation\CacheTool;
use App\Entity\Customer;
use App\Helper\ViolationsTrait;
use App\Repository\CustomerRepository;
use App\Repository\PaginatedRepository;
use App\Security\CustomerVoter;
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
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;

class CustomerController extends AbstractFOSRestController
{
    use ViolationsTrait;

    /**
     * Get the detail of a customer of your shop
     *
     * @Get(
     *     path = "/api/customers/{id}",
     *     name = "customer_show",
     *     requirements = {"id": "\d+"}
     * )
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Return the detail of a customer"
     * )
     * @CacheTool(
     *     isCacheable = true,
     *     isPrivate = true,
     *     tags = {"customer_show"}
     * )
     */
    public function getCustomerAction(Customer $customer)
    {
        $this->denyAccessUnlessGranted(CustomerVoter::READ, $customer);

        return $this->view($customer, Response::HTTP_OK);
    }

    /**
     * Get the list of all the customers of your shop
     *
     * @Get(
     *     path = "/api/customers",
     *     name = "customer_list"
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
     *     description = "Return the list of all customers of the current user"
     * )
     * @CacheTool(
     *     isCacheable = true,
     *     isPrivate = true,
     *     tags = {"customer_list"}
     * )
     */
    public function getCustomersAction(
        CustomerRepository $repository,
        int $page,
        int $quantity
    ) {
        $requestedProperties = [
            "id",
            "name",
            "surname",
            "email",
            "address"
        ];
        $paginatedCustomers = $repository->getPage(
            $page,
            $quantity,
            $requestedProperties,
            null,
            ["user" => $this->getUser()->getId()]
        );

        if (!$paginatedCustomers) {
            return $this->view(null, Response::HTTP_NO_CONTENT);
        }

        $customers = $paginatedCustomers[PaginatedRepository::KEY_PAGING_ENTITIES];
        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($customers),
            "customer_list",
            [
                "page" => $page,
                "quantity" => $quantity
            ],
            $page,
            $quantity,
            $paginatedCustomers[PaginatedRepository::KEY_PAGING_PAGES_COUNT]
        );

        return $this->view($paginatedRepresentation, Response::HTTP_OK);
    }

    /**
     * Add a customer to your list of customers
     *
     * @Post(
     *     "/api/customers",
     *     name = "customer_create"
     * )
     * @ParamConverter(
     *     "newCustomer",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "customer_create"}
     *     }
     * )
     * @View()
     * @SWG\Response(
     *     response = 201,
     *     description = "Return the list of all customers of the current user"
     * )
     * @CacheTool(
     *     tagsToInvalidate = {"customer_list"}
     * )
     */
    public function createCustomerAction(
        Customer $newCustomer,
        EntityManagerInterface $manager,
        ConstraintViolationListInterface $violations
    ) {
        $this->handleViolations($violations);

        $newCustomer->setUser($this->getUser());
        $manager->persist($newCustomer);
        $manager->flush();

        return $this->view($newCustomer, Response::HTTP_CREATED);
    }

    /**
     * Modify the information on one of your customers
     *
     * @Post(
     *     "/api/customers/{id}",
     *     name = "customer_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedCustomer", converter="fos_rest.request_body")
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Update a customer of the current user"
     * )
     * @CacheTool(
     *     tagsToInvalidate = {"customer_list", "customer_show"}
     * )
     */
    public function editCustomerAction(
        Customer $customer,
        Customer $modifiedCustomer,
        EntityManagerInterface $manager
    ) {
        $this->denyAccessUnlessGranted(CustomerVoter::UPDATE, $customer);

        if ($modifiedCustomer->getName() !== null) {
            $customer->setName($modifiedCustomer->getName());
        }
        if ($modifiedCustomer->getSurname() !== null) {
            $customer->setSurname($modifiedCustomer->getSurname());
        }
        if ($modifiedCustomer->getEmail() !== null) {
            $customer->setEmail($modifiedCustomer->getEmail());
        }
        if ($modifiedCustomer->getAddress() !== null) {
            $customer->setAddress($modifiedCustomer->getAddress());
        }

        $manager->flush();

        return $this->view($customer, Response::HTTP_OK);
    }

    /**
     * Delete a customer from your list of customers
     *
     * @Delete(
     *     "/api/customers/{id}",
     *     name = "customer_delete"
     * )
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Delete a customer of the current user"
     * )
     * @CacheTool(
     *     tagsToInvalidate = {"customer_list", "customer_show"}
     * )
     */
    public function deleteCustomerAction(
        Customer $customer,
        EntityManagerInterface $manager
    ) {
        $this->denyAccessUnlessGranted(CustomerVoter::DELETE, $customer);

        $manager->remove($customer);
        $manager->flush();

        return $this->view(null, Response::HTTP_OK);
    }
}
