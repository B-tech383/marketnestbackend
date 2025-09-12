<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== VENDORS TABLE STRUCTURE ===\n";
    $stmt = $db->query('DESCRIBE vendors');
    foreach($stmt as $row) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
