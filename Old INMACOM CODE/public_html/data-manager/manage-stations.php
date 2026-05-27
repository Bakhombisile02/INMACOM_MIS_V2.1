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

  <title>Stations</title>

  <!-- vendor css -->
  <link href="../lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/datatables.net-dt/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="../lib/datatables.net-responsive-dt/css/responsive.dataTables.min.css" rel="stylesheet">
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
                <li class="breadcrumb-item active" aria-current="page">Manage Stations</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Manage Stations</h4>
          </div>
          <div class="d-none d-md-block">
            <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="download" class="wd-10 mg-r-5"></i> Import</a>
            <button id="export_button" class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5">
    <i data-feather="upload" class="wd-10 mg-r-5"></i> Export
</button>
           <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button> 
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-3">
            <h6 class="mg-b-5">Add Monitoring Station</h6>
            <form method="post" id="monitoring_station_form">
              <div class="form-group">
                <label for="code" class="d-block">Code</label>
                <input name="code" id="code" type="text" class="form-control" placeholder="Enter station code">
              </div>
              <div class="form-group">
                <label for="name" class="d-block">Name</label>
                <input name="name" id="name" type="text" class="form-control" placeholder="Enter station Name">
              </div>
              <div class="form-group">
                <label for="latitude" class="d-block">Latitude</label>
                <input name="latitude" id="latitude" type="text" class="form-control" placeholder="Eneter station latitude">
              </div>
              <div class="form-group">
                <label for="longitude" class="d-block">Longitude</label>
                <input name="longitude" id="longitude" type="text" class="form-control" placeholder="Longitude">
              </div>
              <div class="form-group">
                <label for="category" class="d-block">Category</label>
                <select class="form-control select2" multiple="multiple" id="category" name="category[]">
                  
                  <option value="Water Quality">Water Quality Monitoring</option>
                  <option value="Rainfall">Rainfall Monitoring</option>
                  <option value="Dam Levels">Dam Levels</option>
                  <option value="Flow Levels">Flow Gauging</option>
                  <option value="Groundwater">Groundwater Monitoring</option>
                </select>
              </div>
              <button class="btn btn-primary" type="submit">Save Station</button>
              <button class="btn btn-warning" type="reset">Cancel</button>
            </form>
          </div>
          <div class="col-lg-9">
            <table class="table" id="stations-table">
              <thead>
                <tr>
                  <th scope="col">Code</th>
                  <th scope="col">Name</th>
                  <th scope="col">Latitude</th>
                  <th scope="col">Longitude</th>
                  <th scope="col">Category</th>
                  <th scope="col" id="action">Action</th>
                </tr>
              </thead>
              <tbody id="stations-body"></tbody>
            </table>
          </div>
        </div><!-- row -->
      </div><!-- container -->
    </div>
  </div>


  <div class="modal fade" id="import" tabindex="-1" role="dialog" aria-labelledby="importModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title" id="importModal">Import Stations</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="post" id="import_form" class="form-validate is-alter" enctype="multipart/form-data">
    <a href="./templates/flow_levels.csv" target="_blank">Download and open sample csv and populate data </a>
    <a href="./templates/station.csv" target="_blank" class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download wd-10 mg-r-5">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
    </a>
    <div class="custom-file">
        <input type="file" name="file" class="custom-file-input" id="customFile" onchange="updateLabel(this)">
        <label class="custom-file-label" for="customFile" id="fileLabel">Choose excel file</label>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
        <button type="submit" name="submit" id="import_budget" class="btn btn-primary tx-13">Import</button>
    </div>
