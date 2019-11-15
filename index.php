<?php
require_once "vendor/autoload.php";

use GitHookPhpListener\Event;
use GitHookPhpListener\GitHookParser;

// CONFIGURATION
define("DEFAULT_HOME", "/var/www");          // The path to your repostiroy; this must begin with a forward slash (/)
define("DEFAULT_DIR", "/var/www/html/%s");   // The path to your repostiroy; this must begin with a forward slash (/)
define("DEFAULT_BRANCH", "master");          // The base branch

define("GIT", "/usr/bin/git");               // The path to the git executable

define("BEFORE_PULL", "");                   // A command to execute before pulling
define("AFTER_PULL", "");                    // A command to execute after successfully pulling

$start = time();
$gitHook = new GitHookParser();

ob_end_flush();
ob_implicit_flush();

if ($gitHook->getEventName() == Event::PULL_REQUEST || $gitHook->getEventName() == Event::PUSH) {
  $pullRequest = $gitHook->getPullRequest();
  echo "Got Request: " . json_encode($pullRequest) . "\n";

  $deploy = $gitHook->getEventName() == Event::PUSH || ($gitHook->getEventName() == Event::PULL_REQUEST && $pullRequest->getStatus() == "closed");
  if (!$deploy) {
    echo "No need to deploy, ignored.";
    exit();
  }

  $deployConfig = [
    'DIR' => sprintf(DEFAULT_DIR, $pullRequest->getRepository()),
    'BRANCH' => DEFAULT_BRANCH,
    'BEFORE_PULL' => BEFORE_PULL,
    'AFTER_PULL' => AFTER_PULL,
  ];

  $DIR = preg_match("/\/$/", $deployConfig['DIR']) ? $deployConfig['DIR'] : $deployConfig['DIR'] . "/";

  $configFile = sprintf($DIR, $pullRequest->getRepository()) . "/deploy.conf";
  if (file_exists($configFile)) {
    echo "Read config from deploy.conf\n";
    $overrideConfig = parse_ini_file($configFile);
    if ($overrideConfig) $deployConfig = array_merge($deployConfig, $overrideConfig);
  }
  echo "Config: " . json_encode($deployConfig) . "\n";


  if ($pullRequest->getBaseBranch() != $deployConfig['BRANCH']) {
    echo 'Branch detected is not ' . $deployConfig['BRANCH'] . ', request ignored\n';
    exit();
  }

  // change directory to the repository
  echo "Change directory to " . sprintf($DIR, $pullRequest->getRepository()) . "\n";
  chdir(sprintf($DIR, $pullRequest->getRepository()));

  $cmds = [];
  foreach (explode(" && ", $deployConfig['BEFORE_PULL']) as $cmd) $cmds[] = $cmd;
  $cmds[] = 'git reset --hard HEAD';
  $cmds[] = 'git pull';
  foreach (explode(" && ", $deployConfig['AFTER_PULL']) as $cmd) $cmds[] = $cmd;

  foreach ($cmds as $cmd) {
    if (empty($cmd)) continue;
    echo "~ " . $cmd . "\n";

    if (stristr($cmd, "composer install")) $cmd = "export HOME=" . DEFAULT_HOME . " && " . $cmd;
    if (stristr($cmd, "bower install")) $cmd = "export HOME=" . DEFAULT_HOME . " && " . $cmd;

    if( ($fp = popen($cmd . " 2>&1", "r")) ) {
      while( !feof($fp) ){
        echo fread($fp, 1024);
        flush();
      }
      fclose($fp);
    }
  }

  echo "finished in " . (time() - $start) . "s\n";
  exit();
}

echo "Unknown event ({$gitHook->getEventName()}), ignored\n";
exit();