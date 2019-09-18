<?php

namespace App\Annotation;


/**
 * Class CacheAnnotation
 * @package App\Helper\Annotation
 * @Annotation
 */
class CacheTool
{
    public $isCacheable = false;
    public $isPrivate = false;
    public $tagsToInvalidate = [];
}