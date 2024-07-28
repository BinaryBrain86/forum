<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] == 1 && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    echo "User deleted successfully!";
}
?>
