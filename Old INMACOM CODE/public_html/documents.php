<!DOCTYPE html>
<html lang="en">

<head>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Twitter -->
  <meta name="twitter:site" content="@themepixels">
  <meta name="twitter:creator" content="@themepixels">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="DashForge">
  <meta name="twitter:description" content="Responsive Bootstrap 4 Dashboard Template">
  <meta name="twitter:image" content="./dashforge/img/dashforge-social.html">

  <!-- Facebook -->
  <meta property="og:url" content="http://themepixels.me/dashforge">
  <meta property="og:title" content="DashForge">
  <meta property="og:description" content="Responsive Bootstrap 4 Dashboard Template">

  <meta property="og:image" content="./dashforge/img/dashforge-social.html">
  <meta property="og:image:secure_url" content="./dashforge/img/dashforge-social.html">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">

  <!-- Meta -->
  <meta name="description" content="Responsive Bootstrap 4 Dashboard Template">
  <meta name="author" content="ThemePixels">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="./assets/img/favicon.png">

  <title>IMNACOM MIS - Documents</title>

  <!-- vendor css -->
  <link href="./lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="./lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <!-- DashForge CSS -->
  <link rel="stylesheet" href="./assets/css/dashforge.css">
  <link rel="stylesheet" href="./assets/css/dashforge.profile.css">
  <link rel="stylesheet" href="./assets/css/dashforge.filemgr.css">
</head>

