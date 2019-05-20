<?php
class Formatter
{

  private $data;

  public function __construct($data) {
    $this->data = $data;
  }

  public function json() {
    return json_encode($this->data);
  }
}