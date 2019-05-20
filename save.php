<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'Interval.php';
require 'MysqlConn.php';

$mysql_con = new MysqlConn();
$interval = new Interval($mysql_con);

$new_start_date = $_POST['date_start'];
$new_end_date = $_POST['date_end'];
$new_price = $_POST['date_price'];

/**
 *
 */
# Look for an interval that might overlap with new one
# Get start_date, end_date and price of overlaping interval

if($interval->has_outer_conflict($new_start_date, $new_end_date)) {

  $conflict = $interval->get_outer_conflict_entry($new_start_date, $new_end_date);
  $interval->delete_outer($new_start_date, $new_end_date);
  $date_start = $conflict['date_start'];
  $date_end = $conflict['date_start'];
  $pricing = $conflict['price'];

  # Create new interval when overlaping with beggining
  if($date_start < $new_start_date) {
    $currentDate = new DateTime($new_start_date);
    $interval->create($date_start, $currentDate->modify('-1 day')->format('Y-m-d'), $pricing);
  }

  # Create new interval when overlaping with ending
  if($new_end_date < $date_end) {
    $currentDate = new DateTime($new_end_date);
    $interval->create($currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
  }
}

if($interval->has_inner_conflict($new_start_date, $new_end_date)) {
  $interval->delete_inner($new_start_date, $new_end_date);
}

# Check conflict with start date
if($interval->has_side_conflict($new_start_date)) {
  
  $conflict = $interval->get_side_conflict_entry($new_start_date);

  $interval->delete_side($new_start_date);
  $date_start = $conflict['date_start'];
  $pricing = $conflict['price'];

  # Create new internal overlaping with start date
  if($date_start < $new_start_date) {
    $currentDate = new DateTime($new_start_date);
    $interval->create($date_start, $currentDate->modify('-1 day')->format('Y-m-d'), $pricing);
  }
}

# Check conflict with end date
if($interval->has_side_conflict($new_end_date)) {
  
  $conflict = $interval->get_side_conflict_entry($new_end_date);

  $interval->delete_side($new_end_date);
  $date_end = $conflict['date_end'];
  $pricing = $conflict['price'];

  # Create new internal overlaping with end date
  if($new_end_date < $date_end) {
    $currentDate = new DateTime($new_end_date);
    $interval->create($currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
  }
}

# Create interval by user
$interval->create($new_start_date, $new_end_date, $new_price);

# Review if there are side ranges to be merged
$last_start_date = $new_start_date;

while($interval->side_left_range($last_start_date, $new_price)) {
  $date = new DateTime($last_start_date);
  $sided = $interval->get_left_entry($date->modify('-1 day')->format('Y-m-d'));
  $date_start = $sided['date_start'];

  $interval->delete_inner($date_start, $new_end_date);

  $interval->create($date_start, $new_end_date, $new_price);
  $last_start_date = $date_start;
}

$last_end_date = $new_end_date;

while($interval->side_right_range($last_end_date, $new_price)) {
  $date = new DateTime($last_end_date);
  $sided = $interval->get_right_entry($date->modify('+1 day')->format('Y-m-d'));
  $date_end = $sided['date_end'];

  $interval->delete_inner($last_start_date, $date_end);

  $interval->create($last_start_date, $date_end, $new_price);
  $last_end_date = $date_end;
}

header('Location: index.php');
