<?php
$host = "localhost"; 
$user = "root";      // change if you have different username
$pass = "";          // change if you have password
$db   = "clinic";    // database name

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
