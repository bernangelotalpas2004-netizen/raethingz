<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>PHP Version: " . phpversion() . "</h3>";

// Test 1: Can PHP reach MySQL at all?
$conn = @new mysqli('localhost', 'root', '', '');
if ($conn->connect_error) {
    echo "❌ MySQL (localhost) failed: " . $conn->connect_error . "<br>";
} else {
    echo "✅ MySQL connection works!<br>";
}

// Test 2: Try 127.0.0.1 instead
$conn2 = @new mysqli('127.0.0.1', 'root', '', '');
if ($conn2->connect_error) {
    echo "❌ MySQL (127.0.0.1) failed: " . $conn2->connect_error . "<br>";
} else {
    echo "✅ MySQL via 127.0.0.1 works!<br>";
}

// Test 3: Does the database exist?
$conn3 = @new mysqli('127.0.0.1', 'root', '', 'raethingz');
if ($conn3->connect_error) {
    echo "❌ Database 'raethingz' error: " . $conn3->connect_error . "<br>";
} else {
    $r = $conn3->query("SELECT COUNT(*) as c FROM products");
    $row = $r->fetch_assoc();
    echo "✅ Database exists! Products found: " . $row['c'] . "<br>";
}

// Test 4: List all databases
$conn4 = @new mysqli('127.0.0.1', 'root', '', '');
if (!$conn4->connect_error) {
    $dbs = $conn4->query("SHOW DATABASES");
    echo "<br><strong>All databases on this MySQL:</strong><br>";
    while ($db = $dbs->fetch_row()) {
        echo "— " . $db[0] . "<br>";
    }
}
?>