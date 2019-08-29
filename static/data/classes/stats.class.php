<?php

class stats{
  
  var $stats = []; // Main stats array.
  
  var $stats_template = [
      'bytes' => 0,
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
      'files' => 0
    ];
    
  var $uniques_template = [ 
      'layers' => [],
      'backgrounds' => [],
      'bgm' => [],
      'sfx' => [],
      'video' => []
    ];
    
  var $character_stats_template = [
    'lines' => 0,
    'text_length' => 0
  ];
  
  // Constructor.
  public function __construct(){}

  // Add value to array(s).  If array element does not exist, it is created. $u = only add unique if true.
  private function add(&$a,$n,$v,$u=false){
    if(!is_numeric($v)) throw new Exception('Error in stats::add(): "'.$v.'" is a non-numeric value.');
    if(!isset($a[$n])){
      if(!$u || !in_array($v,$a)){
        $a[$n] = $v;
      }
    } else {
      $a[$n] += $v;
    }
  }
  
  // Updates a value.
  private function update(&$a,$n,$v){
    $a[$n] = $v;
  }

  // Adds an array or value if it does not already exist.  $u = set as unique value instead of as unique key.
  private function set(&$a,$n,$v,$u=false){
    if(!isset($a[$n])){
      if($u){
        $a[] = $v;
      } else {
        $a[$n] = $v;
      }
    }
  }
  
  // Adds a value to an sub-array under a specified key.  $u = set as unique value.
  private function push(&$a,$n,$v,$u=false){
    if(!isset($a[$n])){
      $a[$n] = [];
    }
    if(!$u || !in_array($v,$a[$n])){
      $a[$n][] = $v; 
    }
  }

