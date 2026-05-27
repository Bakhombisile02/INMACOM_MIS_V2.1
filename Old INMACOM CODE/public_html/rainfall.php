<?php session_start(); ?>
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
  <link rel="shortcut icon" type="image/x-icon" href="./assets/img/favicon.png">

  <title>Maputo Watercourse Commission (INMACOM)</title>

  <!-- vendor css -->
  <link href="./lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="./lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="./lib/cryptofont/css/cryptofont.min.css" rel="stylesheet">
  <link href="./lib/leaflet/leaflet.css" rel="stylesheet">

  <!-- INMACOM CSS -->
  <link rel="stylesheet" href="./assets/css/dashforge.css">
  <link rel="stylesheet" href="./assets/css/dashforge.dashboard.css">
</head>

<body class="page-profile">
  <?php require_once 'includes/header.php'; ?>
  <!-- navbar -->
  <figure class="pos-relative">
  <img src="./assets/img/MagnificentVibrantDowitcher-max-1mb.gif" alt="" width="100%" height="200">
    <figcaption class="pos-absolute a-0 pd-25 tx-white-8">
      <h1 class="mg-t-80 text-white text-center"><span class="text-danger d-none">INMACOM</span> MANAGEMENT INFORMATION SYSTEM</h1>
    </figcaption>
  </figure>
  <div class="col-12">
    <div class="row row-xs">
      <div class="col-lg-4 mg-t-10 mg-b-20">
        <div class="card">
          <div class="card-header">
            <h6 class="mg-b-0">Stations</h6>
          </div><!-- card-header -->
          <div class="card-body pd-10">
            <div id="scroll3" class="scrollbar-lg pos-relative ht-400">
              <table class="table table-borderless tx-13 mg-b-0">
                <thead>
                  <tr class="tx-uppercase tx-10 tx-spacing-1 tx-semibold tx-color-03">
                    <th>Station</th>
                    <th>Date</th>
                    <th class="text-right">Value</th>
                  </tr>
                </thead>
                <tbody id="stations"></tbody>
              </table>
            </div><!-- table-responsive -->
          </div><!-- card-body -->
        </div><!-- card -->
      </div><!-- col -->
      <div class="col-lg-8 mg-t-10 mg-b-20">
        <div class="card card-crypto">
          <div class="card-header pd-y-8 d-sm-flex align-items-center justify-content-between">
            <nav class="nav nav-line">
              <a href="#" class="nav-link active">Flow Rate</a>
              <a href="#" class="nav-link">DAM LEVELS</a>
              <a href="#" class="nav-link">WATER QUALITY</a>
              <a href="#" class="nav-link">DROUGHT</a>
              <a href="#" class="nav-link">RAINFALL</a>
            </nav>

          </div><!-- card-header -->
          <div class="card-body pd-10">
            <div id="leaflet2" class="ht-400"></div>
          </div><!-- card-body -->

        </div><!-- card -->
      </div><!-- col -->

    </div><!-- row -->
  </div>



  <?php require_once 'includes/footer.php'; ?>

  <script src="./lib/jquery/jquery.min.js"></script>
  <script src="./lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./lib/feather-icons/feather.min.js"></script>
  <script src="./lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <!-- <script src="./lib/jquery.flot/jquery.flot.js"></script>
  <script src="./lib/jquery.flot/jquery.flot.stack.js"></script>
  <script src="./lib/jquery.flot/jquery.flot.resize.js"></script> -->
  <script src="./assets/js/dashforge.js"></script>
  <script src="./data/sub-basins.js"></script>

  <!-- append theme customizer -->
  <script src="./lib/leaflet/leaflet.js"></script>
  <script>
    $(function() {
      'use strict';

      // Adding a Popup
      var mymap2 = L.map('leaflet2').setView([-26.5212, 31.4657], 7);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        minZoom: 2,
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
        id: 'mapbox.streets'
      }).addTo(mymap2);

      var subbasin = L.geoJSON(subbasins, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>' + feature.properties.Basin + ' Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'gray'
        }
      }).addTo(mymap2);

      $.ajax({
        type: "POST",
        url: 'api/get-guaging-stations.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.data;
            console.log(response);
            for (var i = 0; i < data.length; i++) {
              var station = L.circle([data[i].latitude, data[i].longitude], {
                radius: 4000,
                fillColor: "#325a98",
                color: "#000",
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
              }).addTo(mymap2);

              var popupContent = `<h5 class = "text-primary text-center">${data[i].name}</h5>
                <div class="container"><table class="table table-hover">
                <tbody><tr><td> Station Name: </td><td>( ${data[i].code} ) ${data[i].name}</td></tr>
                <tr><td>Station Code: </td><td>${data[i].code}</td></tr>
                <tr><td> Latitude: </td><td>${data[i].latitude}</td></tr>
                <tr><td> Longitude: </td><td>${data[i].longitude}</td></tr>
                <tr><td> Level: </td><td>10 m^3/s</td></tr>`;
              station.bindPopup(popupContent)

              rows += `
              <tr>
                <td>( ${data[i].code} ) ${data[i].name}</td>
                <td>2023-01-16</td>
                <td>20 m^/3s</td>
              </tr>`;
            }
            $('#stations').html(rows);
          } else if (type == "failed") {
            alert('Failed to get data')
            console.log(text);
          }

        }
      });


      const scroll3 = new PerfectScrollbar('#scroll3', {
        suppressScrollX: true
      });

    });
  </script>
</body>

</html>