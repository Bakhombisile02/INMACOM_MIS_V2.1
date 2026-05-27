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

// Fetch records from the database
$query = $con->query('SELECT `station_id`, `fsc`, `value`, `date` FROM `dam_levels`');

if ($query->num_rows > 0) {
    $delimiter = ',';
    $filename = 'dam_levels_'.date('Y-m-d').'.csv';

    // Create a file pointer
    $f = fopen('php://memory', 'w');

    // Set column headers
    $fields = ['Station ID', 'FSC', 'Value', 'Date'];
    fputcsv($f, $fields, $delimiter);

    // Output each row of the data, format line as csv and write to file pointer
    while ($row = $query->fetch_assoc()) {
        $lineData = [$row['station_id'], $row['fsc'], $row['value'], $row['date']];
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
