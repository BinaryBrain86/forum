<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION['user_id'];
    $userName = $_SESSION['username'];
    $receiverID = $_POST['receiver_id'];
    $message = $_POST['message'];
    $locationPath = $_POST['location_path'];

    // Get receiver's username
    $stmt = $conn->prepare("SELECT username FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $receiverID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "Empfänger nicht gefunden.";
        exit;
    }

    $receiverData = $result->fetch_assoc();
    $receiverName = $receiverData['username'];

    // Insert the personal message into the database
    $stmt = $conn->prepare("INSERT INTO personalMessageTable (User_ID_Sender, UserName_Sender, User_ID_Receiver, UserName_Receiver, Message, `Read`) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isiss", $userID, $userName, $receiverID, $receiverName, $message);

    if ($stmt->execute()) {
        echo "Nachricht erfolgreich gesendet.";
        header('Location: ' . $locationPath);
    } else {
        echo "Fehler beim Senden der Nachricht.";
    }

    $stmt->close();
    $conn->close();
}
?>
