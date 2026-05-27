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
  <link href="../lib/jquery-transfer/jquery.transfer.css" rel="stylesheet">
  <link href="../lib/jquery-transfer/icon_font.css" rel="stylesheet">

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
            <!-- <a href="#import" class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5" data-toggle="modal"><i data-feather="upload" class="wd-10 mg-r-5"></i> Import</a>
            <button class="btn btn-xs pd-x-15 btn-success btn-uppercase mg-l-5"><i data-feather="download" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button> -->
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-3">
            <h6 class="mg-b-5">Add User</h6>
            <form action="#" method="post" id="registration_form">
              <div class="form-group">
                <label>Firstname</label>
                <input type="text" name="firstname" class="form-control" placeholder="Enter firstname">
              </div>
              <div class="form-group">
                <label>Lastname</label>
                <input type="text" name="lastname" class="form-control" placeholder="Enter lastname">
              </div>
              <div class="form-group">
                <div class="d-flex justify-content-between mg-b-5">
                  <label class="mg-b-0-f">Country</label>
                </div>
                <select class="custom-select" name="country" id="country">
                  <option value="">Select country</option>
                  <option value="South Africa">South Africa</option>
                  <option value="Eswatini">Eswatini</option>
                  <option value="Mozambique">Mozambique</option>
                </select>
              </div>
              <div class="form-group">
                <label>Organization</label>
                <input type="text" name="organization" class="form-control" placeholder="Enter orginaztion">
              </div>
              <div class="form-group">
                <label>Telephone</label>
                <input type="text" name="telephone" class="form-control" placeholder="+123-012345678">
              </div>
              <div class="form-group">
                <label>Email address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address">
              </div>

              <button type="submit" name="submit" id="submit" class="btn btn-brand-02 btn-block">Create Account</button>
            </form>
          </div>
          <div class="col-lg-9">
            <table class="table" id="users">
              <thead>
                <tr>
                  <th scope="col">Name</th>
                  <th scope="col">Email</th>
                  <th scope="col">Telephone</th>
                  <th scope="col">Country</th>
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
              <label>Firstname</label>
              <input type="text" name="edit_firstname" class="form-control" id="edit_firstname" placeholder="Enter firstname">
            </div>
            <div class="form-group">
              <label>Lastname</label>
              <input type="text" name="edit_lastname" id="edit_lastname" class="form-control" placeholder="Enter lastname">
            </div>
            <div class="form-group">
              <div class="d-flex justify-content-between mg-b-5">
                <label class="mg-b-0-f">Country</label>
              </div>
              <select class="custom-select" name="edit_country" id="edit_country">
                <option value="">Select country</option>
                <option value="South Africa">South Africa</option>
                <option value="Eswatini">Eswatini</option>
                <option value="Mozambique">Mozambique</option>
              </select>
            </div>
            <div class="form-group">
              <label>Organization</label>
              <input type="text" name="edit_organization" id="edit_organization" class="form-control" placeholder="Enter orginaztion">
            </div>
            <div class="form-group">
              <label>Telephone</label>
              <input type="text" name="edit_telephone" id="edit_telephone" class="form-control" placeholder="+123-012345678">
            </div>
            
            <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
            <button type="button" id="btn-edit" name="button" class="btn btn-brand-02 tx-13">Save</button>
          </form>
        </div>
        
      </div>
    </div>
  </div>
  <div class="modal fade" id="assign-stations" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title">Assign Stations</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="transfer1" class="transfer-demo"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" type="button" data-dismiss="modal">Cancel</button>
          <button id="save-items" class="btn btn-primary" type="button">Save</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="view_stations" tabindex="-1" role="dialog" aria-labelledby="editModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title">Assigned Station</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <table class="table table-borderless table-hover">
            <thead>
              <tr>
                <th>Code</th>
                <th>Station Name</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="assigned_stations"></tbody>
          </table>
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
  <script src="../lib/jquery-transfer/jquery.transfer.js"></script>
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
        beforeSend: function() {
          var loading = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span> Please wait... </span>'
          $('#submit').html(loading)
          $('#submit').prop('disabled', true)
        },
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            getUsers();
          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        },
        complete: function() {
          var loading = '<span class="done" ><em class="icon ni ni-check"></em> Create Account </span>';
          $('#submit').html(loading)
          $('#submit').prop('disabled', false)
          $("#registration_form")[0].reset()
        },
      });
    });

    $(document).on('click', '.edit', function(e) {

      var id = $(this).attr('id');
      $('#btn-edit').data('id', id);
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
              $("#edit_country").val(data[i].country);
              $("#edit_telephone").val(data[i].telephone);
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

    $(document).on('click', '#btn-edit', function(e) {
      e.preventDefault();
      var id = $(this).data("id")
      var country = $("#edit_country").val();
      var first_name = $("#edit_firstname").val();
      var last_name = $("#edit_lastname").val();
      var telephone = $("#edit_telephone").val();
      var organization = $("#edit_organization").val();

      $.ajax({
        type: "POST",
        url: "../api/users.php",
        data: {
          id: id,
          first_name: first_name,
          last_name: last_name,
          telephone: telephone,
          organization: organization,
          country: country,
          edit: 'edit'

        },
        dataType: "json",
        success: function(response) {
          respo = response.type
          txt = response.text

          if (respo == 'success') {
            location.reload();
          } else if (resp == "failed") {
            $("#edit_treshhold").modal('hide');
            alert(txt)
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
    $(document).on('click', '.delete', function(e) {

      var id = $(this).attr('id');
      $('#btn-delete').data('id', id);
      $('#delete-station').modal('show');

    });

    $(document).on('click', '.assign', function(e) {

      var id = $(this).attr('id');
      $('#save-items').data('id', id);
      $('#assign-stations').modal('show');

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

    $(document).on('click', '.remove', function(e) {
      e.preventDefault();
      var id = $(this).attr("id")
      var user_id = $(this).data("userid")
      $.ajax({
        type: "POST",
        url: "../api/users.php",
        data: {
          id: id,
          remove: 'station',
        },
        dataType: "json",
        success: function(response) {
          //console.log(response)
          respo = response.type
          txt = response.text

          if (respo == 'successful') {
            getAssignedStations(user_id)

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
      getAssignedStations(id);
      $('#view_stations').modal('show');
    });
    $(document).on('click', '#save-items', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var arr = [];

      for (var i = 0; i < selected_items.length; i++) {
        arr.push(selected_items[i].code);
      }

      $.ajax({
        type: "POST",
        url: '../api/users.php',
        data: {
          user_id: id,
          station_id: arr,
          assign: 'station'
        },
        dataType: "json",
        beforeSend: function() {
          var loading = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span> Please wait... </span>'
          $('#save-items').html(loading)
          $('#save-items').prop('disabled', true)
        },
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            $('#assign-stations').modal('hide');
          } else if (type == "failed") {
            alert('Error occured')
            console.log(text);
          }

        },
        complete: function() {
          var loading = '<span class="done" ><em class="icon ni ni-check"></em> Done </span>';
          $('#save-items').html(loading)
          $('#save-items').prop('disabled', false)
        },
      });



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
                <td><strong>${data[i].first_name} ${data[i].last_name}</strong></td>
                <td>${data[i].email}</td>
                <td>${data[i].telephone}</td>
                <td>${data[i].country}</td>
                <td><a href="#" class="view" id="${data[i].id}">View</a></td>
                <td>
                  <a id="${data[i].id}"  class="btn  btn-xs btn-primary text-white assign"  data-placement="top" title="Assign Station">Assign</a>
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
            var settings1 = {
              "dataArray": data,
              "itemName": "name",
              "valueName": "code",
              "callable": function(items) {
                selected_items = items
              }
            };

            var transfer = $("#transfer1").transfer(settings1);
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
              <td>${data[i].code} </td>
              <td>${data[i].name} </td>
              <td>
                <a id="${data[i].usid}" data-userid="${data[i].user_id}" class="btn  btn-xs btn-danger text-white remove" data-toggle="modal" data-target="#remove_station"  data-placement="top" title="Delete">Remove</a>
              </td>
              </tr>`;
            }

            $('#assigned_stations').html(appdata);
          } else if (resp == "error") {
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