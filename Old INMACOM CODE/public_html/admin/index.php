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
  <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

  <title>Dashboard</title>

  <!-- vendor css -->
  <link href="../lib/%40fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/cryptofont/css/cryptofont.min.css" rel="stylesheet">
  <link href="../lib/leaflet/leaflet.css" rel="stylesheet">
  <link href="../assets/css/Control.FullScreen.css" rel="stylesheet">
  <link href="../lib/datatables.net-dt/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="../lib/datatables.net-responsive-dt/css/responsive.dataTables.min.css" rel="stylesheet">
  <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
  <!-- INMACOM CSS -->
  <link rel="stylesheet" href="../assets/css/dashforge.css">
  <link rel="stylesheet" href="../assets/css/dashforge.dashboard.css">
</head>
<?php include_once 'includes/auth_session.php'; ?>

<body>

  <?php include_once 'includes/sidebar.php'; ?>

  <div class="content ht-100v pd-0">
    <div class="content-header">
      <!-- <div class="content-search">
        <i data-feather="search"></i>
        <input type="search" class="form-control" placeholder="Search...">
      </div> -->
      <!-- <nav class="nav">
        <a href="#" class="new nav-link" data-toggle="tooltip" title="You have new notifications"><i data-feather="bell"></i></a>
        <a href="logout.php" class="nav-link" data-toggle="tooltip" title="Sign out"><i data-feather="log-out"></i></a>
      </nav> -->
    </div><!-- content-header -->

    <div class="content-body">
      <div class="container pd-x-0">
        <div class="d-sm-flex align-items-center justify-content-between mg-b-20 mg-lg-b-25 mg-xl-b-30">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb breadcrumb-style1 mg-b-10">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admin</li>
              </ol>
            </nav>
            <h4 class="mg-b-0 tx-spacing--1">Welcome to your Dashboard</h4>
          </div>
          <div class="d-none d-md-block">
            <!-- <button class="btn btn-xs pd-x-15 btn-white btn-uppercase mg-l-5"><i data-feather="upload" class="wd-10 mg-r-5"></i> Export</button>
            <button class="btn btn-xs pd-x-15 btn-primary btn-uppercase mg-l-5"><i data-feather="share-2" class="wd-10 mg-r-5"></i> Share</button> -->
          </div>
        </div>

        <div class="row row-xs">
          <div class="col-lg-7 col-xl-8">
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
                  <!-- <li class="nav-item">
                <a class="nav-link" id="ground" data-toggle="tab" href="#home3" role="tab" aria-controls="contact" aria-selected="false">GROUNDWATER</a>
              </li> -->
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
            <option value="pH">pH</option>
            <option value="Colour">Colour</option>
            <option value="Odour">Odour</option>
            <option value="TUR">TUR</option>
            <option value="EC" selected="selected">EC</option>
            <option value="NH3-N">NH3-N</option>
            <option value="BOD">BOD</option>
            <option value="COD">COD</option>
            <option value="Cl">Cl</option>
            <option value="DO">DO</option>
            <option value="F">F</option>
            <option value="NO2+NO3">NO2+NO3</option>
            <option value="K">K</option>
            <option value="Na">Na</option>
            <option value="PO4">PO4</option>
            <option value="SO4">SO4</option>
            <option value="Cu">Cu</option>
            <option value="Fe">Fe</option>
            <option value="Mg">Mg</option>
            <option value="E coli">E coli</option>
            <option value="TC">TC</option>
            <option value="FC">FC</option>
            <option value="FS">FS</option>
            <option value="VC">VC</option>
            <option value="SS">SS</option>
            <option value="Mn">Mn</option>
            <option value="As">As</option>
            <option value="Cn">Cn</option>
            <option value="Al">Al</option>
            <option value="Cr6">Cr6</option>
            <option value="Ni(VI)">Ni(VI)</option>
            <option value="Hg">Hg</option>
            <option value="Temp">Temp</option>
            <option value="Sal">Sal</option>
            <option value="Sn">Sn</option>
            <option value="TP">TP</option>
            <option value="TDS">TDS</option>
        </select>
    </div>
    <div id="leaflet3" class="ht-400"></div>
