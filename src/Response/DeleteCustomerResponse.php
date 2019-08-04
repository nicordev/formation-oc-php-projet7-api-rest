<?php

namespace App\Response;


use App\Entity\Customer;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * Class DeleteCustomerResponse
 * @package App\Response
 * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "customer_create",
 *          absolute = true
 *      )
 * )
 */
class DeleteCustomerResponse
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $message;
    /**
     * @var string
     * @Serializer\Type("App\Entity\Customer")
     */
    public $entity;

    public function __construct(Customer $entity, string $message = null)
    {
        $this->message = "The customer {$entity->getName()} has been deleted.";
        if ($message) {
            $this->message .= " $message";
        }
        $this->entity = $entity;
    }
}