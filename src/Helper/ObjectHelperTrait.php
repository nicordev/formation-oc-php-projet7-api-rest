<?php

namespace App\Helper;

use ReflectionObject;

trait ObjectHelperTrait
{
    /**
     * Fetch all property names of an object
     *
     * @param $object
     * @return array
     */
    public function listProperties($object)
    {
        $reflectionObject = new ReflectionObject($object);
        $properties = $reflectionObject->getProperties();
        $propertiesNames = [];

        foreach ($properties as $property) {
            $propertiesNames[] = $property->getName();
        }

        return $propertiesNames;
    }

    /**
     * Update the properties value of an object with the values coming from another object
     *
     * @param $initialEntity
     * @param $modifiedEntity
     * @throws \ReflectionException
     */
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

    /**
     * Get the value of a property from its name
     *
     * @param $object
     * @param string $propertyName
     * @return null
     * @throws \ReflectionException
     */
    private function getPropertyValue($object, string $propertyName)
    {

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
