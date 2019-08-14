<?php

namespace App\Tests\TestHelperTrait;


trait UnitTestHelperTrait
{
    private function setId($entity, int $id)
    {
        $reflectionEntity = new \ReflectionObject($entity);
        $reflectionId = $reflectionEntity->getProperty("id");
        $reflectionId->setAccessible(true);
        $reflectionId->setValue($entity, $id);
    }
}