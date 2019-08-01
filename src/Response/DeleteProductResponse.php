<?php

namespace App\Response;


use App\Entity\Product;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * Class DeleteProductResponse
 * @package App\Response
 * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "product_create",
 *          absolute = true
 *      )
 * )
 */
class DeleteProductResponse
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $message;
    /**
     * @var string
     * @Serializer\Type("App\Entity\Product")
     */
    public $entity;

    public function __construct(Product $entity, string $message = null)
    {
        $this->message = "The product {$entity->getModel()} has been deleted.";
        if ($message) {
            $this->message .= " $message";
        }
        $this->entity = $entity;
    }
}