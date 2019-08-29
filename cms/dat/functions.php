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
    
    $nav['section'] = array_shift($input_vars);

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

function getScript($fn){
 return json_decode(file_get_contents($fn),1);  
}

function getFiles($dir){
  $a = [];
  $d = scandir($dir);
  foreach($d as $file){
    if(($file != '.') && ($file != '..')){
      if(is_dir($dir.'/'.$file)){
        foreach(getFiles($dir.'/'.$file) as $sub_file){
          $a[] = $file.'/'.$sub_file;
        }
      } else {
        $a[] = $file;
      }
    }
  }
  return $a;
}

function getTOC(){
  return json_decode(file_get_contents('../chapters.json'),1);
}

function getScripts(){
  $a = [];
  $files = getFiles('../script/');
  foreach($files as $file){
    $fn = explode('.',$file);
    if(array_pop($fn) == 'json'){
      $a[] = $file;
    }
  }
  return $a;
}

function getLayers(){
  $a = ['','clear'];
  return array_merge($a,getFiles('../assets/layer/'));
}

function getBackgrounds(){
  $a = ['','clear'];
  return array_merge($a,getFiles('../assets/bkgnd/'));
}

function getMusic(){
  $a = ['','clear'];
  return array_merge($a,getFiles('../assets/bgm/'));
}

function getSFX(){
  $a = ['','clear'];
  return array_merge($a,getFiles('../assets/sfx/'));
}

function getVideos(){
  $a = ['','clear'];
  return array_merge($a,getFiles('../assets/vid/'));
}

function buildMenu($name,$d,$sel=false,$onchange=''){
  $id = trim(strtolower(str_replace(']','',str_replace('[','_',$name))));
  $o  = "<select id=\"$id\" name=\"$name\" onchange=\"$onchange\" >\n";
  foreach($d as $n => $v){
    if(is_array($v)){
      $o .= "<optgroup label=\"$n\">\n";
      foreach($v as $n1 => $v1){
        $selected = ($sel == $v1) ? "selected=\"selected\" " : '';
        $o .= "<option value=\"$v1\" $selected>$v1</option>\n";
      }
      $o .= "</optgroup>\n";
    } else {
      $selected = ($sel == $v) ? "selected=\"selected\" " : '';
      $o .= "<option value=\"$v\" $selected>$v</option>\n";
    }
  }
  $o .= "</select>\n";
  return $o;
}

function showScriptSelector(){
  global $nav;
  $o  = "<form id=\"script_selector_form\" method=\"post\" action=\"\" >\n";
  $selected = !empty($nav['script']) ? $nav['script'] : false;
  $add = ['Table of Contents','New Script File','Import CSV Script'];
  if(empty($selected)){
    if(!empty($nav['script']) && ($nav['script'] == 'new')) $selected = 'New Script File';
    if($nav['section'] == 'toc') $selected = 'Table of Contents';
    if($nav['section'] == 'import') $selected = 'Import CSV Script';
  }
  $scripts = ['Options' => $add, 'Scripts' => getScripts()];
  $o .= buildMenu('script_selector',$scripts,$selected,'goMenu(this.value);');
  $o .= "</form>\n";
  return $o;
}

