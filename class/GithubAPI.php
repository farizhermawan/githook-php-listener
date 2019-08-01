<?php
namespace GitHookPhpListener;

use Curl\Curl;

class GithubAPI {
  private $curl;
  private $owner;
  private $repo;

  function __construct($token, $owner = null, $repo = null)
  {
    $this->owner = $owner;
    $this->repo = $repo;

    $this->curl = new Curl();
    $this->curl->setHeader("Authorization", "token {$token}");
  }

  function setOwner($owner)
  {
    $this->owner = $owner;
  }

  public function setRepo($repo)
  {
    $this->repo = $repo;
  }

  function getRawContent($path, $ref = "master")
  {
    if (!$this->owner || !$this->repo) return false;
    $this->curl->setHeader("Accept", "application/vnd.github.VERSION.raw");
    $response = $this->curl->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/contents/{$path}?ref={$ref}");
    return $response->getHttpStatus() != 200 ? false : $response->getResponse();
  }
}
