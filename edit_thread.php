<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role_id'])) {
    echo "Role ID not set in session.";
    exit();
}

$role_id = $_SESSION['role_id'];       

$stmt = $conn->prepare("SELECT RenameThread FROM roletable WHERE ID = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$stmt->bind_result($userCanEdit);
$stmt->fetch();
$stmt->close();

if ($userCanEdit && $_SERVER["REQUEST_METHOD"] == "POST") {
    $threadName = $_POST['threadName'];
    $thread_id = $_POST['thread_id'];
    $stmt = $conn->prepare("UPDATE threadtable SET Name = ? WHERE ID = ?");
    $stmt->bind_param("si", $threadName, $thread_id);
    $stmt->execute();
    header("Location: index.php");
    exit();
} else {
    alert("You do not have permission to edit threads or invalid request.");
}
?>
