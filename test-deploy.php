<?php
header("Content-Type: application/json");

$type    = $_GET['type'];
$project = $_GET['project'];

if (!$type || !$project) die(json_encode(["message" => "missing parameter"]));

chdir("/var/www/html/{$project}");

$cmd = $type == "frontend"
  ? "npm install && bower install"
  : "composer install";

exec("git reset --hard HEAD 2>&1", $output, $exit);
exec("git pull 2>&1", $output, $exit);
exec($cmd . " 2>&1", $output, $exit);

echo json_encode(['message' => !empty($output) ? implode("\n", $output) : "[no output]"]);