</form>
<script>
function updateLabel(input) {
    var fileName = input.files[0].name;
    var label = document.getElementById('fileLabel');
    label.textContent = fileName;
}
</script>

      </div>
    </div>
  </div>
  
  <div class="modal fade" id="edit-station" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title" id="importModal">Edit</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" id="edit_dam_level_form">
            <div class="row">
              <div class="form-group col-md-4">
                <label for="code" class="d-block">Code</label>
                <input name="code" id="edit_code" type="text" class="form-control" placeholder="Enter station code">
              </div>
              <div class="form-group col-md-8">
                <label for="name" class="d-block">Name</label>
                <input name="name" id="edit_name" type="text" class="form-control" placeholder="Enter station Name">
              </div>
              <div class="form-group col-md-6">
                <label for="latitude" class="d-block">Latitude</label>
                <input name="latitude" id="edit_latitude" type="text" class="form-control" placeholder="Eneter station latitude">
              </div>
              <div class="form-group col-md-6">
                <label for="longitude" class="d-block">Longitude</label>
                <input name="longitude" id="edit_longitude" type="text" class="form-control" placeholder="Longitude">
              </div>
              <div class="form-group col-md-12">
                <label for="category" class="d-block">Category</label>
                <select class="form-control select2" multiple="multiple" id="edit_category" name="edit_category[]">
                  
                  <option value="Water Quality">Water Quality Monitoring</option>
                  <option value="Rainfall">Rainfall Monitoring</option>
                  <option value="Dam Levels">Dam Levels</option>
                  <option value="Flow Levels">Flow Gauging</option>
                  <option value="Groundwater">Groundwater Monitoring</option>
                </select>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary tx-13">Save</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" tabindex="-1" id="delete-station">
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
    getStations();
      $('.select2').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options'
      });

      $('#edit_category').select2({
        placeholder: 'Choose station',
        searchInputPlaceholder: 'Search options',
        dropdownParent: $('.modal-body')
      });

    $('#monitoring_station_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: '../api/manage-stations.php',
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            alert(text);
            getStations();

          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        }
      });
    });

    $(document).on('click', '.edit', function(e) {

      var id = $(this).attr('id');
      $.ajax({
        type: "POST",
        url: "../api/manage-stations.php",
        dataType: "json",
        data: {
          getrecord: 'getrecord',
          id: id
        },
        success: function(response) {
          var resp = response.type

          if (resp == "successful") {
            var data = response.data
            var appdata = "";

            for (i = 0; i < data.length; i++) {
              $("#edit_category").val(data[i].category).change();
              $("#edit_name").val(data[i].name);
              $("#edit_latitude").val(data[i].latitude);
              $("#edit_longitude").val(data[i].longitude);
              $("#edit_code").val(data[i].code);
            }

          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
      $('#edit-station').modal('show');
    })
    $(document).on('click', '.delete', function(e) {

      var id = $(this).attr('id');
      $('#btn-delete').data('id', id);
      $('#delete-station').modal('show');

    })

    $(document).on('click', '#btn-delete', function(e) {
      e.preventDefault();
      var id = $(this).data("id")
      $.ajax({
        type: "POST",
        url: "../api/manage-stations.php",
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
            $("#delete-station").modal('hide');
            location.reload();


          } else if (resp == "error") {
            $("#delete-station").modal('hide');
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




$(document).ready(function() {
    $('#export_button').on("click", function(e) {
        e.preventDefault(); // Prevent any default action

        $.ajax({
            url: "../api/export-stations.php",
            method: "GET",
            success: function(data, status, xhr) {
                // Check for a filename
                var disposition = xhr.getResponseHeader('Content-Disposition');
                var matches = /"([^"]*)"/.exec(disposition);
                var filename = (matches != null && matches[1] ? matches[1] : 'stations.csv');

                // Create a link to download the file
                var blob = new Blob([data], { type: 'text/csv' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;

                // Append the link to the document body
                document.body.appendChild(link);

                // Programmatically click the link to trigger the download
                link.click();

                // Remove the link from the document
                document.body.removeChild(link);

                alert("CSV file has been exported successfully");
            },
            error: function() {
                alert("An error occurred while exporting the file.");
            }
        });
    });
});





    $('#import_form').on("submit", function(e) {
      e.preventDefault(); //form will not submitted  
      $.ajax({
        url: "../api/import-stations.php",
        method: "POST",
        data: new FormData(this),
        contentType: false, // The content type used when sending data to the server.  
        cache: false, // To unable request pages to be cached  
        processData: false, // To send DOMDocument or non processed data file it is set to false  
        success: function(data) {
          if (data == 'Error1') {
            alert("Invalid File");
          } else if (data == "Error2") {
            alert("Please Select File");
          } else if (data == "Success") {
            $('#import_form')[0].reset();
            $('#import').modal('hide');
            alert("CSV file data has been imported");
          } else {

          }
        }
      })
    });

    function Export() {
      $("#stations-table").table2excel({
        type: "csv",
        ignoreColumns: "#action",
        filename: "stations.csv"
      });
    }

    function getStations() {
      $.ajax({

        type: "POST",
        url: "../api/stations.php",
        dataType: "json",
        data: {
          stations: 'datamanager'
        },
        success: function(response) {

          resp = response.type
          if (resp == "success") {

            data = response.stations
            appdata = "";

            for (i = 0; i < data.length; i++) {
              appdata += '<tr>'
              appdata += '<td>' + data[i].code + '</td>'
              appdata += '<td>' + data[i].name + '</td>'
              appdata += '<td>' + data[i].latitude + '</td>'
              appdata += '<td>' + data[i].longitude + '</td>'
              appdata += '<td>' + data[i].category + '</td>'
              appdata += '<td>'
              appdata += '<a id="' + data[i].id + '"  class="btn  btn-xs btn-secondary text-white mg-r-10 edit" data-toggle="modal" data-target="#edit-station"  data-placement="top" title="Edit">Edit</a>'
              appdata += '<a id="' + data[i].id + '"  class="btn  btn-xs btn-danger text-white delete" data-toggle="modal" data-target="#delete-station"  data-placement="top" title="Delete">Delete</a>'
              appdata += '</td>'
              appdata += '</tr>'

            }

            $('#stations-body').html(appdata);
            $('#stations-table').DataTable({
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