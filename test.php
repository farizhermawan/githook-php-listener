<?php
header("Content-Type: application/json");

chdir("/var/www/html/riska-data-be");

$output = "";
$cmd = "composer install";
exec($cmd . " 2>&1", $output, $exit);

echo json_encode(['message' => !empty($output) ? implode("\n", $output) : "[no output]"]);
