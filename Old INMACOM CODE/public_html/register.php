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
  <meta name="twitter:title" content="INMACOM">
  <meta name="twitter:description" content="INMACOM MIS">
  <meta name="twitter:image" content="dashforge/img/dashforge-social.php">

  <!-- Facebook -->
  <meta property="og:url" content="http://Datamatics.me/dashforge">
  <meta property="og:title" content="INMACOM">
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
      <div class="media align-items-stretch justify-content-center ht-100p">
        <div class="sign-wrapper mg-lg-r-50 mg-xl-r-60">
          <div class="pd-t-20 wd-100p">
            <h4 class="tx-color-01 mg-b-5">Create New Account</h4>
            <p class="tx-color-03 tx-16 mg-b-40">It's free to signup and only takes a minute.</p>
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
              <div class="form-group tx-12">
                By clicking <strong>Create an account</strong> below, you agree to our terms of service and privacy statement.
              </div><!-- form-group -->

              <button type="submit" name="submit" class="btn btn-brand-02 btn-block">Create Account</button>
            </form>
            <!-- <div class="divider-text">or</div>
              <button class="btn btn-outline-facebook btn-block">Sign Up With Facebook</button>
              <button class="btn btn-outline-twitter btn-block">Sign Up With Twitter</button> -->
            <div class="tx-13 mg-t-20 tx-center">Already have an account? <a href="login.php">Sign In</a></div>
          </div>
        </div><!-- sign-wrapper -->
        <div class="media-body pd-y-30 pd-lg-x-50 pd-xl-x-60 align-items-center d-none d-lg-flex pos-relative">
          <div class="mx-lg-wd-500 mx-xl-wd-550">
            <img src="./assets/img/img16.png" class="img-fluid" alt="">
          </div>
          <div class="pos-absolute b-0 r-0 tx-12">
            Social media marketing vector is created by <a href="https://www.freepik.com/pikisuperstar" target="_blank">pikisuperstar (freepik.com)</a>
          </div>
        </div><!-- media-body -->
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

    $('#registration_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: 'api/register.php',
        data: $(this).serialize(),
        dataType: "json",
        success: function(response) {
          var text = response.text;
          var type = response.type;

          if (type == "success") {
            window.location.replace('login.php')
          } else if (type == "failed") {
            alert('Failed to create account')
            console.log(text);
          }

        }
      });
    })
  </script>
</body>

</html>