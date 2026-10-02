<?php

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$isLocalHost = !isset($_SERVER['HTTP_HOST']) || str_starts_with($httpHost, 'localhost') || str_starts_with($httpHost, '127.0.0.1') || str_starts_with($httpHost, '[::1]') || str_starts_with($httpHost, '::1') || ($httpHost[0] ?? '') === 'l';

if ($isLocalHost) {
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define("URI","https://localhost");  
  $username = "admin";
  $passwd = "1234";

  $username2 = "reader";
  $passwd2 = "1234OLe";


  $servername = "127.0.0.1";
  $usernameDb = "root";
  $password = "";
  $dbname = "crmskchccz";
  $socket = file_exists('/home/lutuk/mariadb/mysql.sock') ? '/home/lutuk/mariadb/mysql.sock' : ini_get('mysqli.default_socket');
  $conn = new mysqli($servername, $usernameDb, $password, $dbname, 3306, $socket);
  $conn->query("set names utf8");
  $conn->set_charset("utf8");
  //mysqli_set_charset($conn, 'utf8mb4');
    
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    exit();
  }
}else {
  $username = "lm"; 
  define("URI","/v3");
  $passwd = "mjf7JIM1WM";

  $username2 = "reader";
  $passwd2 = "1234OLe";


  $servername = "localhost";
  $usernameDb = "crmskchccz";
  $password = "mjf7JIM1WM";
  $dbname = "crmskchccz";
  
  $conn = new mysqli($servername, $usernameDb, $password, $dbname);
   $conn->query("set names utf8");
   $conn->set_charset("utf8");
//$conn->query("set names utf8mb4_unicode_ci");
//  $conn->set_charset("utf8mb4_unicode_ci");
 

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    exit();
  }
}
?>
