<?php

session_start();

// Database connection details
$host = 'localhost';
$username = 'u550237388_inmacomadmin';
$password = 'AccessInmacom2046';
$database = 'u550237388_inmacom_db1';

// Establish connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (! $con) {
    // If connection fails, return an error
    echo json_encode(['type' => 'failed', 'text' => 'Database connection failed: '.mysqli_connect_error()]);
    exit(); // Stop further execution if connection fails
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

$query = 'SELECT * FROM `station`';

if (isset($_POST['stations'])) {
    if ($_POST['stations'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = 'SELECT * FROM `station` ORDER BY `station`.`code` ASC';
    }
} elseif (isset($_POST['station_cat'])) {
    $category = mysqli_real_escape_string($con, $_POST['station_cat']);
    $query = "SELECT * FROM `station` WHERE category = '$category' ORDER BY `station`.`code` ASC";
} elseif (isset($_POST['dams'])) {
    if ($_POST['dams'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE category = 'Dam Levels' AND user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = "SELECT * FROM `station` WHERE category = 'Dam Levels' ORDER BY `station`.`code` ASC";
    }
} elseif (isset($_POST['flow'])) {
    if ($_POST['flow'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE category = 'Flow Levels' AND user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = "SELECT * FROM `station` WHERE category = 'Flow Levels' ORDER BY `station`.`code` ASC";
    }
} elseif (isset($_POST['quality'])) {
    if ($_POST['quality'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE category = 'Water Quality' AND user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = "SELECT * FROM `station` WHERE category = 'Water Quality' ORDER BY `station`.`code` ASC";
    }
} elseif (isset($_POST['ground'])) {
    if ($_POST['ground'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE category = 'Groundwater' AND user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = "SELECT * FROM `station` WHERE category = 'Groundwater' ORDER BY `station`.`code` ASC";
    }
} elseif (isset($_POST['rainfall'])) {
    if ($_POST['rainfall'] == 'datamanager') {
        $query = "SELECT station.*, user_stations.user_id FROM `station` 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        WHERE category = 'Rainfall' AND user_stations.user_id = '$user_id' 
        ORDER BY `station`.`code` ASC;";
    } else {
        $query = "SELECT * FROM `station` WHERE category = 'Rainfall' ORDER BY `station`.`code` ASC";
    }
} elseif (isset($_POST['query_station'])) {
    $query_station = mysqli_real_escape_string($con, $_POST['query_station']);
    $query = "SELECT * FROM `station` WHERE category = '$query_station' ORDER BY `station`.`code` ASC";
}

// Execute query
$results = mysqli_query($con, $query);

if ($results) {
    $response = [];
    while ($row = mysqli_fetch_assoc($results)) {
        $response[] = $row;
    }
    $output = ['type' => 'success', 'text' => 'Successfully got data', 'stations' => $response];
    echo json_encode($output);
} else {
    $output = ['type' => 'failed', 'text' => 'Error executing query: '.mysqli_error($con)];
    echo json_encode($output);
}

// Close connection
mysqli_close($con);
