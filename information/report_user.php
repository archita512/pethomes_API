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
    $user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($cnn, $_POST['user_id']) : '';
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($cnn, $_POST['reason']) : '';
    // $status = isset($_POST['status']) ? mysqli_real_escape_string($cnn, $_POST['status']) : '';
    $report_details = isset($_POST['report_details']) ? mysqli_real_escape_string($cnn, $_POST['report_details']) : '';

    // Check if the search keyword is empty
    if (empty($user_id)) {
        error422('Please enter user_id'); // Call error422 function with the new message
    }
    $query_user = "SELECT * FROM user_login WHERE id='$user_id'";
    $query_run_user = mysqli_query($cnn, $query_user);
    if (mysqli_num_rows($query_run_user) === 0) {
        error422('User ID does not exist'); // Call error422 function with the new message
    }
    if (empty($reason)) {
        error422('Please enter reason'); // Call error422 function with the new message
    }
    if (empty($report_details)) {
        error422('Please enter report_details'); // Updated message to specify valid actions
    }

    // Insert into food_wishlist
    $query = "INSERT INTO  report (u_id, reason, report_details) VALUES ('$user_id', '$reason', '$report_details')";
    $query_run = mysqli_query($cnn, $query);
    $message = 'Report User Successfully';

    // Prepare the response
    $data = [
        'success' => true,
        'message' => $message,
    ];

    header("HTTP/1.0 200 OK");
    return json_encode($data);
}

?>