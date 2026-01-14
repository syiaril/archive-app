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
    <!-- docx-preview (Better Layout) -->
    <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.min.js"></script>
    <!-- SheetJS for Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        /* Custom Styles for Preview Content */
        .preview-content { background: white; width: 100%; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        
        /* specific for xlsx to keep it scrolling nicely */
        #excel-container { padding: 2rem; overflow: auto; max-height: 85vh; }
        #excel-container table { border-collapse: collapse; width: 100%; }
        #excel-container td, #excel-container th { border: 1px solid #e2e8f0; padding: 8px; font-size: 0.875rem; }
        #excel-container th { background-color: #f8fafc; font-weight: 600; }

        /* Custom DOCX styles to make pages look like paper */
        #docx-container { background: transparent !important; padding-bottom: 3rem; }
        #docx-container section, .docx_section { 
            background: white !important; 
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1) !important; 
            margin-bottom: 2rem !important; 
            color: black !important;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm z-10 shrink-0">
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
    <main class="flex-1 overflow-auto bg-slate-200 p-4 md:p-8 flex justify-center">
        
        <?php if (isset($error)): ?>
            <div class="text-center mt-20">
                <i class="ph ph-warning-circle text-5xl text-red-500 mb-2"></i>
                <p class="text-lg text-gray-700"><?php echo $error; ?></p>
            </div>
        
        <?php elseif ($fileExt == 'pdf'): ?>
            <iframe src="<?php echo htmlspecialchars($filePath); ?>" class="w-full h-full border-none rounded-lg shadow-lg bg-white"></iframe>
            
        <?php elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <div class="w-full h-full flex items-center justify-center">
                <img src="<?php echo htmlspecialchars($filePath); ?>" class="max-w-full max-h-full rounded-lg shadow-lg object-contain bg-white">
            </div>

        <?php elseif ($fileExt == 'docx'): ?>
            <!-- DOCX Container -->
            <div id="docx-wrapper" class="w-full flex justify-center pb-20">
                <!-- Removed fixed white background from container, let pages handle it -->
                <div id="docx-container" class="w-full max-w-[900px]">
                     <div class="flex items-center justify-center h-40 text-gray-500 gap-2">
                        <i class="ph ph-spinner animate-spin text-2xl"></i> Loading Document...
                    </div>
                </div>
            </div>
            <script>
                fetch('<?php echo $filePath; ?>')
                    .then(response => response.blob())
                    .then(blob => {
                        const container = document.getElementById('docx-container');
                        container.innerHTML = ''; // Clear loading spinner
                        
                        // docx-preview render options
                        const options = {
                            className: "docx_viewer_custom", 
                            inWrapper: false, 
                            ignoreWidth: false, 
                            ignoreHeight: false,
                            ignoreFonts: false,
                            breakPages: true, 
                            trimXmlDeclaration: true,
                            useBase64URL: true,
                            renderChanges: false,
                            debug: false,
                        };
                        
                        docx.renderAsync(blob, container, null, options)
                            .then(function() {
                                console.log("docx: finished");
                            });
                    })
                    .catch(err => {
                        document.getElementById('docx-container').innerHTML = 
                            '<div class="flex flex-col items-center justify-center h-full text-red-500 gap-2"><i class="ph ph-warning text-3xl"></i><p>Error loading document preview.</p></div>';
                        console.error(err);
                    });
            </script>

        <?php elseif (in_array($fileExt, ['xlsx', 'xls'])): ?>
            <!-- Excel Container -->
            <div id="excel-container" class="preview-content rounded-lg">
                <div class="flex items-center justify-center h-40 text-gray-400 gap-2">
                    <i class="ph ph-spinner animate-spin text-2xl"></i> Loading Spreadsheet...
                </div>
            </div>
            <script>
                fetch('<?php echo $filePath; ?>')
                    .then(response => response.arrayBuffer())
                    .then(arrayBuffer => {
                        const workbook = XLSX.read(arrayBuffer, {type: 'array'});
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const html = XLSX.utils.sheet_to_html(worksheet);
                        document.getElementById('excel-container').innerHTML = html;
                    })
                    .catch(err => {
                        document.getElementById('excel-container').innerHTML = 
                            '<p class="text-red-500 text-center">Error loading spreadsheet preview.</p>';
                        console.error(err);
                    });
            </script>

        <?php else: ?>
            <!-- Fallback for unsupported formats -->
            <div class="bg-white p-8 rounded-xl shadow-2xl text-center max-w-sm mt-20">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ph ph-file-text text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">Preview Not Available</h3>
                <p class="text-gray-500 text-sm mb-6">
                    This file format (.<span class="uppercase"><?php echo $fileExt; ?></span>) cannot be previewed directly.
                </p>
                <a href="<?php echo htmlspecialchars($filePath); ?>" download class="block w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition shadow-md">
                    Download File to View
                </a>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
