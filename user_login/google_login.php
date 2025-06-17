<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include connection file
include '../connection.php';
// session_start();

// Get form data and handle both POST and form-data formats
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST)) {
        // Handle form-data submission
        $data = [
            'name' => $_POST['name'] ?? null,
            'email' => $_POST['email'] ?? null,
            'uid' => $_POST['uid'] ?? null,
        ];
    } else {
        // Handle JSON POST data
        $jsonData = file_get_contents("php://input");
        $data = json_decode($jsonData, true) ?? [];
    }
}

// Debug received data
error_log("Received data: " . json_encode($data));

// Validation
$validationErrors = [];
if (empty($data['name'])) {
    $validationErrors[] = 'Please enter name';
}

if (empty($data['uid'])) {
    $validationErrors[] = 'Please enter uid';
}

if (empty($data['email'])) {
    $validationErrors[] = 'Please enter email';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { // Validate email format
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
    $token_user = bin2hex(random_bytes(32));
    // Sanitize and assign variables
    $name = mysqli_real_escape_string($cnn, $data['name']);
    $email = mysqli_real_escape_string($cnn, $data['email']);
    $uid = mysqli_real_escape_string($cnn, $data['uid']);

    // Check if the user already exists in the database
    $check = mysqli_query($cnn, "SELECT * FROM user_login WHERE email='$email' AND name='$name'");
        if (mysqli_num_rows($check) > 0) {

        echo json_encode(['success' => false, 'message' => 'You are already registered.']); // Include token

    }else {
        // User doesn't exist, insert new record with token
        $addUser = mysqli_query($cnn, "INSERT INTO user_login (name, email, status) VALUES ('$name', '$email', 'Active')");
        if ($addUser) {
            session_start();
            $_SESSION['user_id'] = mysqli_insert_id($cnn); // Get the last inserted ID
            $_SESSION['admin'] = $name; // Store the name
            echo json_encode(['success' => true, 'message' => 'User created successfully.']); // Include token
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating user.']);
            }
    }


// Close the database connection
$cnn->close();
?>
