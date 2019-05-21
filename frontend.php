<!DOCTYPE html>
<html>
  <head>
    <title>Intervals</title>
    <style type="text/css">
      label {
        display:inline-block;
        width: 150px;
      }
      input[type="text"], input[type="date"] {
        width: 150px;
      }
      #update_interval {
        display:none;
      }
    </style>
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript">
      var _url = '';
      $(document).ready(function() {
        fill_entries();

        $(document).on('click', '.delete', function(e) {
          e.preventDefault();
          var id = $(this).data('id');
          if(confirm('Are you sure to delete this entry?')) {
            $.ajax({
              url: _url+'/interval/'+id,
              type: 'delete',
              dataType: 'json',
              success: function(response) {
                fill_entries();
              }
            })
          }
          return false;
        });

        $(document).on('click', '.edit', function(e) {
          e.preventDefault();
          $('#update_interval').show();

          var $id = $(this).data('id');
          var $start_date = $(this).data('start');
          var $end_date = $(this).data('end');
          $('#update_id').val($id);
          $('#start_date').val($start_date);
          $('#end_date').val($end_date);

          return false;
        });

        $(document).on('submit', '#new_interval', function(e) {
          e.preventDefault();
          var data = $(this).serialize();
          $.ajax({
            url: _url+'/intervals',
            data: data,
            type: 'post',
            dataType: 'json',
            success: function(response) {
              fill_entries();
            }
          });
          return false;
        });

        $(document).on('submit', '#update_interval', function(e) {
          e.preventDefault();
          var data = $(this).serialize();
          var id = $('#update_id').val();
          $.ajax({
            url: _url+'/interval/'+id,
            data: data,
            type: 'patch',
            dataType: 'json',
            success: function(response) {
              $('#update_interval').hide();
              fill_entries();
            }
          });
          return false;
        });
      });

      function fill_entries() {
        $.ajax({
          url: _url+'/intervals',
          type: 'get',
          dataType: 'json',
          success: function(response) {
            var intervals = response;
            var chart = '';
            $('#intervals tbody').html('<tr><td>No intervals available</td></tr>');
            for(i = 0; i < intervals.length; i++) {
              var interval = intervals[i];
              var row = '<tr>';
              row += '<td>'+interval.date_start+'</td>';
              row += '<td>'+interval.date_end+'</td>';
              row += '<td>'+interval.price+'</td>';
              row += '<td><span class="delete" data-id="'+interval.id+'">Delete</span> ';
              row += '<span class="edit" data-id="'+interval.id+'" data-start="'+interval.date_start+'"';
              row += 'data-end="'+interval.date_end+'">Edit</span></td>';
              row += '</tr>';
              chart += row;
            }
            $('#intervals tbody').html(chart);
          }
        });
      }
    </script>
  </head>
  <body>
    <div>
      <h2>Current Intervals</h2>
      <small><a href='reset.php'>Reset</a></small>
      <table id='intervals' border=1 spacing=1>
        <thead>
          <tr>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Range Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <br/>
    <form id="new_interval">
      <h2>Create interval</h2>
      <label>Start Date:</label> <input type="date" name='date_start' /><br>
      <label>End Date:</label> <input type="date" name='date_end' /><br>
      <label>Price:</label> <input type="text" name='price' /><br>
      <input type="submit" name='submit' value='Create' />
    </form>

    <form id="update_interval">
      <h2>Update Interval</h2>
      <input type="hidden" id='update_id' name="id"><br>
      <label>Start Date:</label> <input type="text" id="start_date" disabled><br>
      <label>End Date:</label> <input type="text" id="end_date" disabled><br>
      <label>Price:</label> <input type="text" name="price" id="update_price"><br>
      <input type="submit" name='submit' value='Update' />
    </form>
  </body>
</html>