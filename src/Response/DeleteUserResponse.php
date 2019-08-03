<?php

namespace App\Response;


use App\Entity\User;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * Class DeleteUserResponse
 * @package App\Response
 * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "user_create",
 *          absolute = true
 *      )
 * )
 */
class DeleteUserResponse
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    public $message;
    /**
     * @var string
     * @Serializer\Type("App\Entity\User")
     */
    public $entity;

    public function __construct(User $entity, string $message = null)
    {
        $this->message = "The user {$entity->getName()} has been deleted.";
        if ($message) {
            $this->message .= " $message";
        }
        $this->entity = $entity;
    }
}