<?php
// Save as test_db.php in your project root
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Debugging</h2>";

// Test 1: Database connection
$conn = new mysqli('localhost', 'root', '', 'clinic');

if ($conn->connect_error) {
    echo "<p style='color:red'>❌ Connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>✅ Database connection successful</p>";

    // Test 2: Check if medical_examinations table exists
    $result = $conn->query("SHOW TABLES LIKE 'medical_examinations'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✅ Table 'medical_examinations' exists</p>";

        // Test 3: Show table structure
        $columns = $conn->query("DESCRIBE medical_examinations");
        echo "<h3>Table Columns:</h3><ul>";
        while ($col = $columns->fetch_assoc()) {
            echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
        }
        echo "</ul>";

        // Test 4: Check if clims_id column exists
        $clims_check = $conn->query("SHOW COLUMNS FROM medical_examinations LIKE 'clims_id'");
        if ($clims_check->num_rows > 0) {
            echo "<p style='color:green'>✅ Column 'clims_id' exists</p>";
        } else {
            echo "<p style='color:red'>❌ Column 'clims_id' does NOT exist. Run: ALTER TABLE medical_examinations ADD COLUMN clims_id VARCHAR(100) UNIQUE</p>";
        }

    } else {
        echo "<p style='color:red'>❌ Table 'medical_examinations' does NOT exist</p>";
        echo "<p>Run this SQL to create the table:</p>";
        echo "<pre>CREATE TABLE medical_examinations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            clims_id VARCHAR(100) UNIQUE,
            full_name VARCHAR(255),
            age_sex VARCHAR(50),
            address TEXT,
            mobile_no VARCHAR(20),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );</pre>";
    }

    $conn->close();
}
?>
