<?php

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
session_start();

$query = 'SELECT * FROM `view_flow_levels` ORDER BY code ASC';

if ($results = mysqli_query($con, $query)) {
    $response = [];
    while ($row = mysqli_fetch_assoc($results)) {
        $response[] = $row;
    }
    $output = ['type' => 'success', 'text' => 'successully got data', 'flow' => $response];
    echo json_encode($output);
} else {
    $output = ['type' => 'failed', 'text' => 'Error connecting to database'];
    echo json_encode($output);
}

mysqli_close($con);
