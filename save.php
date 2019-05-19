<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Interval
{
  private $db = null;

  public function __construct(DBInterface $db_i)
  {
    $this->db = $db_i;
  }

  public function create($start_date, $end_date, $price)
  {
    $this->db->insert($start_date, $end_date, $price);
  }

  public function delete_outer($start_date, $end_date)
  {
    $this->db->delete_outer($start_date, $end_date);
  }

  public function delete_side($date)
  {
    $query = 'DELETE FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $date);
    $stmn->execute();
    $stmn->close();
  }

  public function delete_inner($start_date, $end_date)
  {
    $query = 'DELETE FROM intervals WHERE ? <= date_start AND date_end <= ?';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->close();
  }

  public function has_outer_conflict($start_date, $end_date)
  {
    $query = 'SELECT * FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->store_result();
    $conflict = $stmn->num_rows > 0 ? true : false;
    $stmn->close();

    return $conflict;
  }

  public function get_outer_conflict_entry($start_date, $end_date)
  {
    $query = 'SELECT date_start, date_end, price FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->bind_result($date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
  }

  public function has_side_conflict($date)
  {
    $query = 'SELECT * FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $date);
    $stmn->execute();
    $stmn->store_result();
    $conflict = $stmn->num_rows > 0 ? true : false;
    $stmn->close();

    return $conflict;
  }

  public function get_side_conflict_entry($date)
  {
    $query = 'SELECT date_start, date_end, price FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $date);
    $stmn->execute();
    $stmn->bind_result($date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
  }

  public function has_inner_conflict($start_date, $end_date)
  {
    $query = 'SELECT * FROM intervals WHERE ? <= date_start AND date_end <= ?';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->store_result();
    $conflict = $stmn->num_rows > 0 ? true : false;
    $stmn->close();

    return $conflict;
  }
}

interface DBInterface
{
  public function insert($start_date, $end_date, $price);

  public function delete_outer($start_date, $end_date);
}

class MysqlConn implements DBInterface
{
  public $connection;
  private $db_host = 'localhost';
  private $db_user = 'root';
  private $db_pass = 'password';
  private $db_dbnm = 'pricing';

  public function __construct()
  {
    $this->connection = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_dbnm);
  }

  public function insert($start_date, $end_date, $price)
  {
    $query = "INSERT INTO intervals (id, date_start, date_end, price) VALUES (null, ?, ?, ?)";
    $new_entry = $this->connection->prepare($query);
    $new_entry->bind_param("sss", $start_date, $end_date, $price);
    $new_entry->execute();
    $new_entry->close();
  }

  public function delete_outer($start_date, $end_date)
  {
    $query = "DELETE FROM intervals WHERE date_start <= ? AND ? <= date_end";
    $del_entry = $this->connection->prepare($query);
    $del_entry->bind_param("ss", $start_date, $end_date);
    $del_entry->execute();
    $del_entry->close();
  }
}

$mysql_con = new MysqlConn();
$interval = new Interval($mysql_con);

$new_start_date = $_POST['date_start'];
$new_end_date = $_POST['date_end'];
$new_price = $_POST['date_price'];

print_r($_POST);

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

# Check conflict with start date
if($interval->has_side_conflict($new_start_date)) {
  
  $conflict = $interval->get_side_conflict_entry($new_start_date);

  $interval->delete_side($new_start_date);
  $date_start = $conflict['date_start'];
  $date_end = $conflict['date_end'];
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
  $date_start = $conflict['date_start'];
  $date_end = $conflict['date_end'];
  $pricing = $conflict['price'];

  # Create new internal overlaping with start date
  if($new_end_date < $date_end) {
    $currentDate = new DateTime($new_end_date);
    $interval->create($currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
  }
}

if($interval->has_inner_conflict($new_start_date, $new_end_date)) {
  $interval->delete_inner($new_start_date, $new_end_date);
}

# Create interval by user
$interval->create($new_start_date, $new_end_date, $new_price);

header('Location: index.php');