<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Read input
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : false;



if (!$email) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid email"]);
    exit;
}

$code = rand(100000, 999999);
$expires = time() + 300;
$file = "users.json";

// Ensure file exists
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

// Load users safely
$users = json_decode(file_get_contents($file), true);
if (!is_array($users)) {
    $users = [];
}

$userFound = false;

// Find user
foreach ($users as &$user) {
    if (isset($user['email']) && $user['email'] === $email) {
        $userFound = true;

        if (!empty($user['verified'])) {
            http_response_code(409);
            echo json_encode(["error" => "Email already verified"]);
            exit;
        }

        $user['code'] = $code;
        $user['expires'] = $expires;
        break;
    }
}
unset($user);

// Create user if new
if (!$userFound) {
    $users[] = [
        "email" => $email,
        "verified" => false,
        "code" => $code,
        "expires" => $expires,
        "profile" => null
    ];
}

// Save safely
file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

// OPTIONAL: comment out for testing
// mail($email, "Your Verification Code", "Your verification code is: $code");

echo json_encode(["success" => true]);
