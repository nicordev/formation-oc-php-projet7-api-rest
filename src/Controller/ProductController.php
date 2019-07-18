<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

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
     * @Route(
     *     "/products/{id}",
     *     name = "product_show",
     *     requirements = {"id": "\d+"}
     * )
     */
    public function showAction(Product $product)
    {
        $data = $this->serializer->serialize($product, "json");
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route(
     *     "/products",
     *     name = "product_create"
     * )
     * @Method({"POST"})
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
