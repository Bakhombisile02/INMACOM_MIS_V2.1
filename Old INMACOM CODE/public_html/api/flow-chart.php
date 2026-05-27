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
$dataset = mysqli_escape_string($con, $_POST['dataset']);
$station_id = mysqli_escape_string($con, $_POST['station_id']);

$data = '';
$date = '';

$query = 'SELECT * FROM '.$dataset." WHERE `station_id` = '$station_id' ORDER BY `flow_levels`.`date` ASC;";

if ($results = mysqli_query($con, $query)) {

    $response = [];
    while ($row = mysqli_fetch_assoc($results)) {
        $response[] = $row;
    }
    $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
    echo json_encode($output);
} else {
    exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
}

mysqli_close($con);
