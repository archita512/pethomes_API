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

    // Sanitize input
    $cat_id     = isset($_POST['cat_id']) ? mysqli_real_escape_string($cnn, $_POST['cat_id']) : '';
    $from_price = isset($_POST['from_price']) ? mysqli_real_escape_string($cnn, $_POST['from_price']) : '';
    $to_price   = isset($_POST['to_price']) ? mysqli_real_escape_string($cnn, $_POST['to_price']) : '';
    $country       = isset($_POST['country']) ? mysqli_real_escape_string($cnn, $_POST['country']) : '';

    // Base query for pets table
    $query = "SELECT p.* FROM pets p WHERE p.status='Active'";

    // Filters
    if (!empty($cat_id)) {
        $query .= " AND p.cat_id = '$cat_id'";
    }

    if (!empty($from_price) && !empty($to_price)) {
        $query .= " AND p.price BETWEEN $from_price AND $to_price";
    }

    if (!empty($country)) {
        $query .= " AND p.country = '$country'";
    }

    // Final sort
    $query .= " ORDER BY p.id DESC";

    $query_run = mysqli_query($cnn, $query);

    if ($query_run) {
        if (mysqli_num_rows($query_run) > 0) {
            $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);

            // Wishlist logic (optional, update table if needed)
            foreach ($res as &$pet) {
                $petId = $pet['id'];
                $query_wishlist = "SELECT * FROM pet_wishlist WHERE pet_id = '$petId'";
                $res_wishlist = mysqli_query($cnn, $query_wishlist);
                $pet['wishlist'] = mysqli_num_rows($res_wishlist) > 0 ? 1 : 0;
            }

            $data = [
                'success' => true,
                'message' => 'Pets List Fetched Successfully',
                'data'    => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No Pets Found',
            ];
            header("HTTP/1.0 404 Not Found");
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