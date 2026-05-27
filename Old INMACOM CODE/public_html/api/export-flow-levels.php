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

// Query to fetch data from the flow_levels table
$query = 'SELECT `station_id`, `value`, `unit`, `date` FROM `flow_levels`';
$result = mysqli_query($con, $query);

// Check if query was successful
if ($result) {
    // Output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Output the column headings
    fputcsv($output, ['Station ID', 'Value', 'Unit', 'Date']);

    // Fetch and output each row of data
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }

    // Close the file pointer
    fclose($output);
} else {
    echo 'Error: '.mysqli_error($con);
}

// Close the database connection
mysqli_close($con);
