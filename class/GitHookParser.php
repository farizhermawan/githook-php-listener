<?php
namespace GitHookPhpListener;

class GitHookParser {
  /**
   * @var PullRequest $pullRequest
   */
  private $rawContent;
  private $rawData;
  private $eventName;
  private $pullRequest;

  function __construct()
  {
    $this->eventName = isset($_SERVER['HTTP_X_GITHUB_EVENT']) ? $_SERVER['HTTP_X_GITHUB_EVENT'] : null;
    $this->rawContent = file_get_contents("php://input");
    $this->rawData = json_decode($this->rawContent, true);

    if ($this->eventName == Event::PULL_REQUEST) {
      $this->pullRequest = new PullRequest(
        $this->rawData['number'],
        $this->rawData['pull_request']['user']['login'],
        $this->rawData['pull_request']['title'],
        $this->rawData['action'],
        $this->rawData['pull_request']['html_url'],
        $this->rawData['pull_request']['head']['ref'],
        $this->rawData['pull_request']['base']['ref'],
        $this->rawData['pull_request']['head']['repo']['name']
      );
    } else if ($this->eventName == Event::PUSH) {
      $this->pullRequest = new PullRequest(
        $this->rawData['head_commit']['id'],
        $this->rawData['head_commit']['author']['username'],
        $this->rawData['head_commit']['message'],
        "PUSH",
        $this->rawData['head_commit']['url'],
        null,
        str_replace("refs/heads/", "", $this->rawData['ref']),
        $this->rawData['repository']['name']
      );
    }
  }

  public function getEventName()
  {
    return $this->eventName;
  }

  /**
   * @return PullRequest
   */
  public function getPullRequest(): PullRequest
  {
    return $this->pullRequest;
  }
}
