<?php

session_start();
// Load the database configuration file
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

if (! empty($_FILES['file']['name'])) {

    // Allowed mime types
    $fileMimes = [
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain',
    ];

    // Validate whether selected file is a CSV file
    if (! empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $fileMimes)) {

        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');

        // Skip the first line
        fgetcsv($csvFile);

        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ',')) !== false) {
            // Get row data
            $station_id = $getData[0];
            $value = $getData[1];
            $unit = $getData[2];
            $date = date_create($getData[3]);
            $date_format = date_format($date, 'Y-m-d H:i:s');

            // If user already exists in the database with the same email
            $query = "SELECT `station_id` FROM `dam_levels` WHERE `station_id` = 'dam' AND `date`='$date_format'";

            $check = mysqli_query($con, $query);

            if ($check->num_rows > 0) {
                mysqli_query($con, "UPDATE `dam_levels` SET `station_id` = '".$station_id."', `value` = '".$value."', `unit` = '".$unit."', `date` = '".$date_format."' WHERE `station_id` = '".$station_id."' AND `date` = '".$date_format."'");
            } else {
                mysqli_query($con, "INSERT INTO `flow_levels` (`station_id`, `value`, `unit`, `date`) 
                VALUES ('".$station_id."', '".$value."', '".$unit."', '".$date_format."')");
            }
        }

        // Close opened CSV file
        fclose($csvFile);

        echo 'Success';
    } else {
        echo 'Error1';
    }
} else {
    echo 'Error2';
}
mysqli_close($con);
