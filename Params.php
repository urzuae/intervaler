<?php
class Params
{
  private $raw_data;

  public function __construct()
  {
    $this->raw_data = file_get_contents('php://input');
  }

  public function parse_json()
  {
    return json_encode($this->raw_data);
  }

  public function get_json_params()
  {
    return $this->parse_json();
  }

  public function get_params()
  {
    $params = array();
    foreach (explode('&', $this->raw_data) as $chunk) {
      $param = explode("=", $chunk);
      $params[$param[0]] = $param[1];
    }
    return $params;
  }
}