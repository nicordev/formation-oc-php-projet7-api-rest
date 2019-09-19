<?php

namespace App\Helper\CacheTagMaker;


use Symfony\Component\HttpFoundation\Request;

class CacheTagMaker
{
    /**
     * Add the requested id for show actions if available
     *
     * @param Request $request
     * @param string $tag
     */
    public function addIdForShowAction(Request $request, string &$tag)
    {
        if (strpos($tag, "show") !== false) {
            $id = $request->get("id");
            if ($id) {
                $tag .= "_{$id}";
            }
        }
    }
}
