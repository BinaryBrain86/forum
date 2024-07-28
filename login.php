<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT usertable.ID, usertable.UserName, usertable.Role_ID, passwordtable.PW_Hash FROM usertable JOIN passwordtable ON usertable.PW_ID = passwordtable.ID WHERE usertable.UserName = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $user_name, $role_id, $pw_hash);
    $stmt->fetch();

    if (password_verify($password, $pw_hash)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $user_name;
        $_SESSION['role_id'] = $role_id;
        header("Location: index.php");
        exit();
    } else {
        echo "Invalid credentials.";
    }
}
?>
