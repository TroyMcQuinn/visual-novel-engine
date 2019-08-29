<?php

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

function rotateTable($d,$label1='item',$label2='value'){
  $a = [];
  foreach($d as $n => $v){
    // if($n == 'bytes') $v = byteFormat($v);
    $n = ucwords(str_replace('_',' ',$n));
    $a[] = [$label1 => $n,$label2 => $v];
  }
  return $a;
}

?>