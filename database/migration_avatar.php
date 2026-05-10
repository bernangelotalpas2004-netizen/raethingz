<?php

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDB();

    // Add profile_pic column to users table
    $pdo->exec("ALTER TABLE users
        ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL
        AFTER address");

    echo "✅ Migration successful: added `profile_pic` column to `users` table.\n";
} catch (PDOException $e) {
    // Ignore "Duplicate column" error if already migrated
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️  Column `profile_pic` already exists. Nothing to do.\n";
    } else {
        echo "❌ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}