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
<?php require_once 'include/header.php'; ?>
  <!-- navbar -->

    <div class="content content-fixed content-auth-alt">
      <div class="container ht-100p">
        <div class="ht-100p d-flex flex-column align-items-center justify-content-center">
          <div class="wd-150 wd-sm-250 mg-b-30"><img src="./assets/img/img17.png" class="img-fluid" alt=""></div>
          <h4 class="tx-20 tx-sm-24">Verify your email address</h4>
          <p class="tx-color-03 mg-b-40">Please check your email and click the verify button or link to verify your account.</p>
          <div class="tx-13 tx-lg-14 mg-b-40">
            <a href="#" class="btn btn-brand-02 d-inline-flex align-items-center">Resend Verification</a>
            <a href="#" class="btn btn-white d-inline-flex align-items-center mg-l-5">Contact Support</a>
          </div>
          <span class="tx-12 tx-color-03">Mailbox with envelope vector is created by <a href="https://www.freepik.com/free-photos-vectors/background">freepik (freepik.com)</a></span>
        </div>
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
      $(function(){
        'use script'

        window.darkMode = function(){
          $('.btn-white').addClass('btn-dark').removeClass('btn-white');
        }

        window.lightMode = function() {
          $('.btn-dark').addClass('btn-white').removeClass('btn-dark');
        }

        var hasMode = Cookies.get('df-mode');
        if(hasMode === 'dark') {
          darkMode();
        } else {
          lightMode();
        }
      })
    </script>
  </body>

</html>
