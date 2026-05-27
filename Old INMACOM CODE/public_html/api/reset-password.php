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
    if (isset($_REQUEST['action'])) {
        $email = mysqli_real_escape_string($con, $_REQUEST['email']);
        $key = mysqli_real_escape_string($con, $_REQUEST['key']);
        $sel_query = "SELECT * FROM `password_reset_temp` WHERE email='".$email."'";
        $results = mysqli_query($con, $sel_query);
        $row = mysqli_num_rows($results);

        if ($row == '') {
            $output = ['type' => 'error', 'text' => 'Link expired'];
            exit(json_encode($output));
        } else {
            $password = bin2hex(openssl_random_pseudo_bytes(4));
            $query = "UPDATE `users` SET `password`='$password' WHERE `email` ='$email'";
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
                                                   Password Reset
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px 24px 15px; color: #8492a6;">
                                                An account has been created for you on the INMACOM MIS. 
                                                <br> Your username is  :<b>'.$email.'</b>
                                                <br> Your new password is  :<b>'.$password.'</b>
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
                    $mail->addAddress($email);
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
        }
    } else {
        $email = mysqli_real_escape_string($con, $_REQUEST['email']);

        $sel_query = "SELECT * FROM `users` WHERE email='".$email."'";
        $results = mysqli_query($con, $sel_query);
        $row = mysqli_num_rows($results);

        if ($row == '') {
            $output = ['type' => 'failed', 'text' => 'Account not found'];
            exit(json_encode($output));
        } else {
            $expFormat = mktime(
                date('H'),
                date('i'),
                date('s'),
                date('m'),
                date('d') + 1,
                date('Y')
            );
            $expDate = date('Y-m-d H:i:s', $expFormat);
            $key = md5($email);
            $addKey = substr(md5(uniqid(rand(), 1)), 3, 10);
            $key = $key.$addKey;
            $ins_query = "INSERT INTO `password_reset_temp` (`email`, `key`, `expDate`)
        VALUES ('".$email."', '".$key."', '".$expDate."');";

            $results = mysqli_query($con, $ins_query);
            if ($results) {
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
                                                    <a href="https://inmacom.net/inmacom/api/reset-password.php?
                                                    key='.$key.'&email='.$email.'&action=reset" target="_blank">
                                                    https://inmacom.net/reset-password.php
                                                    ?key='.$key.'&email='.$email.'&action=reset</a>
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
                $subject = 'INMACOM MIS Password Reset';

                try {
                    $mail->isSMTP();
                    $mail->Host = 'mail.inmacom.net';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'admin@inmacom.net';
                    $mail->Password = 'AccessInmacom';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('admin@inmacom.net', 'Admin');
                    $mail->addAddress($email);
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
                $output = ['type' => 'failed', 'text' => 'Failed to write to database, '.mysqli_error($con)];
                echo json_encode($output);
            }
        }
    }
}
mysqli_close($con);
