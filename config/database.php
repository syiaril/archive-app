<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default Laragon password is usually empty or 'root'
$db   = 'archive_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set base URL for assets if needed, though relative paths work well for simple structures
define('BASE_URL', 'http://localhost/archive-app/');
?>
