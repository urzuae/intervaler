<!DOCTYPE html>
<html>
  <head>
    <title></title>
  </head>
  <body>
    <div>
      <h2>Current Intervals</h2>
      <?php
      $query = 'SELECT date_start, date_end, price FROM intervals order by date_start';
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'password';
$db_dbnm = 'pricing';

$db = new mysqli($db_host, $db_user, $db_pass, $db_dbnm);

$stmn = $db->prepare($query);

$stmn->execute();

$stmn->bind_result($date_start, $date_end, $amount);

while($stmn->fetch())
  printf("( %s - %s ) : %s <br/>", $date_start, $date_end, $amount);

$stmn->close();

$db->close();
?>
    </div>
    <form action='save.php' method="post">
      <input type="text" name='date_start' />
      <input type="text" name='date_end' />
      <input type="text" name='date_price' />
      <input type="submit" name='submit' value='Crear' />
    </form>
  </body>
</html>