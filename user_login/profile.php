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

// Fixed CORS headers (typo correction)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == "POST") {
    $userResponse = handleUserProfile();
    echo $userResponse;
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
    header("HTTP/1.0 422 Unprocessable Entity"); // Fixed typo
    echo json_encode($data);
    exit();
}

function handleUserProfile()
{
    global $cnn;
    
    // Get POST data
    $user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($cnn, $_POST['user_id']) : '';
    $name = isset($_POST['name']) ? mysqli_real_escape_string($cnn, $_POST['name']) : '';
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($cnn, $_POST['phone']) : '';

    // Case 1: If user_id is provided along with name and/or phone, update that specific user
    if (!empty($user_id) && (!empty($name) || !empty($phone))) {
        return updateSpecificUser($user_id, $name, $phone);
    }
    
    // Case 2: If only user_id is provided, show user data
    if (!empty($user_id) && empty($name) && empty($phone)) {
        return getUserData($user_id);
    }
    
    // Case 3: If name and phone are provided without user_id, update current session user
    if (empty($user_id) && (!empty($name) || !empty($phone))) {
        return updateUserProfile($name, $phone);
    }
    
    // If no valid combination provided, return error
    error422('Please provide: user_id (to fetch data) OR user_id with name/phone (to update specific user) OR name/phone (to update current user)');
}

function getUserData($user_id)
{
    global $cnn;
    
    // Validate user_id is numeric
    if (!is_numeric($user_id)) {
        error422('Invalid user ID format');
    }
    
    $query = "SELECT id, name, phone, email, created_at FROM user_login WHERE id='$user_id'";
    $result = mysqli_query($cnn, $query);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $userData = mysqli_fetch_assoc($result);
            
            $data = [
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $userData
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'User not found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    } else {
        $data = [
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($cnn),
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

function updateSpecificUser($user_id, $name, $phone)
{
    global $cnn;
    
    // Validate user_id is numeric
    if (!is_numeric($user_id)) {
        error422('Invalid user ID format');
    }
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    
    if (!empty($name)) {
        $updateFields[] = "name='$name'";
    }
    
    if (!empty($phone)) {
        // Validate phone number format (10 digits)
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            error422('Please enter valid phone number (10 digits)');
        }
        $updateFields[] = "phone='$phone'";
    }
    
    if (empty($updateFields)) {
        error422('No fields to update provided');
    }
    
    $query = "UPDATE user_login SET " . implode(', ', $updateFields) . " WHERE id='$user_id'";
    $query_run = mysqli_query($cnn, $query);
    
    if ($query_run) {
        // Check if any rows were affected
        if (mysqli_affected_rows($cnn) > 0) {
            // Get updated user data
            $getUserQuery = "SELECT id, name, phone, email FROM user_login WHERE id='$user_id'";
            $getUserResult = mysqli_query($cnn, $getUserQuery);
            $updatedUser = mysqli_fetch_assoc($getUserResult);
            
            $data = [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No changes made or user not found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    } else {
        $data = [
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($cnn),
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}

function updateUserProfile($name, $phone)
{
    global $cnn;
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    
    if (!empty($name)) {
        $updateFields[] = "name='$name'";
    }
    
    if (!empty($phone)) {
        // Validate phone number format (10 digits)
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            error422('Please enter valid phone number (10 digits)');
        }
        $updateFields[] = "phone='$phone'";
    }
    
    if (empty($updateFields)) {
        error422('No fields to update provided');
    }
    
    // Get user ID from session or token
    $user_id = 0;
    if (isset($_SESSION['admin'])) {
        $admin_email = $_SESSION['admin'];
        $query_user = mysqli_query($cnn, "SELECT * FROM user_login WHERE email='$admin_email'");
        $row_user = mysqli_fetch_assoc($query_user);
        $user_id = $row_user['id'];
    }
    
    if ($user_id == 0) {
        error422('User session not found');
    }
    
    $query = "UPDATE user_login SET " . implode(', ', $updateFields) . " WHERE id='$user_id'";
    $query_run = mysqli_query($cnn, $query);
    
    if ($query_run) {
        // Check if any rows were affected
        if (mysqli_affected_rows($cnn) > 0) {
            // Get updated user data
            $getUserQuery = "SELECT id, name, phone, email FROM user_login WHERE id='$user_id'";
            $getUserResult = mysqli_query($cnn, $getUserQuery);
            $updatedUser = mysqli_fetch_assoc($getUserResult);
            
            $data = [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No changes made or user not found',
            ];
            header("HTTP/1.0 404 Not Found");
            return json_encode($data);
        }
    } else {
        $data = [
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($cnn),
        ];
        header("HTTP/1.0 500 Internal Server Error");
        return json_encode($data);
    }
}
?>