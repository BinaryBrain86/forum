<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $threadName = $_POST['threadName'];
    $userName = $_SESSION['username'];
    $stmt = $conn->prepare("INSERT INTO threadtable (Name, CreatedByUser) VALUES (?,?)");
    $stmt->bind_param("ss", $threadName, $userName);
    $stmt->execute();
    header("Location: index.php");
    exit();
}
?>
