<?php
session_start();
// print_r($_SESSION);
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
 
    $query = "SELECT * FROM about_us WHERE status='Active'";
    $query_run = mysqli_query($cnn, $query);


    if ($query_run) {
        if (mysqli_num_rows($query_run) > 0) {
            $res = mysqli_fetch_assoc($query_run);
            $data = [
                'success' => true,
                'message' => 'About Us Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No About Us Found',
            ];
            header("HTTP/1.0 404 No About Us Found");
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
?>