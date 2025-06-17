<?php
session_start();
// Clear any previously set headers or outputs
ob_clean();

// Check for the Authorization header
$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Validate the token
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];

    // Check if the token exists in the database
    include '../connection.php'; // Ensure the database connection is included

    $token = mysqli_real_escape_string($cnn, $token);
    $checkTokenQuery = "SELECT * FROM user_login WHERE token='$token'";
    $tokenResult = mysqli_query($cnn, $checkTokenQuery);

    if (mysqli_num_rows($tokenResult) === 0) {
        // Token is invalid
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid token'
        ]);
        http_response_code(401); // Unauthorized
        exit;
    }
} else {
    // No token provided
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authorization token required'
    ]);
    http_response_code(401); // Unauthorized
    exit;
}

header('Access-Contrl-Allow-Origin:*');
header('Content-Type: application/json');
header('Access-Contrl-Allow-Method: POST');
header('Access-Contrl-Allow-Headers: Content-Type,Access-Control-Allow-Headers,Authorization, X-Request-With');

// include ('function.php');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == "POST") {

    $mainCatList = getMainCatList();
    echo $mainCatList;
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . 'Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

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

function getMainCatList()
{
    global $cnn;

    $current_date = date('Y-m-d'); 

    // Fetch POST parameters
    // $user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($cnn, $_POST['user_id']) : '';
    $password = isset($_POST['password']) ? mysqli_real_escape_string($cnn, $_POST['password']) : '';
    $npassword = isset($_POST['npassword']) ? mysqli_real_escape_string($cnn, $_POST['npassword']) : '';
    $cnpassword = isset($_POST['cnpassword']) ? mysqli_real_escape_string($cnn, $_POST['cnpassword']) : '';
    // $status = isset($_POST['status']) ? mysqli_real_escape_string($cnn, $_POST['status']) : '';

    if(isset($_SESSION['admin'])){
        $admin_email = $_SESSION['admin'];
        $query_user = "SELECT * FROM user_login WHERE email='$admin_email'";
        $query_run_user = mysqli_query($cnn, $query_user);
        $row_user = mysqli_fetch_assoc($query_run_user);
        $user_id = $row_user['id'];
     
        
    }else{
        $user_id = 0;
    }

    // Check if the search keyword is empty
    if (empty($password)) {
        error422('Please enter password'); // Call error422 function with the new message
    }
    if (empty($npassword)) {
        error422('Please enter new password LIKE npassword'); // Call error422 function with the new message
    }
    if (empty($cnpassword)) {
        error422('Please enter confirm password LIKE cnpassword'); // Call error422 function with the new message
    }
    if ($npassword !== $cnpassword) {
        error422('New password and confirm password do not match'); // Call error422 function with the new message
    }
    
    $query_user = "SELECT * FROM user_login WHERE id='$user_id'";
    $query_run_user = mysqli_query($cnn, $query_user);
    if (mysqli_num_rows($query_run_user) === 0) {
        error422('User ID does not exist'); // Call error422 function with the new message
    }else{
        $row_user = mysqli_fetch_assoc($query_run_user);
        $oldpassword = password_verify($password, $row_user['password']);
        if($oldpassword){
            $newpassword = password_hash($npassword, PASSWORD_DEFAULT);
            $query_update = "UPDATE user_login SET password='$newpassword' WHERE id='$user_id'";
            $query_run_update = mysqli_query($cnn, $query_update);
            if($query_run_update){
                $data = [
                    'success' => true,
                    'message' => 'Password updated successfully',
                ];
                header("HTTP/1.0 200 OK");
                return json_encode($data);
            }else{
                $data = [
                    'success' => false,
                    'message' => 'Password update failed',
                ];
                header("HTTP/1.0 400 Bad Request");
                return json_encode($data);
            }
        }else{
            error422('Old password is incorrect'); // Call error422 function with the new message
        }
    }
    

    
    header("HTTP/1.0 200 OK");
    return json_encode($data);
}

?>