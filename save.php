<?php
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'password';
$db_dbnm = 'pricing';

$startDate = $_POST['date_start'];
$endDate = $_POST['date_end'];
$price = $_POST['date_price'];

$query = 'SELECT date_start, date_end, price FROM intervals WHERE date_start <= ? AND ? <= date_end';

$db = new mysqli($db_host, $db_user, $db_pass, $db_dbnm);

$stmn = $db->prepare($query);

$stmn->bind_param("ss", $startDate, $endDate);

$stmn->execute();

$stmn->store_result();

$stmn->bind_result($date_start, $date_end, $pricing);

if($stmn->num_rows > 0) {

  $stmn->fetch();

  $stmn->close();

  $query = 'DELETE FROM intervals WHERE date_start <= ? AND ? <= date_end';

  $stmn2 = $db->prepare($query);

  $stmn2->bind_param("ss", $startDate, $endDate);

  $stmn2->execute();

  $stmn2->close();

  print_r($_POST);

  $new_interval = "INSERT INTO intervals (id, date_start, date_end, price) VALUES(null, ?, ?, ?)";

  $new = $db->prepare($new_interval);
  $new->bind_param("sss", $startDate, $endDate, $price);
  $new->execute();
  $new->close();
  echo '1';

  if($date_start < $startDate) {
    $new_interval = "INSERT INTO intervals (id, date_start, date_end, price) VALUES(null, ?, ?, ?)";

    $new = $db->prepare($new_interval);
    $currentDate = new DateTime($startDate);
    $new->bind_param("sss", $date_start, $currentDate->modify('-1 day')->format('Y-m-d'), $pricing);
    echo $new->execute();
    $new->close();
    echo '2';
  }

  if($endDate < $date_end) {
    $new_interval = "INSERT INTO intervals (id, date_start, date_end, price) VALUES(null, ?, ?, ?)";

    $new = $db->prepare($new_interval);
    $currentDate = new DateTime($endDate);
    $new->bind_param("sss", $currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
    echo $new->execute();
    $new->close();
    echo '3';
  }  

}

$db->close();