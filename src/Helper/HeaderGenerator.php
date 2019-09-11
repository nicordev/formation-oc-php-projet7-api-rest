<?php

namespace App\Helper;


class HeaderGenerator
{
    public const CACHE_DATE_FORMAT = "D, d M Y H:i:s e";

    public function generate(
        ?\DateTime $expires = null,
        ?string $etag = null,
        ?\DateTime $lastModified = null,
        bool $public = true
    ) {
        $headers = [];

        if ($expires) {
            $headers["Expires"] = $expires->format(self::CACHE_DATE_FORMAT);
        }

        if ($etag) {
            $headers["Etag"] = $etag;
        }

        if ($lastModified) {
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