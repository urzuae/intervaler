<?php
/**
 * 
 */
class Interval
{
  private $db = null;

  public function __construct(DBInterface $db_i)
  {
    $this->db = $db_i;
  }

  /**
   * Return all intervals
   *
   * This function returns all existing intervals in the database ordered by date_start
   *
   * @return array $intervals all existing intervals in the db
   */
  public function get_all()
  {
    return $this->db->get();
  }

  /**
   * Creation of a new interval
   *
   * This function creates a new interval with the given parameters
   * 
   * @param  string $start_date The start date of the interval
   * @param  string $end_date   The end date of the interval
   * @param  float  $price      The price of the range in the interval
   * 
   * @return void
   */
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

  public function get_left_entry($date)
  {
    $query = 'SELECT date_start, date_end, price FROM intervals WHERE ? = date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("s", $date);
    $stmn->execute();
    $stmn->bind_result($date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
  }

  public function get_right_entry($date)
  {
    $query = 'SELECT date_start, date_end, price FROM intervals WHERE date_start = ?';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("s", $date);
    $stmn->execute();
    $stmn->bind_result($date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
  }

  public function side_left_range($date, $price)
  {
    $date = new DateTime($date);
    $date = $date->modify('-1 day')->format('Y-m-d');
    $query = 'SELECT * FROM intervals WHERE ? = date_end AND ? = price ';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $price);
    $stmn->execute();
    $stmn->store_result();
    $conflict = $stmn->num_rows > 0 ? true : false;
    $stmn->close();

    return $conflict;
  }

  public function side_right_range($date, $price)
  {
    $date = new DateTime($date);
    $date = $date->modify('+1 day')->format('Y-m-d');
    $query = 'SELECT * FROM intervals WHERE ? = date_start AND ? = price';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $price);
    $stmn->execute();
    $stmn->store_result();
    $conflict = $stmn->num_rows > 0 ? true : false;
    $stmn->close();

    return $conflict;
  }
}
