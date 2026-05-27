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
// When form submitted, check and create user session.
if (isset($_POST['email'])) {
    $email = stripslashes($_REQUEST['email']);    // removes backslashes
    $email = mysqli_real_escape_string($con, $email);
    $password = stripslashes($_REQUEST['password']);
    $password = mysqli_real_escape_string($con, $password);
    // Check user is exist in the database
    $query = "SELECT * FROM `users` WHERE email = '$email' AND password ='$password'";
    if ($results = mysqli_query($con, $query)) {
        $rowcount = mysqli_num_rows($results);
        $row = mysqli_fetch_assoc($results);
        if ($rowcount == 1) {
            $username = $row['first_name'].' '.$row['last_name'];

            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            $_SESSION['organization'] = $row['organization'];
            $_SESSION['user_id'] = $row['id'];

            $output = json_encode(['type' => 'success', 'role' => $row['role']]);
            exit($output);
        } else {
            $output = ['type' => 'failed', 'text' => 'Invalid Credentials!'];
            echo json_encode($output);
        }
    } else {
        $output = ['type' => 'failed', 'text' => 'Error connecting to database'];
        echo json_encode($output);
    }
} else {
}
mysqli_close($con);
