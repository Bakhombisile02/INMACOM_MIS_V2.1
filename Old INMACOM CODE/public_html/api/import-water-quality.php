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
            $date = $getData[1];
            $parameter = $getData[2];
            $value = $getData[3];
            $unit = $getData[4];

            // Check if the record already exists
            $check_query = "SELECT `id` FROM `water_quality` WHERE `station_id`='$station_id' AND `date`='$date'";
            $check_result = mysqli_query($con, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                // If record exists, update the value
                $update_query = "UPDATE `water_quality` SET `value`='$value', `parameter`='$parameter', `unit`='$unit' WHERE `station_id`='$station_id' AND `date`='$date'";
                mysqli_query($con, $update_query);
            } else {
                // If record doesn't exist, insert it
                $insert_query = "INSERT INTO `water_quality` (`station_id`, `date`, `parameter`, `value`, `unit`) VALUES ('$station_id', '$date', '$parameter', '$value', '$unit')";
                mysqli_query($con, $insert_query);
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
