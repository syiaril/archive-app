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
$message = '';
$msg_type = '';

// Fetch Document Data
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Document not found.";
    exit();
}

$document = $result->fetch_assoc();
$stmt->close();

// Get Categories
$cats_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Handle Form Submission
// Preserve filters
$redirectParams = $_GET;
unset($redirectParams['id']); // Remove ID as it's not a filter

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $doc_date = $_POST['doc_date'];
    
    // Retrieve preserved filters from POST
    $currentFilters = isset($_POST['filters']) ? $_POST['filters'] : [];

    if (empty($title) || empty($category_id) || empty($doc_date)) {
        $message = "Please fill in all required fields.";
        $msg_type = "red";
    } else {
        // Prepare SQL for Metadata Update
        $sql = "UPDATE documents SET category_id=?, title=?, description=?, doc_date=?";
        $params = [$category_id, $title, $description, $doc_date];
        $types = "isss";

        // Handle File Replacement
        if (isset($_FILES['document']) && $_FILES['document']['error'] === 0) {
            $file = $_FILES['document'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            $uploadDir = 'assets/uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $destination)) {
                // Delete old file
                if (file_exists($document['file_path'])) {
                    unlink($document['file_path']);
                }

                // Add to SQL
                $sql .= ", file_path=?, file_type=?";
                $params[] = $destination;
                $params[] = $fileExt;
                $types .= "ss";
            } else {
                $message = "Failed to upload new file.";
                $msg_type = "red";
            }
        }

        $sql .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";

        if (empty($message) || $msg_type == 'green') {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                // Build Redirect URL with preserved filters
                $currentFilters['updated'] = 1;
                $qry = http_build_query($currentFilters);
                header("Location: dashboard.php?" . $qry);
                exit();
            } else {
                $message = "Database Error: " . $stmt->error;
                $msg_type = "red";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document - <?php echo htmlspecialchars($document['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-6">

    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl overflow-hidden">
        <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="ph ph-pencil-simple text-blue-400"></i>
                Edit Document
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
                <!-- Preserve Filters -->
                <?php foreach($redirectParams as $key => $val): ?>
                    <input type="hidden" name="filters[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars($val); ?>">
                <?php endforeach; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($document['title']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                            <option value="">Select Category</option>
                            <?php while($cat = $cats_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $document['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Date <span class="text-red-500">*</span></label>
                        <input type="date" name="doc_date" value="<?php echo $document['doc_date']; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none"><?php echo htmlspecialchars($document['description']); ?></textarea>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Replace File (Optional)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:bg-gray-50 transition cursor-pointer relative" id="dropzone">
                            <div class="space-y-1 text-center">
                                <i class="ph ph-arrow-clockwise text-4xl text-gray-400"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Upload a new file</span>
                                        <input id="file-upload" name="document" type="file" class="sr-only" onchange="updateFileName(this)">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">Leaving this blank keeps the current file.</p>
                                <p id="filename-display" class="text-sm font-semibold text-gray-800 mt-2">
                                    Current: <?php echo basename($document['file_path']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                    <a href="dashboard.php" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-medium">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-md">
                        Update Document
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
                display.textContent = "Current: <?php echo basename($document['file_path']); ?>";
            }
        }
    </script>

</body>
</html>
