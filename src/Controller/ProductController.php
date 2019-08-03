<?php

namespace App\Controller;

use App\Entity\Product;
use App\Exception\ResourceValidationException;
use App\Helper\ViolationsTrait;
use App\Repository\ProductRepository;
use App\Response\DeleteProductResponse;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ProductController extends AbstractFOSRestController
{
    use ViolationsTrait;

    /**
     * @Get(
     *     path = "/api/products/{id}",
     *     name = "product_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @Get(
     *     path = "/api/products/{model}",
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
     *     path = "/api/products",
     *     name = "product_list"
     * )
     * @Rest\QueryParam(
     *     name = "property",
     *     requirements = "id|price|quantity|brand|model",
     *     default = "price",
     *     description = "Property name required to order results or do a search"
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
     *     name = "exact",
     *     default = "true",
     *     description = "If false, every products containing the search parameter will be returned. The search will be based on the property parameter value. If true, only products having the exact value of the search parameter will be returned. "
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
     * @param ProductRepository $repository
     * @param string $property
     * @param string $order
     * @param string|null $search
     * @param string $exact
     * @param int $page
     * @param int $quantity
     * @return Response
     */
    public function getProductsAction(
        ProductRepository $repository,
        string $property,
        string $order,
        ?string $search,
        string $exact,
        int $page,
        int $quantity
    ) {
        if (!empty($search)) {
            if (in_array($property, ["brand", "model"])) {
                $criteria = [$property => $search];
            } else {
                return new Response("Can not use search parameter, the property is either missing or wrong.", Response::HTTP_NOT_ACCEPTABLE);
            }
        }

        $exactValue = $exact !== "false";

        $paginatedProducts = $repository->getPage(
            $page,
            $quantity,
            [$property => strtoupper($order)],
            $criteria ?? null,
            $exactValue
        );
        $products = $paginatedProducts[ProductRepository::KEY_PAGING_ENTITIES];

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($products),
            "product_list",
            [
                "property" => $property,
                "order" => $order,
                "search" => $search,
                "exact" => $exact,
                "page" => $page,
                "quantity" => $quantity
            ],
            $page,
            $quantity,
            $paginatedProducts[ProductRepository::KEY_PAGING_COUNT]
        );

        $view = $this->view($paginatedRepresentation, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/api/admin/products",
     *     name = "product_create"
     * )
     * @ParamConverter(
     *     "newProduct",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "Create"}
     *     }
     * )
     */
    public function createAction(Product $newProduct, EntityManagerInterface $manager, ConstraintViolationListInterface $violations)
    {
        $this->handleViolations($violations);

        $manager->persist($newProduct);
        $manager->flush();
        $view = $this->view(
            $newProduct,
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl(
                    'product_show_id',
                    ['id' => $newProduct->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]
        );

        return $this->handleView($view);
    }

    /**
     * @Post(
     *     "/api/admin/products/{id}",
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
     *     "/api/admin/products/{id}",
     *     name = "product_delete"
     * )
     */
    public function deleteAction(Product $product, EntityManagerInterface $manager)
    {
        $manager->remove($product);
        $manager->flush();
        $view = $this->view(new DeleteProductResponse($product), Response::HTTP_OK);

        return $this->handleView($view);
    }
}
