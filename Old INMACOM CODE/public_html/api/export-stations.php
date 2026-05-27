<?php

// Start the session
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

// Query to fetch all data from the station table
$query = 'SELECT * FROM `station`';
$result = mysqli_query($con, $query);

// Check if query was successful
if ($result) {
    // Output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=station_data.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Fetch the first row to get the column names
    $first_row = mysqli_fetch_assoc($result);
    if ($first_row) {
        // Output the column headings
        fputcsv($output, array_keys($first_row));

        // Output the first row
        fputcsv($output, $first_row);

        // Output the remaining rows
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, $row);
        }
    } else {
        echo 'No data found in the table.';
    }

    // Close the file pointer
    fclose($output);
} else {
    echo 'Error: '.mysqli_error($con);
}

// Close the database connection
mysqli_close($con);
