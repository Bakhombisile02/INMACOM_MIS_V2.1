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
  <meta name="twitter:title" content="INMACOM">

  <!-- Facebook -->
  <meta property="og:title" content="INMACOM">
  <meta property="og:description" content="INMACOM IMS">

  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">

  <!-- Meta -->
  <meta name="description" content="INMACOM">
  <meta name="author" content="Datamatics">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

  <title>INMACOM</title>

  <!-- vendor css -->
  <link href="../lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <!-- DashForge CSS -->
  <link rel="stylesheet" href="../assets/css/dashforge.css">
  <link rel="stylesheet" href="../assets/css/dashforge.filemgr.css">
</head>
<?php include_once 'includes/auth_session.php'; ?>

<body class="app-filemgr">

  <?php include_once 'includes/sidebar.php'; ?>

  <div class="content ht-100v pd-0">
    <div class="content-header">
      <div class="content-search">
        <i data-feather="search"></i>
        <input type="search" class="form-control" placeholder="Search files">
      </div>
      <nav class="nav">
        <a href="#" class="nav-link"><i data-feather="help-circle"></i></a>
        <a href="#" class="nav-link"><i data-feather="grid"></i></a>
        <a href="#" class="nav-link"><i data-feather="align-left"></i></a>
      </nav>
    </div><!-- content-header -->

    <div class="content-body pd-0">
      <div class="filemgr-wrapper filemgr-wrapper-two">
        <div class="filemgr-sidebar">
          <div class="filemgr-sidebar-header">
            <button class="btn btn-xs btn-white" data-toggle="modal" data-target="#folder_form">New Folder</button>
            <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#upload_form">New File</button>
          </div><!-- filemgr-sidebar-header -->
          <div class="filemgr-sidebar-body">
            <div class="pd-t-20 pd-b-10 pd-x-10">
              <label class="tx-sans tx-uppercase tx-medium tx-10 tx-spacing-1 tx-color-03 pd-l-10">My Drive</label>
              <nav class="nav nav-sidebar tx-13" id="folder_list">
                <a href="#" id="All" class="nav-link active folder"><i data-feather="folder"></i> <span>All Files</span></a>
              </nav>
            </div>
            <div class="pd-10">
              <label class="tx-sans tx-uppercase tx-medium tx-10 tx-spacing-1 tx-color-03 pd-l-10">File Library</label>
              <nav class="nav nav-sidebar tx-13">
                <a href="#" class="nav-link file-library" data-type="document"><i data-feather="file"></i> <span>Documents</span></a>
                <a href="#" class="nav-link file-library" data-type="image"><i data-feather="image"></i> <span>Images</span></a>
                <a href="#" class="nav-link file-library" data-type="video"><i data-feather="video"></i> <span>Videos</span></a>
                <a href="#" class="nav-link file-library" data-type="audio"><i data-feather="music"></i> <span>Audio</span></a>
                <a href="#" class="nav-link file-library" data-type="zip"><i data-feather="package"></i> <span>Zip Files</span></a>
              </nav>
            </div>

          </div><!-- filemgr-sidebar-body -->
        </div><!-- filemgr-sidebar -->

        <div class="filemgr-content">
          <div class="filemgr-content-header">
            <h4 class="mg-b-0">All Files</h4>
            <nav class="nav d-none d-sm-flex mg-l-auto">
              <a href="#" class="nav-link"><i data-feather="list"></i></a>
              <a href="#" class="nav-link"><i data-feather="alert-circle"></i></a>
              <a href="#" class="nav-link"><i data-feather="settings"></i></a>
            </nav>
          </div><!-- filemgr-content-header -->
          <div class="filemgr-content-body">
            <div class="pd-20 pd-lg-25 pd-xl-30">
              <label class="d-block tx-medium tx-10 tx-uppercase tx-sans tx-spacing-1 tx-color-03">Files</label>
              <div class="row row-xs" id="file_list"></div><!-- row -->
            </div>
          </div><!-- filemgr-content-body -->
        </div><!-- filemgr-content -->

      </div><!-- filemgr-wrapper -->
    </div>
  </div><!-- content -->
  <div class="modal fade effect-scale" id="modalViewDetails" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body pd-20 pd-sm-30">
          <button type="button" class="close pos-absolute t-15 r-20" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>

          <h5 class="tx-18 tx-sm-20 mg-b-30">View Details</h5>

          <div class="row mg-b-10">
            <div class="col-4">Filename:</div>
            <div class="col-8">Medical Certificate.pdf</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">File ype:</div>
            <div class="col-8">PDF File</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">File Size:</div>
            <div class="col-8">10.45 KB</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">Created:</div>
            <div class="col-8">Monday, July 02, 2018 9:34am</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">Modified:</div>
            <div class="col-8">Monday, July 02, 2018 9:34am</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">Accessed:</div>
            <div class="col-8">Monday, July 02, 2018 9:34am</div>
          </div><!-- row -->
          <div class="row mg-b-10">
            <div class="col-4">Description:</div>
            <div class="col-8">
              <textarea class="form-control mg-t-5" rows="2" placeholder="Add description"></textarea>
            </div>
          </div><!-- row -->

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary mg-sm-l-5" data-dismiss="modal">Close</button>
        </div><!-- modal-footer -->
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade effect-scale" id="modalShare" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body pd-20 pd-sm-30">
          <button type="button" class="close pos-absolute t-15 r-20" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>

          <h5 class="tx-18 tx-sm-20 mg-b-30">Share With Others</h5>

          <div class="mg-t-20">
            <label class="d-block">Other users:</label>
            <input class="form-control" placeholder="Enter names or email addresses">
            <div class="dropdown mg-t-10">
              Rights: <a href="#" class="dropdown-link" data-toggle="dropdown">Allow editing <i class="icon ion-chevron-down tx-12"></i></a>
            </div><!-- dropdown -->
            <hr>
            <label class="d-block">More:</label>
            <nav class="nav nav-social">
              <a href="#" class="nav-link"><i data-feather="facebook"></i></a>
              <a href="#" class="nav-link"><i data-feather="twitter"></i></a>
            </nav>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary">Share</button>
        </div><!-- modal-footer -->
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade effect-scale" id="modalCopy" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body pd-20 pd-sm-30">
          <button type="button" class="close pos-absolute t-15 r-20" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>

          <h5 class="tx-18 tx-sm-20 mg-b-0">Copy Item to</h5>
          <p class="mg-b-25 tx-color-03">Please select a folder</p>

          <div class="bd bg-ui-01 pd-10">
            <ul class="nav nav-sidebar flex-column tx-13">
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Downloads</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Personal Stuff</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> 3d Objects</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Recordings</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Support</a></li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary mg-sm-r-5" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Copy</button>
        </div><!-- modal-footer -->
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade effect-scale" id="modalMove" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body pd-20 pd-sm-30">
          <button type="button" class="close pos-absolute t-15 r-20" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>

          <h5 class="tx-18 tx-sm-20 mg-b-0">Move Item to</h5>
          <p class="mg-b-25 tx-color-03">Please select a folder</p>

          <div class="bd bg-ui-01 pd-10">
            <ul class="nav nav-sidebar flex-column tx-13">
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Downloads</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Personal Stuff</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> 3d Objects</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Recordings</a></li>
              <li class="nav-item"><a href="#" class="nav-link"><i data-feather="folder"></i> Support</a></li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary mg-sm-r-5" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary">Move</button>
        </div><!-- modal-footer -->
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->

  <div class="modal fade" id="upload_form" tabindex="-1" role="dialog" aria-labelledby="importModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title" id="importModal">Upload File</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="post" id="uploading_form" class="form-validate is-alter" enctype="multipart/form-data">
            <div class="form-group">
              <label for="category" class="d-block">Select Folder</label>
              <select class="form-control custom-select" id="category" name="category"></select>
            </div>
            <div class="custom-file">
              <input type="file" name="file" class="custom-file-input" id="file">
              <label class="custom-file-label" for="customFile">Choose file</label>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
          <button type="submit" name="submit" id="upload_file" class="btn btn-primary tx-13">Upload</button>
        </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="folder_form" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content tx-14">
        <div class="modal-header">
          <h6 class="modal-title">Create Folder</h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="post" id="create_folder" class="form-validate is-alter" enctype="multipart/form-data">
            <div class="form-group">
              <label for="category" class="d-block">Folder Name</label>
              <input type="text" name="folder" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger tx-13" data-dismiss="modal">Cancel</button>
          <button type="submit" name="submit" id="upload_file" class="btn btn-primary tx-13">Upload</button>
        </div>
        </form>
      </div>
    </div>
  </div>

  <div class="pos-fixed b-10 r-10">
    <div id="toast" class="toast bg-dark bd-0 wd-300" data-delay="3000" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-transparent bd-white-1">
        <h6 class="tx-white mg-b-0 mg-r-auto">Downloading</h6>
        <button type="button" class="ml-2 mb-1 close tx-normal tx-shadow-none" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body pd-10 tx-white">
        <h6 class="mg-b-0">Medical_Certificate.pdf</h6>
        <span class="tx-color-03 tx-11">1.2mb of 4.5mb</span>
        <div class="progress ht-5 mg-t-5">
          <div class="progress-bar wd-50p" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </div><!-- toast -->
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../lib/feather-icons/feather.min.js"></script>
  <script src="../lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>

  <script src="../assets/js/dashforge.js"></script>
  <script src="../assets/js/dashforge.aside.js"></script>
  <script src="../assets/js/dashforge.filemgr.js"></script>


  <script>
    'use strict'

    $(document).ready(function(e) {

      var folder = 'All';
      var file_type ='All';

      getFolders();
      getFilesByFolder(folder, file_type);

      $("#create_folder").on('submit', (function(e) {
        e.preventDefault();

        $.ajax({
          url: "../api/folder.php",
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

      $("#uploading_form").on('submit', (function(e) {
        e.preventDefault();

        $.ajax({
          url: "../api/upload_document.php",
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

              $("#uploading_form")[0].reset();
              $('#upload_form').modal('hide');
              getFilesByFolder(folder, file_type);
            }
          },
          error: function(e) {
            alert('Error occurred')
          }
        });
      }));

      $(document).on('click', '.folder', function(e){
        e.preventDefault();
        var folder = $(this).attr('id');
        getFilesByFolder(folder);
      });

      $(document).on('click', '.delete', function(e){
        e.preventDefault();
        var id = $(this).attr('id');
        $.ajax({
          type: "POST",
          url: "../api/files.php",
          dataType: "json",
          data: {
            id: id,
            delete: 'file'
          },
          success: function(response) {

            if (response.type == "successful") {
              getFilesByFolder(folder, file_type);
            } else {
              alert('Error')
            }

          },
          error(a, b, c) {
            console.log(b)
          }
        });
      });

      $(document).on('click', '.file-library', function(e){
        e.preventDefault();
        var file_type = $(this).data('type');
        getFilesByType(file_type);
      })

      function getFolders() {
        $.ajax({

          type: "POST",
          url: "../api/folder.php",
          dataType: "json",
          data: {
            getdata: 'folders'
          },
          success: function(response) {

            if (response.type == "successful") {

              var data = response.data
              var appdata = "";
              var folders = "";

              for (var i = 0; i < data.length; i++) {
                appdata += '<a href="#" id="'+data[i].name+'" class="nav-link folder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg> <span>'+ data[i].name +'</span></a>'
                folders += '<option value="' + data[i].name + '">'+ data[i].name +'</option>';
              }

              $('#folder_list').append(appdata);
              $('#category').html(folders);

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
          url: "../api/files.php",
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
                        <a href="#" class="dropdown-item important"><i data-feather="star"></i>Mark as Important</a>
                        <a href="#modalShare" data-toggle="modal" class="dropdown-item share"><i data-feather="share"></i>Share</a>
                        <a href="../uploads/${data[i].file_name}" class="dropdown-item download"><i data-feather="download"></i>Download</a>
                        <a id="${data[i].id}" class="dropdown-item delete"><i data-feather="trash"></i>Delete</a>
                      </div>
                    </div><!-- dropdown -->`

                      if(data[i].file_type =='docx' || data[i].file_type =='doc'){
                        appdata += `<div class="card-file-thumb tx-primary">
                      <i class="far fa-file-word"></i>
                    </div>`
                      } 
                      else if(data[i].file_type =='ppt'){
                        appdata += `<div class="card-file-thumb tx-orange">
                      <i class="far fa-file-powerpoint"></i>
                    </div>`
                      }
                      else if(data[i].file_type =='pdf'){
                        appdata += `<div class="card-file-thumb tx-danger">
                      <i class="far fa-file-pdf"></i>
                    </div>`
                      }
                      else if(data[i].file_type =='png' || data[i].file_type =='jpg' || data[i].file_type =='jpeg'){
                        appdata += `<div class="card-file-thumb tx-indigo">
                      <i class="far fa-file-image"></i>
                    </div>`
                      } else if(data[i].file_type =='xlsx' || data[i].file_type =='xls' || data[i].file_type =='csv'){
                        appdata += `<div class="card-file-thumb tx-success">
                      <i class="far fa-file-excel"></i>
                    </div>`
                      }
                      else {
                        appdata += `<div class="card-file-thumb tx-gray-600">
                      <i class="far fa-file-alt"></i>
                    </div>`
                      }
                      
                      appdata += ` <div class="card-body">
                      <a href="uploads/${data[i].file_name}" class="link-02 file-name"><h6>${data[i].file_name.substr(0, 50) }</h6></a>
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
          url: "../api/files.php",
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
                        <a href="#" class="dropdown-item important"><i data-feather="star"></i>Mark as Important</a>
                        <a href="#modalShare" data-toggle="modal" class="dropdown-item share"><i data-feather="share"></i>Share</a>
                        <a href="../uploads/${data[i].file_name}" class="dropdown-item download"><i data-feather="download"></i>Download</a>
                        <a id="${data[i].id}" class="dropdown-item delete"><i data-feather="trash"></i>Delete</a>
                      </div>
                    </div><!-- dropdown -->`
                      if(data[i].file_type =='docx'){
                        appdata += `<div class="card-file-thumb tx-primary">
                      <i class="far fa-file-word"></i>
                    </div>`
                      } 
                      else if(data[i].file_type =='ppt'){
                        appdata += `<div class="card-file-thumb tx-orange">
                      <i class="far fa-file-powerpoint"></i>
                    </div>`
                      }
                      else if(data[i].file_type =='pdf'){
                        appdata += `<<div class="card-file-thumb tx-danger">
                      <i class="far fa-file-pdf"></i>
                    </div>`
                      }
                      else if(data[i].file_type =='png' || data[i].file_type =='jpg' || data[i].file_type =='jpeg'){
                        appdata += `<div class="card-file-thumb tx-indigo">
                      <i class="far fa-file-image"></i>
                    </div>`
                      } else if(data[i].file_type =='xlsx' || data[i].file_type =='xls' || data[i].file_type =='csv'){
                        appdata += `<div class="card-file-thumb tx-success">
                      <i class="far fa-file-excel"></i>
                    </div>`
                      }
                      else {
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