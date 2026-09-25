<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
session_set_cookie_params([
'lifetime' => 0,
 'path' => '/',
 'domain' => 'localhost',
 'secure' => true, // Required for SameSite=None
 'httponly' => true,
 'samesite' => 'None',
]);}
session_start();
if ($_SERVER['HTTP_HOST'] !== 'localhost') {

if(isset($_SESSION["login"]) != true){
header('HTTP/1.1 401 Unauthorized');
header('Content-Type: application/json');

$response = [
    'error' => 'Unauthorized',
    'message' => 'Invalid or missing token'
];

echo json_encode($response);
  //header("Location: /");
  exit;
}
}

if (isset($_SESSION["user"]) && $_SESSION["user"]=="reader") {
  if ($_SERVER["REQUEST_METHOD"]!="GET") {
      echo json_encode(array("msg"=>"Reader cannot change data!!!"));
      exit;
 }}

?>
