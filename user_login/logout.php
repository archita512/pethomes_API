<?php
session_start(); // Start the session

// Clear all session data
session_unset();
session_destroy();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Return response in the desired format
echo json_encode([
    'success' => true,
    'message' => 'User Logout successfully'
]);
?>