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
    <figure class="pos-relative">
      <img src="./assets/img/MagnificentVibrantDowitcher-max-1mb.gif" alt="" width="100%" height="200">
      <figcaption class="pos-absolute a-0 pd-25 tx-white-8">
        <h1 class="mg-t-80 text-white text-center"><span class="text-danger d-none">INMACOM</span> MANAGEMENT INFORMATION SYSTEM</h1>
      </figcaption>
    </figure>
    <figcaption class="pos-absolute a-0 pd-25 tx-white-8">
      <h1 class="mg-t-80 text-white text-center"><span class="text-danger d-none">INMACOM</span> MANAGEMENT INFORMATION SYSTEM</h1>
    </figcaption>
  </figure>

  <div class="col-12">
    <div class="row row-xs">
      <div class="col-lg-4 mg-t-10 mg-b-20">
        <div class="card">
          <div class="card-header pd-y-14 d-sm-flex align-items-center justify-content-between">
            <h6 class="mg-b-0">Stations</h6>
            <!-- <input type="text" class="form-control" placeholder="Search by code or name"> -->
          </div><!-- card-header -->
          <div class="card-body pd-10">
            <div id="scroll3" class="scrollbar-lg pos-relative ht-400">
              <table class="table table-borderless table-hover tx-13 mg-b-0">
                <thead>
                  <tr class="tx-uppercase tx-10 tx-spacing-1 tx-semibold tx-color-03">
                    <th>Code</th>
                    <th>Station</th>
                    <th>Date</th>
                    <th>Value</th>
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
                <a class="nav-link active" id="flow" data-toggle="tab" href="#home1" role="tab" aria-controls="home" aria-selected="true">Flow Rate</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="levels" data-toggle="tab" href="#home2" role="tab" aria-controls="profile" aria-selected="false">DAM LEVELS</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="quality" data-toggle="tab" href="#home3" role="tab" aria-controls="contact" aria-selected="false">WATER QUALITY</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="ground" data-toggle="tab" href="#home3" role="tab" aria-controls="contact" aria-selected="false">GROUNDWATER</a>
              </li>
            </ul>

          </div><!-- card-header -->
          <div class="card-body pd-10">
            <div class="tab-content" id="myTabContent5">
              <div class="tab-pane fade show active" id="home1" role="tabpanel" aria-labelledby="Flow Rate">
                <div id="leaflet1" class="ht-400"></div>
              </div>
              <div class="tab-pane fade" id="home2" role="tabpanel" aria-labelledby="Dam Levels">
                <div id="leaflet2" class="ht-400"></div>
              </div>
              <div class="tab-pane fade" id="home3" role="tabpanel" aria-labelledby="Water Quality">
                <div class="wq_container">
                  <select id="select_waterquality_var" class="form-control select2">
                    <option value="pH" selected="selected">pH</option>
                    <option value="EC">EC</option>
                    <option value="NO2 + NO3">NO2 + NO3</option>
                    <option value="PO4">PO4</option>
                    <option value="NH3-N">NH3-N</option>
                    <option value="E coli">E coli</option>
                    <option value="COD">COD</option>
                    <option value="SS">SS</option>
                    <option value="SO4">SO4</option>
                    <option value="Fe">Fe</option>
                    <option value="Mn">Mn</option>
                    <option value="As">As</option>
                    <option value="Na">Na</option>
                    <option value="Cn">Cn</option>
                    <option value="Mg">Mg</option>
                    <option value="Ca">Ca</option>
                    <option value="Al">Al</option>
                    <option value="Cl-">Cl-</option>
                    <option value="Cr6">Cr6</option>
                    <option value="Cu">Cu</option>
                    <option value="Ni">Ni</option>
                    <option value="F">F</option>
                    <option value="K">K</option>
                    <option value="Feacal Coliforms">Feacal Coliforms</option>
                    <option value="Total Coliforms">Total Coliforms</option>
                  </select>
                </div>
                <div id="leaflet3" class="ht-400"></div>
              </div>
              <div class="tab-pane fade" id="home4" role="tabpanel" aria-labelledby="Groundwater">
                <div id="leaflet" class="ht-400"></div>
              </div>
            </div>
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
  <script src="./assets/js/dashforge.js"></script>
  <script src="./data/sub-basins.js"></script>
  <script src="./data/Rivers.js"></script>
  <!-- append theme customizer -->
  <script src="./lib/leaflet/leaflet.js"></script>
  <script src="./assets/js/Control.FullScreen.js"></script>
  <script>
    $(function() {
      'use strict';

      var mymap1 = L.map('leaflet1', {
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
      }).addTo(mymap1);

      var maputoBasin = L.geoJSON(maputo, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Maputo Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(5,196,188)'
        }
      }).addTo(mymap1);

      var incomatiBasin = L.geoJSON(incomati, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Incomati Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(210,109,84)'
        }
      }).addTo(mymap1);

      /*Legend specific*/
      var legend = L.control({
        position: "bottomright"
      });

      legend.onAdd = function(mymap1) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>Map Legend</h4>";
        div.innerHTML += '<i style="background: rgb(0, 51, 204)"></i><span>High</span><br>';
        div.innerHTML += '<i style="background: rgb(51, 153, 255)"></i><span>Above Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(146, 208, 80)"></i><span>Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 192, 0)"></i><span>Below Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 192, 0)"></i><span>Low</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 0, 0)"></i><span>Very Low</span><br>';
        div.innerHTML += '<i style="background: rgb(127 127 127)"></i><span>No Value</span><br>';
        div.innerHTML += '<span style="color: rgb(5,196,188)">___ Maputo Basin</span><br>';
        div.innerHTML += '<span style="color: rgb(210,109,84)">___ Incomati Basin</span><br>';
        return div;
      };

      legend.addTo(mymap1);

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

      var maputoBasin2 = L.geoJSON(maputo, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Incomati Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(5,196,188)'
        }
      }).addTo(mymap2);

      var incomatiBasin2 = L.geoJSON(incomati, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Maputo Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(210,109,84)'
        }
      }).addTo(mymap2);

      /*Legend specific*/
      var legend2 = L.control({
        position: "bottomright"
      });

      legend2.onAdd = function(mymap2) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>Map Legend</h4>";
        div.innerHTML += '<i style="background: rgb(0, 51, 204)"></i><span>High</span><br>';
        div.innerHTML += '<i style="background: rgb(51, 153, 255)"></i><span>Above Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(146, 208, 80)"></i><span>Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 192, 0)"></i><span>Below Normal</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 192, 0)"></i><span>Low</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 0, 0)"></i><span>Very Low</span><br>';
        div.innerHTML += '<i style="background: rgb(127 127 127)"></i><span>No Value</span><br>';
        div.innerHTML += '<span style="color: rgb(5,196,188)">___ Maputo Basin</span><br>';
        div.innerHTML += '<span style="color: rgb(210,109,84)">___ Incomati Basin</span><br>';
        return div;
      };

      legend2.addTo(mymap2);

      var mymap3 = L.map('leaflet3', {
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
      }).addTo(mymap3);

      var maputoBasin3 = L.geoJSON(maputo, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Incomati Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(5,196,188)'
        }
      }).addTo(mymap3);

      var incomatiBasin3 = L.geoJSON(incomati, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Maputo Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(210,109,84)'
        }
      }).addTo(mymap3);

      /*Legend specific*/
      var legend3 = L.control({
        position: "bottomright"
      });

      legend3.onAdd = function(mymap3) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>Map Legend</h4>";
        div.innerHTML += '<i style="background: rgb(0, 51, 204)"></i><span>Compliance</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 0, 0)"></i><span>None-Compliance</span><br>';
        div.innerHTML += '<i style="background: rgb(127 127 127)"></i><span>No Value</span><br>';
        return div;
      };

      legend3.addTo(mymap3);

      $.ajax({
        type: "POST",
        url: 'api/flow-status.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.flow;
            var colors = 'rgb(127, 127, 127)';

            for (var i = 0; i < data.length; i++) {
              if (data[i].value > 0 && data[i].value < 20) {
                colors = 'rgb(255, 0, 0)'
              } else if (data[i].value >= 20 && data[i].value <= 40) {
                colors = 'rgb(255, 192, 0)'
              } else if (data[i].value > 40 && data[i].value < 60) {
                colors = 'rgb(146, 208, 80)'
              } else if (data[i].value >= 60 && data[i].value <= 80) {
                colors = 'rgb(51, 153, 255)'
              } else if (data[i].value > 80 && data[i].value <= 100) {
                colors = 'rgb(51, 153, 255)'
              } else if (data[i].value > 100) {
                colors = 'rgb(0, 51, 204)'
              } else {
                colors = 'rgb(127, 127, 127)'
              }


              var station = L.circleMarker([data[i].latitude, data[i].longitude], {
                radius: 5,
                fillColor: colors,
                color: "#000",
                weight: 1,
                opacity: 80,
                fillOpacity: 0.8
              }).addTo(mymap1).bindTooltip("(" + data[i].code + ") " + data[i].name + " : " + data[i].value + " m^3/s");

              var popupContent = `<h5 class = "text-primary text-center">${data[i].code}</h5>
                <div class="container"><table class="table table-hover">
                <tbody><tr><td> Station: </td><td>${data[i].name}</td></tr>
                <tr><td> Latitude: </td><td>${data[i].latitude}</td></tr>
                <tr><td> Longitude: </td><td>${data[i].longitude}</td></tr>
                <tr><td> Date: </td><td>${data[i].date}</td></tr>
                <tr><td> Level: </td><td style="color:${colors}">${data[i].value} ${data[i].unit} </td></tr>`;
              station.bindPopup(popupContent)

              rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td><strong>${data[i].date}</strong></td>
                <td style="color:${colors}">${data[i].value} ${data[i].unit}</td>
              </tr>`;
            }
            $('#stations').html(rows);
          } else if (type == "failed") {
            alert('Failed to get data')
            console.log(text);
          }

        }
      });

      $('#flow').on('click', function() {

        $.ajax({
          type: "POST",
          url: 'api/flow-status.php',
          dataType: "json",
          success: function(response) {
            var type = response.type;
            var rows = '';

            if (type == "success") {
              var data = response.flow;
              var colors = 'rgb(127, 127, 127)';

              for (var i = 0; i < data.length; i++) {
                if (data[i].value > 0 && data[i].value < 20) {
                  colors = 'rgb(255, 0, 0)'
                } else if (data[i].value >= 20 && data[i].value <= 40) {
                  colors = 'rgb(255, 192, 0)'
                } else if (data[i].value > 40 && data[i].value < 60) {
                  colors = 'rgb(146, 208, 80)'
                } else if (data[i].value >= 60 && data[i].value <= 80) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 80 && data[i].value <= 100) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 100) {
                  colors = 'rgb(0, 51, 204)'
                } else {
                  colors = 'rgb(127, 127, 127)'
                }

                var station = L.circleMarker([data[i].latitude, data[i].longitude], {
                  radius: 5,
                  fillColor: colors,
                  color: "#000",
                  weight: 1,
                  opacity: 80,
                  fillOpacity: 0.8
                }).addTo(mymap1).bindTooltip("(" + data[i].code + ") " + data[i].name + " : " + data[i].value + " m^3/s");

                var popupContent = `<h5 class = "text-primary text-center">${data[i].code}</h5>
                <div class="container"><table class="table table-hover">
                <tbody><tr><td> Station: </td><td>${data[i].name}</td></tr>
                <tr><td> Latitude: </td><td>${data[i].latitude}</td></tr>
                <tr><td> Longitude: </td><td>${data[i].longitude}</td></tr>
                <tr><td> Date: </td><td>${data[i].date}</td></tr>
                <tr><td> Level: </td><td style="color:${colors}">${data[i].value} ${data[i].unit} </td></tr>`;
                station.bindPopup(popupContent)

                rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td><strong>${data[i].date}</strong></td>
                <td style="color:${colors}">${data[i].value} ${data[i].unit}</td>
              </tr>`;
              }
              $('#stations').html(rows);
            } else if (type == "failed") {
              alert('Failed to get data')
              console.log(text);
            }

          }
        });
      })
      $('#levels').on('click', function() {
        mymap2.invalidateSize();
        $.ajax({
          type: "POST",
          url: 'api/dams-status.php',
          dataType: "json",
          success: function(response) {
            var type = response.type;
            var rows = '';

            if (type == "success") {
              var data = response.dams;
              var colors = 'rgb(127, 127, 127)';

              for (var i = 0; i < data.length; i++) {
                if (data[i].value > 0 && data[i].value < 20) {
                  colors = 'rgb(255, 0, 0)'
                } else if (data[i].value >= 20 && data[i].value <= 40) {
                  colors = 'rgb(255, 192, 0)'
                } else if (data[i].value > 40 && data[i].value < 60) {
                  colors = 'rgb(146, 208, 80)'
                } else if (data[i].value >= 60 && data[i].value <= 80) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 80 && data[i].value <= 100) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 100) {
                  colors = 'rgb(0, 51, 204)'
                } else {
                  colors = 'rgb(127, 127, 127)'
                }

                var station = L.circleMarker([data[i].latitude, data[i].longitude], {
                  radius: 5,
                  fillColor: colors,
                  color: "#000",
                  weight: 1,
                  opacity: 80,
                  fillOpacity: 0.8
                }).addTo(mymap2).bindTooltip("(" + data[i].code + ") " + data[i].name + " : " + data[i].value + " %");

                var popupContent = `<h5 class = "text-primary text-center">${data[i].code}</h5>
                <div class="container"><table class="table table-hover">
                <tbody><tr><td> Dam: </td><td>${data[i].name}</td></tr>
                <tr><td> Latitude: </td><td>${data[i].latitude}</td></tr>
                <tr><td> Longitude: </td><td>${data[i].longitude}</td></tr>
                <tr><td> Date: </td><td>${data[i].date}</td></tr>
                <tr><td> % Storage: </td><td style="color:${colors}">${data[i].value} % </td></tr>`;
                station.bindPopup(popupContent)

                rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td><strong>${data[i].date}</strong></td>
                <td style="color:${colors}">${data[i].value} %</td>
              </tr>`;
              }
              $('#stations').html(rows);

            } else if (type == "failed") {
              alert('Failed to get data')
              console.log(text);
            }

          }
        });
      });

      $('#quality').on('click', function() {
        mymap3.invalidateSize();
        $.ajax({
          type: "POST",
          url: 'api/quality-status.php',
          dataType: "json",
          success: function(response) {
            var type = response.type;
            var rows = '';

            if (type == "success") {
              var data = response.quality;
              var colors = 'rgb(127, 127, 127)';

              for (var i = 0; i < data.length; i++) {
                if (data[i].value > 0 && data[i].value < 20) {
                  colors = 'rgb(255, 0, 0)'
                } else if (data[i].value >= 20 && data[i].value <= 40) {
                  colors = 'rgb(255, 192, 0)'
                } else if (data[i].value > 40 && data[i].value < 60) {
                  colors = 'rgb(146, 208, 80)'
                } else if (data[i].value >= 60 && data[i].value <= 80) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 80 && data[i].value <= 100) {
                  colors = 'rgb(51, 153, 255)'
                } else if (data[i].value > 100) {
                  colors = 'rgb(0, 51, 204)'
                } else {
                  colors = 'rgb(127, 127, 127)'
                }
                var station = L.circleMarker([data[i].latitude, data[i].longitude], {
                  radius: 5,
                  fillColor: colors,
                  color: "#000",
                  weight: 1,
                  opacity: 80,
                  fillOpacity: 0.8
                }).addTo(mymap3).bindTooltip("(" + data[i].code + ") " + data[i].name + " : " + data[i].value + " m^3/s");

                var popupContent = `<h5 class = "text-primary text-center">${data[i].code}</h5>
                <div class="container"><table class="table table-hover">
                <tbody><tr><td> Station: </td><td>${data[i].name}</td></tr>
                <tr><td> Latitude: </td><td>${data[i].latitude}</td></tr>
                <tr><td> Longitude: </td><td>${data[i].longitude}</td></tr>
                <tr><td> Date: </td><td>${data[i].date}</td></tr>
                <tr><td> Level: </td><td style="color:${colors}">${data[i].value} ${data[i].unit} </td></tr>`;
                station.bindPopup(popupContent)

                rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td><strong>${data[i].date}</strong></td>
                <td style="color:${colors}">${data[i].value} ${data[i].unit}</td>
              </tr>`;
              }
              $('#stations').html(rows);

            } else if (type == "failed") {
              alert('Failed to get data')
              console.log(text);
            }

          }
        });
      })

      $("a[href='#home3']").on('shown.bs.tab', function(e) {
        mymap3.invalidateSize();
      });
      $("a[href='#home2']").on('shown.bs.tab', function(e) {
        mymap2.invalidateSize();
      });
      $("a[href='#home1']").on('shown.bs.tab', function(e) {
        mymap1.invalidateSize();
      });

    })
    const scroll3 = new PerfectScrollbar('#scroll3', {
      suppressScrollX: true
    });
  </script>
</body>

</html>