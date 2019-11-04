<?php
ob_end_flush();
ob_implicit_flush();

$project = $_GET['project'];

echo "<pre>";
if (!$project) echo "Missing required parameter `project` (e.g: riska-data-be)";

echo "cd {$project}\n";
chdir("/var/www/html/{$project}");

$cmd = substr($project, -2, 2) == "fe"
  ? "npm install && bower install"
  : "composer install";

$output = [];
$cmd_git_reset = "git reset --hard HEAD 2>&1";
echo $cmd_git_reset . "\n";
exec($cmd_git_reset, $output, $exit);
echo implode("\n", $output);

$output = [];
$cmd_git_pull = "git pull 2>&1";
echo $cmd_git_pull . "\n";
exec($cmd_git_pull, $output, $exit);
echo implode("\n", $output);

$output = [];
$cmd_after_pull = "{$cmd} 2>&1";
echo $cmd_after_pull . "\n";
exec($cmd_after_pull, $output, $exit);
echo implode("\n", $output);

echo "finish.";
