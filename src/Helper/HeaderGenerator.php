<?php

namespace App\Helper;


class HeaderGenerator
{
    public const CACHE_DATE_FORMAT = "D, d M Y H:i:s e";
    /**
     * @var \DateTime|null
     */
    private $expires;
    /**
     * @var string|null
     */
    private $etag;
    /**
     * @var \DateTime|null
     */
    private $lastModified;
    /**
     * @var bool
     */
    private $public;

    public function __construct(
        $expires = null,
        ?string $etag = null,
        $lastModified = null,
        bool $public = true
    ) {
        if (is_string($expires)) {
            $expires = new \DateTime($expires);
        }
        $this->expires = $expires;
        $this->etag = $etag;
        if (is_string($lastModified)) {
            $lastModified = new \DateTime($lastModified);
        }
        $this->lastModified = $lastModified;
        $this->public = $public;
    }

    /**
     * Generate an array of headers using the object properties
     *
     * @return array
     * @throws \Exception
     */
    public function build()
    {
        return self::generateHeaders(
            $this->expires,
            $this->etag,
            $this->lastModified,
            $this->public
        );
    }

    /**
     * Generate headers for show routes
     *
     * @param string $expires
     * @param $entity
     * @return array
     * @throws \Exception
     */
    public static function generateShowHeaders(string $expires, $entity)
    {
        $entityClassParts = explode('\\', get_class($entity));
        $entityClass = end($entityClassParts);

        return self::generateHeaders(
            $expires,
            "{$entityClass}{$entity->getId()}{$entity->getUpdatedAt()->getTimestamp()}",
            $entity->getUpdatedAt(),
            true
        );
    }

    /**
     * Generate headers for list headers
     *
     * @param string $expires
     * @param string $entityName
     * @return array
     * @throws \Exception
     */
    public static function generateListHeaders(string $expires, string $entityName)
    {
        $now = new \DateTime();
        $entityName = ucfirst($entityName);

        return self::generateHeaders(
            $expires,
            "Paginated{$entityName}{$now->format('Ymd-His')}",
            $now,
            true
        );
    }

    /**
     * Generate an array of headers
     *
     * @param null $expires
     * @param string|null $etag
     * @param null $lastModified
     * @param bool $public
     * @return array
     * @throws \Exception
     */
    public static function generateHeaders(
        $expires = null,
        ?string $etag = null,
        $lastModified = null,
        bool $public = true
    ) {
        $headers = [];

        if ($expires) {
            if (is_string($expires)) {
                $expires = new \DateTime($expires);
            }
            $headers["Expires"] = $expires->format(self::CACHE_DATE_FORMAT);
        }

        if ($etag) {
            $headers["Etag"] = $etag;
        }

        if ($lastModified) {
            if (is_string($lastModified)) {
                $lastModified = new \DateTime($lastModified);
            }
            $headers["Last-Modified"] = $lastModified->format(self::CACHE_DATE_FORMAT);
        }

        if ($public) {
            $cacheControl[] = "public";
        } else {
            $cacheControl[] = "private";
            $cacheControl[] = "must-revalidate";
        }

        $headers["Cache-Control"] = implode(", ", $cacheControl);

        return $headers;
    }
}
