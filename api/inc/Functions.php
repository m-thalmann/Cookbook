<?php

namespace API\inc;

use API\config\Config;
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
     * @param bool $cacheable whether the image can be cached
     * @param integer|null $thumbnailWidth the width to scale the image to
     *
     * @return Response the generated response with the image
     */
    public static function outputRecipeImage($image, $cacheable = true, $thumbnailWidth = null) {
        if ($cacheable) {
            $etag = md5($image->id);

            header("Cache-Control: private");
            header("ETag: {$etag}");

            if (
                !empty($_SERVER['HTTP_IF_NONE_MATCH']) &&
                $etag === $_SERVER['HTTP_IF_NONE_MATCH']
            ) {
                return new Response('', 304, 'text/plain');
            }
        }

        if($thumbnailWidth !== null){
            switch($image->mimeType){
                case "image/jpeg":
                    $img = imagecreatefromjpeg($image->path);
                    break;
                case "image/png":
                    $img = imagecreatefrompng($image->path);
                    break;
                case "image/gif":
                    $img = imagecreatefromgif($image->path);
                    break;
            }
    
            list($width, $height) = getimagesize($image->path);
    
            $ratio = $height / $width;

            if(($ratio >= 1 && $width > $thumbnailWidth) || ($ratio < 1 && $height > $thumbnailWidth)){
                if($ratio < 1){
                    $newHeight = $thumbnailWidth;
                    $newWidth = $newHeight / $ratio;
                }else{
                    $newWidth = $thumbnailWidth;
                    $newHeight = $newWidth * $ratio;
                }

                $img = imagescale($img, $newWidth, $newHeight);
    
                ob_start();
    
                switch($image->mimeType){
                    case "image/jpeg":
                        imagejpeg($img);
                        break;
                    case "image/png":
                        imagepng($img);
                        break;
                    case "image/gif":
                        imagegif($img);
                        break;
                }
    
                $imageOutput = ob_get_contents();
    
                ob_end_clean();
    
                imagedestroy($img);
    
                return Response::ok($imageOutput, $image->mimeType);
            }
        }

        $size = filesize($image->path);

        $fp = fopen($image->path, 'rb');
        $file = fread($fp, $size);

        fclose($fp);

        return Response::ok($file, $image->mimeType);
    }

    /**
     * Validate the h-captcha token
     *
     * @param string $token the token
     *
     * @return boolean whether it is valid or not
     */
    public static function validateHCaptcha($token) {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt(
            $verify,
            CURLOPT_POSTFIELDS,
            http_build_query([
                "secret" => Config::get("hcaptcha.secret"),
                "response" => $token,
            ])
        );
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);

        if (!Config::get("production", true)) {
            curl_setopt($verify, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $response = curl_exec($verify);
        $responseData = json_decode($response);

        return $response && $responseData->success;
    }
}
