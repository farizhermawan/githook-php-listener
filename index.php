<?php
require_once "vendor/autoload.php";

use GitHookPhpListener\Event;
use GitHookPhpListener\GitHookParser;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

header("Content-Type: application/json");
date_default_timezone_set("Asia/Jakarta");

// CONFIGURATION
define("DEFAULT_DIR", "/var/www/html/%s");   // The path to your repostiroy; this must begin with a forward slash (/)
define("DEFAULT_BRANCH", "master");          // The branch route

define("LOGFILE", "deploy.log");             // The name of the file you want to log to.
define("GIT", "/usr/bin/git");               // The path to the git executable

define("BEFORE_PULL", "");                   // A command to execute before pulling
define("AFTER_PULL", "");                    // A command to execute after successfully pulling

// create a log channel
$log = new Logger('listener');
$streamHandler = new StreamHandler(LOGFILE);
$streamHandler->setFormatter(new LineFormatter(null, null, false, true));

$log->pushHandler($streamHandler);

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
    $deployConfig = [
      'DIR' => sprintf(DEFAULT_DIR, $pullRequest->getRepository()),
      'BRANCH' => DEFAULT_BRANCH,
      'BEFORE_PULL' => BEFORE_PULL,
      'AFTER_PULL' => AFTER_PULL,
    ];

    $DIR = preg_match("/\/$/", $deployConfig['DIR']) ? $deployConfig['DIR'] : $deployConfig['DIR'] . "/";

    $configFile = sprintf($DIR, $pullRequest->getRepository()) . "/deploy.conf";
    if (file_exists($configFile)) {
      $overrideConfig = parse_ini_file($configFile);
      $log->info("Found config file: " . json_encode($overrideConfig));
      if ($overrideConfig) $deployConfig = array_merge($deployConfig, $overrideConfig);
    }
    $log->info("Config: " . json_encode($deployConfig));


    if ($pullRequest->getBaseBranch() != $deployConfig['BRANCH']) {
      $errMsg = 'Branch detected is not ' . $deployConfig['BRANCH'] . ', request ignored';
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
    if (!empty($deployConfig['BEFORE_PULL'])) {
      // execute the command, returning the output and exit code
      $cmdToRuns = explode(" && ", $deployConfig['BEFORE_PULL']);
      foreach ($cmdToRuns as $cmd) {
        $log->info("Perform " . $cmd);
        $output = "";
        exec($cmd . " 2>&1", $output, $exit);
        $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));
      }
    }

    // perform git pull
    $log->info("Perform git pull");
    exec(GIT . " pull 2>&1", $output, $exit);
    $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));

    // perform after pulling action
    if (!empty($deployConfig['AFTER_PULL'])) {
      // execute the command, returning the output and exit code
      $cmdToRuns = explode(" && ", $deployConfig['AFTER_PULL']);
      foreach ($cmdToRuns as $cmd) {
        $log->info("Perform " . $cmd);
        $output = "";
        exec($cmd . " 2>&1", $output, $exit);
        $log->debug((!empty($output) ? implode("\n", $output) : "[no output]"));
      }
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
