<?php
require_once "vendor/autoload.php";

use GitHookPhpListener\GitHookParser;
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
$log->pushHandler(new StreamHandler(LOGFILE));

$DIR = preg_match("/\/$/", DIR) ? DIR : DIR . "/";

$gitHook = new GitHookParser();

if (!$gitHook->isEventPullRequest()) {
  echo json_encode(['message' => 'Event is not PULL_REQUEST, request ignored']);
  exit();
}

$pullRequest = $gitHook->getPullRequest();

if ($pullRequest->getStatus() == "opened") {

}
else if ($pullRequest->getStatus() == "closed") {
  if ($pullRequest->getBaseBranch() != BRANCH) {
    echo json_encode(['message' => 'Branch detected is not ' . BRANCH . ', request ignored']);
    exit();
  }

  // change directory to the repository
  chdir(sprintf($DIR, $pullRequest->getRepository()));

  // reset git head in remote repository
  exec(GIT . " reset --hard HEAD 2>&1", $output, $exit);

  // perform before pulling action
  if (!empty(BEFORE_PULL)) {
    // execute the command, returning the output and exit code
    exec(BEFORE_PULL . " 2>&1", $output, $exit);
  }

  // perform git pull
  exec(GIT . " pull 2>&1", $output, $exit);

  // perform after pulling action
  if (!empty(AFTER_PULL)) {
    // execute the command, returning the output and exit code
    exec(AFTER_PULL . " 2>&1", $output, $exit);
  }

  echo json_encode([
    'message' => $pullRequest->getRepository() . ' has been successfully deployed',
    'changes' => $pullRequest->getTitle(),
    'author'  => $pullRequest->getAuthor()
  ]);
  exit();
}

echo json_encode(['message' => 'Unkown error, request ignored', 'pull_request' => $pullRequest]);
