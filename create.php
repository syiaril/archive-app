<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get Categories
$cats_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$message = '';
$msg_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $doc_date = $_POST['doc_date'];
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($category_id) || empty($doc_date) || !isset($_FILES['document'])) {
        $message = "Please fill in all required fields and select a file.";
        $msg_type = "red";
    } else {
        $file = $_FILES['document'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileError === 0) {
            // Random unique name: timestamp_random.ext
            $newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            $uploadDir = 'assets/uploads/';
            
            // Ensure dir exists (already created but safe check)
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $destination)) {
                // Insert into DB
                $stmt = $conn->prepare("INSERT INTO documents (user_id, category_id, title, description, doc_date, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssss", $user_id, $category_id, $title, $description, $doc_date, $destination, $fileExt);

                if ($stmt->execute()) {
                    header("Location: dashboard.php?upload_success=1");
                    exit();
                } else {
                    $message = "Database Error: " . $stmt->error;
                    $msg_type = "red";
                    // Cleanup uploaded file if DB fails
                    unlink($destination);
                }
                $stmt->close();

            } else {
                $message = "Failed to move uploaded file.";
                $msg_type = "red";
            }
        } else {
            $message = "Error uploading file. Error code: " . $fileError;
            $msg_type = "red";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document - Archive App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-6">

    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl overflow-hidden">
        <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="ph ph-upload-simple text-blue-400"></i>
                Upload New Document
            </h1>
            <a href="dashboard.php" class="text-slate-300 hover:text-white transition text-sm flex items-center gap-1">
                <i class="ph ph-x"></i> Cancel
            </a>
        </div>

        <div class="p-8">
            <?php if ($message): ?>
                <div class="bg-<?php echo $msg_type; ?>-100 border border-<?php echo $msg_type; ?>-400 text-<?php echo $msg_type; ?>-700 px-4 py-3 rounded relative mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="e.g. SPJ January 2024">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="">Select Category</option>
                            <?php while($cat = $cats_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Date <span class="text-red-500">*</span></label>
                        <input type="date" name="doc_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Optional details..."></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Attachment <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:bg-gray-50 transition cursor-pointer relative" id="dropzone">
                            <div class="space-y-1 text-center">
                                <i class="ph ph-file-cloud text-4xl text-gray-400"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="document" type="file" class="sr-only" required onchange="updateFileName(this)">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, PNG, JPG, DOCX, XLSX up to 10MB</p>
                                <p id="filename-display" class="text-sm font-semibold text-gray-800 mt-2"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                    <a href="dashboard.php" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-md">
                        Save Document
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('filename-display');
            if (input.files && input.files.length > 0) {
                display.textContent = "Selected: " + input.files[0].name;
            } else {
                display.textContent = "";
            }
        }
    </script>

</body>
</html>
