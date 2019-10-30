<?php
require_once "vendor/autoload.php";

use GitHookPhpListener\Event;
use GitHookPhpListener\GitHookParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

header("Content-Type: application/json");
date_default_timezone_set("Asia/Jakarta");

chdir("/var/www/html/riska-data-be");

$output = "";
$cmd = "composer install";
exec($cmd . " 2>&1", $output, $exit);
$log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));

echo json_encode(['message' => $errMsg]);
