<?php
session_start();
include 'db.php';

if ($_SESSION['role_id'] == 1 && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $user_id = $_POST['user_id'];
    $new_role_id = $_POST['role_id'];
    $stmt = $conn->prepare("UPDATE usertable SET Role_ID = ? WHERE ID = ?");
    $stmt->bind_param("ii", $new_role_id, $user_id);
    $stmt->execute();
    echo "User role updated successfully!";
}
?>
