<!doctype html>
<html lang="en">

  <head>
  
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  
    <title>The 2214 Saga Visual Novel</title>
    <meta name="description" content="The 2214 Saga reboot!  From web comic to interactive visual novel format." />
  
    <link rel="stylesheet" type="text/css" href="data.css" />
    
    <link rel="icon" href="../../img/favicon.ico" type="image/x-icon" />
    
    <link rel="alternate" type="application/rss+xml" title="RSS" href="../../rss/" />

  </head>
  <body>

<?php

// This file performs a statistical analysis on the VN.

function byteFormat($x){
  $gb = pow(1024,3);
  $mb = pow(1024,2);
  $kb = 1024;
  if($x >= $gb){
    return round($x/$gb,1).' GB';
  } else if($x >= $mb){
    return round($x/$mb,1).' MB';
  } else if($x >= $kb){
    return round($x/$kb,1).' KB';
  } else {
    return $x.' bytes';
  }
}

// Builds a table out of 2D database query results.
function buildSimpleDataTable($d){
  if((empty($d)) || (empty($d[0]))) return false;
  $o  = "<table class=\"listing\">\n<tr>";
  foreach($d[0] as $n => $v){
    $o .= "<th>".ucwords(str_replace('_',' ',$n))."</th>";
  }
  $o .= "</tr>\n";
  foreach($d as $row){
    $rc = empty($rc) ? 1 : 0;
    $o .= "<tr class=\"row_$rc detail\">";
    foreach($row as $n => $v){
      $a = (is_numeric($v)) ? 'right' : 'left';
      if($n == 'bytes') $v = byteFormat($v);
      if($n == 'description') $v = ucwords(str_replace('_',' ',$v));
      $o .= "<td style=\"text-align: $a;\" >".$v."</td>";
    }
    $o .= "</tr>\n";
  }  
  $o .= "</table>\n";
  return $o;
}

$d = json_decode(file_get_contents('../../chapters.json'),true);

$all_unique_characters = [];

