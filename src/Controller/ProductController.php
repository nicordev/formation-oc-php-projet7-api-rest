<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;

class ProductController extends AbstractController
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
        $data = $this->serializer->serialize($product, "json");
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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
        $data = $this->serializer->serialize($products, "json");
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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

        return new Response("", Response::HTTP_CREATED);
    }
}
