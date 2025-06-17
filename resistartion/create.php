<?php
// Disable error reporting
error_reporting(0);

// Set headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include the necessary function file
include('function.php');

// Get the request method
$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'POST') {

    // Get the input data
    $userInput = json_decode(file_get_contents("php://input"), true);

    if (empty($userInput)) {
        // If no JSON input, fallback to $_POST
        $storeUser = storeUser($_POST);
    } else {
        // Use the JSON input data
        $storeUser = storeUser($userInput);
    }

    // Output the result
    echo $storeUser;

} else {
    // Handle incorrect request method
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
?>