<?php
require '../connection.php';

function error422($message)
{
    $data = [
        'success' => false,
        'message' => $message,
    ];
    header("HTTP/1.0 422 Unprocesble Entity");
    echo json_encode($data);
    exit();
}

function getuserlist()
{

    global $cnn;

    $query = "select id,username,email,password from user_login where status='Active'";
    $query_run = mysqli_query($cnn, $query);

    if ($query_run) {
        if (mysqli_num_rows($query_run) > 0) {
            $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);

            $data = [
                'success' => true,
                'message' => 'User List Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No User Found',
            ];
            header("HTTP/1.0 404 No User Found");
            return json_encode($data);
        }
    } else {
        $data = [
            'success' => false,
            'message' => 'Internal Server Error',
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }

}



function storeUser($userInput)
{
    global $cnn;

    $username = mysqli_real_escape_string($cnn, $userInput['username']);
    $email = mysqli_real_escape_string($cnn, $userInput['email']);
    $password = mysqli_real_escape_string($cnn, $userInput['password']);

    if (empty(trim($username))) {
        return error422('Enter username');
    } elseif (empty(trim($email))) {
        return error422('Enter email');
    } elseif (empty(trim($password))) {
        return error422('Enter password');
    } elseif (empty(trim($userInput['cpassword']))) {
        return error422('Enter confirm password');
    } elseif ($password !== $userInput['cpassword']) {
        return error422('Password and Confirm Password do not match');
    }

    $checkEmailQuery = "SELECT * FROM user_login WHERE email='$email'";
    $checkEmailResult = mysqli_query($cnn, $checkEmailQuery);

    if (mysqli_num_rows($checkEmailResult) > 0) {
        return error422('Email already exists');
    }

    // Generate OTP
    $otp = rand(1000, 9999); // Generate a 6-digit OTP

    // Send OTP to email (you would need to implement email sending logic here)
    // Send OTP to email
    $subject = "Your OTP Code";
    $message = "Your OTP code is: $otp"; // Include OTP in the message
    
    if(mail($email, $subject, $message)){
        session_start();
        $_SESSION['username'] = $username; // Store username in session
        $_SESSION['email'] = $email;       // Store email in session
        $_SESSION['password'] = $password; // Store password in session (consider security implications)
        $_SESSION['otp'] = $otp;
    }

    // Return success response with OTP
    $data = [
        'status' => 200,
        'message' => 'OTP generated and sent to email',
        'otp' => $otp, // Optionally return OTP for verification
    ];
    header("HTTP/1.0 200 OK");
    return json_encode($data);
}







?>