<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$redirectParams = $_POST;
// Remove post-specific inputs, keep filters if they were passed (we passed them as hidden inputs)
unset($redirectParams['ids']); 

$ids = isset($_POST['ids']) ? $_POST['ids'] : [];
$deletedCount = 0;

if (!empty($ids) && is_array($ids)) {
    // Basic security: ensure IDs are integers to allow usage in IN clause
    $safeIds = array_map('intval', $ids);
    $inQuery = implode(',', $safeIds);

    // 1. Get file paths
    $sql = "SELECT id, file_path FROM documents WHERE id IN ($inQuery)";
    $result = $conn->query($sql);
    
    if ($result) {
        while($row = $result->fetch_assoc()) {
            if (file_exists($row['file_path'])) {
                unlink($row['file_path']);
            }
        }
    }

    // 2. Delete from DB
    $sql = "DELETE FROM documents WHERE id IN ($inQuery)";
    if ($conn->query($sql)) {
        $deletedCount = $conn->affected_rows;
        $redirectParams['deleted'] = 1;
        $redirectParams['count'] = $deletedCount;
    } else {
        $redirectParams['error'] = 'delete_failed';
    }
} else {
    $redirectParams['error'] = 'no_selection';
}

$redirectQuery = http_build_query($redirectParams);
header("Location: dashboard.php?" . $redirectQuery);
exit();
?>
