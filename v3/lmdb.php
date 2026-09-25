<?php
require_once "conn.php";

// ✅ jednoduché heslo
$PASSWORD = "tvoje_silne_heslo_@";

if (!isset($_GET['pass']) || $_GET['pass'] !== $PASSWORD) {
    http_response_code(403);
    exit("Forbidden");
}

// název souboru
$filename = $dbname . "_backup_" . date("Y-m-d_H-i-s") . ".sql";

// hlavičky pro stažení
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// tabulky
$tables = [];
$result = $conn->query("SHOW TABLES");

while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$output = "";

// export tabulek
foreach ($tables as $table) {
    $res = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $res->fetch_row();

    $output .= "\n\n-- TABLE: $table\n\n";
    $output .= $row[1] . ";\n\n";

    $res = $conn->query("SELECT * FROM `$table`");

    while ($row = $res->fetch_assoc()) {
        $cols = array_map(fn($col) => "`$col`", array_keys($row));

        $vals = array_map(function($val) use ($conn) {
            if (is_null($val)) return "NULL";
            return "'" . $conn->real_escape_string($val) . "'";
        }, array_values($row));

        $output .= "INSERT INTO `$table` (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ");\n";
    }
}

// výstup
echo $output;
exit;
