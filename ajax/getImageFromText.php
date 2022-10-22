<?php

header("Content-type: image/png");
$string = base64_decode($_REQUEST['text']);

$size = 12;
$width = 15 * strlen($string);
$height = $size+5;

$image = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $white);

imagettftext($image, $size, 0, 0, 12, $black, __DIR__ . '/../fonts/arial.ttf', $string);

imagepng($image);
imagedestroy($image);
