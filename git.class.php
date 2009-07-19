<?php

class Git {
  public  $date_format = "D n/j/y G:i";
  private $projects    = array();
  
  public function __construct($path) {
    if(is_array($path)) { 
      foreach($path as $name => $repo) {
        if(is_string($name) && is_string($repo)) {
          $this->projects[$name] = $repo;
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
  
  public function blob($project, $hash) {
    $path = $this->git_repo_path($project);
    $repo = $this->get_git($path);
    $out = array();
    exec("GIT_DIR={$repo} git cat-file blob {$hash}", &$out);
    if(count($out) > 0) { 
      $source = implode("\n", $out);
      $geshi = new GeSHi($source, "plain");
      $code = "<div class=\"gitcode\">\n";
      $code .= $geshi->parse_code();
      $code .= "</div>\n";
      return $code;
    }
    return false;
  }
  
  public function plain($project, $file_hash) {
    $path = $this->git_repo_path($project);
    $repo = $this->get_git($path);
    $out = array();
    exec("GIT_DIR={$repo} git cat-file blob {$file_hash}", &$out);
    if(count($out) > 0) {
      return join("\n", $out);
    }
    return false;
  }
  
  public function browse($project, $tree="HEAD", $blob=false) {
    if($blob == true) {
      return $this->blob($project, $tree);
    } else {
      return $this->tree($project, $tree); 
    }
  }
  
  private function git_repo_path($project) {
    foreach ($this->projects as $repo => $repo_path) {
      $path = basename($repo);
      if($path == $project) {
        return $this->get_git($repo_path);
      }
    }
    return false;
  }
  
  private function git_ls_tree($repo, $tree) {
    $ary = array();
    $out = array();
    //Have to strip the \t between hash and file
    exec("GIT_DIR=$repo git-ls-tree $tree | sed -e 's/\t/ /g'", &$out);
    if(count($out) == 0) {
      unset($out);
      exec("GIT_DIR=$repo git ls-tree $tree | sed -e 's/\t/ /g'", &$out);
    }
    
    foreach ($out as $line) {
        $entry = array();
        $arr = explode(" ", $line);
        $entry['perm'] = $arr[0];
        $entry['type'] = $arr[1];
        $entry['hash'] = $arr[2];
        $entry['file'] = $arr[3];
        $ary[] = $entry;
    }
    return $ary;
  }
  
  
  private function git_commit($repo, $cid)  {
    $out = array();
    $commit = array();

    if (strlen($cid) <= 0) {
        return 0;
    }
    $repo = $this->get_git($repo);
  
    exec("GIT_DIR=$repo git-rev-list  --header --max-count=1 $cid", &$out);
    if(count($out) == 0) {
      exec("GIT_DIR=$repo git rev-list  --header --max-count=1 $cid", &$out);
    }
    
    $commit["commit_id"] = $out[0];
    $g = explode(" ", $out[1]);
    $commit["tree"] = $g[1];

    $g = explode(" ", $out[2]);
    $commit["parent"] = $g[1];

    $g = explode(" ", $out[3]);

    preg_match("/author(.*)<(.*)>(.*)/", $out[3], $matches);
    if(count($matches) > 0) {
      $commit['author'] = trim($matches[1]);
      $commit['author_email'] = trim($matches[2]);
      $commit['author_date'] = trim($matches[3]);
      unset($matches);
    }    
    
    preg_match("/committer(.*)<(.*)>(.*)/", $out[4], $matches);
    if(count($matches) > 0) {
      $commit['committer'] = trim($matches[1]);
      $commit['committer_email'] = trim($matches[2]);
      $commit['committer_date'] = trim($matches[3]);
      unset($matches);
    }

    $commit["date"] = $commit['author_date'];
    $commit["message"] = "";
    $size = count($out);
    for($i=5; $i < $size-1; $i++) {
      $commit["message"] .= $out[$i];
    }
    $commit["message"] = trim($commit["message"]);
    
    return $commit;
  }
  
  
  public function diff_name_status($project, $parent_hash, $commit_hash) {
    $path = $this->git_repo_path($project);
    $repo = $this->get_git($path);
    $out = array();
    exec("GIT_DIR={$repo} git diff {$parent_hash} {$commit_hash} --name-status", &$out);
    if(count($out) > 0) {
      $status = array();
      foreach($out as $file) {
        preg_match("/^([A-Z]+)(.*)/", $file, $matches);
        if(count($matches) == 3) {
          $file = array();      
          $file['status'] = trim($matches[1]);
          $file['file']   = trim($matches[2]);
          array_push($status, $file);
        }
      }
      return $status;
    }
    return false;       
  }
  
  
  public function diff($project, $parent_hash, $commit_hash, $file=false) {
    $path = $this->git_repo_path($project);
    $repo = $this->get_git($path);
    $out = array();
    if($file !== false) {
      exec("GIT_DIR={$repo} git diff {$parent_hash} {$commit_hash} -- {$file}", &$out);
    } else {
      exec("GIT_DIR={$repo} git diff {$parent_hash} {$commit_hash}", &$out);
    }
    
    if(count($out) > 0) {
      $source = implode("\n", $out);
      $geshi = new GeSHi($source, "diff");
      $diff = "<div class=\"git-diff\">\n";
      $diff .= $geshi->parse_code();
      $diff .= "</div>\n";
      return $diff;
    }
    return false;
  }
  
  public function log($project, $size=6) {
    $repo = $this->git_repo_path($project);
    $c = $this->git_commit($repo, "HEAD");
    $log = array();
    $commit = array();
    for($i = 0; $i < $size && $c; $i++)  {
      $commit['date']      = date($this->date_format, (int)$c['date']);
      $commit['commit_id'] = $c['commit_id'];
      $commit['parent']    = $c['parent'];
      $commit['message']   = trim($this->short_desc($c['message'], 110));
      $commit['commit_diff'] = $this->sanitized_url() . "p={$project}&a=commitdiff&h=". $commit['commit_id'] ."&hb=". $commit['parent'];
      array_push($log, $commit);
      
      $c = git_commit($repo, $c["parent"]);
      unset($commit);
      $commit = array();
    }
    
    return $log;
  }
  
  private function short_desc($desc, $size=25)  {
    $trunc = false;
    $short = "";
    $d = explode(" ", $desc);
    foreach ($d as $str)    {
        if (strlen($short) < $size)
            $short .= "$str ";
        else    {
            $trunc = true;
            break;
        }
    }

    if ($trunc)
        $short .= "...";

    return $short;
  }
    
  
  private function tree($project, $tree) {
    $path = $this->git_repo_path($project);
    $t = $this->git_ls_tree($path, $tree);
    var_dump($t);
  }
  
  
  private function get_last_changed($repo) {
    $out = array();
    $repo = $this->get_git($repo);
    $date = exec("GIT_DIR=$repo git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
    if(count($out) == 0) {
      $date = exec("GIT_DIR=$repo git rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
    }
    return date($this->date_format, (int)$date);
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