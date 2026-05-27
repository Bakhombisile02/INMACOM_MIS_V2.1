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
  <link href="./assets/css/Control.FullScreen.css" rel="stylesheet">

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
        
            <ul class="nav nav-line" id="myTab5" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="flow" data-toggle="tab" href="#" role="tab" aria-controls="home" aria-selected="true">MAP</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="levels" data-toggle="tab" href="#" role="tab" aria-controls="profile" aria-selected="false">CHART</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="quality" data-toggle="tab" href="#" role="tab" aria-controls="contact" aria-selected="false">STOCHASTIC</a>
              </li>
            </ul>

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
  <script src="./data/Rivers.js"></script>
  <!-- append theme customizer -->
  <script src="./lib/leaflet/leaflet.js"></script>
  <script src="./assets/js/Control.FullScreen.js"></script>
  <script>
    $(function() {
      'use strict';

      var mymap2 = L.map('leaflet2', {
        fullscreenControl: true,
        fullscreenControlOptions: { // optional
          title: "Show me the fullscreen !",
          titleCancel: "Exit fullscreen mode"
        }
      }).setView([-26.0012, 31.4657], 7);

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
          fillColor: 'tan',
          fillOpacity: 1,
          color: 'gray'
        }
      }).addTo(mymap2);

      /*Legend specific*/
      var legend = L.control({
        position: "bottomright"
      });

      legend.onAdd = function(mymap2) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>Map Legend</h4>";
        div.innerHTML += '<i style="background: #8d0801"></i><span>High</span><br>';
        div.innerHTML += '<i style="background: #bf0603"></i><span>Above Normal</span><br>';
        div.innerHTML += '<i style="background: #7cb518"></i><span>Normal</span><br>';
        div.innerHTML += '<i style="background: #8cb369"></i><span>Below Normal</span><br>';
        div.innerHTML += '<i style="background: #c38e70"></i><span>Low</span><br>';
        div.innerHTML += '<i style="background: #edc4b3"></i><span>Very Low</span><br>';
        return div;
      };

      legend.addTo(mymap2);

      $.ajax({
        type: "POST",
        url: 'api/get-guaging-stations.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.data;

            for (var i = 0; i < data.length; i++) {
              var station = L.circle([data[i].latitude, data[i].longitude], {
                radius: 8000,
                fillColor: "#8d0801",
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