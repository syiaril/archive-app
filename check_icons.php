<?php
require_once 'config/database.php';

$result = $conn->query("SELECT name, icon FROM categories");
while($row = $result->fetch_assoc()) {
    echo $row['name'] . " | " . $row['icon'] . "\n";
}
?>