<body class="page-profile">

  <?php require_once './includes/header.php'; ?>
  <figure class="pos-relative">
    <img src="./assets/img/MagnificentVibrantDowitcher-max-1mb.gif" alt="" width="100%" height="200">
    <figcaption class="pos-absolute a-0 pd-25 tx-white-8">
      <h1 class="mg-t-80 text-white text-center"><span class="text-danger d-none">INMACOM</span> MANAGEMENT INFORMATION SYSTEM</h1>
    </figcaption>
  </figure>
  <div class="col-12 pd-x-25 mg-b-50">
    <div class="row row-xs">
      <div class="col-lg-2 mg-t-40 mg-lg-t-0">
        <h6 class="tx-uppercase tx-semibold mg-t-50 mg-b-15">File Storage</h6>
        <nav class="nav nav-classic tx-13" id="folder_list">
          <a href="#" id="All" class="nav-link active folder"><i data-feather="folder"></i> <span>All Files</span></a>
        </nav>

        <h6 class="tx-uppercase tx-semibold mg-t-50 mg-b-15">File Library</h6>
        <nav class="nav nav-classic tx-13">
          <a href="#" class="nav-link file-library" data-type="document"><i data-feather="file"></i> <span>Documents</span></a>
          <a href="#" class="nav-link file-library" data-type="image"><i data-feather="image"></i> <span>Images</span></a>
          <a href="#" class="nav-link file-library" data-type="video"><i data-feather="video"></i> <span>Videos</span></a>
          <a href="#" class="nav-link file-library" data-type="audio"><i data-feather="music"></i> <span>Audio</span></a>
          <a href="#" class="nav-link file-library" data-type="zip"><i data-feather="package"></i> <span>Zip Files</span></a>
        </nav>
      </div><!-- col -->
      <div class="col-lg-10">
        <div class="d-sm-flex align-items-center justify-content-between">
          <h6 class="tx-uppercase tx-semibold mg-t-50 mg-b-15">Files</h6>
          <div class="search-form mg-t-20 mg-sm-t-0">
            <input type="search" class="form-control" placeholder="Search ">
            <button class="btn" type="button"><i data-feather="search"></i></button>
          </div>
        </div>

        <div class="row row-xs mg-b-25" id="file_list">
        </div><!-- row -->
        <!-- <button class="btn btn-block btn-xs btn-white">Load more</button> -->
      </div><!-- col -->

    </div><!-- row -->
  </div><!-- content -->

  <?php require_once './includes/footer.php'; ?>

  <script src="./lib/jquery/jquery.min.js"></script>
  <script src="./lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./lib/feather-icons/feather.min.js"></script>
  <script src="./lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>

  <script src="./assets/js/dashforge.js"></script>


  <script>
    $(document).ready(function(e) {

      var folder = 'All';
      var file_type = 'All';

      getFolders();
      getFilesByFolder(folder, file_type);

      $("#create_folder").on('submit', (function(e) {
        e.preventDefault();

        $.ajax({
          url: "api/folder.php",
          type: "POST",
          data: new FormData(this),
          contentType: false,
          cache: false,
          processData: false,
          beforeSend: function() {

          },
          success: function(data) {
            if (data == 'invalid') {

              alert("Invalid File")
            } else {

              $("#create_folder")[0].reset();
              $('#folder_form').modal('hide');
              getFolders();
            }
          },
          error: function(e) {
            alert('Error occurred')
          }
        });
      }));



      $(document).on('click', '.folder', function(e) {
        e.preventDefault();
        var folder = $(this).attr('id');
        getFilesByFolder(folder);
      });

      $(document).on('click', '.file-library', function(e) {
        e.preventDefault();
        var file_type = $(this).data('type');
        getFilesByType(file_type);
      })

      function getFolders() {
        $.ajax({

          type: "POST",
          url: "api/folder.php",
          dataType: "json",
          data: {
            getdata: 'folders'
          },
          success: function(response) {

            if (response.type == "successful") {

              var data = response.data
              var appdata = "";

              for (var i = 0; i < data.length; i++) {
                appdata += `<a href="#" id="${data[i].name}" class="nav-link active folder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg> <span>${data[i].name}</span></a>`

              }

              $('#folder_list').append(appdata);

            } else {
              alert('Error')
            }

          },
          error(a, b, c) {
            console.log(b)
          }
        });
      }

      function getFilesByFolder(folder) {
        $.ajax({

          type: "POST",
          url: "api/files.php",
          dataType: "json",
          data: {
            getdata: folder
          },
          success: function(response) {

            if (response.type == "successful") {

              var data = response.data
              var appdata = "";

              for (var i = 0; i < data.length; i++) {
                appdata += `<div class="col-xs-6 col-sm-4 col-md-2 mg-t-15">
            <div class="card card-file">
              <div class="dropdown-file">
              <a href="#" class="dropdown-link" data-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg></a>
                <div class="dropdown-menu dropdown-menu-right">
                  <a href="uploads/${data[i].file_name}" class="dropdown-item download"><i data-feather="download"></i>Download</a>
                </div>
              </div><!-- dropdown -->`
                if (data[i].file_type == 'docx' || data[i].file_type == 'doc') {
                  appdata += `<div class="card-file-thumb tx-primary">
                <i class="far fa-file-word"></i>
              </div>`
                } else if (data[i].file_type == 'ppt') {
                  appdata += `<div class="card-file-thumb tx-orange">
                <i class="far fa-file-powerpoint"></i>
              </div>`
                } else if (data[i].file_type == 'pdf') {
                  appdata += `<div class="card-file-thumb tx-danger">
                <i class="far fa-file-pdf"></i>
              </div>`
                } else if (data[i].file_type == 'png' || data[i].file_type == 'jpg' || data[i].file_type == 'jpeg') {
                  appdata += `<div class="card-file-thumb tx-indigo">
                <i class="far fa-file-image"></i>
              </div>`
                } else if (data[i].file_type == 'xlsx' || data[i].file_type == 'xls' || data[i].file_type == 'csv') {
                  appdata += `<div class="card-file-thumb tx-success">
                <i class="far fa-file-excel"></i>
              </div>`
                } else {
                  appdata += `<div class="card-file-thumb tx-gray-600">
                <i class="far fa-file-alt"></i>
              </div>`
                }

                appdata += ` <div class="card-body">
                <a href="uploads/${data[i].file_name}" class="link-02 file-name"><h6>${data[i].file_name.substr(0, 50)}</h6></a>
              </div>
            </div>
          </div>`;
                
              }

              $('#file_list').html(appdata);


            } else {
              alert('Error')
            }

          },
          error(a, b, c) {
            console.log(b)
          }
        });
      }

      function getFilesByType(file_type) {
        $.ajax({
          type: "POST",
          url: "api/files.php",
          dataType: "json",
          data: {
            file_type: file_type
          },
          success: function(response) {

            if (response.type == "successful") {

              var data = response.data
              var appdata = "";

              for (var i = 0; i < data.length; i++) {
                appdata += `<div class="col-xs-6 col-sm-4 col-md-2 mg-t-15">
                <div class="card card-file">
                  <div class="dropdown-file">
                  <a href="#" class="dropdown-link" data-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg></a>
                    <div class="dropdown-menu dropdown-menu-right">
                      <a href="uploads/${data[i].file_name}" class="dropdown-item download"><i data-feather="download"></i>Download</a>
                    </div>
                  </div><!-- dropdown -->`
                if (data[i].file_type == 'docx') {
                  appdata += `<div class="card-file-thumb tx-primary">
                    <i class="far fa-file-word"></i>
                  </div>`
                } else if (data[i].file_type == 'ppt') {
                  appdata += `<div class="card-file-thumb tx-orange">
                <i class="far fa-file-powerpoint"></i>
              </div>`
                } else if (data[i].file_type == 'pdf') {
                  appdata += `<div class="card-file-thumb tx-danger">
                <i class="far fa-file-pdf"></i>
              </div>`
                } else if (data[i].file_type == 'png' || data[i].file_type == 'jpg' || data[i].file_type == 'jpeg') {
                  appdata += `<div class="card-file-thumb tx-indigo">
                <i class="far fa-file-image"></i>
              </div>`
                } else if (data[i].file_type == 'xlsx' || data[i].file_type == 'xls' || data[i].file_type == 'csv') {
                  appdata += `<div class="card-file-thumb tx-success">
                <i class="far fa-file-excel"></i>
              </div>`
                } else {
                  appdata += `<div class="card-file-thumb tx-gray-600">
                <i class="far fa-file-alt"></i>
              </div>`
                }

                appdata += ` <div class="card-body">
                <a href="uploads/${data[i].file_name}" class="link-02 file-name"><h6>${data[i].file_name.substr(0, 50)}</h6></a>
              </div>
            </div>
          </div>`;
                
              }

              $('#file_list').html(appdata);

            } else {
              alert('Error')
            }

          },
          error(a, b, c) {
            console.log(b)
          }
        });
      }
    });
  </script>
</body>

</html>