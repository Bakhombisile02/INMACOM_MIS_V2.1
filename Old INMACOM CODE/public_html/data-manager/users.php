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

  <title>Users</title>

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
                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Manage Users</h4>
          </div>
          <div class="d-none d-md-block">
            <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="upload" class="wd-10 mg-r-5"></i> Import</a>
            <button class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><i data-feather="download" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button>
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-3">
            <h6 class="mg-b-5">Add User</h6>
            <form action="#" method="post" id="registration_form">
              <div class="form-group">
                <label>Email address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address">
              </div>
              <div class="form-group">
                <div class="d-flex justify-content-between mg-b-5">
                  <label class="mg-b-0-f">Password</label>
                </div>
                <input type="password" name="password" class="form-control" placeholder="Enter password">
              </div>
              <div class="form-group">
                <label>Firstname</label>
                <input type="text" name="firstname" class="form-control" placeholder="Enter firstname">
              </div>
              <div class="form-group">
                <label>Lastname</label>
                <input type="text" name="lastname" class="form-control" placeholder="Enter lastname">
              </div>
              <div class="form-group">
                <label>Organization</label>
                <input type="text" name="organization" class="form-control" placeholder="Enter orginaztion">
              </div>
              <button type="submit" name="submit" class="btn btn-brand-02 btn-block">Create Account</button>
            </form>
          </div>
          <div class="col-lg-9">
            <table class="table" id="users">
              <thead>
                <tr>
                  <th scope="col">Firstname</th>
                  <th scope="col">Lastname</th>
                  <th scope="col">Email</th>
                  <th scope="col">Role</th>
                  <th scope="col">Organization</th>
                  <th scope="col">Stations</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody id="users-body">
              </tbody>
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
          <h6 class="modal-title" id="importModal">Import Users</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
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
  <div class="modal fade" id="edit_user" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title" id="importModal">Edit</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="#" method="post" id="edit_form">
            <div class="form-group">
              <label>Email address</label>
              <input type="email" name="email" id="edit_email" class="form-control" placeholder="Enter email address">
            </div>
            <div class="form-group">
              <div class="d-flex justify-content-between mg-b-5">
                <label class="mg-b-0-f">Password</label>
              </div>
              <input type="password" name="password" id="edit_password" class="form-control" placeholder="Enter password">
            </div>
            <div class="form-group">
              <label>Firstname</label>
              <input type="text" name="firstname" id="edit_firstname" class="form-control" placeholder="Enter firstname">
            </div>
            <div class="form-group">
              <label>Lastname</label>
              <input type="text" name="lastname" id="edit_lastname" class="form-control" placeholder="Enter lastname">
            </div>
            <div class="form-group">
              <label>Organization</label>
              <input type="text" name="organization" id="edit_organization" class="form-control" placeholder="Enter orginaztion">
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
          <button type="submit" name="submit" class="btn btn-brand-02 tx-13">Save</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal fade" id="assign_station" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title">Assign Station</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="#" method="post" id="assign_form">
            <div class="form-group">
              <label for="station" class="d-block">Station</label>
              <select name="station" id="station" class="form-control select2">
                
              </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
          <button type="button" id="btn_assign" name="assign" class="btn btn-brand-02 tx-13">Save</button>
        </div>
        </form>
      </div>
    </div>
  </div>
  <div class="modal fade" id="view_stations" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title">Assign Station</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <table class="table table-borderless table-hover">
            <thead>
              <tr>
                <th>Code</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="assigned_stations"></tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="delete_user" tabindex="-1">
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
    getUsers();
    getStation();

    $('#registration_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: '../api/register.php',
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            getUsers();
          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        }
      });
    });

    $('#edit_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: '../api/register.php',
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            getUsers();
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
        url: "../api/users.php",
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
              $("#edit_email").val(data[i].email);
              $("#edit_firstname").val(data[i].first_name);
              $("#edit_lastname").val(data[i].last_name);
              $("#edit_password").val(data[i].password);
              $("#edit_organization").val(data[i].organization);
            }

          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
      $('#edit-station').modal('show');
    });
    $(document).on('click', '.delete', function(e) {

      var id = $(this).attr('id');
      $('#btn-delete').data('id', id);
      $('#delete-station').modal('show');

    });
    $(document).on('click', '.assign', function(e) {

      var id = $(this).attr('id');
      $('#btn_assign').data('id', id);
      $('#assign_station').modal('show');

    });
    $(document).on('click', '#btn_assign', function(e) {
      e.preventDefault();
      var id = $(this).data("id")
      var station = $('#station').val();
      $.ajax({
        type: "POST",
        url: '../api/users.php',
        data: {
          user_id: id,
          station_id: station,
          assign: 'station'
        },
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            getUsers();
          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        }
      });

    });
    $(document).on('click', '#btn-delete', function(e) {
      e.preventDefault();
      var id = $(this).data("id")
      $.ajax({
        type: "POST",
        url: "../api/users.php",
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

    $(document).on('click', '.view', function(e) {
      e.preventDefault();
      var id = $(this).attr("id");
      alert(id)
      getAssignedStations(id);
    });

    function getUsers() {
      $.ajax({

        type: "POST",
        url: "../api/users.php",
        data: {
          getdata: 'users'
        },
        dataType: "json",
        success: function(response) {

          var resp = response.type
          if (resp == "successful") {

            var data = response.data
            var appdata = "";

            for (i = 0; i < data.length; i++) {

              appdata += `
              <tr>
                <td><strong>${data[i].first_name}</strong></td>
                <td>${data[i].last_name}</td>
                <td>${data[i].email}</td>
                <td>${data[i].role}</td>
                <td>${data[i].organization}</td>
                <td><a href="#" class="view" id="${data[i].id}" data-toggle="modal" data-target="#view_stations">View</a></td>
                <td>
                  <a id="${data[i].id}"  class="btn  btn-xs btn-primary text-white assign" data-toggle="modal" data-target="#assign_station"  data-placement="top" title="Assign Station">Assign</a>
                  <a id="${data[i].id}"  class="btn  btn-xs btn-secondary text-white edit" data-toggle="modal" data-target="#edit_user"  data-placement="top" title="Edit">Edit</a>
                  <a id="${data[i].id}"  class="btn  btn-xs btn-danger text-white delete" data-toggle="modal" data-target="#delete_user"  data-placement="top" title="Delete">Delete</a>
                </td>
              </tr>`;

            }
            if ($.fn.dataTable.isDataTable('#users')) {
              $('#users').DataTable().destroy();
            }
            $('#users-body').html(appdata);
            $('#users').DataTable({
              language: {
                searchPlaceholder: 'Search...',
                sSearch: '',
                lengthMenu: '_MENU_ dams/page',
              }
            });
          } else if (resp == "error") {
            alert('Failed to load users')
          }
        },
        error(a, b, c) {
          console.log(b)
        }
      });
    }

    function getStation() {
      $.ajax({

        type: "POST",
        url: "../api/stations.php",
        data: {
          stations: 'stations'
        },
        dataType: "json",
        success: function(response) {

          resp = response.type
          if (resp == "success") {
            data = response.stations
            appdata = "";

            for (i = 0; i < data.length; i++) {
              appdata += '<option value="' + data[i].code + '">(' + data[i].code + ') ' + data[i].name + '</option>';
            }

            $('#station').html(appdata);
            $("#edit_station-list").html(appdata);
          }

        },
        error(a, b, c) {
          console.log(b)
        }
      });
    }

    function getAssignedStations(id) {
      $.ajax({

        type: "POST",
        url: "../api/users.php",
        data: {
          assigned: 'stations',
          id: id,
        },
        dataType: "json",
        success: function(response) {
          data = response.data;
          resp = response.type;

          if (resp == "successful") {
            
            data = response.data
            appdata = "";

            for (i = 0; i < data.length; i++) {
              appdata += `<tr>
              <td>${data[i].station_id} </td>
              <td>
                <a id="${data[i].id}"  class="btn  btn-xs btn-danger text-white remove" data-toggle="modal" data-target="#remove_station"  data-placement="top" title="Delete">Remove</a>
              </td>
              </tr>`;
            }
            
            $('#assigned_stations').html(appdata);
          } else if(resp =="error"){
            console.log('Error loading assigned stations');
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