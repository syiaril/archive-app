<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT d.*, c.name as category_name FROM documents d JOIN categories c ON d.category_id = c.id WHERE d.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Document not found.";
    exit();
}

$doc = $result->fetch_assoc();
$fileExt = strtolower($doc['file_type']);
$filePath = $doc['file_path'];

// Check file existence
if (!file_exists($filePath)) {
    $error = "File not found on server.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - <?php echo htmlspecialchars($doc['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm z-10">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-500 hover:text-gray-800">
                <i class="ph ph-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($doc['title']); ?></h1>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold uppercase"><?php echo htmlspecialchars($doc['category_name']); ?></span>
                    <span><?php echo date('d M Y', strtotime($doc['doc_date'])); ?></span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
             <a href="<?php echo htmlspecialchars($filePath); ?>" download class="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-medium transition shadow-sm">
                <i class="ph ph-download-simple text-lg"></i> Download
            </a>
        </div>
    </header>

    <!-- Content -->
    <main class="flex-1 overflow-hidden relative bg-slate-900 flex items-center justify-center">
        
        <?php if (isset($error)): ?>
            <div class="text-white text-center">
                <i class="ph ph-warning-circle text-5xl text-red-500 mb-2"></i>
                <p class="text-lg"><?php echo $error; ?></p>
            </div>
        
        <?php elseif ($fileExt == 'pdf'): ?>
            <iframe src="<?php echo htmlspecialchars($filePath); ?>" class="w-full h-full border-none"></iframe>
            
        <?php elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <div class="p-8 overflow-auto w-full h-full flex items-center justify-center">
                <img src="<?php echo htmlspecialchars($filePath); ?>" class="max-w-full max-h-full rounded shadow-2xl object-contain">
            </div>

        <?php else: ?>
            <!-- Fallback for unsupported formats -->
            <div class="bg-white p-8 rounded-xl shadow-2xl text-center max-w-sm mx-4">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ph ph-file-text text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Preview Not Available</h3>
                <p class="text-gray-500 text-sm mb-6">
                    This file format (.<span class="uppercase"><?php echo $fileExt; ?></span>) cannot be previewed directly in the browser.
                </p>
                <a href="<?php echo htmlspecialchars($filePath); ?>" download class="block w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition shadow-md">
                    Download File to View
                </a>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
