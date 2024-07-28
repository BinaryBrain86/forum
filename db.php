<?php
$servername = "localhost";
$username = "root"; // Ersetzen Sie dies durch Ihren MySQL-Benutzernamen
$password = ""; // Ersetzen Sie dies durch Ihr MySQL-Passwort
$dbname = "forum";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>
