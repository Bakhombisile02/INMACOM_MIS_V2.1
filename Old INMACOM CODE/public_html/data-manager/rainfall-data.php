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
                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Rainfalls</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Manage Rainfall Data</h4>
          </div>
          <div class="d-none d-md-block">
            <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="upload" class="wd-10 mg-r-5"></i> Import</a>
            <button class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><i data-feather="download" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button>
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-3">
            <h6 class="mg-b-5">Add Rainfall Data</h6>
            <form method="POST" id="flow_level_form">
              <div class="form-group">
                <label for="station" class="d-block">Rainfall Station</label>
                <select name="station" id="station-list" class="form-control select2">
                  <option selected>Select Rainfall Station</option>
                </select>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="value" class="d-block">Value</label>
                  <input name="value" id="value" type="number" step=".01" class="form-control" placeholder="Enter value">
                </div>
                <div class="form-group col-md-6">
                  <label for="unit" class="d-block">Unit</label>
                  <select name="unit" id="unit" class="form-control select2">
                    <option value="mm">MM</option>
                    <option value="%">%</option>
                  </select>
                </div>
                <div class="form-group col-md-12">
                  <label for="date_time" class="d-block">Date Time</label>
                  <input class="form-control" type="datetime-local" id="date_time" name="date_time">
                </div>
              </div>

              <button class="btn btn-primary" type="submit">Save</button>
              <button class="btn btn-warning" type="reset">Cancel</button>
            </form>
          </div>
          <div class="col-lg-9">
            <table class="table" id="flow_level">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Station</th>
                  <th scope="col">Value</th>
                  <th scope="col">Unit</th>
                  <th scope="col">Date</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody id="flow-level-body">
              </tbody>
            </table>
          </div>
        </div><!-- row -->
      </div><!-- container -->
      <div class="modal fade" id="import" tabindex="-1" role="dialog" aria-labelledby="importModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content tx-14">
            <div class="modal-header">
              <h6 class="modal-title" id="importModal">Import Rainfall Data</h6>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
            <a href="./templates/flow_levels.csv" target="_blank">Download and open sample csv and populate data </a>
              <a href="./templates/rainfall.csv" target="_blank" class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download wd-10 mg-r-5">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg></a>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="customFile">
                <label class="custom-file-label" for="customFile">Choose excel file</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary tx-13">Import</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" tabindex="-1" id="delete-flow-level">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <a href="#" class="close" data-dismiss="modal"><em class="icon ni ni-cross-sm"></em></a>
        <div class="modal-body modal-body-lg text-center">
          <div class="nk-modal">
            <em class="nk-modal-icon icon icon-circle icon-circle-xxl  ni ni-help bg-warning"></em>
            <h4 class="nk-modal-title">Are you sure you want to delete?</h4>
            <div class="nk-modal-text">
              <p class="sub-text"> You can't revert this action! </p>
            </div>
            <div class="nk-modal-action-lg">
              <button type="button" id="btn-delete" class="btn btn-lg btn-mw btn-primary"><em class="icon ni ni-check-circle-cut"></em><span>Yes! Delete it!</span></button>
              <button data-dismiss="modal" class="btn btn-lg btn-mw  btn-danger"><em class="icon ni ni-na"></em><span>Cancel</span></button>
            </div>
          </div>
        </div><!-- .modal-body -->
      </div><!-- .modal-content -->
    </div><!-- .modla-dialog -->
  </div><!-- .modal -->

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../lib/feather-icons/feather.min.js"></script>
  <script src="../lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="../lib/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="../lib/select2/js/select2.min.js"></script>
  <script src="../assets/js/dashforge.js"></script>
  <script src="../assets/js/dashforge.aside.js"></script>
  <script src="../lib/jquery.table2excel.min.js"></script>

  <script>
    getData();
    getStations();
      $('.select2').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options'
      });

    $('#flow_level_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: '../api/rainfall-data.php',
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            alert(text);
            location.reload();

          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        }
      });
    })
    $(document).on('click', '.delete', function(e) {

      var id = $(this).attr('id');
      $('#btn-delete').data('id', id)
    });

    $(document).on('click', '#btn-delete', function(e) {
      e.preventDefault();
      var id = $(this).data("id")
      $.ajax({
        type: "POST",
        url: "../api/flow-level-data.php",
        data: {

          'id': id,
          delete: 'delete',
        },
        dataType: "json",
        success: function(response) {
          //console.log(response)
          respo = response.type
          txt = response.text

          if (respo == 'successful') {
            $("#delete-flow-level").modal('hide');
            location.reload();


          } else if (resp == "error") {
            $("#delete-flow-level").modal('hide');
            $(".successMessage").html(txt);
            $("#errorModals").modal('show');
          }
        },
        error: function(b) {
          $("#title").html("Error");
          $("#icon").html('<em class="nk-modal-icon icon icon-circle icon-circle-xxl ni ni-cross bg-danger"></em>');
          $(".successMessage").html("Something went wrong. Contact system administrator!");
          $("#errorModals").modal('show');
          console.log(b)
          alert(b);
        }
      });
    });

    function getData() {
      $.ajax({

        type: "POST",
        url: "../api/rainfall-data.php",
        dataType: "json",
        data: {
          getdata: 'datamanager'
        },
        success: function(response) {

          resp = response.type
          if (resp == "successful") {

            data = response.data
            appdata = "";
            var colors = 'rgb(127, 127, 127)';

            for (i = 0; i < data.length; i++) {
              var counter = i + 1;
              if (data[i].value > 0 && data[i].value < 20) {
                colors = 'rgb(255, 0, 0)'
              } else if (data[i].value >= 20 && data[i].value <= 40) {
                colors = 'rgb(255, 192, 0)'
              } else if (data[i].value > 40 && data[i].value < 60) {
                colors = 'rgb(146, 208, 80)'
              } else if (data[i].value >= 60 && data[i].value <= 80) {
                colors = 'rgb(51, 153, 255)'
              } else if (data[i].value > 80 && data[i].value <= 100) {
                colors = 'rgb(51, 153, 255)'
              } else if (data[i].value > 100) {
                colors = 'rgb(0, 51, 204)'
              }
              appdata += `<tr>
      <td>${counter} </td>
      <td>${data[i].name} </td>
      <td style="color:${colors}">${data[i].value} </td>
      <td>${data[i].unit} </td>
      <td>${data[i].date} </td>
      <td>
        <a id="${data[i].id}"  class="btn  btn-xs btn-primary text-white edit" data-toggle="modal" data-target="#edit-flow-level"  data-placement="top" title="Edit">Edit</a>
        <a id="${data[i].id}"  class="btn  btn-xs btn-danger text-white delete" data-toggle="modal" data-target="#delete-flow-level"  data-placement="top" title="Delete">Delete</a>
      </td>
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
          rainfall: 'datamanager'
        },
        dataType: "json",
        success: function(response) {

          resp = response.type
          if (resp == "success") {
            console.log(response)
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