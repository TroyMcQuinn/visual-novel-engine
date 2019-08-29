<!doctype html>
<html lang="en">

  <head>
  
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  
    <title>The 2214 Saga Visual Novel</title>
    <meta name="description" content="The 2214 Saga reboot!  From web comic to interactive visual novel format." />
  
    <link rel="stylesheet" type="text/css" href="../static.css" />
    
    <link rel="icon" href="../../img/favicon.ico" type="image/x-icon" />
    
    <link rel="alternate" type="application/rss+xml" title="RSS" href="../../rss/" />

  </head>
  <body>
    <?php
    
    require('./markov.php');
    
    function getFiles($dir){
      $d = scandir($dir);
      $a = [];
      foreach($d as $file){
        if(($file !== '.') && ($file !== '..') && (!is_dir($file))){
          $a[] = $file;
        }
      }
      return $a;
    }
    
    function getRandom($d){
      return $d[mt_rand(0,count($d)-1)];
    }
    
    $cfg = parse_ini_file('../config.ini');
    
    $quality = $cfg['quality'];
    $resolution = $cfg['resolution'];
    $image_format = $cfg['image_format'];

    $toc = json_decode(file_get_contents('../../chapters.json'),1);
    
    $mt = []; // Markov text.
    $tl = []; // Textbox titles.
    foreach($toc as $chapter){
      $mt[] = $chapter['label'];
      foreach($chapter['scenes'] as $scene){
        $scene['script'] = json_decode(file_get_contents('../../script/'.$scene['file'].'.json'),1);
        $mt[] = $scene['name'];
        foreach($scene['script'] as $frame){
          if(isset($frame['title']) && !in_array($frame['title'],$tl)) $tl[] = $frame['title'];
          if(isset($frame['text'])) $mt[] = $frame['text'];
        }
      }
    }
    
    $markov = generate_markov_table(implode(' ',$mt),mt_rand(4,8));
    
    $backgrounds = getFiles('../../assets/bkgnd');
    $layers = getFiles('../../assets/layer');
   
    $frame_count = mt_rand(2,5); // Set higher after testing.
    
    $background = getRandom($backgrounds); // Randomly select background.
    
    $page_title = ucwords(trim(generate_markov_text(mt_rand(10,32),$markov,mt_rand(4,8)))); // Randomly generate page title.
    
    echo "<h1>".$page_title."</h1>\n";
        
    for($i=1;$i<=$frame_count;$i++){
      
      $text = generate_markov_text(mt_rand(10,140),$markov,mt_rand(4,8));

      $title = getRandom($tl);
      $title_file = '../../assets/layer/'.strtolower(str_replace(' ','_',$title)).'.png';
      $layer = file_exists($title_file) ? $title_file : getRandom($layers);

      $img_src = "img/?background=$background&layer=$layer&format=$image_format&quality=$quality&size=$resolution";
          
      echo "<img class=\"panel_img\" src=\"$img_src\" alt=\"Random\" />\n";
      echo "<div class=\"panel\" style=\"background-image: url('$img_src');\" title=\"Random\">";
      if(!empty($text)){
        if(!empty($title)){
          $text = "<strong>".$title."</strong>: ".$text;
        }
        echo "<div class=\"textbox\" >".$text."</div>";
      }
      echo "</div>\n";
    }

    ?>
  </body>
</html>