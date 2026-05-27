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
            $code = $getData[0];
            $name = $getData[1];
            $latitude = $getData[2];
            $longitude = $getData[3];
            $category = $getData[4];

            // If user already exists in the database with the same email
            $query = "SELECT `name` FROM `station` WHERE `name` = '".$getData[1]."'";

            $check = mysqli_query($con, $query);

            if ($check->num_rows > 0) {
                mysqli_query($con, "UPDATE `station` SET `name` = '".$name."', `latitude` = '".$latitude."', `longitude` = '".$longitude."', `category` = '".$category."' WHERE name = '".$name."'");
            } else {
                mysqli_query($con, "INSERT INTO station (`name`, `latitude`, `longitude`, `category`) VALUES ('".$name."', '".$latitude."', '".$longitude."', '".$category."')");
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
