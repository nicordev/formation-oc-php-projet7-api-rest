<?php

namespace App\Helper\AnnotationTool;


use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationTool
{
    public static function getClassAnnotation(string $class)
    {
        $reader = new AnnotationReader();
        $reflector = new \ReflectionClass($class);

        return $reader->getClassAnnotations($reflector);
    }

    public static function getPropertyAnnotation($class, $property)
    {
        $reader = new AnnotationReader;
        $reflector = new \ReflectionProperty($class, $property);

        return $reader->getPropertyAnnotations($reflector);
    }

    public static function getMethod($class, $method)
    {
        $reader = new AnnotationReader();
        $reflector = new \ReflectionMethod($class, $method);

        return $reader->getMethodAnnotations($reflector);
    }
}