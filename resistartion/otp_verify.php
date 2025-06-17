<?php
session_start();
print_r($_SESSION);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include connection first
include '../connection.php';
// print_r($_SESSION);

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


// Get form data and handle both POST and form-data formats
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST)) {
        // Handle form-data submission
        $data = [
            'otp' => $_POST['otp'] ?? null,

        ];
    } else {
        // Handle JSON POST data
        $jsonData = file_get_contents("php://input");
        $data = json_decode($jsonData, true) ?? [];
    }
}

// Debug received data
error_log("Received search data: " . json_encode($data));

// Show search parameters in response
$searchParams = [
    'otp' => $data['otp'] ?? null,
];

// Validation messages array
$validationErrors = [];

// Check each required field
if (empty($data['otp'])) {
    $validationErrors[] = 'Please enter otp';
} elseif (!preg_match('/^\d{4}$/', $data['otp'])) { // Validate OTP format
    $validationErrors[] = 'Please enter a valid 4-digit OTP';
}
// If any validation errors exist, return them
if (!empty($validationErrors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $validationErrors),
    ]);
    exit;
}

// Make sure connection exists before using $cnn
if (!isset($cnn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$otp = mysqli_real_escape_string($cnn, $data['otp']);

if (isset($_SESSION['otp']) && isset($_SESSION['username']) && isset($_SESSION['email']) && isset($_SESSION['password'])) {
    $otp_v = $_SESSION['otp'];
    $email= $_SESSION['email'];
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];

    // Debugging: Log the OTP values
    error_log("Entered OTP: " . $otp);
    error_log("Session OTP: " . $otp_v);

    if (trim($otp) === trim($otp_v)) { // Check if entered OTP matches session OTP
         // Check if the email already exists
    

    // Hash the password before inserting it into the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user with the hashed password
    $query = "INSERT INTO user_login (name, email, password,otp,status) VALUES ('$username', '$email', '$hashedPassword','$otp','Active')";
    $result = mysqli_query($cnn, $query);
        $response = [
            'success' => true,
            'message' => 'OTP is valid & Ragister Successfully',
            'username' => $username,
            'email' => $email,

        ];
        unset($_SESSION['email']);
        unset($_SESSION['otp']);
        http_response_code(200);
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid OTP',
        ];
        http_response_code(404);
        echo json_encode($response);
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid OTP',
    ];
    http_response_code(500);
    echo json_encode($response);
}
// $user_id = $_SESSION['user_id'];
// print_r($user_id);

// Modified SQL query to be more precise
// $query = "SELECT  FROM user WHERE otp = '$otp' AND id = '$user_id' AND status = 'Active'";
// $query_run = mysqli_query($cnn, $query);

// // Check query execution
// if ($query_run) {
//     if (mysqli_num_rows($query_run) > 0) {
//         $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);

//         $response = [
//             'success' => true,
//             'message' => 'User Login Successfully',
//             'data' => $res
//         ];
//         http_response_code(200);
//         echo json_encode($response);
//     } else {
//         $response = [
//             'success' => false,
//             'message' => 'Invalid OTP',
//         ];
//         http_response_code(404);
//         echo json_encode($response);
//     }
// } else {
//     // Log error for debugging
//     error_log("Query Error: " . mysqli_error($cnn));

//     $response = [
//         'success' => false,
//         'message' => 'Internal Server Error',
//     ];
//     http_response_code(500);
//     echo json_encode($response);
// }

$cnn->close();
// unset($_SESSION['email']);
// unset($_SESSION['otp']);
?>
