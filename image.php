<?php
chdir("../");
require_once("engine.php");
$engine = new Engine();
$engine->init();
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (86400 * 30)));
header("Last-Modified: ". gmdate("D, d M Y H:i:s", filemtime($_GET[$engine->get("imggetvar")]))." GMT");

$file = $_GET[$engine->get("imggetvar")];

// Get mime type
$type = exif_imagetype($file);
$mime = image_type_to_mime_type($type);
header("content-type:".$mime."; charset=UTF-8");

switch($type) {
    case IMAGETYPE_GIF:
        $i = imagecreatefromgif($file);
        imagegif($i);
    break;
    
    case IMAGETYPE_JPEG:
        $i = imagecreatefromjpeg($file);
        imagejpeg($i);
    break;
    
    case IMAGETYPE_PNG:
        $i = imagecreatefrompng($file);
        imagesavealpha($i, true);
        imagepng($i);
    break;
    
    // case IMAGETYPE_SWF:
        // // Not supported? Output directly
    // break;
    
    // case IMAGETYPE_PSD:
    // break;
    
    // case IMAGETYPE_BMP:
    // break;
    
    // case IMAGETYPE_TIFF_II:
    // break;
    
    // case IMAGETYPE_TIF_MM:
    // break;
    
    // case IMAGETYPE_JPC:
    // break;
    
    // case IMAGETYPE_JP2:
    // break;
    
    // case IMAGETYPE_JPX:
    // break;
    
    // case IMAGETYPE_JB2:
    // break;
    
    // case IMAGETYPE_SWC:
    // break;
    
    // case IMAGETYPE_IFF:
    // break;
    
    // case IMAGETYPE_WBMP:
    // break;
    
    // case IMAGETYPE_XBM:
    // break;
    
    // case IMAGETYPE_ICO:
    // break;
    
    default:
        header("Content-type: image/png");
        $i = imagecreate(500, 500);
        $bg = imagecolorallocate($i, 255, 255, 255);
        $color = imagecolorallocate($i, 0, 0, 0);
        imagestring($i, 1, 250, 250, "Image error, unsupported file format", $color);
        imagepng($i);
    break;
}

imagedestroy($i);