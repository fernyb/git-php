<?php
date_default_timezone_set("America/Los_Angeles");
$git_embed = false;

include_once "lib/geshi.php";  
include_once "git.php";

require_once "git.class.php";


foreach ($_GET as $var=>$val){
    $_GET[$var] = str_replace(";", "", $_GET[$var]);
}


$git = new Git(array("invoizer" => "/Users/fernyb/rails/invoizer"));


if(isset($_GET['p'])) {
  
  if(isset($_GET['dl']) && $_GET['dl'] == "plain") {
    $project   = $_GET['p'];
    $file_hash = $_GET['h'];
    
    echo "<br /><strong>Plain View Source Code</strong><br />";
    $source = $git->plain($project, $file_hash);
    var_dump($source);
  }
  
  if(isset($_GET['b'])) {
    echo "<br /><br /><strong>Blob</strong><br /><br />";
    $project   = $_GET['p'];
    $blob = $git->blob($project, $_GET['b']);        
    echo $blob;
    echo "<br />";
  }
  
  
  if(isset($_GET['a']) && $_GET['a'] == "commitdiff") {
    
    echo "<br /><strong>Commit Diff Name Status</strong><br />";
    $diff_file_names = $git->diff_name_status($_GET['p'], $_GET['hb'], $_GET['h']);
    var_dump($diff_file_names);

    echo "<hr />";
    
    echo "<br /><strong>Commit Diff File => ". $diff_file_names[0]['file'] ."</strong><br />";
    $diff_file = $git->diff($_GET['p'], $_GET['hb'], $_GET['h'], $diff_file_names[0]['file']);
    echo $diff_file;
  
    echo "<hr />";
  
    echo "<br /><strong>Commit Diff Code</strong><br />";    
    $diff = $git->diff($_GET['p'], $_GET['hb'], $_GET['h']);
    echo $diff;
    echo "<br />";
  }
  
  echo "<hr />";
    
  echo "<br /><strong>Summary Commit Log:</strong><br />";
  $log = $git->log($_GET['p']);
  var_dump($log);
  
  echo "<hr />";
    
  echo "<br /><br /><strong>Browse Project:</strong><br />";

  
  $tree = isset($_GET['t']) ? $_GET['t'] : "HEAD"; 

  $project = $_GET['p'];  
  
  if(isset($_GET['b'])) {
    $tree = $_GET['b'];
    echo "Blob:<br />";
    $list = $git->browse($project, $tree, true);
    echo $list;
  } else {
    $list = $git->browse($project, $tree);
    var_dump($list); 
  }
 

  
 exit; 
}

$projects = $git->projects();

var_dump($projects);

?>