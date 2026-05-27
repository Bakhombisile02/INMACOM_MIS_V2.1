<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Twitter -->
  <meta name="twitter:site" content="@datamatics">
  <meta name="twitter:creator" content="@datamatics">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="INMACOM">
  <meta name="twitter:description" content="INMACOM MIS">
  <meta name="twitter:image" content="dashforge/img/dashforge-social.php">

  <!-- Facebook -->
  <meta property="og:url" content="https://datamatics.co.za">
  <meta property="og:title" content="INMACOM">
  <meta property="og:description" content="INMACOM MIS">

  <meta property="og:image" content="dashforge/img/dashforge-social.php">
  <meta property="og:image:secure_url" content="dashforge/img/dashforge-social.php">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">

  <!-- Meta -->
  <meta name="description" content="INMACOM MIS">
  <meta name="author" content="datamatics">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="./assets/img/favicon.png">

  <title>IMNACOM MIS - Historical Data</title>
  <!-- <link href="./library/bootstrap-5/bootstrap.min.css" rel="stylesheet" /> -->
  <!-- <link href="./library/dataTables.bootstrap5.min.css" rel="stylesheet" /> -->
  <link href="./lib/datatables.net-dt/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="./library/daterangepicker.css" rel="stylesheet" />
  <link href="./lib/select2/css/select2.min.css" rel="stylesheet">
  <!-- vendor css -->
  <link href="./lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="./lib/ionicons/css/ionicons.min.css" rel="stylesheet">


  <!-- INMACOM CSS -->
  <link rel="stylesheet" href="./assets/css/dashforge.css">
  <link rel="stylesheet" href="./assets/css/dashforge.dashboard.css">
</head>