</div>
                  <div class="tab-pane fade" id="home4" role="tabpanel" aria-labelledby="Groundwater">
                    <div id="leaflet" class="ht-400"></div>
                  </div>
                </div>
              </div><!-- card-body -->

            </div>
          </div>

          <div class="col-md-6 col-lg-5 col-xl-4 mg-t-10 mg-lg-t-0">
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
                    <th>Date Time</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody id="stations"></tbody>
              </table>
                </div><!-- table-responsive -->
              </div><!-- card-body -->
            </div><!-- card -->
          </div>

          <div class="col-lg-6 mg-t-10">
            <div class="card">
              <div class="card-header d-flex align-items-start justify-content-between">
                <h6 class="lh-5 mg-b-0">Non-Compliant Quality</h6>
                <select id="select_waterquality_var" class="custom-select select2 col-md-4">
                        option value="pH">pH</option>
            <option value="Colour">Colour</option>
            <option value="Odour">Odour</option>
            <option value="TUR">TUR</option>
            <option value="EC" selected="selected">EC</option>
            <option value="NH3-N">NH3-N</option>
            <option value="BOD">BOD</option>
            <option value="COD">COD</option>
            <option value="Cl">Cl</option>
            <option value="DO">DO</option>
            <option value="F">F</option>
            <option value="NO2+NO3">NO2+NO3</option>
            <option value="K">K</option>
            <option value="Na">Na</option>
            <option value="PO4">PO4</option>
            <option value="SO4">SO4</option>
            <option value="Cu">Cu</option>
            <option value="Fe">Fe</option>
            <option value="Mg">Mg</option>
            <option value="E coli">E coli</option>
            <option value="TC">TC</option>
            <option value="FC">FC</option>
            <option value="FS">FS</option>
            <option value="VC">VC</option>
            <option value="SS">SS</option>
            <option value="Mn">Mn</option>
            <option value="As">As</option>
            <option value="Cn">Cn</option>
            <option value="Al">Al</option>
            <option value="Cr6">Cr6</option>
            <option value="Ni(VI)">Ni(VI)</option>
            <option value="Hg">Hg</option>
            <option value="Temp">Temp</option>
            <option value="Sal">Sal</option>
            <option value="Sn">Sn</option>
            <option value="TP">TP</option>
            <option value="TDS">TDS</option>
                      </select>
              </div><!-- card-header -->
              <div class="card-body pd-y-15 pd-x-10">
                <div class="table-responsive">
                  <table class="table table-borderless table-hover tx-13 mg-b-0" style="border: 0px !important;" id="non_compliant_quality_table">
                    <thead>
                      <tr class="tx-10 tx-spacing-1 tx-color-03 tx-uppercase">
                        <th>Code</th>
                        <th>Station</th>
                        <th>Value</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody id="non_compliant_quality"></tbody>
                  </table>
                </div>
              </div><!-- card-body -->
            </div><!-- card -->
          </div><!-- col -->
          <div class="col-lg-6 mg-t-10">
            <div class="card">
              <div class="card-header d-sm-flex align-items-start justify-content-between">
                <h6 class="lh-5 mg-b-0">Non-Compliant Flows</h6>
              </div><!-- card-header -->
              <div class="card-body pd-10">
                <table class="table table-borderless table-hover tx-13 mg-b-0" style="border: 0px !important;" id="non_compliant_flow_table">
                  <thead>
                    <tr class="tx-uppercase tx-10 tx-spacing-1 tx-semibold tx-color-03">
                      <th>Code</th>
                      <th>Station</th>
                      <th>Value</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody id="non_compliant_flow"></tbody>
                </table>
              </div><!-- card-body -->
            </div><!-- card -->
          </div><!-- col -->
          <div class="col mg-t-10">
            <div class="card card-dashboard-table">
              <div class="table-responsive">
                <table class="table table-bordered" id="overflowing_dams_table">
                  <thead>
                    <tr>
                      <th colspan="10">
                        <h6 class="lh-5 mg-b-0">Dams Above Normal</h6>
                      </th>
                    </tr>
                    <tr>
                      <th>Dam Name</th>
                      <th>FSC</th>
                      <th>% Storage</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody id="overflowing_dams"></tbody>
                </table>
              </div><!-- table-responsive -->
            </div><!-- card -->
          </div><!-- col -->
        </div><!-- row -->
      </div><!-- container -->
    </div>
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../lib/feather-icons/feather.min.js"></script>
  <script src="../lib/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="../lib/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="../lib/select2/js/select2.min.js"></script>
  <script src="../assets/js/dashforge.js"></script>
  <script src="../assets/js/dashforge.aside.js"></script>
  <script src="../data/sub-basins.js"></script>
  <!-- append theme customizer -->
  <script src="../lib/leaflet/leaflet.js"></script>
  <script src="../assets/js/Control.FullScreen.js"></script>

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
          layer.bindPopup('<b>This is </b>Incomati Basin')
        },
        style: {
          fillColor: 'transparent',
          fillOpacity: 1,
          color: 'rgb(5,196,188)'
        }
      }).addTo(mymap1);

      var incomatiBasin = L.geoJSON(incomati, {
        onEachFeature: function(feature, layer) {
          layer.bindPopup('<b>This is </b>Maputo Basin')
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
        div.innerHTML += '<i style="background: rgb(0, 51, 204)"></i><span>Compliant</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 0, 0)"></i><span>Non-Compliant</span><br>';
        div.innerHTML += '<i style="background: rgb(127 127 127)"></i><span>No Value</span><br>';
        div.innerHTML += '<span style="color: rgb(5,196,188)">___ Incomati Basin</span><br>';
        div.innerHTML += '<span style="color: rgb(210,109,84)">___ Maputo Basin</span><br>';
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
        div.innerHTML += '<span style="color: rgb(5,196,188)">___ Incomati Basin</span><br>';
        div.innerHTML += '<span style="color: rgb(210,109,84)">___ Maputo Basin</span><br>';
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
        div.innerHTML += '<i style="background: rgb(146, 208, 80)"></i><span>Compliance</span><br>';
        div.innerHTML += '<i style="background: rgb(255, 0, 0)"></i><span>None-Compliance</span><br>';
        div.innerHTML += '<i style="background: rgb(127 127 127)"></i><span>No Value</span><br>';
        div.innerHTML += '<span style="color: rgb(5,196,188)">___ Incomati Basin</span><br>';
        div.innerHTML += '<span style="color: rgb(210,109,84)">___ Maputo Basin</span><br>';
        return div;
      };

      legend3.addTo(mymap3);
      getOverFlowingDams();
      getNonCompliantFlow();
      getNonCompliantQuality();

      $.ajax({
    type: "POST",
    url: '../api/flow-status.php',
    dataType: "json",
    success: function(response) {
        var type = response.type;
        var rows = '';

        if (type == "success") {
            var data = response.flow;

            // Define the limits based on code
             var limits = {
                    'E-173': 1.4,
                    'E-23': 1.7,
                    'E-28': 1.7,
                    'E-393': 1.3,
                    'E-413': 2,
                    'E-43': 3,
                    'E-4': 2.7,
                    'GS8': 0.1,
                    'GS25': 0.1,
                    'GS21': 0.1,
                    'GS16': 1.7,
                    'GS23': 0.1,
                    'GS30': 0.1,
                    'GS31': 0.1,
                    'GS33': 0.1,
                    'GS34': 1.7,
                    'E-6': 0.1,
                    'E-572': 0.1,
                    'X1H049': 1.3,
                    'X2H036': 1.3
                };

            for (var i = 0; i < data.length; i++) {
                var colors = 'rgb(127, 127, 127)'; // Default color for No Value

                if (data[i].value !== null) {
                    if (data[i].code in limits) {
                        var limit = limits[data[i].code];
                        if (data[i].value >= limit) {
                            colors = 'rgb(0, 51, 204)'; // Compliant
                        } else {
                            colors = 'rgb(255, 0, 0)'; // Non-Compliant
                        }
                    }
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
                station.bindPopup(popupContent);

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
            alert('Failed to get data');
            console.log(text);
        }
    }
});


      
      $('#levels').on('click', function() {
        mymap2.invalidateSize();
        $.ajax({
          type: "POST",
          url: '../api/dams-status.php',
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










        $('#quality, #select_waterquality_var').on('click change', function() {
    mymap3.invalidateSize();
    var parameter = $('#select_waterquality_var').val();
    $.ajax({
        type: "POST",
        url: '../api/quality-status.php',
        data: {
            parameter: parameter
        },
        dataType: "json",
        success: function(response) {
            var type = response.type;
            var rows = '';

            if (type == "success") {
                var data = response.quality;
                var colors = 'rgb(127, 127, 127)';

                for (var i = 0; i < data.length; i++) {
                    var value = data[i].value;

                    if (data[i].parameter === parameter) { // Check if the parameter matches
                        if (parameter === 'pH') {
                            colors = (value >= 6.5 && value <= 8.5) ? 'rgb(146, 208, 80)' : 'rgb(255, 0, 0)';
                        } else if (parameter === 'Colour') {
                            colors = (value <= 15) ? 'rgb(146, 208, 80)' : 'rgb(255, 0, 0)';
                        }
                       
if (parameter === 'pH') {
    colors = ((value === null || value === undefined || (value >= 6.5 && value <= 8.5)) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Colour') {
    colors = ((value === null || value === undefined || value <= 15) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Odour') {
    colors = ((value === null || value === undefined || value <= 3) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'TUR') {
    colors = ((value === null || value === undefined || value <= 5) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'EC') {
    colors = ((value === null || value === undefined || value <= 150) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'NH3-N') {
    colors = ((value === null || value === undefined || value <= 1) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'BOD') {
    colors = ((value === null || value === undefined || value <= 5) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'COD') {
    colors = ((value === null || value === undefined || value <= 10) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Cl') {
    colors = ((value === null || value === undefined || value <= 250) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'DO') {
    colors = ((value === null || value === undefined || value > 75) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'F') {
    colors = ((value === null || value === undefined || value <= 0.75) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'NO2+NO3') {
    colors = ((value === null || value === undefined || value <= 50) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'K') {
    colors = ((value === null || value === undefined || value <= 50) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Na') {
    colors = ((value === null || value === undefined || value <= 200) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'PO4') {
    colors = ((value === null || value === undefined || value <= 2) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'SO4') {
    colors = ((value === null || value === undefined || value <= 250) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Cu') {
    colors = ((value === null || value === undefined || value <= 0.02) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'Mg') {
    colors = ((value === null || value === undefined || value <= 0.3) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'E coli') {
    colors = ((value === null || value === undefined || value <= 2000) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'TC') {
    colors = ((value === null || value === undefined || value <= 10000) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'FC') {
    colors = ((value === null || value === undefined || value <= 2000) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else if (parameter === 'FS') {
    colors = ((value === null || value === undefined || value <= 1000) && (data[i].date !== null)) ? 'rgb(146, 208, 80)' : 'rgb(127, 127, 127)';
} else {
    colors = ((value !== null && value !== undefined) && (data[i].date !== null)) ? 'rgb(127, 127, 127)' : 'rgb(127, 127, 127)'; // default color if not specified
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
                        station.bindPopup(popupContent);

                        rows += `
                        <tr>
                            <td>${data[i].code}</td>
                            <td>${data[i].name}</td>
                            <td><strong>${data[i].date}</strong></td>
                            <td style="color:${colors}">${data[i].value} ${data[i].unit}</td>
                        </tr>`;
                    }
                }
                $('#stations').html(rows);

            } else if (type == "failed") {
                alert('Failed to get data');
                console.log(text);
            }
        }
    });
});


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

    function getOverFlowingDams() {
      $.ajax({
        type: "POST",
        url: '../api/overflowing-dams.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.dams;
            var colors = 'rgb(127, 127, 127)';

            for (var i = 0; i < data.length; i++) {

              rows += `
              <tr>
                <td>${data[i].name}</td>
                <td>${data[i].fsc}</td>
                <td>${data[i].value} %</td>
                <td>${data[i].date}</td>
              </tr>`;
            }
            $('#overflowing_dams').html(rows);
            
          } else if (type == "failed") {
            alert('Failed to get data')
            console.log(text);
          }

        }
      });
    }

    function getNonCompliantFlow() {
      $.ajax({
        type: "POST",
        url: '../api/non_compliant_flow-status.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.flow;

            for (var i = 0; i < data.length; i++) {

              rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td>${data[i].value} m^3/s</td>
                <td>${data[i].date}</td>
              </tr>`;
            }
            $('#non_compliant_flow').html(rows);
            $('#non_compliant_flow_table').DataTable({
              searching: false,
              dom: 'Bfrtip',
              buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
              ]
            });
          } else if (type == "failed") {
            alert('Failed to get data')
            console.log(text);
          }

        }
      });
    }

    function getNonCompliantQuality() {
      $.ajax({
        type: "POST",
        url: '../api/non_compliant_quality.php',
        dataType: "json",
        success: function(response) {
          var type = response.type;
          var rows = '';

          if (type == "success") {
            var data = response.flow;

            for (var i = 0; i < data.length; i++) {

              rows += `
              <tr>
                <td>${data[i].code}</td>
                <td>${data[i].name}</td>
                <td>${data[i].value} m^3/s</td>
                <td>${data[i].date}</td>
              </tr>`;
            }
            $('#non_compliant_quality').html(rows);
            $('#non_compliant_quality_table').DataTable({
              searching: false,
              dom: 'Bfrtip',
              buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
              ]
            });
          } else if (type == "failed") {
            alert('Failed to get data')
            console.log(text);
          }

        }
      });
    }
  </script>
</body>

</html>