function showScriptEditor($d,$new=false){
  
  global $nav;
  
  //echo "<pre>".print_r($d,1),"</pre>";
  
  if((empty($d)) && (!$new)) return "<h1>No script file selected.</h1>\n";
  
  if($new){
    $d = [[]];
    $o  = "<h1>New Script (Please set a file name.  Click a line to edit)</h1>\n";
    $script_file_name = '';
  } else {
    $o  = "<h1>Script: ".$nav['script']." (click a line to edit)</h1>\n";
    $script_file_name = $nav['script'];
  }
  
  $d['TEMPLATEID'] = [];
  
  $o  = "<h1>Script: ".$nav['script']." (click a line to edit)</h1>\n";
  
  // Preview window
  $o .= "<div id=\"preview\" class=\"preview_hide\" onclick=\"togglePreview();\" >PREVIEW\n";
  for($i=1;$i<=6;$i++){
    $o .= "<div class=\"preview_layer\" id=\"preview_layer_$i\" style=\"display: none;\"></div>\n";
  }
  $o .= "<div id=\"preview_scroll\" style=\"display: none;\"></div>\n";
  $o .= "<div id=\"preview_card\" style=\"display: none;\"></div>\n";
  $o .= "<div id=\"preview_video\" style=\"display: none;\">VIDEO PLAYS HERE</div>\n";
  $o .= "</div>\n";
  
  $o .= "<div id=\"script_lines\"></div>\n";
  
  $o .= "<form id=\"script_editor\" method=\"post\" action=\"\" >\n";
  
  $o .= "<input type=\"hidden\" id=\"script_file_name\" name=\"script_file_name\" value=\"$script_file_name\" />\n";
  
  $o .= "<div id=\"script_editor_frames\">\n";
  
  foreach($d as $id => $frame){
    
    $o .= str_repeat("\n",20);
    
    if($id === 'TEMPLATEID'){
      $o .= "</div><div id=\"script_editor_frame_templates\">\n";
    }
    
    $onchange = 'updateEditor();';
    
    $hr = "<tr><td colspan=\"3\"><hr /></td></tr>\n";
    
    // Start frame
    $o .= "<table class=\"script_frame\" style=\"display: none;\" id=\"frame_$id\" >\n";
    $o .= "<thead><tr><th colspan=\"3\" class=\"frame_header\" >Frame #<span id=\"frame_ord_$id\"></span> (ID $id)</th></tr></thead>\n";
    $o .= "<tbody>\n";
    
    // Controls and Options
    $o .= "<tr><td colspan=\"3\"><div class=\"controls\">";
    $o .= "<a class=\"button\" title=\"Move this frame up in line\" onclick=\"moveFrame('up',$id);\">&#9650;</a>";
    $o .= "<a class=\"button\" title=\"Move this frame back in line\" onclick=\"moveFrame('down',$id);\">&#9660;</a>";
    $o .= "<a class=\"button nc\" title=\"Add a new frame\" onclick=\"addFrame($id);\">&#10133;</a>";
    $o .= "<a class=\"button nc\" title=\"Remove this frame\" onclick=\"removeFrame($id);\">&#10060;</a>";
    
    $o .= "<div class=\"nav\">";
    $o .= "<a class=\"button nc\" title=\"Go to first frame\" onclick=\"firstFrame();\">&#8810;</a>";
    $o .= "<a class=\"button nc\" title=\"Go to previous frame\" onclick=\"prevFrame($id);\">&#9668;</a>";
    $o .= "<a class=\"button nc\" title=\"Go to next frame\"onclick=\"nextFrame($id);\">&#9658;</a>";
    $o .= "<a class=\"button nc\" title=\"Go to last frame\" onclick=\"lastFrame();\">&#8811;</a>";
    $o .= "</div>";
    
    $o .= "</div></td></tr>\n";
    
    // Background
    $background_files = getBackgrounds();
    $selected = isset($frame['background']) ? $frame['background'] : false;
    $eid = "script[$id][background]";
    $o .= "<tr><td>Background</td><td id=\"current_background_$id\"></td><td>".buildMenu($eid,$background_files,$selected,$onchange)."</td></tr>";    
    
    // Layers
    $layer_files = getLayers();
    $o .= "<tr><td colspan=\"3\">Layer Images</td></tr><tr><td colspan=\"3\"><table>";
    $o .= "<thead><tr><th></th><th></th><th>Image</th><th>X</th><th>Y</th></tr></thead><tbody>";
    for($i=1;$i<=6;$i++){
      $selected = isset($frame['layer'.$i]) ? $frame['layer'.$i] : false;
      $eid = "script[$id][layers][$i][image]";
      $o .= "<tr><td>Layer $i</td><td id=\"current_layer_{$i}_$id\"></td><td>";
      $o .= buildMenu($eid,$layer_files,$selected,$onchange);
      $o .= "</td>";
      $tweak_x = isset($frame['tweaks']['layer'.$i]['x']) ? $frame['tweaks']['layer'.$i]['x'] : '';
      $tweak_y = isset($frame['tweaks']['layer'.$i]['y']) ? $frame['tweaks']['layer'.$i]['y'] : '';
      $o .= "<td><input type=\"number\" id=\"script_{$id}_layers_{$i}_tweaks_x\" name=\"script[$id][layers][$i][tweaks][x]\" value=\"$tweak_x\" /></td>";
      $o .= "<td><input type=\"number\" id=\"script_{$id}_layers_{$i}_tweaks_y\" name=\"script[$id][layers][$i][tweaks][y]\" value=\"$tweak_y\" /></td>";
      $o .= "</tr><tr><td colspan=\"5\"></td></tr>";      
    }
    $o .= "</tbody></table></td></tr>"; 
    
    // Music
    $music_files = getMusic();
    $selected = isset($frame['music']) ? $frame['music'] : false;
    $eid = "script[$id][music]";
    $o .= "<tr><td>Music / Background Audio</td><td id=\"current_music_$id\"></td><td>".buildMenu($eid,$music_files,$selected,$onchange)."</td></tr>";   
    
    // Sound Effects
    $sfx_files = getSFX();
    $selected = isset($frame['sfx']) ? $frame['sfx'] : false;
    $eid = "script[$id][sfx]";
    $o .= "<tr><td>Sound Effect</td><td id=\"current_sfx_$id\"></td><td>".buildMenu($eid,$sfx_files,$selected,$onchange)."</td></tr>";  
    
    // Video Clips
    $video_files = getVideos();
    $selected = isset($frame['video']) ? $frame['video'] : false;
    $eid = "script[$id][video]";
    $o .= "<tr><td>Video Clip</td><td id=\"current_video_$id\"></td><td>".buildMenu($eid,$video_files,$selected,$onchange)."</td></tr>";  
    
    // Title Card
    $card = !empty($frame['card']) ? $frame['card'] : '';
    $o .= "<tr><td>Title Card</td><td id=\"current_card_$id\"></td><td><input type=\"text\" id=\"script_{$id}_card\" name=\"script[$id][card]\" value=\"$card\" onchange=\"$onchange\" /></td></tr>";
    
    // Credits Scroller
    $scroll = !empty($frame['scroll']) ? htmlspecialchars(implode("\n",$frame['scroll'])) : '';
    $o .= "<tr><td>Credits Scroll</td><td id=\"current_scroll_$id\"></td><td><textarea id=\"script_{$id}_scroll\" name=\"script[$id][scroll]\" onchange=\"$onchange\" >$scroll</textarea></td></tr>";
    
    // Fade Option
    $fade = !empty($frame['fade']) ? $frame['fade'] : '';
    $o .= "<tr><td>Fade</td><td id=\"current_fade_$id\"></td><td>";
    $eid = "script[$id][fade]";
    $fade_options = ['','black','white'];
    $selected = isset($frame['fade']) ? $frame['fade'] : false;
    $o .= buildMenu($eid,$fade_options,$selected);
    $o .= "</td></tr>";    
    
    // Notes
    $notes = !empty($frame['notes']) ? $frame['notes'] : '';
    $o .= "<tr><td>Notes</td><td></td><td>";
    $o .= "<textarea id=\"script_{$id}_notes\" name=\"script[$id][notes]\" >$notes</textarea>";
    $o .= "</td></tr>";
    
    // Text
    $title = !empty($frame['title']) ? $frame['title'] : '';
    $text = !empty($frame['text']) ? $frame['text'] : '';
    $o .= "<tr><td>Dialogue</td><td colspan=\"2\" class=\"dialogue\">";
    $o .= "<label for=\"script[$id][title]\">Title / Character</label><input type=\"text\" id=\"script_{$id}_title\" name=\"script[$id][title]\" value=\"$title\" onchange=\"updateEditor();\" />";
    $o .= "<label for=\"script[$id][text]\">Text</label><textarea id=\"script_{$id}_text\" name=\"script[$id][text]\" onchange=\"updateEditor();\">$text</textarea>";
    $o .= "</td></tr>";
    
    // End frame
    $o .= "</tbody>\n";
    $o .= "</table>\n";
    
  }
  
  $o .= "</div>\n";
  
  $o .= "<div id=\"button_container\">\n";
  $o .= "<button id=\"save_script\" name=\"save_script\" onclick=\"return saveScript();\" >Save</button>\n";
  $o .= "<button id=\"save_script_as\" name=\"save_script\" onclick=\"return saveAs();\" >Save As...</button>\n";
  $o .= "<button class=\"delete\" id=\"delete_script\" name=\"delete_script\" onclick=\"return deleteScript();\" >Delete</button>\n";
  $o .= "</div>\n";
  $o .= "</form>\n";
  
  $o .= "<script type=\"text/javascript\">initEditor();</script>\n";
  
  return $o;
  
}

