<?php
$host = "127.0.0.1";
$port = 3306;
$socket = "";
$password = "YOUR_PASSWORD";
$user = "app_sarsntdb";
$dbname = "app_sarsntdb";

$con = null;
try {
    $con = new mysqli($host, $user, $password, $dbname, $port, $socket);
} catch (mysqli_sql_exception $e) {
    $detail = htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
    die('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Database</title></head><body style="font-family:Segoe UI,sans-serif;padding:2rem;max-width:40rem;">'
        . '<h1 style="font-size:1.25rem;">Cannot connect to MySQL</h1>'
        . '<p>Copy <code>connection.example.php</code> to <code>connection.php</code>, set the password, and create a MySQL user that matches.</p>'
        . '<p>Open the <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to <strong>MySQL</strong>, then reload this page.</p>'
        . '<p style="color:#888;font-size:0.85rem;">' . $detail . "</p></body></html>");
}

if ($con->connect_errno) {
    echo "Failed to connect to MySQL: (" . $con->connect_errno . ") " . $con->connect_error;
}
