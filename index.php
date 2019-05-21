<?php

require 'Interval.php';
require 'MysqlConn.php';
require 'Formatter.php';
require 'Params.php';

$url = $_SERVER['REDIRECT_URL'];
$method = $_SERVER['REQUEST_METHOD'];
//print_r($_SERVER['HTTP_ACCEPT']);

$conn = new MysqlConn();
$interval = new Interval($conn);
$url = str_replace("/freelance/intervals", "", $url);

switch ($url) {
  case '/intervals':
    if('GET' == $method) {
      $intervals = $interval->get_all();
      $format = new Formatter($intervals);
      echo ($format->json());
      die();
    }
    if('POST' == $method) {
      $param = new Params();
      $params = $param->get_params();
      $interval->add($params['date_start'], $params['date_end'], $params['price']);
      http_response_code(204);
    }
    break;
  case (preg_match('/\/interval\/(\d+)/', $url, $match) ? true : false) :
    $id = $match[1];
    if('PATCH' == $method) {
      $param = new Params();
      $params = $param->get_params();
      $interval->update_price($params['id'], $params['price']);
      http_response_code(204);
    }
    if('DELETE' == $method) {
      $interval->delete($id);
      http_response_code(204);
    }
    break;
  default:
    http_response_code(404);
    break;
}
