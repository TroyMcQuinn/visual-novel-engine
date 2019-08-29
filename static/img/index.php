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

if(!isset($_GET['chapter']) || !isset($_GET['scene']) || !isset($_GET['frame'])){
  exit(); // Probably want to return a blank image or something later on.
}

$cur_chapter = intval($_GET['chapter']);
$cur_scene = intval($_GET['scene']);
$cur_frame = intval($_GET['frame']);

// Image output format
$final_format = (!empty($_GET['format']) && ($_GET['format'] == 'jpg')) ? 'jpg' : 'png';

// Final image sizes
$final_width = !empty($_GET['size']) ? intval($_GET['size']) : $width;
$final_height = floor(($height / $width) * $final_width);

// Final image quality
$final_quality = !empty($_GET['quality']) ? intval($_GET['quality']) : 100;

// Whether or not to render the dialogue box
$show_text = (!empty($_GET['show_text']) && ($_GET['show_text'] == 'true'));

// Cache the image if specified.
$cache = (!empty($_GET['cache']) && ($_GET['cache'] == 'true'));

// Some limiting on input values
$final_width = ($final_width > $width) ? $width : $final_width;
$final_height = ($final_height > $height) ? $height : $final_height;
$final_quality = ($final_quality > 100) ? 100 : $final_quality;
$final_quality = ($final_quality < 0) ? 0 : $final_quality;

$toc = json_decode(file_get_contents('../../chapters.json'),1);

$cur = [];
$pos = [];

function resetCurrent(){
  
  global $cur, $pos;
  
  $cur = [
    'background' => 'clear',
    'layer1' => 'clear',
    'layer2' => 'clear',
    'layer3' => 'clear',
    'layer4' => 'clear',
    'layer5' => 'clear',
    'layer6' => 'clear',
    'title' => '',
    'text' => ''
  ];
  
  $pos = [
    'layer1' => ['x'=>-30,'y'=>'0'],
    'layer2' => ['x'=>0,'y'=>'0'],
    'layer3' => ['x'=>20,'y'=>'0'],
    'layer4' => ['x'=>40,'y'=>'0'],
    'layer5' => ['x'=>0,'y'=>'0'],
    'layer6' => ['x'=>0,'y'=>'0'],
  ];
  
}

resetCurrent();

if(!isset($toc[$cur_chapter]['scenes'][$cur_scene])) exit();

$scene = $toc[$cur_chapter]['scenes'][$cur_scene];

$script = json_decode(file_get_contents('../../script/'.$scene['file'].'.json'),1);

$fade = false;
$fade_color = 'black';

foreach($script as $frame_no => $frame){    
  
  $cur['background'] = (empty($frame['background']) || ($frame['background'] == 'clear')) ? $cur['background'] : $frame['background'];
  for($i=1;$i<=6;$i++){
    if(!empty($frame['layer'.$i])){
      $cur['layer'.$i] = $frame['layer'.$i];
    }
  }
  
  if(!empty($frame['tweaks'])){
    foreach($frame['tweaks'] as $tweak_layer => $tweak){
      $pos[$tweak_layer] = $tweak;
    }        
  }
  
  // Fade state.  If the state is faded down, return a solid image.
  $fade = (isset($frame['fade'])) ? !$fade : $fade;
  if(isset($frame['fade'])) $fade_color = $frame['fade'];
  
  if($frame_no == $cur_frame){
    
    //print_r($cur); exit();
    
    if(!$fade){
    
      // Background
      $bgit = explode('.',$cur['background']);
      $bgit = !empty($bgit[1]) ? $bgit[1] : false;
      switch($bgit){
        case 'png':
          $main_img = imagecreatefrompng('../../assets/bkgnd/'.$cur['background']);
        break;
        case 'jpg':
        case 'jpeg':
          $main_img = imagecreatefromjpeg('../../assets/bkgnd/'.$cur['background']);
        break;
        case false:
          $main_img = imagecreatetruecolor($width,$height);
        break;
      }
      imagealphablending($main_img,true);
      imagesavealpha($main_img,true);
      
      // Insert layers
      for($i=1;$i<=6;$i++){
        if(!empty($cur['layer'.$i]) && ($cur['layer'.$i] != 'clear')){
          $tmp = imagecreatefrompng('../../assets/layer/'.$cur['layer'.$i]);
          if(imagesy($tmp) != $height){
            $tw = ($height / imagesy($tmp)) * imagesx($tmp);
            $tmp = imageScaleNitro($tmp,$tw,$height,IMG_BICUBIC_FIXED);
          }
          $x = floor($width * ($pos['layer'.$i]['x'] / 100)) + floor(($width - imagesx($tmp)) / 2);
          $y = floor($height * ($pos['layer'.$i]['y'] / 100));
          imagealphablending($tmp,true);
          imagesavealpha($tmp,true);
          imagecopy($main_img,$tmp,$x,$y,0,0,imagesx($tmp),imagesy($tmp));
          imagedestroy($tmp);
        }
      }
    
    } else { // If faded down, just return a solid black or white image.
      $main_img = imagecreatetruecolor($width,$height);
      if($fade_color == 'white'){
        imagefill($main_img,0,0,imagecolorallocate($main_img,255,255,255));
      }
      imagealphablending($main_img,true);
      imagesavealpha($main_img,true);
    }
    
    // Insert dialogue box
    if((!empty($frame['text'])) && $show_text){
      
      $tmp = imagecreatefrompng('../res/dialogue_box.png');
      $x = floor(($width - imagesx($tmp)) / 2);
      $y = 626;
      imagealphablending($tmp,true);
      imagesavealpha($tmp,true);
      imagecopy($main_img,$tmp,$x,$y,0,0,imagesx($tmp),imagesy($tmp));
      imagedestroy($tmp);
      
      // Insert text.
      $text_color = imagecolorallocate($main_img,255,255,255);            
      imagettftext($main_img,24,0,$x+44,$y+80,$text_color,'../../fonts/MavenPro-Bold.ttf',$frame['text']); //NOTE / TODO:  Word Wrap needs to be implemented!
      
      // Insert dialogue title
      $tmp = imagecreatefrompng('../res/dialogue_title.png');
      $x = 410;
      $y = 601;
      imagealphablending($tmp,true);
      imagesavealpha($tmp,true);
      imagecopy($main_img,$tmp,$x,$y,0,0,imagesx($tmp),imagesy($tmp));
      imagedestroy($tmp);
      
      // Insert title.
      imagettftext($main_img,22,0,$x+32,$y+38,$text_color,'../../fonts/MavenPro-Bold.ttf',$frame['title']);            
      
    }
    
    // Resize if final size is different
    if($width != $final_width){
      $main_img = imageScaleNitro($main_img,$final_width,$final_height,IMG_BICUBIC);
    }
    
    // Final output
    if($final_format == 'png'){
      header('Content-Type: image/png');
      imagepng($main_img);
      if($cache){
        imagepng($main_img,"./cache/$cur_chapter-$cur_scene-$cur_frame.png");
      }
    } else {
      header('Content-Type: image/jpeg');
      imagejpeg($main_img,NULL,$final_quality);
      if($cache){
        imagejpeg($main_img,"./cache/$cur_chapter-$cur_scene-$cur_frame.jpg",$final_quality);
      }
    }
    imagedestroy($main_img);
    exit();
    
  }

}



?>