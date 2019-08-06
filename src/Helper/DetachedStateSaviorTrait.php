<?php

namespace App\Helper;


trait DetachedStateSaviorTrait
{
    protected function createEntityFromDetachedEntity($detachedEntity)
    {
        $entityClass = $this->getDetachedEntityClassName($detachedEntity);
        $entity = new $entityClass();
        $reflectionEntity = new \ReflectionObject($entity);
        $reflectionDetachedEntity = new \ReflectionObject($detachedEntity);
        $detachedProperties = $reflectionDetachedEntity->getProperties();

        foreach ($detachedProperties as $detachedProperty) {
            $propertyName = $detachedProperty->getName();
            if ($reflectionEntity->hasProperty($propertyName)) {
                $detachedProperty->setAccessible(true);
                $property = $reflectionEntity->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue($entity, $detachedProperty->getValue($detachedEntity));
            }
        }

        return $entity;
    }

    protected function getDetachedEntityClassName($detachedEntity)
    {
        $classNameParts = explode("@", get_class($detachedEntity));

        return $classNameParts[0];
    }
}