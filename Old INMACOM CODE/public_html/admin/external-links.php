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
                <li class="breadcrumb-item active" aria-current="page">Manage External Links</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Manage External Links</h4>
          </div>
          <div class="d-none d-md-block">
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-3">
            <h6 class="mg-b-5">Add External Link</h6>
            <form method="POST" id="flow_level_form">
              <div class="form-group">
                <label for="link_name" class="d-block">Link Name</label>
                <input name="link_name" id="link_name" type="text" class="form-control" placeholder="Enter Website Name">
              </div>
              <div class="form-group">
                <label for="link" class="d-block">URL</label>
                <input name="link" id="url" type="text" class="form-control" placeholder="Enter website link">
              </div>
              <button class="btn btn-primary" type="submit">Save</button>
              <button class="btn btn-warning" type="reset">Cancel</button>
            </form>
          </div>
          <div class="col-lg-9">
            <table class="table" id="links_table">
              <thead>
                <tr>
                  <th scope="col">Link</th>
                  <th scope="col">URL</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody id="links-table-body">
              </tbody>
            </table>
          </div>
        </div><!-- row -->
      </div><!-- container -->
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
        url: '../api/flow-level-data.php',
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
    $(document).on('click', '.edit', function(e) {

      var id = $(this).attr('id');
      $.ajax({
        type: "POST",
        url: "../api/flow-level-data.php",
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
              $("#edit_station-list").val(data[i].id).change();
              $("#edit_value").val(data[i].value);
            }


          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
      $('#edit_form').modal('show');
    })
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
    $('#import_form').on("submit", function(e) {
      e.preventDefault(); //form will not submitted  
      $.ajax({
        url: "../api/import-flow-levels.php",
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

    function getStations() {
      $.ajax({

        type: "POST",
        url: "../api/stations.php",
        data: {
          flow: 'datamanager'
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
            $("#edit_station-list").html(appdata);
          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
    }

    function getData() {
      $.ajax({

        type: "POST",
        url: "../api/flow-level-data.php",
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
            $('#link-table-body').html(appdata);
            $('#link-table').DataTable({
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