<body class="page-profile">
  <?php require_once 'includes/header.php'; ?>
  <!-- navbar -->
  <figure class="pos-relative">
    <img src="./assets/img/MagnificentVibrantDowitcher-max-1mb.gif" alt="" width="100%" height="200">
    <figcaption class="pos-absolute a-0 pd-25 tx-white-8">
      <h1 class="mg-t-80 text-white text-center"><span class="text-danger d-none">INMACOM</span> MANAGEMENT INFORMATION SYSTEM</h1>
    </figcaption>
  </figure>
  <div class="col-12 mg-b-50">
    <div class="row row-xs">

      <div class="col-lg-12 mg-t-10 mg-b-20">
        <div class="card card-crypto">
          <div class="card-header pd-y-8 d-sm-flex align-items-center justify-content-between">

            <ul class="nav nav-line" id="myTab5" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="flow" data-toggle="tab" href="#home1" role="tab" aria-controls="home" aria-selected="true">Flow Rate</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="levels" data-toggle="tab" href="#home1" role="tab" aria-controls="profile" aria-selected="false">DAM LEVELS</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="quality" data-toggle="tab" href="#home1" role="tab" aria-controls="contact" aria-selected="false">WATER QUALITY</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="ground" data-toggle="tab" href="#home1" role="tab" aria-controls="contact" aria-selected="false">GROUNDWATER</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="rainfall" data-toggle="tab" href="#home1" role="tab" aria-controls="contact" aria-selected="false">RAINFALL</a>
              </li>
            </ul>
            <div class="col col-sm-3">
              <div class="row">
                <label for="station">Station</label>
                <select class="form-control" id="station"></select>
              </div>
            </div>
            <div class="col col-sm-3">
              <div class="row">
                <label for="daterage_textbox">Data Range</label>
                <input type="text" id="daterange_textbox" class="form-control" />
              </div>
            </div>
          </div><!-- card-header -->
          <div class="card-body pd-10">
            <div class="tab-content" id="myTabContent5">
              <div class="tab-pane fade show active" id="home1" role="tabpanel" aria-labelledby="Flow Rate">
                <div data-label="Example" class="df-example">
                  <canvas id="bar_chart" height="40"></canvas>
                  <table class="table table-hover table-bordered" id="order_table">
                    <thead>
                      <tr>
                        <th>Station</th>
                        <th>Value</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
              <div class="tab-pane fade" id="home2" role="tabpanel" aria-labelledby="Dam Levels">
                <canvas id="chart1" class="ht-400" style="width: 100%; background: #ffffff;"></canvas>
              </div>
              <div class="tab-pane fade" id="home3" role="tabpanel" aria-labelledby="Water Quality">
                <canvas id="chart2" class="ht-400" style="width: 100%; background: #ffffff;"></canvas>
              </div>
              <div class="tab-pane fade" id="home4" role="tabpanel" aria-labelledby="Groundwater">
                <canvas id="chart3" class="ht-400" style="width: 100%; background: #ffffff;"></canvas>
              </div>
            </div>
          </div><!-- card-body -->

        </div><!-- card -->
      </div><!-- col -->

    </div><!-- row -->
  </div>



  <?php require_once 'includes/footer.php'; ?>

  <script src="./lib/jquery/jquery.min.js"></script>
  <script src="./lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./library/moment.min.js"></script>
  <script src="./library/daterangepicker.min.js"></script>
  <script src="./library/Chart.bundle.min.js"></script>
  <script src="./library/jquery.dataTables.min.js"></script>
  <script src="./lib/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="./lib/feather-icons/feather.min.js"></script>
  <script src="./lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="./lib/jqueryui/jquery-ui.min.js"></script>
  <script src="./lib/select2/js/select2.min.js"></script>
  <script src="./assets/js/dashforge.js"></script>

  <script>
    $(document).ready(function() {
      var table = 'flow_levels';
      var category = 'Flow Levels';
      var label_text = 'Daily Flow AVG (m^3/s)';

      getStations(category);
      $('.select2').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options'
      });

      $('#station').on('change', function() {
        var station_id = $("#station option:selected").val();
        fetch_data(start_date = '', end_date = '', station_id, table);
      });

      $('#flow').on('click', function() {
        category = 'Flow Levels';
        table = 'flow_levels';
        label_text = 'Daily Flow AVG (m^3/s)';

        getStations(category);
        fetch_data('', '', $('#station').select2().val(), table);
      });
      $('#levels').on('click', function() {
        category = 'Dam Levels'
        table = 'dam_levels';
        label_text = 'Daily Storage (%)'
        getStations(category);
        fetch_data('', '', $('#station').select2().val(), table);
      });
      $('#quality').on('click', function() {
        category = 'Water Quality';
        table = 'water_quality';
        label_text = 'Water Quality Data'
        getStations(category);
        fetch_data('', '', $('#station').select2().val(), table);
      });

      $('#rainfall').on('click', function() {
        category = 'Rainfall';
        table = 'rainfall';
        label_text = 'Rainfall (mm)'
        getStations(category);
        fetch_data('', '', $('#station').select2().val(), table);
      });

      $('#ground').on('click', function() {
        category = 'Groundwater';
        table = 'groundwater';
        label_text = 'Groundwater Data'
        getStations(category);
        fetch_data('', '', $('#station').select2().val(), table);
      });


      fetch_data('', '', $('#station').select2().val(), table);

      var sale_chart;

      function fetch_data(start_date, end_date, station_id, table) {
        var dataTable = $('#order_table').DataTable({
          "destroy": true,
          "processing": true,
          "serverSide": true,
          "order": [],
          "lengthMenu": [
            [7, 30, 31, 60, 90, 180, 365, -1],
            [7, 30, 31, 60, 90, 180, 365, 'All'],
          ],
          "ajax": {
            url: "api/action.php",
            type: "POST",
            data: {
              action: 'fetch',
              start_date: start_date,
              end_date: end_date,
              station_id: station_id,
              table: table
            }
          },
          "drawCallback": function(settings) {
            var sales_date = [];
            var sale = [];

            for (var count = 0; count < settings.aoData.length; count++) {
              sales_date.push(settings.aoData[count]._aData[2]);
              sale.push(parseFloat(settings.aoData[count]._aData[1]));
            }

            var chart_data = {
              labels: sales_date,
              datasets: [{
                label: label_text,
                backgroundColor: '#d4f1f9',
                color: '#fff',
                data: sale
              }]
            };

            var group_chart3 = $('#bar_chart');

            if (sale_chart) {
              sale_chart.destroy();
            }

            sale_chart = new Chart(group_chart3, {
              type: 'line',
              data: chart_data
            });
          }
        });
      }

      $('#daterange_textbox').daterangepicker({
        ranges: {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        format: 'YYYY-MM-DD'
      }, function(start, end) {

        $('#order_table').DataTable().destroy();

        fetch_data(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'), $('#station').select2().val(), table);

      });

      function getStations(category) {
        $.ajax({
          type: "POST",
          url: 'api/stations.php',
          data: {
            station_cat: category
          },
          dataType: "json",
          success: function(response) {
            var type = response.type;
            var rows = '';

            if (type == "success") {
              var data = response.stations;
              for (var i = 0; i < data.length; i++) {
                rows += `<option value="${data[i].code}">${data[i].name}</option>`;
              }
              $('#station').html(rows);
              $('#station').val(data[0].code).select2();
            } else if (type == "failed") {
              alert('Failed to get data')
              console.log(text);
            }

          }
        });
      }

    });
  </script>

</body>

</html>