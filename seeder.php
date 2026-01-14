<?php
require_once 'config/database.php';

echo "Starting Seeder...\n";

// 1. Create Dummy User
$username = 'demo';
$password = 'password';
$fullname = 'Demo User';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if user exists
$check = $conn->query("SELECT id FROM users WHERE username = '$username'");
if ($check->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, fullname) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hash, $fullname);
    $stmt->execute();
    $userId = $conn->insert_id;
    echo "Created user: $username / $password\n";
} else {
    $row = $check->fetch_assoc();
    $userId = $row['id'];
    echo "User $username already exists (ID: $userId)\n";
}

// 2. Helper to create files
function createDummyFile($ext, $prefix) {
    $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
    $path = 'assets/uploads/' . $filename;
    
    // Ensure dir
    if (!is_dir('assets/uploads/')) mkdir('assets/uploads/', 0777, true);

    if ($ext == 'pdf') {
        // Minimal PDF Header
        $content = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n3 0 obj\n<</Type/Page/MediaBox[0 0 595 842]>>\nendobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000060 00000 n\n0000000111 00000 n\ntrailer\n<</Size 4/Root 1 0 R>>\nstartxref\n162\n%%EOF";
    } elseif ($ext == 'png') {
        // 1x1 Red Pixel
        $content = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==");
    } else {
        $content = "This is a dummy file content for testing.";
    }

    file_put_contents($path, $content);
    return [$path, $filename]; // return relative path
}

// 3. Generate Documents
$years = range(2017, 2030);
$categories = [
    1 => 'Anggaran', 
    2 => 'Realisasi Anggaran', 
    3 => 'SPJ', 
    4 => 'BKU'
];
$types = ['pdf', 'png', 'xlsx', 'docx'];

echo "Generating documents...\n";

for ($i = 0; $i < 20; $i++) {
    $catId = array_rand($categories);
    $catName = $categories[$catId];
    $year = $years[array_rand($years)];
    $month = rand(1, 12);
    $day = rand(1, 28);
    $date = "$year-$month-$day";
    
    $type = $types[array_rand($types)];
    
    // Create physical file
    list($filePath, $fileName) = createDummyFile($type, "dummy");
    
    $title = "Dokumen $catName $year - Sample $i";
    $desc = "This is a generated dummy document for testing purposes.";
    
    $stmt = $conn->prepare("INSERT INTO documents (user_id, category_id, title, description, doc_date, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $userId, $catId, $title, $desc, $date, $filePath, $type);
    $stmt->execute();
}

echo "Successfully generated 20 dummy documents.\n";
echo "Done!";
?>
