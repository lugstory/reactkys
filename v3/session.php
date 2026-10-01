<?php
$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$isLocalHost = str_starts_with($httpHost, 'localhost') || str_starts_with($httpHost, '127.0.0.1') || str_starts_with($httpHost, '[::1]') || str_starts_with($httpHost, '::1');

if ($isLocalHost) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => 'localhost',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}

session_start();

if (!$isLocalHost) {
    if (!isset($_SESSION["login"])) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');

        $response = [
            'error' => 'Unauthorized',
            'message' => 'Invalid or missing token'
        ];

        echo json_encode($response);
        exit;
    }
}

if (isset($_SESSION["user"]) && $_SESSION["user"] == "reader") {
    if ($_SERVER["REQUEST_METHOD"] != "GET") {
        echo json_encode(array("msg" => "Reader cannot change data!!!"));
        exit;
    }
}

