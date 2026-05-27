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
    // If connection fails, show an error message in a pop-up
    echo "<script>alert('Database connection failed: ".addslashes(mysqli_connect_error())."');</script>";
    exit(); // Stop further execution
}

// If connection is successful, show a success message in a pop-up
echo "<script>alert('Database connected successfully!');</script>";
