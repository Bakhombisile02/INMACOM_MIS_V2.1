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

if (isset($_POST['getdata'])) {
    if ($_POST['getdata'] == 'All') {
        $sql = 'SELECT * FROM documents';
    } else {
        $category = mysqli_escape_string($con, $_POST['getdata']);

        $sql = "SELECT * FROM `documents` WHERE `category` = '$category';";
    }

    if ($results = mysqli_query($con, $sql)) {

        $response = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $response[] = $row;
        }
        $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
        echo json_encode($output);
    } else {
        exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
    }
} elseif (isset($_POST['file_type'])) {
    if ($_POST['file_type'] == 'document') {

        $sql = "SELECT * FROM `documents` WHERE `file_type` = 'docx' OR file_type = 'xlsx' OR file_type = 'pdf' OR file_type = 'ppt';";
    } elseif ($_POST['file_type'] == 'video') {

        $sql = "SELECT * FROM `documents` WHERE `file_type` = 'mp4';";
    } elseif ($_POST['file_type'] == 'audio') {

        $sql = "SELECT * FROM `documents` WHERE `file_type` = 'mp3';";
    } elseif ($_POST['file_type'] == 'image') {

        $sql = "SELECT * FROM `documents` WHERE `file_type` = 'png' OR file_type = 'jpg' OR file_type = 'jpeg' OR file_type = 'gif';";
    }

    if ($results = mysqli_query($con, $sql)) {

        $response = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $response[] = $row;
        }
        $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
        echo json_encode($output);
    } else {
        exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
    }
} elseif (isset($_POST['getrecord'])) {
    $id = mysqli_escape_string($con, $_POST['id']);
    $sql = "SELECT * FROM `documents` WHERE `id` ='$id'";

    if ($results = mysqli_query($con, $sql)) {

        $response = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $response[] = $row;
        }
        $output = ['type' => 'successful', 'text' => 'successully got data', 'data' => $response];
        echo json_encode($output);
    } else {
        exit(json_encode(['type' => 'error', 'text' => "Couldn't get data"]));
    }
} elseif (isset($_POST['delete'])) {

    $id = mysqli_escape_string($con, $_POST['id']);
    $sql = "DELETE FROM `documents` WHERE `id`= '$id'";
    if (mysqli_query($con, $sql)) {
        $output = ['type' => 'successful', 'text' => 'Successfully deleted!'];
        echo json_encode($output);
    } else {
        $output = ['type' => 'error', 'text' => 'Failed to get data', 'data' => mysqli_error($con)];
        echo json_encode($output);
    }
}

mysqli_close($con);
