<?php

function imageScaleNitro(&$img,$w,$h,$mode){
  $newimg = imagecreatetruecolor($w,$h);
  imagealphablending($img,true);
  imagesavealpha($img,true);
  imagealphablending($newimg,true);
  imagesavealpha($newimg,true);
  $tc = imagecolorallocatealpha($newimg,0,0,0,127);
  imagefill($newimg,1,1,$tc);
  imagecopyresampled($newimg,$img,0,0,0,0,$w,$h,imagesx($img),imagesy($img));
  return $newimg;  
}

$width = 1920;
$height = 1080;

if(!isset($_GET['background']) || !isset($_GET['layer'])){
  exit(); // Probably want to return a blank image or something later on.
}

$background = $_GET['background'];
$layer = $_GET['layer'];

// Image output format
$final_format = (!empty($_GET['format']) && ($_GET['format'] == 'jpg')) ? 'jpg' : 'png';

// Final image sizes
$final_width = !empty($_GET['size']) ? intval($_GET['size']) : $width;
$final_height = floor(($height / $width) * $final_width);

// Final image quality
$final_quality = !empty($_GET['quality']) ? intval($_GET['quality']) : 100;

// Whether or not to render the dialogue box
$show_text = (!empty($_GET['show_text']) && ($_GET['show_text'] == 'true'));

// Some limiting on input values
$final_width = ($final_width > $width) ? $width : $final_width;
$final_height = ($final_height > $height) ? $height : $final_height;
$final_quality = ($final_quality > 100) ? 100 : $final_quality;
$final_quality = ($final_quality < 0) ? 0 : $final_quality;

$background_file = '../../../assets/bkgnd/'.$background;
$layer_file = '../../../assets/layer/'.$layer;
    
// Background
$bgit = explode('.',$background);
$bgit = !empty($bgit[1]) ? $bgit[1] : false;
if(file_exists($background_file)){
  
  switch($bgit){
    case 'png':
      $main_img = imagecreatefrompng($background_file);
    break;
    case 'jpg':
    case 'jpeg':
      $main_img = imagecreatefromjpeg($background_file);
    break;
    case false:
      $main_img = imagecreatetruecolor($width,$height);
    break;
  }

} else {
  $main_img = imagecreatetruecolor($width,$height);
}
imagealphablending($main_img,true);
imagesavealpha($main_img,true);

// Insert layer
if(file_exists($layer_file)){
  $tmp = imagecreatefrompng($layer_file);
  if(imagesy($tmp) != $height){
    $tw = ($height / imagesy($tmp)) * imagesx($tmp);
    $tmp = imageScaleNitro($tmp,$tw,$height,IMG_BICUBIC_FIXED);
  }
  $x = floor($width * (0 / 100)) + floor(($width - imagesx($tmp)) / 2);
  $y = floor($height * (0 / 100));
  imagealphablending($tmp,true);
  imagesavealpha($tmp,true);
  imagecopy($main_img,$tmp,$x,$y,0,0,imagesx($tmp),imagesy($tmp));
  imagedestroy($tmp);
}
  
// Resize if final size is different
if($width != $final_width){
  $main_img = imageScaleNitro($main_img,$final_width,$final_height,IMG_BICUBIC);
}

// Final output
if($final_format == 'png'){
  header('Content-Type: image/png');
  imagepng($main_img);
} else {
  header('Content-Type: image/jpeg');
  imagejpeg($main_img,NULL,$final_quality);
}
imagedestroy($main_img);
exit();

?>