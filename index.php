<?php
date_default_timezone_set("America/Los_Angeles");
$git_embed = false;

include_once "lib/geshi.php";  
include_once "git.php";

require_once "git.class.php";


$git = new Git("/Users/fernyb/rails/invoizer");
$projects = $git->projects();

var_dump($projects);

?>