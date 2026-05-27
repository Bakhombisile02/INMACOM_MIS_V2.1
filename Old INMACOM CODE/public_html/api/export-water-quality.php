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

// Fetch records from database
$query = 'SELECT * FROM water_quality';
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) > 0) {
    $delimiter = ',';
    $filename = 'water_quality_'.date('Y-m-d').'.csv';

    // Create a file pointer
    $f = fopen('php://memory', 'w');

    // Set column headers
    $fields = ['Station ID', 'Date', 'Parameter', 'Value', 'Unit'];
    fputcsv($f, $fields, $delimiter);

    // Output each row of the data, format line as csv and write to file pointer
    while ($row = mysqli_fetch_assoc($result)) {
        $lineData = [$row['station_id'], $row['date'], $row['parameter'], $row['value'], $row['unit']];
        fputcsv($f, $lineData, $delimiter);
    }

    // Move back to beginning of file
    fseek($f, 0);

    // Set headers to download file rather than displayed
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    // Output all remaining data on a file pointer
    fpassthru($f);
}
exit;

mysqli_close($con);
