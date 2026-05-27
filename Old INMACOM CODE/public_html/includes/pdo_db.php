<?php

try {
    $connect = new PDO(
        'mysql:host=localhost;dbname=u550237388_inmacom_db1',
        'inmacomadmin',
        'AccessInmacom2046'
    );

    // Set the PDO error mode to exception
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If connection is successful, trigger a JavaScript pop-up
    echo "<script>alert('Database connected successfully!');</script>";
} catch (PDOException $e) {
    // If connection fails, trigger a JavaScript pop-up with the error message
    echo "<script>alert('Database connection failed: ".addslashes($e->getMessage())."');</script>";
}
