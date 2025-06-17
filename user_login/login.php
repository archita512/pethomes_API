<?php
session_start();
// Clear any previously set headers or outputs
unset($_SESSION['user_id']);
ob_clean();

// Check for the Authorization header
$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Include database connection
include '../connection.php';

// Fixed CORS headers (corrected typos)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == "POST") {
    $mainCatList = getMainCatList();
    echo $mainCatList;
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
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
    header("HTTP/1.0 422 Unprocessable Entity");
    echo json_encode($data);
    exit();
}

function getMainCatList()
{
    global $cnn;
    
    // Get form data and handle both POST and form-data formats
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            // Handle form-data submission
            $data = [
                'email' => $_POST['email'] ?? null,
                'password' => $_POST['password'] ?? null,
            ];
        } else {
            // Handle JSON POST data
            $jsonData = file_get_contents("php://input");
            $data = json_decode($jsonData, true) ?? [];
        }
    }

    // Debug received data (remove in production)
    error_log("Received data: " . json_encode($data));

    // Validation
    $validationErrors = [];
    if (empty($data['password'])) {
        $validationErrors[] = 'Please enter password';
    }

    if (empty($data['email'])) {
        $validationErrors[] = 'Please enter email';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = 'Please enter a valid email address';
    }

    // If validation fails, return error
    if (!empty($validationErrors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $validationErrors),
        ]);
        exit;
    }

    $email = mysqli_real_escape_string($cnn, $data['email']);
    $password = $data['password']; // Don't escape password as it will be verified with password_verify

    // Fetch user data
    $query = "SELECT * FROM user_login WHERE email='$email'";
    $query_run = mysqli_query($cnn, $query);
    
    if (mysqli_num_rows($query_run) === 0) {
        error422('User not found');
    }

    $user = mysqli_fetch_assoc($query_run);
    
    // Debug: Log the stored hash and input password (remove in production)
    error_log("Stored password hash: " . $user['password']);
    error_log("Input password: " . $password);
    
    // Check if password is hashed or plain text
    if (password_get_info($user['password'])['algo'] === null) {
        // Password is stored as plain text - compare directly
        if ($password !== $user['password']) {
            error422('Invalid password - plain text comparison');
        }
    } else {
        // Password is hashed - use password_verify
        if (!password_verify($password, $user['password'])) {
            error422('Invalid password - hash verification failed');
        }
    }

    // Store email in session
    $_SESSION['email'] = $email;
    $_SESSION['user_id'] = $user['id']; // Also store user ID if available

    // Generate token and update user record
    $token_user = bin2hex(random_bytes(32));
    $token_user_escaped = mysqli_real_escape_string($cnn, $token_user);
    $updateQuery = "UPDATE user_login SET token='$token_user_escaped' WHERE email='$email'";
    
    if (!mysqli_query($cnn, $updateQuery)) {
        error422('Failed to update token');
    }

    // Fetch updated user data
    $user_query = "SELECT id, email, name, token, created_at FROM user_login WHERE email='$email'";
    $user_run = mysqli_query($cnn, $user_query);
    $user_data = mysqli_fetch_assoc($user_run);

    // Remove sensitive data from response
    unset($user_data['password']);

    // Prepare the response
    $data = [
        'success' => true,
        'message' => 'Login Successfully',
        'user' => $user_data,
    ];

    header("HTTP/1.0 200 OK");
    return json_encode($data);
}
?>