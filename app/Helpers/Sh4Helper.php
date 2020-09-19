<?php
/**
 * Created by PhpStorm.
 * User: ali
 * Date: 6/8/19
 * Time: 9:31 PM
 */

namespace App\Helpers;


use App\Http\Controllers\Controller;
use Hekmatinasser\Verta\Verta;

class Sh4Helper
{

    private static $thumbnailPattern;
    private static $thumbnailPath;
    private static $defaultPicture;


    public static function setProperties()
    {
        $controller = new Controller();
        self::$thumbnailPattern = '(' . $controller->pathMedia . ')';
        self::$thumbnailPath = $controller->pathThumbnail;
        self::$defaultPicture = $controller->defaultPicture;
    }

    public static function formatPersianDate($datetime, $format = 'y/m/d')
    {
        return Verta::createTimestamp(strtotime($datetime))->format($format);
    }

    public static function convertCharsToPersian($string)
    {
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩‎', 'ك', 'ي');
        $standard = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'ک', 'ی');
        $new_string = str_replace($persian, $standard, str_replace($arabic, $standard, $string));
        return preg_replace('~[\r\n]+~', '', $new_string);
    }

    public static function createThumbnailPath($mediaPath, $default = null, $pattern = null, $replacement = null)
    {
        self::setProperties();

        $mediaPath = self::isMedia($mediaPath) ?? self::isMedia($default) ?? self::$defaultPicture;
        $pattern = $pattern ?? self::$thumbnailPattern;
        $replacement = $replacement ?? self::$thumbnailPath;


        return preg_replace($pattern, $replacement, $mediaPath);
    }


    public static function createPicturePath($mediaPath, $default = null)
    {
        self::setProperties();
        $mediaPath = self::isMedia($mediaPath) ?? self::isMedia($default) ?? self::$defaultPicture;
        return $mediaPath;
    }


    public static function createThumbnailPathForUnlink($mediaPath)
    {
        self::setProperties();
        return preg_replace(self::$thumbnailPattern, self::$thumbnailPath, $mediaPath);
    }


    public static function isMedia($media)
    {
        return is_file(public_path($media)) ? $media : null;
    }


}
