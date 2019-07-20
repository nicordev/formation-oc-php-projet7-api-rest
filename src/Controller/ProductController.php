<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
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

class ProductController extends AbstractFOSRestController
{
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
     *     path = "/products/{id}",
     *     name = "product_show",
     *     requirements = {"id": "\d+"}
     * )
     */
    public function getProductAction(Product $product)
    {
        $view = $this->view($product, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @Get(
     *     path = "/products",
     *     name = "product_list"
     * )
     */
    public function getProductsAction(ProductRepository $repository)
    {
        $products = $repository->findAll();
        $view = $this->view($products, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/products",
     *     name = "product_create"
     * )
     */
    public function createAction(Request $request, EntityManagerInterface $manager)
    {
        $data = $request->getContent();
        $newProduct = $this->serializer->deserialize($data, "App\\Entity\\Product", "json");
        $manager->persist($newProduct);
        $manager->flush();
        $view = $this->view($newProduct, Response::HTTP_CREATED);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/products/{id}",
     *     name = "product_edit",
     *     requirements = {"id": "\d+"}
     * )
     */
    public function editAction(Request $request, Product $product, EntityManagerInterface $manager)
    {
        $data = $request->getContent();
        $editedProduct = $this->serializer->deserialize($data, "App\\Entity\\Product", "json");

        if ($editedProduct->getBrand() !== null) {
            $product->setBrand($editedProduct->getBrand());
        }

        if ($editedProduct->getModel() !== null) {
            $product->setModel($editedProduct->getModel());
        }

        if ($editedProduct->getPrice() !== null) {
            $product->setPrice($editedProduct->getPrice());
        }

        if ($editedProduct->getQuantity() !== null) {
            $product->setQuantity($editedProduct->getQuantity());
        }

        $manager->flush();
        $view = $this->view($product, Response::HTTP_ACCEPTED);

        return $this->handleView($view);
    }

    /**
     * @Delete(
     *     "/products/{id}",
     *     name = "product_delete"
     * )
     */
    public function deleteAction(Product $product, EntityManagerInterface $manager)
    {
        $manager->remove($product);
        $manager->flush();
        $view = $this->view($product, Response::HTTP_ACCEPTED);

        return $this->handleView($view);
    }
}
