<?php

namespace App\Controller;


use App\Entity\Product;
use App\Exception\ResourceValidationException;
use App\Helper\ViolationsTrait;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
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

class ProductController extends AbstractFOSRestController
{
    use ViolationsTrait;

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
     * @View(serializerGroups = {"product_detail"})
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
     * @View(serializerGroups = {"product_list"})
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
        $paginatedProducts = $repository->getPage(
            $page,
            $quantity,
            [$property => strtoupper($order)],
            $criteria ?? null,
            $exactValue
        );
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
     *     response = 202,
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

        return $this->view($product, Response::HTTP_ACCEPTED);
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
    public function deleteProductAction(Product $product, EntityManagerInterface $manager)
    {
        $id = $product->getId();
        $manager->remove($product);
        $manager->flush();

        return  $this->view("Product {$id} deleted.", Response::HTTP_OK);
    }
}
