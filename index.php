<?php

define("REMOTE_REPOSITORY", "git@github.com:farizhermawan/%s.git");              // The SSH URL to your repository
define("DIR", "/var/www/html/%s");                                                 // The path to your repostiroy; this must begin with a forward slash (/)
define("BRANCH", "refs/heads/master");                                           // The branch route
define("LOGFILE", "deploy.log");                                                 // The name of the file you want to log to.
define("GIT", "/usr/bin/git");                                                   // The path to the git executable
define("BEFORE_PULL", "");                                                       // A command to execute before pulling
define("AFTER_PULL", "");                                                        // A command to execute after successfully pulling

require_once("listener.php");
