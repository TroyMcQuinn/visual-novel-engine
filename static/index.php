<?php

function getBaseURL($dispatch='go'){
  // Parse the dispatch token with or without the trailing slash.
  if(strpos($_SERVER['REQUEST_URI'],"/$dispatch/") !== false){
    $tmp = explode("/$dispatch/",$_SERVER['REQUEST_URI']);
  } else {
    $tmp = explode("/$dispatch",$_SERVER['REQUEST_URI']);
  }
  $url = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['SERVER_NAME'].$tmp[0];
  return rtrim($url,'/');		
}

$base = getBaseURL();

?><!doctype html>
<html lang="en">

  <head>
  
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  
    <title>The 2214 Saga Visual Novel</title>
    <meta name="description" content="The 2214 Saga reboot!  From web comic to interactive visual novel format." />
    
    <?php
    
    if(isset($base)){
      echo "<base href=\"$base/\" />\n";
    }
    
    ?>
  
    <link rel="stylesheet" type="text/css" href="static.css" />
    
    <link rel="icon" href="../img/favicon.ico" type="image/x-icon" />
    
    <link rel="alternate" type="application/rss+xml" title="RSS" href="../rss/" />

  </head>
  <body>
    <?php
    
    function setupNavigationScheme(){

      // Remove any query string.
      if(strpos($_SERVER['REQUEST_URI'],'?') !== false){
        $url_string = explode('?',$_SERVER['REQUEST_URI']);
        $url_string = $url_string[0];
      } else {
        $url_string = $_SERVER['REQUEST_URI'];
      }

      // Remove everything before "go"
      $input_vars = explode('go/',$url_string);
      
      $nav = Array();

      if(isset($input_vars[1])){
      
        $input_vars = explode('/',$input_vars[1]);

        // Create list of navigation variables    
        if(!empty($input_vars)){
          foreach($input_vars as $c => $input_var){
            if($c % 2 == 0){
              $cur_name = trim(strtolower(substr(preg_replace("/[^A-Za-z0-9_\-\.]/",'',$input_var),0,64)));
            } else {
              $nav[$cur_name] = strtolower(substr(preg_replace("/[^A-Za-z0-9_\-\.]/",'',$input_var),0,64));
            }
          }
        }
      }
      
      return $nav;
      
    }
    
    // This function prevents the generation of repetitive images when backgrounds and layers do not change between scene frames.
    function isFrameDifferent($frame){
      global $prev_frame;
      if(empty($prev_frame)){
        $prev_frame = $frame;
        return true;
      }
      if(isset($frame['fade'])){
        $prev_frame = $frame;
        return true;
      }
      foreach($frame as $n => $element){
        if((strpos($n,'layer') !== false) || (strpos($n,'background') !== false)){
          if(!isset($prev_frame[$n]) || (isset($prev_frame[$n]) && ($prev_frame[$n] != $element))){
            $prev_frame = $frame;
            return true;
          }
        }
      }
      $prev_frame = $frame;
      return false;
    }
    
    // This saves all frames, indexed by their elements.
    function recordFrame($frame,$img_src){
      global $prev_frames;
      ksort($frame);
      $index = '';
      foreach($frame as $n => $element){
        if((strpos($n,'layer') !== false) || (strpos($n,'background') !== false)){
          $index .= $n.':'.$element.'|';
        }
      }
      if(!empty($index)){
        $prev_frames[$index] = $img_src;
      }
    }
    
    // This retrieves an image source if a frame's elements match a previous frame's elements.
    function retrieveFrame($frame){
      global $prev_frames;
      ksort($frame);
      $index = '';
      foreach($frame as $n => $element){
        if((strpos($n,'layer') !== false) || (strpos($n,'background') !== false)){
          $index .= $n.':'.$element.'|';
        }
      }
      return (isset($prev_frames[$index])) ? $prev_frames[$index] : false;
    }
    
    $nav = setupNavigationScheme();
    
    $cfg = parse_ini_file('./config.ini');
    
    $quality = $cfg['quality'];
    $resolution = $cfg['resolution'];
    $image_format = $cfg['image_format'];

    $cur_chapter = !isset($nav['chapter']) ? 0 : intval($nav['chapter']);
    $cur_scene = !isset($nav['scene']) ? 0 : intval($nav['scene']) - 1;
    
    $use_cache = (file_exists("./img/cache/$cur_chapter-$cur_scene-0.$image_format") 
                  && ($cfg['use_caching'] == 1) 
                  && (filemtime("./img/cache/$cur_chapter-$cur_scene-0.$image_format") > (time() - ($cfg['cache_ttl'] * 60))));
    $make_cache = ($cfg['use_caching'] == 1) ? 'true' : 'false'; // Needs to be string values.

    $prev_frame = []; // Records the elements of the previous frame.
    $prev_frames = []; // Saving all frames to avoid repetition of images.

    $toc = json_decode(file_get_contents('../chapters.json'),1);
    
    echo "<div id=\"fixed_header\">\n";
    
    // Navigation previous and first
    if(($cur_chapter > 0) || ($cur_scene > 0)){
      $first_link = $base.'/go/chapter/0/scene/1';
      echo "<a id=\"first\" class=\"nav_button\" href=\"$first_link\" title=\"Start from the beginning\"><img src=\"../img/buttons/first.png\" alt=\"Start from beginning\" title=\"Start from beginning\" /></a>\n";
      if($cur_scene > 0){
        $prev_link = $base.'/go/chapter/'.$cur_chapter.'/scene/'.$cur_scene;
        echo "<a id=\"previous\" class=\"nav_button\" href=\"$prev_link\" title=\"Previous Page\"><img src=\"../img/buttons/skip_back.png\" alt=\"Previous Page\" title=\"Previous Page\" /></a>\n";
      } else if($cur_chapter > 0){
        $prev_chapter_length = count($toc[$cur_chapter-1]['scenes']);
        $prev_link = $base.'/go/chapter/'.($cur_chapter-1).'/scene/'.$prev_chapter_length;
        echo "<a id=\"previous\" class=\"nav_button\" href=\"$prev_link\" title=\"Previous Page\"><img src=\"../img/buttons/skip_back.png\" alt=\"Previous Page\" title=\"Previous Page\" /></a>\n";
      }
    }
    
    // Navigation next and last
    if(($cur_chapter <= (count($toc) - 1)) || ($cur_scene <= (count($toc[$cur_chapter]['scenes']) - 1))){
      $chapter_count = count($toc)-1;
      $last_chapter_length = count($toc[$chapter_count]['scenes']) - 1;
      $last_link = $base.'/go/chapter/'.$chapter_count.'/scene/'.$last_chapter_length;
      echo "<a id=\"last\" class=\"nav_button\" href=\"$last_link\" title=\"Skip ahead to the latest page\"><img src=\"../img/buttons/last.png\" alt=\"Skip ahead to the latest page\" title=\"Skip ahead to the latest page\" /></a>\n";
      if($cur_scene < (count($toc[$cur_chapter]['scenes']) - 1)){
        $next_link = $base.'/go/chapter/'.$cur_chapter.'/scene/'.($cur_scene + 2); // + 2 instead of +1 because internal scene = link scene - 1
        echo "<a id=\"next\" class=\"nav_button\" href=\"$next_link\" title=\"Next Page\"><img src=\"../img/buttons/skip_next.png\" alt=\"Next Page\" title=\"Next Page\" /></a>\n";
      } else if($cur_chapter < (count($toc) - 1)){
        $next_link = $base.'/go/chapter/'.($cur_chapter + 1).'/scene/1';
        echo "<a id=\"next\" class=\"nav_button\" href=\"$next_link\" title=\"Next Page\"><img src=\"../img/buttons/skip_next.png\" alt=\"Next Page\" title=\"Next Page\" /></a>\n";
      }
    }
    
    echo "<select id=\"scene_selector\" onchange=\"window.location = window.location.href.split('/go/')[0] + '/go/' + this.value; \" >\n";
    foreach($toc as $chapter_no => $chapter){
      echo "<optgroup label=\"".$chapter['label']."\">\n";
      foreach($chapter['scenes'] as $scene_no => $scene){
        $selected = (($chapter_no == $cur_chapter) && ($scene_no == $cur_scene)) ? ' selected="selected" ': '';
        echo "<option value=\"chapter/$chapter_no/scene/".($scene_no + 1)."\" $selected >".$scene['name']."</option>\n";
      }
      echo "</optgroup>\n";
    }    
    echo "</select>\n";    
    
    echo "</div>\n";
    
    if(isset($toc[$cur_chapter]['scenes'][$cur_scene])){

      $scene = $toc[$cur_chapter]['scenes'][$cur_scene];    
      
      echo "<h1>".$scene['name']."</h1>\n";

      $script = json_decode(file_get_contents('../script/'.$scene['file'].'.json'),1);

      foreach($script as $cur_frame => $frame){      
          
          if(($saved_frame = retrieveFrame($frame)) !== false){
            $img_src = $saved_frame;
          } else if(empty($img_src) || isFrameDifferent($frame)){
            if($use_cache){
              $img_src = "img/cache/$cur_chapter-$cur_scene-$cur_frame.$image_format";
            } else {
              $img_src = "img/?chapter=$cur_chapter&scene=$cur_scene&frame=$cur_frame&format=$image_format&quality=$quality&size=$resolution&cache=$make_cache";
            }
            recordFrame($frame,$img_src);
          }
          
          echo "<img class=\"panel_img\" src=\"$img_src\" alt=\"Chapter $cur_chapter Scene $cur_scene Frame $cur_frame\" />\n";
          echo "<div class=\"panel\" style=\"background-image: url('$img_src');\" title=\"Chapter $cur_chapter Scene $cur_scene Frame $cur_frame\">";
          if(!empty($frame['text'])){
            if(!empty($frame['title'])){
              $frame['text'] = "<strong>".$frame['title']."</strong>: ".$frame['text'];
            }
            echo "<div class=\"textbox\" >".$frame['text']."</div>";
          }
          echo "</div>\n";     

          if(!empty($frame['card'])){
            echo "<div class=\"card\">".$frame['card']."</div>\n";
          }
          
          if(!empty($frame['scroll'])){
            // Fix any image links in the scroll line code to correct the paths
            foreach($frame['scroll'] as $i => $line){
              $frame['scroll'][$i] = str_replace('src="assets/','src="../assets/',$line);
            }
            echo "<div class=\"scroll\">".implode("\n",$frame['scroll'])."</div>\n";
          }
          
      }
    
    } else { // 404'd!
      
      echo "<h1>SCENE MISSING</h1>";
      
    }

    ?>
  </body>
</html>