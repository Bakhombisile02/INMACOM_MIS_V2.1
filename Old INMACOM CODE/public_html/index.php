<?php session_start(); ?>
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
  <meta name="twitter:image" content="../../dashforge/img/dashforge-social.html">

  <!-- Facebook -->
  <meta property="og:url" content="https://datamatics.co.za">
  <meta property="og:title" content="DashForge">
  <meta property="og:description" content="INMACOM">

  <meta property="og:image" content="./dashforge/img/dashforge-social.html">
  <meta property="og:image:secure_url" content="./dashforge/img/dashforge-social.html">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">

  <!-- Meta -->
  <meta name="description" content="INMACOM">
  <meta name="author" content="Datamatics">

  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">

  <title>INMACOM MIS</title>

  <!-- vendor css -->
  <link href="lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="lib/ionicons/css/ionicons.min.css" rel="stylesheet">

  <!-- DashForge CSS -->
  <link rel="stylesheet" href="assets/css/dashforge.css">
  <link rel="stylesheet" href="assets/css/dashforge.landing.css">

</head>

<body class="page-profile">
  <?php include 'includes/header.php'; ?>

  <div class="home-slider">
    <div class="home-lead">
      <p class="home-text">Welcome to INMACOM MIS</p>

      <h6 class="home-headline">The Management Information System (MIS) is an online web application developed by INMACOM for the purpose of data sharing</span> between member of states.</h6>

      <div class="d-flex tx-20 mg-t-40">
        <div class="img-group">
          <img src="./assets/img/Flag_of_South_Africa.svg.png" class="img wd-60 ht-60 rounded-circle" alt="">
          <img src="./assets/img/Flag_of_Eswatini.svg.png" class="img wd-60 ht-60 rounded-circle" alt="">
          <img src="./assets/img/Flag_of_Mozambique.svg.png" class="img wd-60 ht-60 rounded-circle" alt="">
        </div>
      </div>
      <div class="d-flex tx-20 mg-t-40">
        <p class="tx-sans tx-uppercase tx-10 tx-medium tx-spacing-1 tx-color-03"></p>
        <p class="tx-12">
          <span class="text-danger tx-uppercase tx-12 tx-medium">Desclaimer:</span> INMACOM is maintaining this system as a public service. However, INMACOM does not assume any legal responsibility for the accuracy or completeness of the information contained on this site. Persons using information from this site for official purposes, or other purposes,
          for which accuracy and completeness are required, are hereby notified that they should first verify the information with the public records or other primary sources from which the information was obtained.
          <br><span class="text-danger">By using this site, you agree to have read and accepted our Conditions of Use and Privacy Policy.</span>
        </p>
      </div>

    </div>
    <div class="home-slider-img">
      <div><img src="./assets/img/home-1.png" alt=""></div>
      <div><img src="./assets/img/home-2.png" alt=""></div>
      <div><img src="./assets/img/home-2-dark.png" alt=""></div>
    </div>
    <div class="home-slider-bg-one"></div>
  </div><!-- home-slider -->

  <script src="./lib/jquery/jquery.min.js"></script>
  <script src="./lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./lib/feather-icons/feather.min.js"></script>
  <script src="./lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="assets/js/dashforge.js"></script>
  <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
  <script>
    function googleTranslateElementInit() {
      new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages:'en,pt',
      }, 'google_translate_element');
    }
  </script>
</body>

</html>