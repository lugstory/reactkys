<?php

if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']="l";

if ($_SERVER['HTTP_HOST'][0] === 'l'){
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define("URI","https://localhost");  
  $username = "admin";
  $passwd = "1234";

  $username2 = "reader";
  $passwd2 = "1234OLe";


  $servername = "localhost";
  $usernameDb = "root";
  $password = "";
  $dbname = "crmskchccz";
  $conn = new mysqli($servername, $usernameDb, $password, $dbname);
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
