<?php

namespace API\inc;

use API\models\RecipeImage;
use PAF\Model\PaginationResult;
use PAF\Model\Query;
use PAF\Router\Response;

class Functions {
    /**
     * Applies pagination to the query
     *
     * @param Query $query
     *
     * @return PaginationResult
     */
    public static function pagination($query) {
        return $query->pagination(
            intval($_GET["itemsPerPage"] ?? 10),
            intval($_GET["page"] ?? 0)
        );
    }

    /**
     * Applies pagination to the query
     *
     * @param Query $query
     *
     * @return Query
     */
    public static function sort($query) {
        if (!empty($_GET["sort"])) {
            return $query->orderBy(
                $_GET["sort"],
                !empty($_GET["sortDir"]) ? $_GET["sortDir"] : "asc"
            );
        }

        return $query;
    }

    /**
     * Generates a response containing the given RecipeImage
     *
     * @param RecipeImage $image
     *
     * @return Response the generated response with the image
     */
    public static function outputRecipeImage($image, $cacheable = true) {
        $size = filesize($image->path);

        if($cacheable){
            $etag = md5($image->id);

            header("Cache-Control: private");
            header("ETag: {$etag}");

            if(!empty($_SERVER['HTTP_IF_NONE_MATCH']) && $etag === $_SERVER['HTTP_IF_NONE_MATCH']){
                return new Response('', 304, 'text/plain');
            }
        }
        
        $fp = fopen($image->path, 'rb');
        $file = fread($fp, $size);

        fclose($fp);

        return Response::ok($file, $image->mimeType);
    }
}
