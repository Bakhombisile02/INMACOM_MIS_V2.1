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
  <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

  <title>Water Quantity</title>

  <!-- vendor css -->
  <link href="../lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <link href="../lib/datatables.net-dt/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="../lib/datatables.net-dt/css/buttons.dataTables.min.css" rel="stylesheet">
  <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
  <link href="../library/daterangepicker.css" rel="stylesheet" />
  <!-- INMACOM CSS -->
  <link rel="stylesheet" href="../assets/css/dashforge.css">
  <link rel="stylesheet" href="../assets/css/dashforge.dashboard.css">
</head>
<?php include_once 'includes/auth_session.php'; ?>

<body>

  <?php include_once 'includes/sidebar.php'; ?>

  <div class="content ht-100v pd-0">
    <div class="content-header">
      <!-- <div class="content-search">
        <i data-feather="search"></i>
        <input type="search" class="form-control" placeholder="Search...">
      </div> -->
      <!-- <nav class="nav">
        <a href="#" class="new nav-link" data-toggle="tooltip" title="You have new notifications"><i data-feather="bell"></i></a>
        <a href="logout.php" class="nav-link" data-toggle="tooltip" title="Sign out"><i data-feather="log-out"></i></a>
      </nav> -->
    </div><!-- content-header -->

    <div class="content-body">
      <div class="container pd-x-0">
        <div class="d-sm-flex align-items-center justify-content-between mg-b-20 mg-lg-b-25 mg-xl-b-30">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb breadcrumb-style1 mg-b-10">
                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Groundwater</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Historic Groundwater Data</h4>
          </div>
          <div class="d-none d-md-block">
            <!-- <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="upload" class="wd-10 mg-r-5"></i> Import</a> -->
            <!-- <button class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><i data-feather="download" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button> -->
          </div>
        </div>

        <div class="row row-xs">

          <div class="col-lg-12">
            <div class="row pd-y-8 d-sm-flex align-items-center justify-content-between">
              <div class="col col-sm-3">
                <select class="form-control" id="station"></select>
              </div>
              <div class="col col-sm-3">
                <input type="text" id="daterange_textbox" class="form-control" />
              </div>
              <canvas id="bar_chart" height="60"></canvas>
            </div>
            <table class="table table-hover table-bordered" id="flow_level">
              <thead>
                <tr>
                  <th scope="col">Code</th>
                  <th scope="col">Station</th>
                  <th scope="col">Value</th>
                  <th scope="col">Unit</th>
                  <th scope="col">Date</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>

          </div>
        </div><!-- row -->
      </div><!-- container -->
    </div>
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../lib/feather-icons/feather.min.js"></script>
  <script src="../lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="../lib/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="../library/moment.min.js"></script>
  <script src="../library/daterangepicker.min.js"></script>
  <script src="../library/Chart.bundle.min.js"></script>
  <script src="../lib/select2/js/select2.min.js"></script>
  <script src="../assets/js/dashforge.js"></script>
  <script src="../assets/js/dashforge.aside.js"></script>
  <script src="../lib/jquery.table2excel.min.js"></script>
  <script src="../lib/datatables.net/js/dataTables.buttons.min.js"></script>
  <script src="../lib/datatables.net/js/jszip.min.js"></script>
  <script src="../lib/datatables.net/js/pdfmake.min.js"></script>
  <script src="../lib/datatables.net/js/vfs_fonts.js"></script>
  <script src="../lib/datatables.net/js/buttons.html5.min.js"></script>

  <script>
    $(document).ready(function() {
      getStations();
      $('.select2').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options'
      });


      $('#station').on('change', function() {
        var station_id = $("#station option:selected").val();
        fetch_data(start_date = '', end_date = '', station_id);
      })
      fetch_data();

      var data_chart;

      function fetch_data(start_date = '', end_date = '', station_id = $('#station').select2().val()) {
        var dataTable = $('#flow_level').DataTable({
          "dom": 'Blfrtip',
          "buttons": [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5'
          ],
          "destroy": true,
          "processing": true,
          "serverSide": true,
          "order": [],
          "lengthMenu": [
            [7, 30, 31, 60, 90, 180, 365, -1],
            [7, 30, 31, 60, 90, 180, 365, 'All'],
          ],
          "ajax": {
            url: "../api/admin_flow.php",
            type: "POST",
            data: {
              action: 'fetch',
              start_date: start_date,
              end_date: end_date,
              station_id: station_id
            }
          },
          "drawCallback": function(response) {
            var date = [];
            var data = [];

            for (var i = 0; i < response.aoData.length; i++) {
              date.push(response.aoData[i]._aData[4]);
              data.push(parseFloat(response.aoData[i]._aData[2]));
            }

            var chart_data = {
              labels: date,
              datasets: [{
                label: 'Groundwater Data',
                backgroundColor: '#d4f1f9',
                color: '#fff',
                data: data
              }]
            };

            var group_chart3 = $('#bar_chart');

            if (data_chart) {
              data_chart.destroy();
            }

            data_chart = new Chart(group_chart3, {
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

        $('#flow_level').DataTable().destroy();

        fetch_data(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));

      });

    });

    function getStations() {
      $.ajax({
        type: "POST",
        url: '../api/stations.php',
        data: {
          station_cat: 'Groundwater'
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

    function getData() {
      $.ajax({

        type: "POST",
        url: "../api/flow-level-data.php",
        dataType: "json",
        data: {
          getdata: 'getdata'
        },
        success: function(response) {

          resp = response.type
          if (resp == "successful") {

            data = response.data
            appdata = "";
            var colors = 'rgb(127, 127, 127)';

            for (i = 0; i < data.length; i++) {
              
              appdata += `<tr>
              <td>${data[i].station_id} </td>
              <td>${data[i].name} </td>
              <td>${data[i].value} </td>
              <td>${data[i].unit} </td>
              <td>${data[i].date} </td>
              </tr>`;

            }
            $('#flow-level-body').html(appdata);
            $('#flow_level').DataTable({
              dom: 'Bfrtip',
              buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5'
              ],
              language: {
                searchPlaceholder: 'Search...',
                sSearch: '',
                lengthMenu: '_MENU_ stations/page',
              }
            });
          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
    }
  </script>
</body>

</html>