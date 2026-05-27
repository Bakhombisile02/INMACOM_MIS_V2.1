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

$query = "
CREATE VIEW IF NOT EXISTS view_rainfall AS
SELECT 
    `s`.`id` AS `id`,
    `s`.`code` AS `code`,
    `s`.`name` AS `name`,
    `s`.`latitude` AS `latitude`,
    `s`.`longitude` AS `longitude`,
    `s`.`category` AS `category`,
    `rf`.`value` AS `value`,
    `rf`.`unit` AS `unit`,
    `rf`.`date` AS `date`,
    `rf`.`id` AS `rainfall_id`
FROM 
    `inmacom_db`.`station` `s`
LEFT JOIN 
    (
        SELECT 
            `rf1`.`station_id` AS `station_id`,
            `rf1`.`value` AS `value`,
            `rf1`.`date` AS `date`,
            `rf1`.`unit` AS `unit`,
            `rf1`.`id` AS `id`
        FROM 
            (
                SELECT 
                    `inmacom_db`.`rainfall`.`id` AS `id`,
                    `inmacom_db`.`rainfall`.`station_id` AS `station_id`,
                    `inmacom_db`.`rainfall`.`date` AS `date`,
                    `inmacom_db`.`rainfall`.`value` AS `value`,
                    `inmacom_db`.`rainfall`.`unit` AS `unit`,
                    ROW_NUMBER() OVER (PARTITION BY `inmacom_db`.`rainfall`.`station_id` ORDER BY `inmacom_db`.`rainfall`.`date` DESC) AS `rn`
                FROM 
                    `inmacom_db`.`rainfall`
            ) `rf1`
        WHERE 
            `rf1`.`rn` = 1
    ) `rf` 
ON 
    `s`.`code` = `rf`.`station_id`
WHERE 
    `s`.`category` = 'Rainfall';
";

mysqli_query($con, $query);

$query = 'SELECT * FROM view_rainfall';
if ($results = mysqli_query($con, $query)) {
    $response = [];
    while ($row = mysqli_fetch_assoc($results)) {
        $response[] = $row;
    }
    $output = ['type' => 'success', 'text' => 'successfully got data', 'quality' => $response];
    echo json_encode($output);
} else {
    $output = ['type' => 'failed', 'text' => 'Error connecting to database'];
    echo json_encode($output);
}

mysqli_close($con);
