<?php
session_start();
require_once 'config/database.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Params
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Messages
$message = '';
$msg_type = '';

if (isset($_GET['upload_success'])) {
    $message = "Document uploaded successfully!";
    $msg_type = "green";
} elseif (isset($_GET['deleted'])) {
    $count = isset($_GET['count']) ? $_GET['count'] : '';
    $message = $count ? "$count Document(s) deleted successfully!" : "Document deleted successfully!";
    $msg_type = "green";
} elseif (isset($_GET['updated'])) {
    $message = "Document updated successfully!";
    $msg_type = "green";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'delete_failed') $message = "Failed to delete document.";
    elseif ($_GET['error'] == 'not_found') $message = "Document not found to delete.";
    elseif ($_GET['error'] == 'no_selection') $message = "No documents selected.";
    else $message = "An error occurred.";
    $msg_type = "red";
}

// Get Categories for Sidebar
$cats_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
while($row = $cats_result->fetch_assoc()) {
    $categories[] = $row;
}

// Build Query
$sql = "SELECT d.*, c.name as category_name, c.slug as category_slug 
        FROM documents d 
        JOIN categories c ON d.category_id = c.id 
        WHERE 1=1";

$types = "";
$params = [];

if ($category_slug) {
    $sql .= " AND c.slug = ?";
    $types .= "s";
    $params[] = $category_slug;
}
if ($year) {
    $sql .= " AND YEAR(doc_date) = ?";
    $types .= "s";
    $params[] = $year;
}
if ($month) {
    $sql .= " AND MONTH(doc_date) = ?";
    $types .= "s";
    $params[] = $month;
}
if ($search) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $types .= "ss";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nandar Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons for nice UI icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 transition-all duration-300">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-xl font-bold tracking-wide flex items-center gap-2">
                <i class="ph ph-archive text-blue-400 text-2xl"></i> 
                Nandar Archive
            </h1>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <div class="px-4 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Parameters</div>
            
            <a href="dashboard.php" class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-slate-800 transition <?php echo $category_slug == '' ? 'bg-slate-800 text-blue-400 border-r-4 border-blue-400' : 'text-slate-300'; ?>">
                <i class="ph ph-squares-four text-lg"></i>
                All Documents
            </a>

            <div class="px-4 mt-6 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categories</div>
            
            <?php foreach ($categories as $cat): ?>
                <a href="dashboard.php?category=<?php echo $cat['slug']; ?>" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition group <?php echo ($category_slug == $cat['slug']) ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'; ?>">
                    <i class="ph <?php echo htmlspecialchars($cat['icon']); ?> text-lg"></i>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
            
            <div class="px-4 mt-6 border-t border-slate-700 pt-4">
                <a href="categories.php" class="flex items-center gap-3 px-2 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded transition">
                    <i class="ph ph-gear text-lg"></i>
                    Manage Categories
                </a>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center gap-3 mb-4 px-2">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div>
                    <p class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p class="text-xs text-slate-400">User</p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full py-2 bg-slate-800 hover:bg-red-600/20 hover:text-red-400 text-slate-300 rounded-lg transition text-sm font-medium border border-slate-700 hover:border-red-500/30">
                <i class="ph ph-sign-out"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden bg-gray-50 relative">
        <!-- Top Scroll Shadow -->
        <div class="absolute top-0 left-0 right-0 h-4 bg-gradient-to-b from-gray-200/50 to-transparent pointer-events-none z-10 opactiy-0 transition-opacity"></div>

        <header class="bg-white border-b border-gray-200 p-6 flex items-center justify-between shadow-sm z-20">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    <?php 
                        if ($category_slug) {
                            foreach($categories as $c) {
                                if ($c['slug'] == $category_slug) {
                                    echo htmlspecialchars($c['name']);
                                    break;
                                }
                            }
                        } else {
                            echo "All Documents";
                        }
                    ?>
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage and view your archived files here.</p>
            </div>
            
            <!-- Bulk Actions Button (Hidden by default or disabled) -->
            <button type="submit" form="bulkForm" id="bulkDeleteBtn" onclick="return confirm('Delete selected documents? This action cannot be undone.')" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hidden">
                <i class="ph ph-trash text-lg"></i> Delete Selected
            </button>
            <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition flex items-center gap-2">
                <i class="ph ph-plus-circle text-lg"></i> Upload New
            </a>
        </header>

        <!-- Filters & Search -->
        <div class="p-6 pb-2">
            <?php if (!empty($message)): ?>
                <div class="bg-<?php echo $msg_type; ?>-100 border border-<?php echo $msg_type; ?>-400 text-<?php echo $msg_type; ?>-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="GET" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex flex-wrap gap-4 items-end">
                <?php if($category_slug): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                <?php endif; ?>

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                    </div>
                </div>

                <div class="w-32">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Year</label>
                    <select name="year" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none text-sm cursor-pointer">
                        <option value="">All Years</option>
                        <?php 
                        for ($y = 2030; $y >= 2017; $y--) {
                            $selected = ($year == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="w-32">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Month</label>
                    <select name="month" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none text-sm cursor-pointer">
                        <option value="">All Months</option>
                        <?php 
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($month == $m) ? 'selected' : '';
                            $monthName = date("F", mktime(0, 0, 0, $m, 10));
                            echo "<option value='$m' $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-sm font-medium transition shadow-sm h-[38px]">
                    Filter
                </button>
                
                <?php if($search || $year || $month): ?>
                    <a href="dashboard.php<?php echo $category_slug ? '?category=' . $category_slug : ''; ?>" class="px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-medium transition h-[38px] flex items-center">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table List -->
        <div class="flex-1 overflow-auto p-6 pt-2">
            <form action="bulk_delete.php" method="POST" id="bulkForm">
                <!-- Preserve current filters for redirect -->
                <?php if($category_slug): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>"><?php endif; ?>
                <?php if($year): ?><input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>"><?php endif; ?>
                <?php if($month): ?><input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>"><?php endif; ?>
                <?php if($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-10 text-center">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="p-4 w-16 text-center">No</th>
                                <th class="p-4">Document Title</th>
                                <th class="p-4 w-40">Category</th>
                                <th class="p-4 w-32">Date</th>
                                <th class="p-4 w-24 text-center">Type</th>
                                <th class="p-4 w-48 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($result->num_rows > 0): ?>
                                <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-blue-50/50 transition group">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="p-4 text-center text-gray-500 text-sm"><?php echo $i++; ?></td>
                                        <td class="p-4">
                                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <?php if($row['description']): ?>
                                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs"><?php echo htmlspecialchars($row['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">
                                                <?php echo htmlspecialchars($row['category_name']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-sm text-gray-600 font-mono">
                                            <?php echo date('d M Y', strtotime($row['doc_date'])); ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <?php 
                                                // Simple file type badges
                                                $ft = strtolower($row['file_type']);
                                                $color = 'gray';
                                                if ($ft == 'pdf') $color = 'red';
                                                elseif (in_array($ft, ['jpg', 'jpeg', 'png', 'gif'])) $color = 'purple';
                                                elseif (in_array($ft, ['xlsx', 'xls', 'csv'])) $color = 'green';
                                                elseif (in_array($ft, ['doc', 'docx'])) $color = 'blue';
                                            ?>
                                            <span class="uppercase text-xs font-bold text-<?php echo $color; ?>-600"><?php echo $ft; ?></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-80 group-hover:opacity-100 transition">
                                                <!-- Edit -->
                                                <?php
                                                    // Prepare query params for redirect back (reuse logic from delete)
                                                    $currentParams = $_GET;
                                                    unset($currentParams['upload_success'], $currentParams['deleted'], $currentParams['error'], $currentParams['updated']);
                                                    $qry = http_build_query($currentParams);
                                                ?>
                                                <a href="edit.php?id=<?php echo $row['id']; ?>&<?php echo $qry; ?>" class="p-2 text-yellow-600 hover:bg-yellow-100 rounded-lg transition" title="Edit">
                                                    <i class="ph ph-pencil-simple text-lg"></i>
                                                </a>
                                                <!-- View -->
                                                <a href="view.php?id=<?php echo $row['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="View">
                                                    <i class="ph ph-eye text-lg"></i>
                                                </a>
                                                <!-- Download (Direct) -->
                                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition" title="Download">
                                                    <i class="ph ph-download-simple text-lg"></i>
                                                </a>
                                                <!-- Delete -->
                                                <?php
                                                    $currentParams = $_GET;
                                                    unset($currentParams['upload_success'], $currentParams['deleted'], $currentParams['error']);
                                                    $qry = http_build_query($currentParams);
                                                ?>
                                                <a href="delete.php?id=<?php echo $row['id']; ?>&<?php echo $qry; ?>" onclick="return confirm('Are you sure you want to delete this document?');" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition" title="Delete">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="ph ph-folder-open text-4xl mb-3 text-gray-300"></i>
                                            <p>No documents found.</p>
                                            <a href="create.php" class="text-blue-500 hover:underline mt-2 text-sm">Upload one now?</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

    <script>
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const bulkBtn = document.getElementById('bulkDeleteBtn');

        function toggleBulkBtn() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (anyChecked) {
                bulkBtn.classList.remove('hidden');
            } else {
                bulkBtn.classList.add('hidden');
            }
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleBulkBtn();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleBulkBtn);
        });

        // Clear flash message params from URL to prevent showing them again on reload
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            const params = ['upload_success', 'deleted', 'updated', 'error'];
            let changed = false;
            
            params.forEach(p => {
                if(url.searchParams.has(p)) {
                    url.searchParams.delete(p);
                    changed = true;
                }
            });

            if(changed) {
                window.history.replaceState(null, '', url.toString());
            }
        }
    </script>
    </main>

</body>
</html>
