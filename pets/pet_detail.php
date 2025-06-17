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
    // $status = isset($_POST['status']) ? mysqli_real_escape_string($cnn, $_POST['status']) : '';
    $pet_id = isset($_POST['pet_id']) ? mysqli_real_escape_string($cnn, $_POST['pet_id']) : '';



    // Check if the search keyword is empty
    if (empty($pet_id)) {
        error422('Please enter Pet ID LIKE pet_id'); // Call error422 function with the new message
    }

    if(isset($_SESSION['admin'])){
        $admin_email = $_SESSION['admin'];
        $query_user =  mysqli_query($cnn,"SELECT * FROM user_login WHERE email='$admin_email'");
        $row_user = mysqli_fetch_assoc($query_user);
        $user_id = $row_user['id'];
    }else{
        $user_id = 0;
    }
    
    $query = "SELECT c.name as cat_name,s.name as sub_name,p.* FROM pets AS p JOIN category AS c ON p.cat_id=c.id JOIN subcategory AS s ON p.sub_id=s.id WHERE p.id='$pet_id' AND p.status='Active' ORDER BY p.id DESC";
    $query_run = mysqli_query($cnn, $query);

    if ($query_run) {
        if (mysqli_num_rows($query_run) > 0) {
            $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);
            foreach ($res as &$pets) {
                // Check if the pet is in the user's wishlist
               $wishlist_query = mysqli_query($cnn, "SELECT * FROM pet_wishlist WHERE pet_id = '{$pets['id']}' AND u_id = '$user_id'");
                $is_wishlisted = mysqli_num_rows($wishlist_query) > 0; // True if the pet is in the wishlist
               // Set wishlist status
               $pets['wishlist'] = $is_wishlisted ? 1 : 0;
           }
            $data = [
                'success' => true,
                'message' => 'Pet List Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);
        } else {
            $data = [
                'success' => false,
                'message' => 'No Pet Found',
            ];
            header("HTTP/1.0 404 No Pet Found");
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