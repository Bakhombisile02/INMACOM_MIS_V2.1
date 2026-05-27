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

    $sql = 'SELECT * FROM `quality_compliance`';

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
    $sql = "SELECT * FROM `quality_compliance` WHERE `id` ='$id'";

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
    $sql = "DELETE FROM `quality_compliance` WHERE `id`= '$id'";
    if (mysqli_query($con, $sql)) {
        $output = ['type' => 'successful', 'text' => 'Successfully deleted!'];
        echo json_encode($output);
    } else {
        $output = ['type' => 'error', 'text' => 'Failed to get data', 'data' => mysqli_error($con)];
        echo json_encode($output);
    }
} elseif (isset($_POST['edit'])) {
    $id = mysqli_escape_string($con, $_POST['id']);
    $station_id = mysqli_real_escape_string($con, $_POST['station_id']);
    $min = mysqli_real_escape_string($con, $_POST['min']);
    $max = mysqli_real_escape_string($con, $_POST['max']);
    $parameter = mysqli_real_escape_string($con, $_POST['parameter']);
    $unit = $_POST['unit'];
    $query = "UPDATE `quality_compliance` SET `station_id`='$station_id',`min`='$min',`max`='$max',`unit`='$unit',`parameter`='$parameter' WHERE `id` ='$id'";
    $result = mysqli_query($con, $query);

    if ($result) {
        $response = ['type' => 'success', 'text' => 'Successfully Updated'];
        echo json_encode($response);
    } else {
        $output = ['type' => 'failed', 'text' => 'Failed to write to database, '.mysqli_error($link)];
        exit(json_encode($output));
    }
} else {
    if (isset($_REQUEST['station'])) {

        $station = mysqli_real_escape_string($con, $_REQUEST['station']);
        $min = mysqli_real_escape_string($con, $_REQUEST['min']);
        $max = mysqli_real_escape_string($con, $_REQUEST['max']);
        $unit = mysqli_real_escape_string($con, $_REQUEST['unit']);
        $parameter = mysqli_real_escape_string($con, $_REQUEST['parameter']);

        $query = "INSERT INTO `quality_compliance`(`station_id`, `min`, `max`,`unit`, `parameter`) 
            VALUES ('$station','$min','$max', '$unit', '$parameter')";

        $result = mysqli_query($con, $query);

        if ($result) {
            $response = ['type' => 'success', 'text' => 'Successfully Saved'];
            echo json_encode($response);
        } else {
            $output = ['type' => 'failed', 'text' => 'Failed to write to database, '.mysqli_error($link)];
            echo json_encode($output);
        }
    }
}
mysqli_close($con);
