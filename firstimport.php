<?php
$host = "localhost"; // Host name
$username = "root"; // MySQL username
$password = ""; // MySQL password
$db_name = "railres"; // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>