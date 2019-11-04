<?php
ob_end_flush();
ob_implicit_flush();

$project = $_GET['project'];
$start = time();

echo "<pre>";
if (!$project) echo "Missing required parameter `project` (e.g: riska-data-be)";

echo "~ cd {$project}\n\n";
chdir("/var/www/html/{$project}");

$cmd_after_pull = substr($project, -2, 2) == "fe"
  ? "npm install && bower install"
  : "composer install";

$cmds = ['git reset --hard HEAD', 'git pull'];
foreach (explode(" && ", $cmd_after_pull) as $cmd) $cmds[] = $cmd;

foreach ($cmds as $cmd) {
  $output = [];
  echo "~ " . $cmd . "\n";
  exec($cmd . " 2>&1", $output, $exit);
  echo implode("\n", $output);
  echo "\n\n";
}

$end = time();

echo "finish in " . ($end - $start) . "ms";