foreach($d as $chapter_id => $chapter){
  
  $chapter_unique_characters = [];
  
  foreach($chapter['scenes'] as $scene_id => $scene){
    
    $script = $d[$chapter_id]['scenes'][$scene_id]['script'] = json_decode(file_get_contents('../../script/'.$scene['file'].'.json'),true);
    
    $t = [
      'bytes' => filesize('../../script/'.$scene['file'].'.json'), // The script counts in the total file size.
      'frames' => 0,
      'layers' => 0,
      'backgrounds' => 0,
      'bgm' => 0,
      'sfx' => 0,
      'video' => 0,
      'cards' => 0,
      'scrolls' => 0,
      'scroll_lines' => 0,
      'characters' => 0,
      'text_length' => 0,
      'text_lines' => 0,
      'files' => 1, // The script itself counts as a file.
    ];
    
    $unique_files = [];
    $unique_characters = [];
    $character_totals = [];
    
    foreach($script as $frame){
      
      // Tally frames
      $t['frames']++;
      
      // Tally backgrounds
      if((!empty($frame['background'])) && ($frame['background'] != 'clear')){
        if(!in_array('background_'.$frame['background'],$unique_files)){
          $t['backgrounds']++;
          $t['bytes'] += filesize('../../assets/bkgnd/'.$frame['background']);
          $t['files']++;
          $unique_files[] = 'background_'.$frame['background'];
        }
      }
      
      // Tally layers
      for($i=1;$i<=6;$i++){
        if((!empty($frame['layer'.$i])) && ($frame['layer'.$i] != 'clear')){
          if(!in_array('layers_'.$frame['layer'.$i],$unique_files)){
            $t['layers']++;
            $t['bytes'] += filesize('../../assets/layer/'.$frame['layer'.$i]);
            $t['files']++;
            $unique_files[] = 'layers_'.$frame['layer'.$i];
          }
        }
      }
      
      // Tally background music / audio
      if((!empty($frame['music'])) && ($frame['music'] != 'clear')){
        if(!in_array('music_'.$frame['music'],$unique_files)){
          $t['bgm']++;
          $t['bytes'] += filesize('../../assets/bgm/'.$frame['music']);
          $t['files']++;
          $unique_files[] = 'music_'.$frame['music'];
        }
      }
      
      // Tally sound effects
      if((!empty($frame['sfx'])) && ($frame['sfx'] != 'clear')){
        if(!in_array('sfx_'.$frame['sfx'],$unique_files)){
          $t['sfx']++;
          $t['bytes'] += filesize('../../assets/sfx/'.$frame['sfx']);
          $t['files']++;
          $unique_files[] = 'sfx_'.$frame['sfx'];
        }
      }
      
      // Tally video clips
      if((!empty($frame['video'])) && ($frame['video'] != 'clear')){
        if(!in_array('video_'.$frame['video'],$unique_files)){
          $t['video']++;
          $t['bytes'] += filesize('../../assets/vid/'.$frame['video']);
          $t['files']++;
          $unique_files[] = 'video_'.$frame['video'];
        }
      }
      
      // Tally title cards
      if((!empty($frame['card'])) && ($frame['card'] != 'clear')){
        $t['cards']++;
        $t['text_length'] += strlen($frame['card']);        
      }
      
      // Tally credit scrolls
      if((!empty($frame['scroll'])) && ($frame['scroll'] != 'clear')){
        $t['scrolls']++;
        foreach($frame['scroll'] as $line){
          $t['scroll_lines']++;
          $t['text_length'] += strlen($line);
        }
      }
      
      // Tally text
      if(!empty($frame['text'])){
        $t['text_lines']++;
        $t['text_length'] += strlen($frame['text']);
        if(!empty($frame['title'])){
          $t['text_length'] += strlen($frame['title']);
          $all_unique_characters[$frame['title']] = $chapter_unique_characters[$frame['title']] = $unique_characters[$frame['title']] = true;
          if(!isset($character_totals[$frame['title']])){
            $character_totals[$frame['title']]['lines'] = 1;
            $character_totals[$frame['title']]['text_length'] = strlen($frame['text']);
          } else {
            $character_totals[$frame['title']]['lines']++;
            $character_totals[$frame['title']]['text_length'] += strlen($frame['text']);
          }
        }
      }
      
    }    
    
    $d[$chapter_id]['scenes'][$scene_id]['stats'] = $t;
    $d[$chapter_id]['scenes'][$scene_id]['stats']['characters'] = count($unique_characters);
    $d[$chapter_id]['scenes'][$scene_id]['character_totals'] = $character_totals;
    
    // Add scene stats to chapter stats.
    if(empty($d[$chapter_id]['stats'])){
      $d[$chapter_id]['stats'] = $d[$chapter_id]['scenes'][$scene_id]['stats'];
    } else {
      foreach($d[$chapter_id]['scenes'][$scene_id]['stats'] as $n => $v){
        $d[$chapter_id]['stats'][$n] += $v;
      }           
    }
    
    // Get sum of unique characters for the chapter.
    $d[$chapter_id]['stats']['characters'] = count($chapter_unique_characters);
    
    // Start or add to the character totals for the chapter.
    if(empty($d[$chapter_id]['character_totals'])){
      $d[$chapter_id]['character_totals'] = $character_totals;
    } else {
      foreach($character_totals as $n => $v){
        if(!isset($d[$chapter_id]['character_totals'][$n])){
          $d[$chapter_id]['character_totals'][$n] = $v;
        } else {
          foreach($v as $n1 => $v1){
            $d[$chapter_id]['character_totals'][$n][$n1] += $v1;
          }
        }
      } 
    }
    
    // Add scene stats to grand totals.
    if(empty($d['stats'])){
      $d['stats'] = $d[$chapter_id]['scenes'][$scene_id]['stats'];
    } else {
      foreach($d[$chapter_id]['scenes'][$scene_id]['stats'] as $n => $v){
        $d['stats'][$n] += $v;
      }  
    }
    
    // Get the sum of all unique characters for the entire VN.
    $d['stats']['characters'] = count($all_unique_characters);
    
    // Start or add to the character totals for the entire VN.
    if(empty($d['character_totals'])){
      $d['character_totals'] = $character_totals;
    } else {
      foreach($character_totals as $n => $v){
        if(!isset($d['character_totals'][$n])){
          $d['character_totals'][$n] = $v;
        } else {
          foreach($v as $n1 => $v1){
            $d['character_totals'][$n][$n1] += $v1;
          }
        }
      } 
    }
    
  }  
  
}

// echo "<pre>".print_r($d,1)."</pre>";

$grand_totals = [];
$character_totals = [];
$chapter_totals = [];
$scene_totals = [];

foreach($d['stats'] as $n => $v){
  //if($n == 'bytes') $v = byteFormat($v);
  $grand_totals[] = ['description' => $n, 'Total' => $v];
}

foreach($d as $n => $chapter){
  if(is_numeric($n)){
    $chapter_totals[] = array_merge(['chapter' => $chapter['label']],$chapter['stats']);
    foreach($chapter['scenes'] as $s => $scene){
      if(is_numeric($s)){
        $scene_totals[] = array_merge(['scene' => $scene['name']],$scene['stats']);
      }
    }
  }
}

foreach($d['character_totals'] as $n => $v){
  $character_totals[] = array_merge(['character' => $n],$v);
}

$caption = "<h4>Files, bgm, sfx, video, and bytes reflect total file requests, including multiple requests per file</h4>\n";

echo "<h1>Visual Novel System Statistics</h1><br /><br />\n";

echo "<h2>Grand Totals</h2>\n".$caption;
echo buildSimpleDataTable($grand_totals);
echo "<h2>Totals per Chapter</h2>\n".$caption;
echo buildSimpleDataTable($chapter_totals);
echo "<h2>Totals per Scene (All Chapters)</h2>\n".$caption;
echo buildSimpleDataTable($scene_totals);
echo "<h2>Totals per Character</h2>\n".$caption;
echo buildSimpleDataTable($character_totals);




?>

</body>
</html>