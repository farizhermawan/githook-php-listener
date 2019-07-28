<?php
require_once "vendor/autoload.php";

use GitHookPhpListener\Event;
use GitHookPhpListener\GitHookParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

define("DIR", "/var/www/html/%s");                                               // The path to your repostiroy; this must begin with a forward slash (/)
define("BRANCH", "master");                                                      // The branch route
define("LOGFILE", "deploy.log");                                                 // The name of the file you want to log to.
define("GIT", "/usr/bin/git");                                                   // The path to the git executable
define("BEFORE_PULL", "");                                                       // A command to execute before pulling
define("AFTER_PULL", "");                                                        // A command to execute after successfully pulling

header("Content-Type: application/json");
date_default_timezone_set("Asia/Jakarta");

// create a log channel
$log = new Logger('listener');
$streamHandler = new StreamHandler(LOGFILE);
$streamHandler->setFormatter(new LineFormatter(null, null, false, true));

$log->pushHandler($streamHandler);

$DIR = preg_match("/\/$/", DIR) ? DIR : DIR . "/";

$gitHook = new GitHookParser();

$log->info("Got request: " . $gitHook->getEventName());

if ($gitHook->getEventName() == Event::PULL_REQUEST || $gitHook->getEventName() == Event::PUSH) {
  $pullRequest = $gitHook->getPullRequest();
  $log->info("Parsing Request: " . json_encode($pullRequest));

  $deploy = false;
  if ($gitHook->getEventName() == Event::PULL_REQUEST) {
    if ($pullRequest->getStatus() == "opened") {

    } else if ($pullRequest->getStatus() == "closed") {
      $deploy = true;
    }
  } else if ($gitHook->getEventName() == Event::PUSH) {
    $deploy = true;
  }


  if ($deploy) {
    if ($pullRequest->getBaseBranch() != BRANCH) {
      $errMsg = 'Branch detected is not ' . BRANCH . ', request ignored';
      $log->err($errMsg);
      echo json_encode(['message' => $errMsg, 'request' => $pullRequest]);
      exit();
    }

    // get home directory
    $log->info("Get home directory");
    exec("echo ~", $output, $exit);
    $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));

    // change directory to the repository
    $log->info("Change directory to " . sprintf($DIR, $pullRequest->getRepository()));
    chdir(sprintf($DIR, $pullRequest->getRepository()));

    // reset git head in remote repository
    $log->info("Perform git reset");
    exec(GIT . " reset --hard HEAD 2>&1", $output, $exit);
    $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));

    // perform before pulling action
    if (!empty(BEFORE_PULL)) {
      // execute the command, returning the output and exit code
      $log->info("Perform " . BEFORE_PULL);
      exec(BEFORE_PULL . " 2>&1", $output, $exit);
      $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));
    }

    // perform git pull
    $log->info("Perform git pull");
    exec(GIT . " pull 2>&1", $output, $exit);
    $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));

    // perform after pulling action
    if (!empty(AFTER_PULL)) {
      // execute the command, returning the output and exit code
      $log->info("Perform " . AFTER_PULL);
      exec(AFTER_PULL . " 2>&1", $output, $exit);
      $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));
    }

    $msg = $pullRequest->getRepository() . ' has been successfully deployed';
    $log->info($msg);
    echo json_encode(['message' => $msg, 'request' => $pullRequest]);
    exit();
  }
}

$errMsg = 'Unkown error, request ignored';
$log->err($errMsg);
echo json_encode(['message' => $errMsg]);