  // Adds stats to stats array.
  // $chapter = chapter number.
  // $scene = scene number.
  // $type = background, layer, character, etc.
  // $name = the name of the item.  Not used for all types.  Not to be confused with the name of the stats field!
  // $value = amount to add to total.  If omitted, will increment by 1.
  public function put($chapter,$scene,$type,$name=false,$value=1){
    
    // Ensure all levels in the array exist for scenes.
    $this->set($this->stats,'stats',$this->stats_template);
    $this->set($this->stats,'chapters',[]);
    $this->set($this->stats['chapters'],$chapter,[]);
    $this->set($this->stats['chapters'][$chapter],'stats',$this->stats_template);
    $this->set($this->stats['chapters'][$chapter],'scenes',[]);
    $this->set($this->stats['chapters'][$chapter]['scenes'],$scene,[]);
    $this->set($this->stats['chapters'][$chapter]['scenes'][$scene],'stats',$this->stats_template);
    
    // Ensure all levels in the array exist for uniques.
    $this->set($this->stats,'uniques',$this->uniques_template);
    $this->set($this->stats['chapters'][$chapter],'uniques',$this->uniques_template);
    $this->set($this->stats['chapters'][$chapter]['scenes'][$scene],'uniques',$this->uniques_template);
    
    // Ensure all levels in the array exist for character stats.
    $this->set($this->stats,'character_stats',[]);
    $this->set($this->stats['chapters'][$chapter],'character_stats',[]);
    $this->set($this->stats['chapters'][$chapter]['scenes'][$scene],'character_stats',[]);
    
    // Process stats based on the type.
    switch($type){
      
      case 'script':
      
        $this->add($this->stats['stats'],'files',1);
        $this->add($this->stats['stats'],'bytes',$value);
        $this->add($this->stats['chapters'][$chapter]['stats'],'files',1);
        $this->add($this->stats['chapters'][$chapter]['stats'],'bytes',$value);
        $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],'files',1);
        $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],'bytes',$value);
        
      break;
      
      case 'frames':
      case 'cards':      
      case 'scrolls':     
      case 'scroll_lines':
      case 'text_lines':
      case 'text_length':
        
        $this->add($this->stats['stats'],$type,$value);
        $this->add($this->stats['chapters'][$chapter]['stats'],$type,$value);
        $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],$type,$value);

      break;
      
      case 'backgrounds':
      case 'layers':
      case 'bgm':
      case 'sfx':
      case 'video':
      
        // Tally unique items, add bytes
        if(!in_array($name,$this->stats['uniques'][$type])){
          $this->add($this->stats['stats'],$type,1);
          $this->add($this->stats['stats'],'files',1);
          $this->add($this->stats['stats'],'bytes',$value);
        }
        if(!in_array($name,$this->stats['chapters'][$chapter]['uniques'][$type])){
          $this->add($this->stats['chapters'][$chapter]['stats'],$type,1);
          $this->add($this->stats['chapters'][$chapter]['stats'],'files',1);
          $this->add($this->stats['chapters'][$chapter]['stats'],'bytes',$value);
        }
        if(!in_array($name,($this->stats['chapters'][$chapter]['scenes'][$scene]['uniques'][$type]))){
          $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],$type,1);
          $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],'files',1);
          $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],'bytes',$value);
        }
        
        // Add uniques
        $this->push($this->stats['uniques'],$type,$name,true);
        $this->push($this->stats['chapters'][$chapter]['uniques'],$type,$name,true);
        $this->push($this->stats['chapters'][$chapter]['scenes'][$scene]['uniques'],$type,$name,true);
        
      break;
      
      case 'characters': // The way character stats are stored automatically keeps them unique.
      
        // Ensure character stats slot exists.
        $this->set($this->stats['character_stats'],$name,$this->character_stats_template);
        $this->set($this->stats['chapters'][$chapter]['character_stats'],$name,$this->character_stats_template);
        $this->set($this->stats['chapters'][$chapter]['scenes'][$scene]['character_stats'],$name,$this->character_stats_template);
        
        // Increment number of lines for the character.
        $this->add($this->stats['character_stats'][$name],'lines',1);
        $this->add($this->stats['chapters'][$chapter]['character_stats'][$name],'lines',1);
        $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['character_stats'][$name],'lines',1);
      
        // Add total text line length for character.
        $this->add($this->stats['character_stats'][$name],'text_length',$value);
        $this->add($this->stats['chapters'][$chapter]['character_stats'][$name],'text_length',$value);
        $this->add($this->stats['chapters'][$chapter]['scenes'][$scene]['character_stats'][$name],'text_length',$value);
        
        // Update stats character counts
        $this->update($this->stats['stats'],'characters',count($this->stats['character_stats']));
        $this->update($this->stats['chapters'][$chapter]['stats'],'characters',count($this->stats['chapters'][$chapter]['character_stats']));
        $this->update($this->stats['chapters'][$chapter]['scenes'][$scene]['stats'],'characters',count($this->stats['chapters'][$chapter]['scenes'][$scene]['character_stats']));
      
      break;
      
    }
    
  }
  
  public function getGrandTotals($as_row=false){
    if($as_row){
      return [0 => $this->stats['stats']];
    } else {
      return $this->stats['stats'];
    }
  }
  
  public function getChapterTotals(&$d){
    $a = [];
    foreach($d as $chapter_id => $chapter){
      $a[] = array_merge(['chapter' => $chapter['label']],$this->stats['chapters'][$chapter_id]['stats']);
    }
    return $a;
  }
  
  public function getSceneTotals(&$d){
    $a = [];
    foreach($d as $chapter_id => $chapter){
      foreach($chapter['scenes'] as $scene_id => $scene){
        $a[] = array_merge(['scene' => $scene['name']],$this->stats['chapters'][$chapter_id]['scenes'][$scene_id]['stats']);
      }
    }
    return $a;    
  }
  
  public function getCharacterGrandTotals(){
    $a = [];
    foreach($this->stats['character_stats'] as $character => $character_stats){
      $a[] = array_merge(['character' => $character],$character_stats);
    }
    return $a;
  }

}

?>