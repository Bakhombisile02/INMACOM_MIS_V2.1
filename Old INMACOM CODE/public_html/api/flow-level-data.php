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
    echo json_encode(['error' => 'Database connection failed: '.mysqli_connect_error()]);
    exit(); // Stop further execution if connection fails
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
if (isset($_POST['getdata'])) {

    if ($_POST['getdata'] == 'datamanager') {
        $sql = "SELECT user_stations.user_id, station.code, station.name, station.category, flow_levels.* 
        FROM station 
        INNER JOIN user_stations ON station.code = user_stations.station_id 
        INNER JOIN flow_levels ON flow_levels.station_id = user_stations.station_id 
        WHERE user_id = '$user_id';";
    } else {
        $sql = 'SELECT flow_levels.*, station.name FROM `flow_levels` 
        INNER JOIN station ON flow_levels.station_id = station.code';
    }

    if ($results = mysqli_query($con, $sql)) {

        $response = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $response[] = $row;
        }
        $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
        echo json_encode($output);
    } else {
        exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
    }
} elseif (isset($_POST['getrecord'])) {
    $id = mysqli_escape_string($con, $_POST['id']);
    $sql = "SELECT * FROM `view_flow_levels` WHERE `flow_levels_id` ='$id'";

    if ($results = mysqli_query($con, $sql)) {

        $response = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $response[] = $row;
        }
        $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
        echo json_encode($output);
    } else {
        exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
    }
} elseif (isset($_POST['delete'])) {

    $id = mysqli_escape_string($con, $_POST['id']);
    $sql = "DELETE FROM `flow_levels` WHERE `id`= '$id'";
    if (mysqli_query($con, $sql)) {
        $output = ['type' => 'successful', 'text' => 'Successfully deleted!'];
        echo json_encode($output);
    } else {
        $output = ['type' => 'error', 'text' => 'Failed to get data', 'data' => mysqli_error($con)];
        echo json_encode($output);
    }
} else {
    if (isset($_REQUEST['station'])) {

        $station = mysqli_real_escape_string($con, $_REQUEST['station']);
        $value = mysqli_real_escape_string($con, $_REQUEST['value']);
        $unit = mysqli_real_escape_string($con, $_REQUEST['unit']);
        $date = mysqli_real_escape_string($con, $_REQUEST['date_time']);
        $query = "INSERT INTO `flow_levels`(`station_id`, `value`, `unit`, `date`) 
            VALUES ('$station','$value','$unit', '$date')";

        $result = mysqli_query($con, $query);

        if ($result) {
            $response = ['type' => 'success', 'text' => 'Successfully Saved'];
            echo json_encode($response);
        } else {
            $output = ['type' => 'failed', 'text' => 'Failed to write to database, '.mysqli_error($link)];
            echo json_encode($output);
        }
    }
}
mysqli_close($con);