function buildScript(){
  
  $d = $_POST['script'];
  $o = [];
  
  // echo "<pre>".print_r($d,1)."</pre>\n"; exit();
  
  if(isset($d['TEMPLATEID'])) unset($d['TEMPLATEID']);
  
  foreach($d as $id => $frame){
    
    $a = [];
    
    // Layers and layer tweaks
    foreach($frame['layers'] as $i => $layer){
      if(!empty($layer['image'])){
        $a['layer'.$i] = $layer['image'];
      }
      if(!empty($layer['tweaks']['x']) || !empty($layer['tweaks']['y'])){
        if(!isset($a['tweaks'])){
          $a['tweaks'] = [];          
        }
        $a['tweaks']['layer'.$i] = new stdClass();
        $a['tweaks']['layer'.$i]->x = !empty($layer['tweaks']['x']) ? $layer['tweaks']['x'] : 0;
        $a['tweaks']['layer'.$i]->y = !empty($layer['tweaks']['y']) ? $layer['tweaks']['y'] : 0;
      }      
    }
    
    // Everything else.
    $items = ['background', 'music','sfx','video','card','title','text','fade','scroll','notes'];
    foreach($items as $item){
      if(!empty($frame[$item])){
        if($item == 'scroll'){
          $scroll = [];
          foreach(explode("\n",$frame[$item]) as $n => $v){
            if(!empty(trim($v))){
              $scroll[] = trim(str_replace(Array("\n","\r"),'',$v));
            }
          }
          $a[$item] = $scroll;
        } else {
          $a[$item] = $frame[$item];
        }
      }
    }
    
    if(!empty($a)){
      $o[] = $a;
    }
        
  }
  //echo "<pre>".json_encode($o,JSON_PRETTY_PRINT)."</pre>\n"; exit();
  return json_encode($o,JSON_PRETTY_PRINT);
}

