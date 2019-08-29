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

require('./classes/stats.class.php');
require('./functions.php');

$d = json_decode(file_get_contents('../../chapters.json'),true);

$stats = new stats();

foreach($d as $chapter_id => $chapter){
  
  foreach($chapter['scenes'] as $scene_id => $scene){
    
    $script = $d[$chapter_id]['scenes'][$scene_id]['script'] = json_decode(file_get_contents('../../script/'.$scene['file'].'.json'),true);
    
    // Count script as a file
    $stats->put($chapter_id,$scene_id,'script',false,filesize('../../script/'.$scene['file'].'.json'));
        
    foreach($script as $frame){
      
      // Tally frames
      $stats->put($chapter_id,$scene_id,'frames');
      
      // Tally backgrounds
      if((!empty($frame['background'])) && ($frame['background'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'backgrounds',$frame['background'],filesize('../../assets/bkgnd/'.$frame['background']));
      }
      
      // Tally layers
      for($i=1;$i<=6;$i++){
        if((!empty($frame['layer'.$i])) && ($frame['layer'.$i] != 'clear')){
          $stats->put($chapter_id,$scene_id,'layers',$frame['layer'.$i]);
        }
      }
      
      // Tally background music / audio
      if((!empty($frame['music'])) && ($frame['music'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'bgm',$frame['music'],filesize('../../assets/bgm/'.$frame['music']));
      }
      
      // Tally sound effects
      if((!empty($frame['sfx'])) && ($frame['sfx'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'sfx',$frame['sfx'],filesize('../../assets/sfx/'.$frame['sfx']));
      }
      
      // Tally video clips
      if((!empty($frame['video'])) && ($frame['video'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'video',$frame['video'],filesize('../../assets/vid/'.$frame['video']));
      }
      
      // Tally title cards
      if((!empty($frame['card'])) && ($frame['card'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'cards');
        $stats->put($chapter_id,$scene_id,'text_length',false,strlen($frame['card']));
      }
      
      // Tally credit scrolls
      if((!empty($frame['scroll'])) && ($frame['scroll'] != 'clear')){
        $stats->put($chapter_id,$scene_id,'scrolls');
        foreach($frame['scroll'] as $line){
          $stats->put($chapter_id,$scene_id,'scroll_lines');
          $stats->put($chapter_id,$scene_id,'text_length',false,strlen($line));
        }
      }
      
      // Tally text
      if(!empty($frame['text'])){
        $stats->put($chapter_id,$scene_id,'text_lines');
        $stats->put($chapter_id,$scene_id,'text_length',false,strlen($frame['text']));
        if(!empty($frame['title'])){
          $stats->put($chapter_id,$scene_id,'text_length',false,strlen($frame['title']));
          $stats->put($chapter_id,$scene_id,'characters',$frame['title'],strlen($frame['text']));
        }
      }
      
    }
    
  }  
  
}

// echo "<pre>".print_r($stats->stats,1)."</pre>"; exit();

// echo "<pre>".print_r($d,1)."</pre>";

$caption = "<h4></h4>\n";

echo "<h1>Visual Novel System Statistics</h1><br /><br />\n";

echo "<h2>Grand Totals</h2>\n".$caption;
echo buildSimpleDataTable(rotateTable($stats->getGrandTotals()));
echo "<h2>Totals per Chapter</h2>\n".$caption;
echo buildSimpleDataTable($stats->getChapterTotals($d));
echo "<h2>Totals per Scene (All Chapters)</h2>\n".$caption;
echo buildSimpleDataTable($stats->getSceneTotals($d));
echo "<h2>Totals per Character</h2>\n".$caption;
echo buildSimpleDataTable($stats->getCharacterGrandTotals());

?>

</body>
</html>