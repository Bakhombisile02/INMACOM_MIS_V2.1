<?php

// connect to mysql db
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

// read the json file contents
$jsondata = file_get_contents('../data/station_ids.geojson');

// convert json object to php associative array
$data = json_decode($jsondata, true);

foreach ($data as $value) {
    foreach ($data['features'] as $ft => $values) {
        $name = $values['properties']['STATION_NA'];
        $code = $values['properties']['STATION__I'];
        $latitude = $values['properties']['Latitude'];
        $longitude = $values['properties']['Longitude'];

        // insert into mysql table
        $sql = "INSERT INTO `station`(`code`, `name`, `latitude`, `longitude`) 
        VALUES ('$code','$name','$latitude','$longitude')";

        if ($results = mysqli_query($con, $sql)) {
        } else {
            $output = ['type' => 'failed', 'text' => 'Failed to import. Error occured!'];
            echo json_encode($output);
        }
    }
}

$output = json_encode(['type' => 'success', 'text' => 'Imported']);
exit($output);

mysqli_close($con);
