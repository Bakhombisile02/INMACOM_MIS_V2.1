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
require '../vendor/phpmailer/phpmailer/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/Exception.php';
require '../vendor/phpmailer/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

if (isset($_REQUEST['email'])) {

    $organization = mysqli_real_escape_string($con, $_REQUEST['organization']);
    $first_name = mysqli_real_escape_string($con, $_REQUEST['firstname']);
    $last_name = mysqli_real_escape_string($con, $_REQUEST['lastname']);
    $telephone = mysqli_real_escape_string($con, $_REQUEST['telephone']);
    $country = mysqli_real_escape_string($con, $_REQUEST['country']);
    $email = mysqli_real_escape_string($con, $_REQUEST['email']);
    $password = bin2hex(openssl_random_pseudo_bytes(4));

    $sel_query = "SELECT * FROM `users` WHERE email='".$email."'";
    $results = mysqli_query($con, $sel_query);
    $row = mysqli_num_rows($results);

    if ($row != '') {
        $output = ['type' => 'failed', 'text' => 'Account already exist'];
        exit(json_encode($output));
    }

    $query = "INSERT INTO `users`(`first_name`, `last_name`, `email`, `telephone`, `country`, `password`, `organization`, `status`) 
        VALUES ('$first_name','$last_name','$email', '$telephone', '$country', '$password', '$organization', 'Active')";
    $result = mysqli_query($con, $query);

    if ($result) {
        $message = '<!DOCTYPE html>
                        <html lang="en">
                            <head>
                                <meta charset="utf-8" />
                                <title>Account Information</title>
                                
                                <!-- Font -->
                                <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap" rel="stylesheet">
                                <!-- Main Css -->
                            
                            </head>
                            <body style="font-family: Nunito, sans-serif; font-size: 15px; font-weight: 400;">
                                <!-- Hero Start -->
                                <div style="margin-top: 50px;">
                                    <table cellpadding="0" cellspacing="0" style="font-family: Nunito, sans-serif; font-size: 15px; font-weight: 400; max-width: 600px; border: none; margin: 0 auto; border-radius: 6px; overflow: hidden; background-color: #fff; box-shadow: 0 0 3px rgba(60, 72, 88, 0.15);">
                                        <thead>
                                            <tr style="background-color: #1b4c6d; padding: 3px 0; border: none; line-height: 68px; text-align: center; color: #fff; font-size: 24px; letter-spacing: 1px;">
                                                <th scope="col"><h3>INMACOM MIS</h3></th>
                                            </tr>
                                        </thead>
                            
                                        <tbody>
                                            <tr>
                                                <td style="padding: 48px 24px 0; color: #161c2d; font-size: 18px; font-weight: 600;">
                                                   Account Information
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px 24px 15px; color: #8492a6;">
                                                An account has been created for you on the INMACOM MIS. 
                                                <br> Your username is  :<b>'.$email.'</b>
                                                <br> Your password is  :<b>'.$password.'</b>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px 24px 0; color: #8492a6;">
                                                    <a href="https://inmacom.net/login.php">Click here to login</a> 
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px 24px 15px; color: #8492a6;">
                                                    INMACOM MIS <br> Support Team
                                                </td>
                                            </tr>
                            
                                            <tr>
                                                <td style="padding: 16px 8px; color: #8492a6; background-color: #f8f9fc; text-align: center;">
                                                    © <script>document.write(new Date().getFullYear())</script> INMACOM MIS.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Hero End -->
                            </body>
                        </html>';
        $subject = 'INMACOM MIS Account';

        try {
            $mail->isSMTP();
            $mail->Host = 'mail.inmacom.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@inmacom.net';
            $mail->Password = 'AccessInmacom';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('admin@inmacom.net', 'Admin');
            $mail->addAddress($email, $first_name.' '.$last_name);
            $mail->addReplyTo('noreply@inmacom.net', 'noreply');
            $mail->addCC('admin@inmacom.net');

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            $response = ['type' => 'success', 'text' => 'Successfully Added'];
            echo json_encode($response);
        } catch (Exception $e) {
            echo 'Mailer Error: '.$mail->ErrorInfo;
        }
    } else {
        $output = ['type' => 'failed', 'text' => 'Failed to write to database, '.mysqli_error($link)];
        echo json_encode($output);
    }
} else {
}
mysqli_close($con);
