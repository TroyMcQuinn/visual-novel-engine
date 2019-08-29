<?php

require('./dat/functions.php');

$cfg = parse_ini_file('./dat/config.ini');

$base = getBaseURL();

$nav = setupNavigationScheme();
$nav['section'] = !isset($nav['section']) ? $cfg['default_section'] : $nav['section'];

$html = ['header'=>'','main'=>''];

switch($nav['section']){
  
  case 'toc':
  default:
    $html['main'] .= "<h1>Table of Contents</h1>\n";
    if(!empty($_POST['toc'])){
      saveTOC(buildTOC());
    }
    $html['main'] .= showTOCEditor();
  break;
  
  case 'import':
    if(!empty($_POST['import_csv'])){
      $html['main'] .= importCSV();
    } else {
      $html['main'] .= showImporter();
    }
  break;
  
  case 'editor':
  
    if(!empty($_POST['script'])){
      if(isset($_POST['delete_script'])){
        $delete_filename = isset($_POST['script_file_name']) ? $_POST['script_file_name'] : false;
        $delete_filename = (strpos($delete_filename,'.json') === false) ? false : $delete_filename;
        unlink('../script/'.$delete_filename);
        $nav['script'] = false; // Do not open the deleted file.
      } else {
        $filename = !empty($_POST['script_file_name']) ? $_POST['script_file_name'] : 'untitled.json';
        if(strpos($filename,'.json') === false) $filename .= '.json';
        saveScript($filename,buildScript());
        $nav['script'] = $filename; // Open new file if saved as new name.
      }
    }
    
    if(!empty($nav['script']) && file_exists('../script/'.$nav['script'])){
      $script = getScript('../script/'.$nav['script']);
      if(empty($script)) $script = [[]]; // Create a basic empty array.
    } else {
      $script = false;
    }
    $new = (isset($nav['script']) && ($nav['script'] == 'new'));
    $html['main'] .= showScriptEditor($script,$new);

  
  break;
  
}

$html['main'] = showScriptSelector().$html['main']; // This has to be prepended after everything else.

?>