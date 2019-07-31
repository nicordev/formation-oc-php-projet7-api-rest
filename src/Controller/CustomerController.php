<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CustomerController extends AbstractFOSRestController
{
    /**
     * @Get(
     *     path = "/customers/{id}",
     *     name = "customer_show",
     *     requirements = {"id": "\d+"}
     * )
     */
    public function getCustomerAction(Customer $customer)
    {
        $view = $this->view($customer, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @Get(
     *     path = "/customers",
     *     name = "customer_list"
     * )
     */
    public function getCustomersAction(CustomerRepository $repository)
    {
        $customers = $repository->findAll();
        $view = $this->view($customers, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/customers",
     *     name = "customer_create"
     * )
     * @ParamConverter(
     *     "newCustomer",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "Create"}
     *     }
     * )
     */
    public function createAction(Customer $newCustomer, EntityManagerInterface $manager, ConstraintViolationListInterface $violations)
    {
        $data = $request->getContent();
        $newCustomer = $this->serializer->deserialize($data, "App\\Entity\\Customer", "json");
        $manager->persist($newCustomer);
        $manager->flush();

        $view = $this->view($newCustomer, Response::HTTP_CREATED);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/customers/{id}",
     *     name = "customer_edit",
     *     requirements = {"id": "\d+"}
     * )
     */
    public function editAction(Request $request, Customer $customer, EntityManagerInterface $manager)
    {
        $data = $request->getContent();
        $editedCustomer = $this->serializer->deserialize($data, Customer::class, "json");

        $this->updateProperties($customer, $editedCustomer);

        $manager->flush();
        $view = $this->view($customer, Response::HTTP_ACCEPTED);

        return $this->handleView($view);
    }

    /**
     * @Delete(
     *     "/customers/{id}",
     *     name = "customer_delete"
     * )
     */
    public function deleteAction(Customer $customer, EntityManagerInterface $manager)
    {
        $manager->remove($customer);
        $manager->flush();

        $view = $this->view($customer, Response::HTTP_OK);

        return $this->handleView($view);
    }
}
