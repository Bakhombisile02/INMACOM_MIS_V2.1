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

  <div class="content content-fixed content-auth-alt">
    <div class="container d-flex justify-content-center ht-100p">
      <div class="mx-wd-300 wd-sm-450 ht-100p d-flex flex-column align-items-center justify-content-center">
        <div class="wd-80p wd-sm-300 mg-b-15"><img src="./assets/img/img18.png" class="img-fluid" alt=""></div>
        <h4 class="tx-20 tx-sm-24">Reset your password</h4>
        <p class="tx-color-03 mg-b-30 tx-center">Enter your username or email address and we will send you a link to reset your password.</p>
        <form action="#" method="POST" id="reset_form">
        <div class="wd-100p d-flex flex-column flex-sm-row mg-b-40">
          <input type="text" class="form-control wd-sm-250 flex-fill" name="email" placeholder="Enter username or email address">
          <button class="btn btn-brand-02 mg-sm-l-10 mg-t-10 mg-sm-t-0" id="submit" type="submit">Reset Password</button>
        </div>
        </form>
        <!-- <span class="tx-12 tx-color-03">Key business concept vector is created by <a href="https://www.freepik.com/free-photos-vectors/business">freepik (freepik.com)</a></span> -->

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

    $('#reset_form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        url: 'api/reset-password.php',
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
            window.location.replace('login.php')
          } else if (type == "failed") {
            alert('Failed to reset password')
            console.log(text);
          }

        }
      });
    })
  </script>
</body>

</html>