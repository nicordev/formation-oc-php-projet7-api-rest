<?php

namespace App\Helper;


use ReflectionObject;

trait ObjectEditorTrait
{
    public function updateProperties($initialEntity, $modifiedEntity)
    {
        $reflectionEntity = new ReflectionObject($initialEntity);

        $properties = $reflectionEntity->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $modifiedProperty = $this->getPropertyValue($modifiedEntity, $propertyName);

            if ($modifiedProperty) {
                if ($property->isPublic()) {
                    $initialEntity->$propertyName = $modifiedProperty;

                } else {
                    $setter = "set" . ucfirst($propertyName);

                    if ($reflectionEntity->hasMethod($setter)) {
                        $initialEntity->$setter($modifiedProperty);
                    }
                }
            }
        }
    }

    private function getPropertyValue($object, string $propertyName) {

        if (isset($object->$propertyName) || property_exists(get_class($object), $propertyName)) {
            $reflectionObject = new ReflectionObject($object);
            $reflectionProperty = $reflectionObject->getProperty($propertyName);

            if ($reflectionProperty->isPublic()) {
                return $object->$propertyName;
            }

            $getter = "get" . ucfirst($propertyName);

            if ($reflectionObject->hasMethod($getter)) {
                return $object->$getter($propertyName);
            }
        }

        return null;
    }
}
