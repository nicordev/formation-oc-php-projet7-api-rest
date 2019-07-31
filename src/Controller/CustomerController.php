<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Helper\ObjectEditorTrait;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

class CustomerController extends AbstractFOSRestController
{
    use ObjectEditorTrait;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

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
     */
    public function createAction(Request $request, EntityManagerInterface $manager)
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
