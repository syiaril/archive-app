<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];

// Get file path first to unlink it
$stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($filePath);
$stmt->fetch();
$stmt->close();

// Preserve parameters for redirect
$redirectParams = $_GET;
unset($redirectParams['id']); // Remove ID as we don't need it in dashboard
unset($redirectParams['deleted']);
unset($redirectParams['error']);

if ($filePath) {
    // Delete from DB
    $del = $conn->prepare("DELETE FROM documents WHERE id = ?");
    $del->bind_param("i", $id);
    
    if ($del->execute()) {
        // Delete physical file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $redirectParams['deleted'] = 1;
    } else {
        $redirectParams['error'] = 'delete_failed';
    }
    $del->close();
} else {
    $redirectParams['error'] = 'not_found';
}

$redirectQuery = http_build_query($redirectParams);
header("Location: dashboard.php?" . $redirectQuery);
exit();
?>
