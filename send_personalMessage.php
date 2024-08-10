<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.

// Check if the user is logged in by verifying the presence of the 'user_id' session variable.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to the login page if the user is not logged in.
    exit; // Terminate the script to ensure no further code is executed.
}

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Check if the form was submitted using the POST method.
    // Retrieve data from the POST request and session variables.
    $userID = $_SESSION['user_id'];
    $userName = $_SESSION['username'];
    $receiverID = $_POST['receiver_id'];
    $message = $_POST['message'];
    $locationPath = $_POST['location_path'];

    // Fetch the receiver's username from the database.
    $stmt = $conn->prepare("SELECT username FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $receiverID);
    $stmt->execute();
    $result = $stmt->get_result();

    // If no user is found with the provided receiver ID, display an error and exit.
    if ($result->num_rows == 0) {
        echo "Receiver not found.";
        exit;
    }

    // Fetch the receiver's username from the query result.
    $receiverData = $result->fetch_assoc();
    $receiverName = $receiverData['username'];

    // Insert the personal message into the database, marking it as unread (`Read` column is 0).
    $stmt = $conn->prepare("INSERT INTO personalMessageTable (User_ID_Sender, UserName_Sender, User_ID_Receiver, UserName_Receiver, Message, `Read`) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isiss", $userID, $userName, $receiverID, $receiverName, $message);

    // Execute the query and check if the message was sent successfully.
    if ($stmt->execute()) {
        echo "Message sent successfully";
        header('Location: ' . $locationPath); // Redirect to the provided location path after sending the message.
    } else {
        echo "Error while sending the message."; // Display an error message if the query fails.
    }

    // Close the prepared statement and the database connection.
    $stmt->close();
    $conn->close();
}
?>
