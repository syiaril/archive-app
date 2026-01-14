<?php
require_once 'config/database.php';

$updates = [
    'Realisasi' => 'ph-chart-line-up',
    'BM' => 'ph-buildings',           // Belanja Modal -> Assets/Buildings
    'BBM' => 'ph-gas-pump',           // Bahan Bakar -> Gas Pump
    'BANJAS' => 'ph-package',         // Barang Jasa -> Package
    'Pemeliharaan' => 'ph-wrench'     // Maintenance -> Wrench
];

foreach ($updates as $name => $icon) {
    // Try update with wildcard matching if exact name fails
    $stmt = $conn->prepare("UPDATE categories SET icon = ? WHERE name LIKE ?");
    $searchTerm = "%$name%";
    $stmt->bind_param("ss", $icon, $searchTerm);
    $stmt->execute();
    
    if ($conn->affected_rows > 0) {
        echo "Updated '$name' (or match) to '$icon'.<br>";
    } else {
        echo "No match found for '$name', skipping.<br>";
    }
}

echo "Icon update complete.";
?>
