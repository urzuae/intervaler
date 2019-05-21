<?php
interface DBInterface
{
  public function get();

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

  public function get()
  {
    $intervals = array();
    $query = 'SELECT id, date_start, date_end, price FROM intervals ORDER BY date_start';
    $stmn = $this->connection->prepare($query);
    $stmn->execute();
    $stmn->bind_result($id, $date_start, $date_end, $price);
    while($stmn->fetch()) {
      $intervals[] = array('id' => $id, 'date_start' => $date_start, 'date_end' => $date_end, 'price' => $price);
    }
    $stmn->close();

    return $intervals;
  }

  public function insert($start_date, $end_date, $price)
  {
    $query = "INSERT INTO intervals (id, date_start, date_end, price) VALUES (null, ?, ?, ?)";
    $new_entry = $this->connection->prepare($query);
    $new_entry->bind_param("sss", $start_date, $end_date, $price);
    $new_entry->execute();
    $new_entry->close();
  }

  public function delete($id)
  {
    $query = "DELETE FROM intervals where id = ?";
    $del_entry = $this->connection->prepare($query);
    $del_entry->bind_param("i", $id);
    $del_entry->execute();
    $del_entry->close();
  }

  public function update($id, $price)
  {
    $query = "UPDATE intervals SET price = ? WHERE id = ?";
    $upd_entry = $this->connection->prepare($query);
    $upd_entry->bind_param("di", $price, $id);
    $upd_entry->execute();
    $upd_entry->close();
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
