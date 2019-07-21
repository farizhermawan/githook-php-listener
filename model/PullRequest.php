<?php

class PullRequest
{
  private $id;
  private $author;
  private $title;
  private $status;
  private $link;
  private $headBranch;
  private $baseBranch;
  private $repository;

  /**
   * PullRequest constructor.
   * @param $id
   * @param $author
   * @param $title
   * @param $status
   * @param $link
   * @param $headBranch
   * @param $baseBranch
   * @param $repository
   */
  public function __construct($id, $author, $title, $status, $link, $headBranch, $baseBranch, $repository)
  {
    $this->id = $id;
    $this->author = $author;
    $this->title = $title;
    $this->status = $status;
    $this->link = $link;
    $this->headBranch = $headBranch;
    $this->baseBranch = $baseBranch;
    $this->repository = $repository;
  }


  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getAuthor()
  {
    return $this->author;
  }

  /**
   * @return mixed
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @return mixed
   */
  public function getLink()
  {
    return $this->link;
  }

  /**
   * @return mixed
   */
  public function getHeadBranch()
  {
    return $this->headBranch;
  }

  /**
   * @return mixed
   */
  public function getBaseBranch()
  {
    return $this->baseBranch;
  }

  /**
   * @return mixed
   */
  public function getRepository()
  {
    return $this->repository;
  }
}
