<?php
$servername = "localhost"; // Server name or IP address of the MySQL server
$username = "root"; // Username for the MySQL database
$password = ""; // Password for the MySQL database
$dbname = "forum"; // Name of the database to connect to

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection error: " . $conn->connect_error); // Exit if there is a connection error
}
?>