function showTOCEditor(){
  
  $h = ''; // Main editor HTML
  $t = ''; // Template HTML
  
  $onchange = '';
  
  $d = getTOC();
  
  $d['TOCTEMPLATE'] = [
    'label' => '',
    'scenes' => [
       'TOCSCENETEMPLATE' => [
        'name' => '',
        'file' => ''
      ]
    ]
  ];
  
  $scripts = getScripts();
  
  // print_r($d); exit();
  
  foreach($d as $chapter_id => $chapter){
    
    $o  = "<tbody class=\"chapter\" id=\"chapter_$chapter_id\">\n";
    
    $o .= "<tr class=\"chapter_header\"><td colspan=\"2\"><input class=\"chapter_label\" type=\"text\" id=\"toc_{$chapter_id}_label\" name=\"toc[$chapter_id][label]\" value=\"".$chapter['label']."\" /></td>\n";
    
    // Chapter controls.
    $o .= "<td class=\"toc_chapter_controls\">\n";
    $o .= "<a class=\"button\" title=\"Move this chapter up in line\" onclick=\"moveChapter('up',$chapter_id);\">&#9650;</a>";
    $o .= "<a class=\"button\" title=\"Move this chapter back in line\" onclick=\"moveChapter('down',$chapter_id);\">&#9660;</a>";
    $o .= "<a class=\"button nc\" title=\"Add a new chapter\" onclick=\"addChapter($chapter_id);\">&#10133;</a>";
    $o .= "<a class=\"button nc\" title=\"Remove this chapter\" onclick=\"removeChapter($chapter_id);\">&#10060;</a>";
    $o .= "</td></tr>\n";  
    
    $o .= "<tr><td colspan=\"3\" id=\"chapter_{$chapter_id}_scenes\" ><br />\n";        
    if(!empty($chapter['scenes'])){
      foreach($chapter['scenes'] as $scene_id => $scene){
        $o .= "<table class=\"scene\" id=\"scene_$scene_id\">\n";
        $o .= "<tr>\n";
        $o .= "<td><input type=\"text\" id=\"toc_{$chapter_id}_scenes_{$scene_id}_name\" name=\"toc[$chapter_id][scenes][$scene_id][name]\" value=\"".$scene['name']."\" /></td>";
        $selected = isset($scene['file']) ? $scene['file'].'.json' : false;
        $eid = "toc[$chapter_id][scenes][$scene_id][file]";
        $o .= "<td>".buildMenu($eid,$scripts,$selected,$onchange)."</td>\n";
        
        // Scene controls.
        $o .= "<td class=\"toc_scene_controls\">\n";
        $edit_link = 'go/editor/script/'.$scene['file'].'.json';
        $o .= "<a class=\"button\" title=\"Edit this scene script\" onclick=\"window.location = '$edit_link'\">E</a>";
        $o .= "<a class=\"button\" title=\"Move this scene up in line\" onclick=\"moveScene('up',$chapter_id,$scene_id);\">&#9650;</a>";
        $o .= "<a class=\"button\" title=\"Move this scene back in line\" onclick=\"moveScene('down',$chapter_id,$scene_id);\">&#9660;</a>";
        $o .= "<a class=\"button nc\" title=\"Add a new scene\" onclick=\"addScene($chapter_id,$scene_id);\">&#10133;</a>";
        $o .= "<a class=\"button nc\" title=\"Remove this scene\" onclick=\"removeScene($chapter_id,$scene_id);\">&#10060;</a>";
        $o .= "</td>\n";    
        $o .= "</tr>\n";
        $o .= "</table>\n";
        
      }
    }
    $o .= "</td></tr><tr><td colspan=\"3\"><hr /><br /></td></tr></tbody>\n";
    if($chapter_id !== 'TOCTEMPLATE'){
      $h .= $o;
    } else {
      $t = $o;
    }
    
  }

  $c  = "<div id=\"button_container\">\n";
  $c .= "<button id=\"add_scene\" name=\"add_scene\" onclick=\"event.preventDefault(); addScene(); \">New Scene</button>\n";
  $c .= "<button id=\"save_toc\" name=\"save_toc\">Update</button>\n";
  $c .= "</div>\n";

  return "<form method=\"post\" action=\"\"><table id=\"toc_editor\">\n$h\n</table>\n<table id=\"toc_editor_template\">$t</table>\n$c</form>";
  
}

