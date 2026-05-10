<?php
// Run this script ONCE to add the payment_method column
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDB();
    
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT 'cod' AFTER notes");
        echo "✅ Added 'payment_method' column to orders table.\n";
    } else {
        echo "ℹ️ 'payment_method' column already exists.\n";
    }
    
    echo "Migration complete!\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}