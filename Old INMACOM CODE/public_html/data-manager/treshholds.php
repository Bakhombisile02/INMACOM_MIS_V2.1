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

  <title>Treshholds</title>

  <!-- vendor css -->
  <link href="../lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <link href="../lib/datatables.net-dt/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="../lib/select2/css/select2.min.css" rel="stylesheet">

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
                <li class="breadcrumb-item"><a href="#">Data Manager</a></li>
                <li class="breadcrumb-item active" aria-current="page">Treshholds</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Treshholds</h4>
          </div>
          <div class="d-none d-md-block">
            <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="upload" class="wd-10 mg-r-5"></i> Import</a>
            <button class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><i data-feather="download" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button>
          </div>
        </div>

        <div class="row row-xs">
          <!-- <div class="col-lg-3">
            <h6 class="mg-b-5">Add Treshhold</h6>
            <form method="POST" id="flow_level_form">
              <div class="form-group">
                <label for="category" class="d-block">Category</label>
                <select id="category" class="form-control select2">
                  
                  <option value="Flow Levels">Flow Levels</option>
                  <option value="Dam Levels">Dam Levels</option>
                  <option value="Rainfall">Rainfall</option>
                  <option value="Water Quality">Water Quality</option>
                  <option value="Groundwater">Groundwater</option>
                </select>
              </div>
              <div class="form-group">
                <label for="station" class="d-block">Station</label>
                <select name="station" id="station-list" class="form-control select2">
                  
                </select>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="min" class="d-block">Min</label>
                  <input name="min" id="min" type="number" step=".01" class="form-control" placeholder="Enter value">
                </div>
                <div class="form-group col-md-6">
                  <label for="min" class="d-block">Max</label>
                  <input name="min" id="min" type="number" step=".01" class="form-control" placeholder="Enter value">
                </div>
                <div class="form-group col-md-12">
                  <label for="unit" class="d-block">Unit</label>
                  <select name="unit" id="unit" class="form-control select2">
                    <option selected>Unit</option>
                    <option value="cm">cm</option>
                    <option value="m">m</option>
                    <option value="km">km</option>
                    <option value="m^3/s">m^3/s</option>
                    <option value="%">%</option>
                    <option value="BOD">BOD</option>
                    <option value="DO">DO</option>
                    <option value="COD">COD</option>
                    <option value="NO3">NO3</option>
                  </select>
                </div>
              </div>

              <button class="btn btn-primary" type="submit">Save</button>
              <button class="btn btn-warning" type="reset">Cancel</button>
            </form>
          </div> -->
          <div class="col-lg-12">
            <table class="table" id="flow_level">
              <thead>
                <tr>
                  <th scope="col">Station</th>
                  <th scope="col">Minimum</th>
                  <th scope="col">Maximum</th>
                  <th scope="col">Unit</th>
                </tr>
              </thead>
              <tbody id="flow-level-body">
              </tbody>
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
  <script src="../lib/select2/js/select2.min.js"></script>
  <script src="../assets/js/dashforge.js"></script>
  <script src="../assets/js/dashforge.aside.js"></script>

  <script>
    getData();
    getStations();
      $('.select2').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options'
      });

    function getData() {
      $.ajax({

        type: "POST",
        url: "../api/treshholds.php",
        dataType: "json",
        data: {
          getdata: 'getdata'
        },
        success: function(response) {

          resp = response.type
          if (resp == "successful") {

            data = response.data
            appdata = "";

            for (i = 0; i < data.length; i++) {
              var counter = i + 1;

              appdata += `<tr>
      <td>${data[i].station_id} </td>
      <td>${data[i].min} </td>
      <td>${data[i].max} </td>
      <td>${data[i].unit} </td>
      </tr>`;

            }
            $('#flow-level-body').html(appdata);
            $('#flow_level').DataTable({
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

    function getStations() {
      $.ajax({

        type: "POST",
        url: "../api/stations.php",
        data: {
          stations: 'datamanager'
        },
        dataType: "json",
        success: function(response) {

          resp = response.type
          if (resp == "success") {
            data = response.stations
            appdata = "";

            for (i = 0; i < data.length; i++) {
              appdata += '<option value="' + data[i].code + '">' + data[i].name + '</option>';
            }

            $('#station-list').html(appdata);
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