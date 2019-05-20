<?php

require 'Interval.php';
require 'MysqlConn.php';
require 'Formatter.php';

$url = $_SERVER['REDIRECT_URL'];
$method = $_SERVER['REQUEST_METHOD'];
echo $_SERVER['CONTENT_TYPE'];

$conn = new MysqlConn();
$interval = new Interval($conn);

switch ($url) {
  case '/intervals':
    if($method == 'GET') {
      $intervals = $interval->get_all();
      $format = new Formatter($intervals);
      echo ($format->json());
      die();
    }
    break;
  default:
    #return 404;
    break;
}