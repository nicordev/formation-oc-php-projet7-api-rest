<?php

namespace App\Helper\AnnotationReadingTool;


use Doctrine\Common\Annotations\Reader;

class AnnotationReadingTool
{
    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * Get a specific annotation from the given class
     *
     * @param string $annotationClass
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    public function getClassAnnotation(string $annotationClass, string $class)
    {
        return $this->filterAnnotations($this->getClassAnnotations($class), $annotationClass);
    }

    /**
     * Get a specific annotation from the given property
     *
     * @param string $annotationClass
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    public function getPropertyAnnotation(string $annotationClass, string $class, string $method)
    {
        return $this->filterAnnotations($this->getPropertyAnnotations($class, $method), $annotationClass);
    }

    /**
     * Get a specific annotation from the given method
     *
     * @param string $annotationClass
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    public function getMethodAnnotation(string $annotationClass, string $class, string $method)
    {
        return $this->filterAnnotations($this->getMethodAnnotations($class, $method), $annotationClass);
    }

    /**
     * Get all annotations from a class
     *
     * @param string $class
     * @return array
     * @throws \ReflectionException
     */
    public function getClassAnnotations(string $class)
    {
        $reflector = new \ReflectionClass($class);

        return $this->annotationReader->getClassAnnotations($reflector);
    }

    /**
     * Get all annotations from a property
     *
     * @param string $class
     * @param string $property
     * @return array
     * @throws \ReflectionException
     */
    public function getPropertyAnnotations(string $class, string $property)
    {
        $reflector = new \ReflectionProperty($class, $property);

        return $this->annotationReader->getPropertyAnnotations($reflector);
    }

    /**
     * Get all annotations from a method
     *
     * @param string $class
     * @param string $method
     * @return array
     * @throws \ReflectionException
     */
    public function getMethodAnnotations(string $class, string $method)
    {
        $reflector = new \ReflectionMethod($class, $method);

        return $this->annotationReader->getMethodAnnotations($reflector);
    }

    // Private

    private function filterAnnotations(array $annotations, string $requestedAnnotation)
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $requestedAnnotation) {
                return $annotation;
            }
        }
    }
}