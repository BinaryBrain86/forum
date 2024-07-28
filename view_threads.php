<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id']) && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $thread_id = $_POST['thread_id'];
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    $stmt = $conn->prepare("INSERT INTO messagetable (User_ID, UserName, Thread_ID, Message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $username, $thread_id, $message);
    $stmt->execute();
    echo "Message posted successfully!";
}
?>
