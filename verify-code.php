<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);

$code = isset($data['code']) ? $data['code'] : null;


if (!$email || !$code) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$file = "users.json";

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}

$users = json_decode(file_get_contents($file), true);

$userFound = false;

foreach ($users as &$user) {
    if ($user['email'] === $email) {
        $userFound = true;

        if (!empty($user['verified'])) {
            http_response_code(409);
            echo json_encode(["error" => "Email already verified"]);
            exit;
        }

        if ($user['code'] != $code) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid verification code"]);
            exit;
        }

        if (time() > $user['expires']) {
            http_response_code(401);
            echo json_encode(["error" => "Verification code expired"]);
            exit;
        }

        // Verify user
        $user['verified'] = true;
        $user['code'] = null;
        $user['expires'] = null;
        $user['profile'] = array(
            "createdAt" => date("c"),
            "role" => "user"
        );

        break;
    }
}



if (!$userFound) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}

// Save changes
file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

echo json_encode(["success" => true]);
