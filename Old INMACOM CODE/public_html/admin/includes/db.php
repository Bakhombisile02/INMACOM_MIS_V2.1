<?php

// $con = mysqli_connect("localhost","root","","inmacom");
$con = mysqli_connect('localhost', 'inmacom_db', 'AccessInmacom', 'inmacom_db');

if (mysqli_connect_errno()) {
    echo 'Failed to connect to MySQL: '.mysqli_connect_error();
}
