<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     *     name = "product_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @Get(
     *     path = "/products/{model}",
     *     name = "product_show_model"
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
     * @Rest\QueryParam(
     *     name = "property",
     *     requirements = "id|price|quantity|brand|model",
     *     default = "price",
     *     description = "Sort order (asc or desc)"
     * )
     * @Rest\QueryParam(
     *     name = "order",
     *     requirements = "asc|desc",
     *     default = "asc",
     *     description = "Sort order (asc or desc)"
     * )
     * @Rest\QueryParam(
     *     name = "search",
     *     default = null,
     *     description = "Requested brand or model. The query parameter *property* must be either brand or model to work."
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
     * @param ProductRepository $repository
     * @param string $property
     * @param string $order
     * @param string|null $search
     * @param int $page
     * @param int $itemsPerPage
     * @return Response
     */
    public function getProductsAction(
        ProductRepository $repository,
        string $property = "price",
        string $order = "asc",
        ?string $search = null,
        int $page = 1,
        int $itemsPerPage = 5
    )
    {
        if ($search !== null) {
            if (in_array($property, ["brand", "model"])) {
                $criteria = [$property => $search];
            } else {
                return new Response("Can not use search parameter, the property is either missing or wrong.", Response::HTTP_NOT_ACCEPTABLE);
            }
        }

        $products = $repository->getPage(
            $page,
            $itemsPerPage,
            [$property => strtoupper($order)],
            $criteria ?? null
        );
        $view = $this->view($products, Response::HTTP_OK, ["Count" => count($products)]);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/products",
     *     name = "product_create"
     * )
     * @ParamConverter("newProduct", converter="fos_rest.request_body")
     */
    public function createAction(Product $newProduct, EntityManagerInterface $manager)
    {
        $manager->persist($newProduct);
        $manager->flush();
        $view = $this->view(
            $newProduct,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl(
                'product_show',
                [
                    'id' => $newProduct->getId(),
                    UrlGeneratorInterface::ABSOLUTE_URL
                ]
            )]
        );

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/products/{id}",
     *     name = "product_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedProduct", converter="fos_rest.request_body")
     */
    public function editAction(Product $product, Product $modifiedProduct, EntityManagerInterface $manager)
    {
        if ($modifiedProduct->getBrand() !== null) {
            $product->setBrand($modifiedProduct->getBrand());
        }

        if ($modifiedProduct->getModel() !== null) {
            $product->setModel($modifiedProduct->getModel());
        }

        if ($modifiedProduct->getPrice() !== null) {
            $product->setPrice($modifiedProduct->getPrice());
        }

        if ($modifiedProduct->getQuantity() !== null) {
            $product->setQuantity($modifiedProduct->getQuantity());
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
