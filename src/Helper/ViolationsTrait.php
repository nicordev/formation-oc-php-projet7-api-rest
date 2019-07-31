<?php

namespace App\Helper;


use App\Exception\ResourceValidationException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ViolationsTrait
{
    public function handleViolations(ConstraintViolationListInterface $violations)
    {
        if (count($violations)) {
            $message = "The JSON sent contains invalid data. ";

            foreach ($violations as $violation) {
                $message .= sprintf(
                    "Field %s: %s ",
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }

            throw new ResourceValidationException($message);
        }
    }
}