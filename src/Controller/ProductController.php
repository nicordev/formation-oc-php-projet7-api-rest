<?php

namespace App\Controller;


use App\Entity\Product;
use App\Exception\ResourceValidationException;
use App\Helper\CacheTool;
use App\Helper\ViolationsTrait;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Swagger\Annotations as SWG;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractFOSRestController
{
    use ViolationsTrait;

    public const TAG_CACHE_LIST = "product_list";
    public const CACHE_EXPIRATION = "+10 minutes";

    /**
     * Consult the detail of a particular phone
     *
     * @Get(
     *     path = "/api/products/{id}",
     *     name = "product_show_id",
     *     requirements = {"id": "\d+"}
     * )
     * @Get(
     *     path = "/api/products/{model}",
     *     name = "product_show_model"
     * )
     * @View()
     * @Cache(
     *     public = true,
     *     expires = "+10 minutes",
     *     lastModified = "product.getUpdatedAt()",
     *     Etag = "'Product' ~ product.getId() ~ product.getUpdatedAt().getTimestamp()"
     * )
     * @SWG\Response(
     *     response = 200,
     *     description = "Return the detail of a product"
     * )
     */
    public function getProductAction(Product $product)
    {
        return $this->view($product, Response::HTTP_OK);
    }

    /**
     * Consult the list of all phones
     *
     * @Get(
     *     path = "/api/products",
     *     name = "product_list"
     * )
     * @Rest\QueryParam(
     *     name = "property",
     *     requirements = "id|price|quantity|brand|model",
     *     strict = true,
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
     * @View()
     * @SWG\Response(
     *     response = 200,
     *     description = "Return the list of all products available"
     * )
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
                return $this->view("Can not use search parameter, the property is either missing or wrong.", Response::HTTP_NOT_ACCEPTABLE);
            }
        }

        $exactValue = $exact !== "false";
        $requestedProperties = [
            "id",
            "model",
            "brand",
            "price",
            "quantity"
        ];
        $paginatedProducts = $repository->getPage(
            $page,
            $quantity,
            $requestedProperties,
            [$property => strtoupper($order)],
            $criteria ?? null,
            $exactValue
        );

        if (!$paginatedProducts) {
            return $this->view(null, Response::HTTP_NO_CONTENT);
        }

        $paginatedRepresentation = new PaginatedRepresentation(
            new CollectionRepresentation($paginatedProducts[ProductRepository::KEY_PAGING_ENTITIES]),
            "product_list",
            [
                "property" => $property,
                "order" => $order,
                "search" => $search,
                "exact" => $exact,
                "page" => $paginatedProducts[ProductRepository::KEY_PAGING_CURRENT_PAGE],
                "quantity" => $paginatedProducts[ProductRepository::KEY_PAGING_ITEMS_PER_PAGE]
            ],
            $paginatedProducts[ProductRepository::KEY_PAGING_CURRENT_PAGE],
            $paginatedProducts[ProductRepository::KEY_PAGING_ITEMS_PER_PAGE],
            $paginatedProducts[ProductRepository::KEY_PAGING_PAGES_COUNT],
            null,
            null,
            true,
            $paginatedProducts[ProductRepository::KEY_PAGING_ITEMS_COUNT]
        );

        return $this->view($paginatedRepresentation, Response::HTTP_OK);
    }

    /**
     * Add a phone (admin only)
     *
     * @Post(
     *     "/api/products",
     *     name = "product_create"
     * )
     * @ParamConverter(
     *     "newProduct",
     *     converter="fos_rest.request_body",
     *     options = {
     *          "validator" = {"groups" = "product_create"}
     *     }
     * )
     * @View()
     * @IsGranted("ROLE_ADMIN")
     * @SWG\Response(
     *     response = 201,
     *     description = "Create a product (admin only)"
     * )
     */
    public function createProductAction(
        Product $newProduct,
        EntityManagerInterface $manager,
        ConstraintViolationListInterface $violations
    ) {
        $this->handleViolations($violations);

        $manager->persist($newProduct);
        $manager->flush();
        // Cache
//        $cache->invalidateTags([self::TAG_CACHE_LIST]);

        return $this->view($newProduct, Response::HTTP_CREATED);
    }

    /**
     * Modify a phone (admin only)
     *
     * @Post(
     *     "/api/products/{id}",
     *     name = "product_edit",
     *     requirements = {"id": "\d+"}
     * )
     * @ParamConverter("modifiedProduct", converter="fos_rest.request_body")
     * @View()
     * @IsGranted("ROLE_ADMIN")
     * @SWG\Response(
     *     response = 200,
     *     description = "Update a product (admin only)"
     * )
     */
    public function editProductAction(
        Product $product,
        Product $modifiedProduct,
        EntityManagerInterface $manager
    ) {
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
        // Cache
//        $cache->invalidateTags([self::TAG_CACHE_LIST]);

        return $this->view($product, Response::HTTP_OK);
    }

    /**
     * Delete a phone (admin only)
     *
     * @Delete(
     *     "/api/products/{id}",
     *     name = "product_delete"
     * )
     * @View()
     * @IsGranted("ROLE_ADMIN")
     * @SWG\Response(
     *     response = 200,
     *     description = "Delete a product (admin only)"
     * )
     */
    public function deleteProductAction(
        Product $product,
        EntityManagerInterface $manager
    )
    {
        $manager->remove($product);
        $manager->flush();
        // Cache
//        $cache->invalidateTags([self::TAG_CACHE_LIST]);

        return  $this->view(null, Response::HTTP_OK);
    }
}
