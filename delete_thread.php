<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role_id'])) {
    echo "Role ID not set in session.";
    exit();
}

$role_id = $_SESSION['role_id'];       

$stmt = $conn->prepare("SELECT DeleteThread FROM roletable WHERE ID = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$stmt->bind_result($userCanDelete);
$stmt->fetch();
$stmt->close();

if ($userCanDelete && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $thread_id = $_POST['thread_id'];
    $delete_stmt = $conn->prepare("DELETE FROM threadtable WHERE ID = ?");
    $delete_stmt->bind_param("i", $thread_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: index.php");
    exit();
} else {
    alert("You do not have permission to delete threads or invalid request.");
}
?>
