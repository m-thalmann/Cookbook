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
            intval($_GET["itemsPerPage"] ?? 9),
            intval($_GET["page"] ?? 0)
        );
    }

    /**
     * Applies pagination to the query
     *
     * @param Query $query
     * @param string[] $forbiddenProperties A list of properties that are forbidden to sort by
     *
     * @return Query
     */
    public static function sort($query, $forbiddenProperties = []) {
        if (
            !empty($_GET["sort"]) &&
            !in_array($_GET["sort"], $forbiddenProperties)
        ) {
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
     * @param integer|null $maxSize the width/height to scale the image to
     *
     * @return Response the generated response with the image
     */
    public static function outputRecipeImage(
        $image,
        $cacheable = true,
        $maxSize = null
    ) {
        if (!is_file($image->path)) {
            return Response::notFound();
        }

        if ($cacheable) {
            $etag = md5($image->id);

            header("Cache-Control: private");
            header("ETag: $etag");

            if (
                !empty($_SERVER['HTTP_IF_NONE_MATCH']) &&
                $etag === $_SERVER['HTTP_IF_NONE_MATCH']
            ) {
                return new Response('', 304, 'text/plain');
            }
        }

        // resize image
        if ($maxSize !== null) {
            switch ($image->mimeType) {
                case "image/jpeg":
                    $img = imagecreatefromjpeg($image->path);
                    break;
                case "image/png":
                    $img = imagecreatefrompng($image->path);
                    break;
                case "image/gif":
                    $img = imagecreatefromgif($image->path);
                    break;
                default:
                    return Response::error("Bad image-type");
            }

            list($width, $height) = getimagesize($image->path);

            $ratio = $width / $height;

            if (
                ($ratio >= 1 && $width > $maxSize) ||
                ($ratio < 1 && $height > $maxSize)
            ) {
                if ($ratio >= 1) {
                    $newWidth = $maxSize;
                    $newHeight = $newWidth / $ratio;
                } else {
                    $newHeight = $maxSize;
                    $newWidth = $newHeight * $ratio;
                }

                $img = imagescale($img, $newWidth, $newHeight);

                ob_start();

                switch ($image->mimeType) {
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

    /**
     * Generates a random 32-character string
     *
     * @return string
     */
    public static function getRandomString() {
        return md5(random_int(PHP_INT_MIN, PHP_INT_MAX));
    }

    /**
     * Returns the total directory size in mega-bytes
     *
     * @param string $path the directory's path
     *
     * @return float the total directory size
     */
    public static function getDirectorySize($path) {
        $size = 0; // size in bytes

        $files = scandir($path);

        foreach ($files as $filename) {
            if ($filename !== ".." && $filename !== ".") {
                $file = "$path/$filename";

                if (is_dir($file)) {
                    $subdirectorySize = self::getDirectorySize($file);
                    $size += $subdirectorySize;
                } elseif (is_file($file)) {
                    $size += filesize($file);
                }
            }
        }

        return round($size / pow(1024, 2), 2);
    }
}