function buildTOC(){
  
  $d = $_POST['toc'];
  $a = [];
  if(isset($d['TOCTEMPLATE'])) unset($d['TOCTEMPLATE']);
  foreach($d as $chapter_id => $chapter){
    $tmp = ['label'=>$chapter['label']];
    $tmp['scenes'] = [];
    foreach($chapter['scenes'] as $scene){
      $scene['file'] = str_replace('.json','',$scene['file']);
      $tmp['scenes'][] = $scene;
    }
    $a[] = $tmp;    
  }
  
  return json_encode($a,JSON_PRETTY_PRINT);
  
}

function saveScript($filename,$script){
  file_put_contents('../script/'.$filename,$script);
}

function saveTOC($toc){
  file_put_contents('../chapters.json',$toc);  
}

function showImporter(){
  
  $o  = "<h1>Import CSV Script</h1>\n";
  
  $o .= "<form id=\"import_form\" method=\"post\" action=\"\" enctype=\"multipart/form-data\" >\n";
  $o .= "<table>\n";
  
  $o .= "<tr><td><label for=\"filename\">File Name:</label></td><td><input type=\"text\" name=\"filename\" value=\"imported.json\" /></td></tr>\n";
  $o .= "<tr><td><label for=\"delimiter\">Delimiter (1 character only):</label></td><td><input type=\"text\" name=\"delimiter\" value=\",\" size=\"1\" maxlength=\"1\" /></td></tr>\n";
  $o .= "<tr><td><label for=\"enclosure\">Enclosure (1 character only):</label></td><td><input type=\"text\" name=\"enclosure\" value=\"&quot;\" size=\"1\" maxlength=\"1\" /></td></tr>\n";
  $o .= "<tr><td><label for=\"upload\">Upload CSV File:</label></td><td><input type=\"file\" name=\"upload\" /></td></tr>\n";
  
  $o .= "</table>\n";
  $o .= "<div id=\"button_container\"><button name=\"import_csv\" value=\"import_csv\" >Import</button></div>\n";
  $o .= "</form>\n";  
  
  return $o;
  
}

