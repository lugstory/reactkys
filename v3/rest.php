<?php
require_once "cors.php";
require_once "session.php";
require_once "conn.php";

require_once "requests.php";
//header('Content-Type: application/json');
header('Content-Type: application/json; charset=UTF-8');
$r = new requests($conn);

?>
