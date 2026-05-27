<?php

// File upload folder
$uploadDir = '../uploads/';

// Allowed file types
$allowTypes = ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg', 'csv', 'xlsx', 'xls', 'ptt', 'zip', 'mp3', 'mp4'];

// Default response
$response = [
    'status' => 0,
    'message' => 'Form submission failed, please try again.',
];

// Get the submitted form data
$category = $_POST['category'];

$uploadStatus = 1;

// Upload file
$uploadedFile = '';
if (! empty($_FILES['file']['name'])) {
    // File path config
    $fileName = basename($_FILES['file']['name']);
    $targetFilePath = $uploadDir.$fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Allow certain file formats to upload
    if (in_array($fileType, $allowTypes)) {
        // Upload file to the server
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
            $uploadedFile = $fileName;
        } else {
            $uploadStatus = 0;
            $response['message'] = 'Sorry, there was an error uploading your file.';
        }
    } else {
        $uploadStatus = 0;
        $response['message'] = 'Sorry, only '.implode('/', $allowTypes).' files are allowed to upload.';
    }
}

if ($uploadStatus == 1) {
    // Include the database config file
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

    // Insert form data in the database
    $query = "INSERT INTO `documents`(`file_name`, `category`, `file_type`, `date`) 
    VALUES ('$uploadedFile', '$category', '$fileType' , NOW())";

    if ($results = mysqli_query($con, $query)) {
        $response['status'] = 1;
        $response['message'] = 'Form data submitted successfully!';
    }
}

// Return response
echo json_encode($response);
