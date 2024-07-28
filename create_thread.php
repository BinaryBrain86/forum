<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $threadName = $_POST['threadName'];
    $stmt = $conn->prepare("INSERT INTO threadtable (Name) VALUES (?)");
    $stmt->bind_param("s", $threadName);
    $stmt->execute();
    header("Location: index.php");
    exit();
}
?>
