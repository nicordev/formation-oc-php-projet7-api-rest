<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Helper\ViolationsTrait;
use App\Repository\CustomerRepository;
use App\Repository\PaginatedRepository;
use App\Security\CustomerVoter;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class CustomerController extends AbstractFOSRestController
{
    use ViolationsTrait;

    /**
     * @Get(
     *     path = "/api/customers/{id}",
     *     name = "customer_show",
     *     requirements = {"id": "\d+"}
     * )
     * @View
     */
    public function getCustomerAction(Customer $customer)
    {
        $this->denyAccessUnlessGranted(CustomerVoter::READ, $customer);

        return $this->view($customer, Response::HTTP_OK);
    }

    /**
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
     */
    public function getCustomersAction(
        CustomerRepository $repository,
        int $page,
        int $quantity
    ) {
        $paginatedCustomers = $repository->getPage(
            $page,
            $quantity,
            null,
            ["user" => $this->getUser()]
        );
        $customers = $paginatedCustomers[PaginatedRepository::KEY_PAGING_ENTITIES];

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($customers),
            "product_list",
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
     * @Post(
     *     "/api/customers",
     *     name = "customer_create"
     * )
     * @ParamConverter(
     *     "newCustomer",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "Create"}
     *     }
     * )
     * @View()
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
     * @Post(
     *     "/api/customers/{id}",
     *     name = "customer_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedCustomer", converter="fos_rest.request_body")
     * @View()
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

        return $this->view($customer, Response::HTTP_ACCEPTED);
    }

    /**
     * @Delete(
     *     "/api/customers/{id}",
     *     name = "customer_delete"
     * )
     * @View()
     */
    public function deleteCustomerAction(Customer $customer, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted(CustomerVoter::DELETE, $customer);

        $id = $customer->getId();
        $manager->remove($customer);
        $manager->flush();

        return $this->view("Customer {$id} deleted.", Response::HTTP_OK);
    }
}
