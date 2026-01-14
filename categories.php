<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$msg_type = '';

// Handle Add Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        // Generate Slug
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        $slug = trim($slug, '-');

        // Check if exists
        $check = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
        $check->bind_param("s", $slug);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Category already exists.";
            $msg_type = "red";
        } else {
            $icon = trim($_POST['icon']);
            if(empty($icon)) $icon = 'ph-folder';

            $stmt = $conn->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $slug, $icon);
            if ($stmt->execute()) {
                $message = "Category added successfully!";
                $msg_type = "green";
            } else {
                $message = "Error adding category.";
                $msg_type = "red";
            }
        }
    } else {
        $message = "Category name is required.";
        $msg_type = "red";
    }
}

// Handle Delete Category
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    
    // 1. Get files
    $files = $conn->query("SELECT file_path FROM documents WHERE category_id = $del_id");
    while($f = $files->fetch_assoc()) {
        if(file_exists($f['file_path'])) unlink($f['file_path']);
    }

    // 2. Delete Category (DB will cascade delete doc rows)
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $message = "Category and its documents deleted.";
        $msg_type = "green";
    } else {
        $message = "Error deleting category.";
        $msg_type = "red";
    }
}

// Fetch Categories
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM documents d WHERE d.category_id = c.id) as doc_count FROM categories c ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Nandar Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-6">

    <div class="bg-white rounded-xl shadow-lg w-full max-w-4xl overflow-hidden flex flex-col md:flex-row h-[600px]">
        
        <!-- Sidebar / Navigation -->
        <div class="bg-slate-900 w-full md:w-64 p-6 flex flex-col justify-between text-white shrink-0">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2 mb-8">
                    <i class="ph ph-list-dashes text-blue-400"></i> Categories
                </h1>
                <p class="text-slate-400 text-sm mb-4">Manage document categories here. Be careful when deleting, as it will also delete associated documents.</p>
            </div>
            <a href="dashboard.php" class="flex items-center gap-2 text-slate-300 hover:text-white transition group">
                <i class="ph ph-arrow-left group-hover:-translate-x-1 transition-transform"></i> Back to Dashboard
            </a>
        </div>

        <!-- Content -->
        <div class="flex-1 p-8 overflow-hidden flex flex-col">
            
            <?php if ($message): ?>
                <div class="bg-<?php echo $msg_type; ?>-100 border border-<?php echo $msg_type; ?>-400 text-<?php echo $msg_type; ?>-700 px-4 py-3 rounded relative mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Add Form -->
            <form action="" method="POST" class="flex gap-4 mb-8">
                <input type="hidden" name="action" value="add">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">New Category Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="e.g. Surat Masuk">
                </div>
                <div class="w-48">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Icon Class (<a href="https://phosphoricons.com" target="_blank" class="text-blue-500 underline">Phosphor</a>)</label>
                    <input type="text" name="icon" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="e.g. ph-star">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium shadow-md transition h-[42px] flex items-center gap-2">
                        <i class="ph ph-plus"></i> Add
                    </button>
                </div>
            </form>

            <!-- List -->
            <div class="flex-1 overflow-auto border rounded-lg border-gray-200">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr class="text-xs font-semibold text-gray-500 uppercase border-b border-gray-200">
                            <th class="p-4 w-16">No</th>
                            <th class="p-4 w-16 text-center">Icon</th>
                            <th class="p-4">Name</th>
                            <th class="p-4">Slug</th>
                            <th class="p-4 text-center">Docs</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $i=1; while($row = $categories->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 text-gray-500 text-sm"><?php echo $i++; ?></td>
                                <td class="p-4 text-center text-xl text-blue-600">
                                    <i class="ph <?php echo htmlspecialchars($row['icon']); ?>"></i>
                                </td>
                                <td class="p-4 font-medium text-gray-800"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="p-4 font-mono text-xs text-gray-500 bg-gray-100 rounded inline-block my-3 ml-4"><?php echo htmlspecialchars($row['slug']); ?></td>
                                <td class="p-4 text-center">
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-bold">
                                        <?php echo $row['doc_count']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="categories.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('WARNING: Deleting this category will PERMANENTLY DELETE ALL <?php echo $row['doc_count']; ?> documents inside it. Continue?');" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded transition" title="Delete Category">
                                        <i class="ph ph-trash text-lg"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

</body>
</html>
