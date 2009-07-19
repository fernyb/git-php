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
  
  echo "<br /><strong>Summary Commit Log:</strong><br />";
  $log = $git->short_log($_GET['p']);
  var_dump($log);
  
  
  echo "<br /><br /><strong>Browse Project:</strong><br />";
  $list = $git->browse($_GET['p']);
  var_dump($list);
  
 exit; 
}

$projects = $git->projects();

var_dump($projects);

?>