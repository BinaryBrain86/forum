<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id']) && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'];
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usertable SET UserName = ?, Name = ?, FirstName = ?, EMail = ? WHERE ID = ?");
    $stmt->bind_param("ssssi", $username, $name, $firstname, $email, $user_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE passwordtable SET PW_Hash = ? WHERE ID = (SELECT PW_ID FROM usertable WHERE ID = ?)");
    $stmt->bind_param("si", $password, $user_id);
    $stmt->execute();

    echo "Account updated successfully!";
}
?>
