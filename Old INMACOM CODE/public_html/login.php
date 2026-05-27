<!DOCTYPE html>
<html lang="en">


<head>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Twitter -->
  <meta name="twitter:site" content="@Datamatics">
  <meta name="twitter:creator" content="@Datamatics">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Datamatics">
  <meta name="twitter:description" content="INMACOM MIS">
  <meta name="twitter:image" content="dashforge/img/dashforge-social.php">

  <!-- Facebook -->
  <meta property="og:url" content="http://datamatics.co.sz">
  <meta property="og:title" content="Datamatics">
  <meta property="og:description" content="INMACOM MIS">


  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">

  <!-- Meta -->
  <meta name="description" content="INMACOM MIS">
  <meta name="author" content="Datamatics">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="./assets/img/favicon.png">

  <title>INMACOM MIS</title>

  <!-- vendor css -->
  <link href="./lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="./lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <!-- DashForge CSS -->
  <link rel="stylesheet" href="./assets/css/dashforge.css">
  <link rel="stylesheet" href="./assets/css/dashforge.auth.css">
</head>

<body>
  <?php require_once 'includes/header.php'; ?>
  <!-- navbar -->

  <div class="content content-fixed content-auth">
    <div class="container">
      <div class="media align-items-stretch justify-content-center ht-100p pos-relative">
        <div class="media-body align-items-center d-none d-lg-flex">
          <div class="mx-wd-600">
            <img src="./assets/img/img15.png" class="img-fluid" alt="">
          </div>
          <div class="pos-absolute b-0 l-0 tx-12 tx-center">
            Workspace design vector is created by <a href="https://www.freepik.com/pikisuperstar" target="_blank">pikisuperstar (freepik.com)</a>
          </div>
        </div><!-- media-body -->
        <div class="sign-wrapper mg-lg-l-50 mg-xl-l-60">
          <div class="wd-100p">
            <h3 class="tx-color-01 mg-b-5">Sign In</h3>
            <p class="tx-color-03 tx-16 mg-b-40">Welcome back! Please signin to continue.</p>
            <form action="#" method="post" id="login_form">
              <div class="form-group">
                <label>Email address</label>
                <input type="email" name="email" class="form-control" placeholder="yourname@yourmail.com">
              </div>
              <div class="form-group">
                <div class="d-flex justify-content-between mg-b-5">
                  <label class="mg-b-0-f">Password</label>
                  <a href="forgot.php" class="tx-13">Forgot password?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password">
              </div>
              <button type="submit" name="submit" id="submit" class="btn btn-brand-02 btn-block">Sign In</button>
            </form>
          </div>
        </div><!-- sign-wrapper -->
      </div><!-- media -->
    </div><!-- container -->
  </div><!-- content -->

  <?php require_once 'includes/footer.php'; ?>

  <script src="./lib/jquery/jquery.min.js"></script>
  <script src="./lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./lib/feather-icons/feather.min.js"></script>
  <script src="./lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>

  <script src="./assets/js/dashforge.js"></script>

  <!-- append theme customizer -->
  <script src="./lib/js-cookie/js.cookie.js"></script>
  <script src="./assets/js/dashforge.settings.js"></script>
  <script>
    $(function() {
      'use script'

      window.darkMode = function() {
        $('.btn-white').addClass('btn-dark').removeClass('btn-white');
      }

      window.lightMode = function() {
        $('.btn-dark').addClass('btn-white').removeClass('btn-dark');
      }

      var hasMode = Cookies.get('df-mode');
      if (hasMode === 'dark') {
        darkMode();
      } else {
        lightMode();
      }
    })

    $('#login_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: 'api/login.php',
        data: $(this).serialize(),
        dataType: "json",
        beforeSend: function() {
          var loading = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span> Please wait... </span>'
          $('#submit').html(loading)
          $('#submit').prop('disabled', true)
        },
        success: function(response) {
          var type = response.type;

          if (type == "success") {
            var role = response.role
            if (role == 'Admin') {
              window.location.replace('./admin/');
            } else if (role == 'Data Manager') {
              window.location.replace('./data-manager/');
            }

          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        },
        complete: function() {
          var loading = '<span class="done" ><em class="icon ni ni-check"></em> Sign In </span>';
          $('#submit').html(loading)
          $('#submit').prop('disabled', false)
        },
      });
    })
  </script>
</body>

</html>