function importCSV(){
  
  if(empty($_FILES['upload'])) return "<h1>No file uploaded.</h1>\n";
  
  $delimiter = !empty($_POST['delimiter']) ? $_POST['delimiter'] : ',';
  $enclosure = !empty($_POST['enclosure']) ? $_POST['enclosure'] : '"';
  
  $filename = trim($_POST['filename']);
  if(strpos($filename,'.json') === false) return "<h1>File Name must end in .json</h1>\n";
  
  $d = []; // Main data output
  $k = []; // Keys
  
  // Read the data from the CSV file.
  if(($fh = fopen($_FILES['upload']['tmp_name'],'r')) !== false){
    while(($line = fgetcsv($fh,2214,$delimiter,$enclosure)) !== false){
      if(empty($k)){
        foreach($line as $n => $v){
          if(!empty($v)){
            $k[] = trim($v);
          }
        }
      } else {
        $t = [];
        foreach($line as $n => $v){
          if(!empty($k[$n])){
            switch($k[$n]){
              case 'tweaks':
                if(!empty($v)){
                  $tmp = [];
                  foreach(explode(',',$v) as $i => $w){
                    $layer = 'layer'.(floor($i/2)+1);
                    if($i % 2 == 0){
                      $tmp[$layer] = [];
                      $tmp[$layer]['x'] = intval(trim($w));
                    } else {
                      $tmp[$layer]['y'] = intval(trim($w));
                    }
                  }
                  $t[$k[$n]] = $tmp;
                }
              break;
              case 'scroll':
                $t[$k[$n]] = explode("\n",trim($v));
              break;
              default:
                $t[$k[$n]] = trim($v);
              break;
            }
          }
        }
        $d[] = $t;
      }      
    }    
  }
  
  $d = json_encode($d,JSON_PRETTY_PRINT);  
  //echo $d; exit();  
  file_put_contents('../script/'.$filename,$d);
  
  return "<h1>Import Successful.</h1>\n";
  
}






?>