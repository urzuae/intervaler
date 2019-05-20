<!DOCTYPE html>
<html>
  <head>
    <title></title>
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        $.ajax({
          url: '/intervals',
          type: 'get',
          dataType: 'json',
          success: function(response) {
            var intervals = response;
            var chart = '';
            $('#intervals tbody').html('<tr><td>No intervals available</td></tr>');
            for(i = 0; i < intervals.length; i++) {
              var interval = intervals[i];
              var row = '<tr>';
              row += '<td>'+interval.date_start+'</td>'
              row += '<td>'+interval.date_end+'</td>'
              row += '<td>'+interval.price+'</td>'
              row += '</tr>';
              chart += row;
            }
            $('#intervals tbody').html(chart);
          }
        });
      })
    </script>
  </head>
  <body>
    <div>
      <h2>Current Intervals</h2>
      <table id='intervals'>
        <thead>
          <tr>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Range Price</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <form action='save.php' method="post">
      <input type="text" name='date_start' />
      <input type="text" name='date_end' />
      <input type="text" name='date_price' />
      <input type="submit" name='submit' value='Crear' />
    </form>
  </body>
</html>