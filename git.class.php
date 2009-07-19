<?php

class Git {
  private $projects = array();
  
  public function __construct($path) {
    if(is_string($path) && file_exists($path)) {
      array_push($this->projects, $path);
    } else if(is_array($path)) { 
      foreach($path as $repo) {
        if(is_string($repo)) {
          array_push($this->projects, $repo);
        }
      }
    } else {
      return false;
    }
    if(count($this->projects) == 0) {
      return false;
    }
    return true;
  }
  
  
  public function projects() {
    $projects = array();
    foreach($this->projects as $k => $repo) {
      $projects[$k]["description"]  = $this->get_short_desc($repo);
      $projects[$k]["owner"]        = $this->get_file_owner($repo);
      $projects[$k]["last_changed"] = $this->get_last_changed($repo);
      $projects[$k]["link"]         = $this->get_project_link($repo);
      $projects[$k]["targz"]        = $this->get_project_link($repo, "targz");
      $projects[$k]["zip"]          = $this->get_project_link($repo, "zip");
    }
    return $projects;
  }
  
  
  private function sanitized_url() {
    /* the sanitized url */
    $url = $_SERVER['SCRIPT_NAME'] . "?";
    /* the GET vars used by git-php */
    $git_get = array('p', 'dl', 'b', 'a', 'h', 't');
    foreach ($_GET as $var => $val) {
        if(!in_array($var, $git_get)){
            $get[$var] = $val;
            $url.="{$var}={$val}&amp;";
        }
    }
    return $url;
  }
  
  
  private function get_project_link($repo) {
    $path = basename($repo);
    if(!$type) {
        return "<a href=\"". $this->sanitized_url() ."p=$path\">$path</a>";
    } else if ($type == "targz") {
        return "<a href=\"". $this->sanitized_url() ."p=$path&dl=targz\">.tar.gz</a>";
    } else if ($type == "zip") {
        return "<a href=\"". $this->sanitized_url() ."p=$path&dl=zip\">.zip</a>";
    }    
  }
  
  
  private function get_last_changed($repo) {
    $out = array();
    $repo = $this->get_git($repo);
    $date = exec("GIT_DIR=$repo git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
    if(count($out) == 0) {
      $date = exec("GIT_DIR=$repo git rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
    }
    return date("D n/j/y G:i", (int)$date);
  }
  
  
  private function get_file_owner($repo) {
    $s = stat($repo);
    $pw = posix_getpwuid($s["uid"]);
    return preg_replace("/[,;]/", "", $pw["gecos"]);
  }
  
  
  private function get_short_desc($repo, $size=25) {
    $repo = $this->get_git($repo);
    $desc = file_get_contents("{$repo}/description");
    $trunc = false;
    $short = "";
    $d = explode(" ", $desc);
    foreach($d as $str) {
      if(strlen($short) < $size) {
        $short .= "{$str} ";
      } else {
        $trunc = true;
        break;
      }
    }
    if($trunc) {
      $short .= "...";
    }
    return $short;
  }
  
  
  private function get_git($repo=false) {
    if($repo) {
      if(file_exists("{$repo}/.git")) {
        $repo = "{$repo}/.git";
      }
    }
   return $repo;
  }
  
}

?>