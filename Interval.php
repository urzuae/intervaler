<?php
/**
 * Class Interval
 *
 * Provides CRUD actions for intervals within database
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

  /**
   * Delete an entry by id
   *
   * This function deletes an interval with the id information
   *
   * @param int $id ID of the interval
   *
   * @return void
   */
  public function delete($id)
  {
    $this->db->delete($id);
  }

  /**
   * Updates a price for an interval
   *
   * @param  int    $id    Id of the interval
   * @param  float  $price New price for interval
   * @return void
   */
  public function update_price($id, $price)
  {
    $this->db->update($id, $price);
  }

  /**
   * Add new interval
   *
   * Add a new interval with the given parameters.
   * Check for any conflict with existing intervals
   * Reviews conflicts with completely overlaping intervals, overlaping, with only
   * end or start date and within the new interval
   *
   * Finally creates new interval and merges whenever there are ranges with same price
   *
   * @param string $new_start_date The start date of the new interval
   * @param string $new_end_date   The end date of the new interval
   * @param float  $new_price      The price of the interval
   *
   * @return void
   */
  public function add($new_start_date, $new_end_date, $new_price)
  {
    $this->check_for_outer_conflict($new_start_date, $new_end_date, $new_price);

    $this->check_for_side_left_conflict($new_start_date);

    $this->check_for_side_left_conflict($new_end_date);

    $this->check_for_inner_conflict($new_start_date, $new_end_date);
    
    # Create interval by user
    $this->create($new_start_date, $new_end_date, $new_price);

    $this->check_for_merging($new_start_date, $new_end_date, $new_price);
  }

  /**
   * Deletes all intervals within the given dates
   *
   * @param  string $start_date
   * @param  string $end_date
   * @return void
   */
  public function delete_inner($start_date, $end_date)
  {
    $query = 'DELETE FROM intervals WHERE ? <= date_start AND date_end <= ?';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->close();
  }

  /**
   * Verifies for conflicts with intervals overlaping new one completely
   * Creates new intervals if overlaping existing ones.
   *
   * @param  string $new_start_date New start date for interval
   * @param  string $new_end_date   New end date for interval
   * @param  float  $new_price      Price for new interbal
   * @return void
   */
  public function check_for_outer_conflict($new_start_date, $new_end_date, $new_price)
  {
    if($this->has_outer_conflict($new_start_date, $new_end_date)) {
      $conflict = $this->get_outer_conflict_entry($new_start_date, $new_end_date);
      $date_start = $conflict['date_start'];
      $date_end = $conflict['date_start'];
      $pricing = $conflict['price'];
      $id = $conflict['id'];

      $this->delete($id);

      # Create new interval when overlaping with beggining
      if($date_start < $new_start_date) {
        $currentDate = new DateTime($new_start_date);
        $this->create($date_start, $currentDate->modify('-1 day')->format('Y-m-d'), $pricing);
      }

      # Create new interval when overlaping with ending
      if($new_end_date < $date_end) {
        $currentDate = new DateTime($new_end_date);
        $this->create($currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
      }
    }
  }

  /**
   * This functions checks if there is a conflict with new interval only from the start_date
   * Deletes and creates new sub-intervals
   *
   * @param  string $new_stat_date Start date for new interval
   * @return void
   */
  public function check_for_side_left_conflict($new_start_date)
  {
    if($this->has_side_conflict($new_start_date)) {

      $conflict = $this->get_side_conflict_entry($new_start_date);

      $date_start = $conflict['date_start'];
      $pricing = $conflict['price'];
      $id = $conflict['id'];

      $this->delete($id);

      # Create new internal overlaping with start date
      if($date_start < $new_start_date) {
        $currentDate = new DateTime($new_start_date);
        $this->create($date_start, $currentDate->modify('-1 day')->format('Y-m-d'), $pricing);
      }
    }
  }

  /**
   * This functions checks if there is a conflict with new interval only from the end_date
   * Deletes and creates new sub-intervals
   *
   * @param  string $new_end_date End date for new interval
   * @return void
   */
  public function check_for_side_right_conflict($new_end_date)
  {
    # Check conflict with end date
    if($this->has_side_conflict($new_end_date)) {

      $conflict = $this->get_side_conflict_entry($new_end_date);

      $date_end = $conflict['date_end'];
      $pricing = $conflict['price'];
      $id = $conflict['id'];

      $this->delete($id);

      # Create new internal overlaping with end date
      if($new_end_date < $date_end) {
        $currentDate = new DateTime($new_end_date);
        $this->create($currentDate->modify('+1 day')->format('Y-m-d'), $date_end, $pricing);
      }
    }
  }

  /**
   * This functions checks if there is a conflict within new interval betwens start and end dates
   * Deletes all those entries
   *
   * @param  string $new_stat_date Start date for new interval
   * @return void
   */
  public function check_for_inner_conflict($new_start_date, $new_end_date)
  {
    if($this->has_inner_conflict($new_start_date, $new_end_date)) {
      $this->delete_inner($new_start_date, $new_end_date);
    }
  }

  /**
   * Check for interval next to the new interval and merges deleting old ones
   * and creating a whole new interval
   *
   * @param  string $new_start_date Start date of new interval
   * @param  string $new_end_date   End date of new interval
   * @param  float  $new_price      Price for new interval
   * @return void
   */
  public function check_for_merging($new_start_date, $new_end_date, $new_price)
  {
    # Review if there are side ranges to be merged
    $last_start_date = $new_start_date;

    while($this->side_left_range($last_start_date, $new_price)) {
      $date = new DateTime($last_start_date);
      $sided = $this->get_left_entry($date->modify('-1 day')->format('Y-m-d'));
      $date_start = $sided['date_start'];

      $this->delete_inner($date_start, $new_end_date);

      $this->create($date_start, $new_end_date, $new_price);
      $last_start_date = $date_start;
    }

    $last_end_date = $new_end_date;

    while($this->side_right_range($last_end_date, $new_price)) {
      $date = new DateTime($last_end_date);
      $sided = $this->get_right_entry($date->modify('+1 day')->format('Y-m-d'));
      $date_end = $sided['date_end'];

      $this->delete_inner($last_start_date, $date_end);

      $this->create($last_start_date, $date_end, $new_price);
      $last_end_date = $date_end;
    }
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
    $query = 'SELECT id, date_start, date_end, price FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $start_date, $end_date);
    $stmn->execute();
    $stmn->bind_result($id, $date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('id' => $id, 'date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
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
    $query = 'SELECT id, date_start, date_end, price FROM intervals WHERE date_start <= ? AND ? <= date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("ss", $date, $date);
    $stmn->execute();
    $stmn->bind_result($id, $date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('id' => $id, 'date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
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
    $query = 'SELECT id, date_start, date_end, price FROM intervals WHERE ? = date_end';
    $stmn = $this->db->connection->prepare($query);
    $stmn->bind_param("s", $date);
    $stmn->execute();
    $stmn->bind_result($date_start, $date_end, $price);
    $stmn->fetch();
    $stmn->close();

    return array('id' => $id, 'date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
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

    return array('id' => $id, 'date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
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
