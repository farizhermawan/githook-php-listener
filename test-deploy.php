<?php
$start = time();

if (PHP_SAPI === 'cli') {
  $project = $_SERVER['argv'][1];
} else {
  ob_end_flush();
  ob_implicit_flush();
  $project = $_GET['project'];
  echo "<pre>";
}

if (!$project) echo "Missing required parameter `project` (e.g: riska-data-be)";

echo "~ cd {$project}\n";
chdir("../{$project}");

$cmd_after_pull = substr($project, -2, 2) == "fe"
  ? "npm install && bower install"
  : "composer install -q --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist && php artisan migrate --force";

$cmds = ['git reset --hard HEAD', 'git pull'];
foreach (explode(" && ", $cmd_after_pull) as $cmd) $cmds[] = $cmd;

foreach ($cmds as $cmd) {
  echo "~ " . $cmd . "\n";
  if( ($fp = popen($cmd . " 2>&1", "r")) ) {
    while( !feof($fp) ){
      echo fread($fp, 1024);
      flush();
    }
    fclose($fp);
  }
}

$end = time();

echo "finished in " . ($end - $start) . "ms\n";
