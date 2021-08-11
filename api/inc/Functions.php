<?php

namespace API\inc;

use API\Models\RecipeImage;
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
    public static function outputRecipeImage($image) {
        $size = filesize($image->path);
        $fp = fopen($image->path, 'rb');
        $file = fread($fp, $size);

        fclose($fp);

        return Response::ok($file, $image->mimeType);
    }
}
