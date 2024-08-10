<?php

// Initialize user-related variables
$userID = null;
$roleID = null;
$userName = null;

// If the user is logged in, retrieve their details from the session
if (isset($_SESSION['username'])):
    $userID = $_SESSION['user_id'];
    $roleID = $_SESSION['role_id'];
    $userName = $_SESSION['username'];
endif;

// Set default permissions for the user's role
$roleName = false;
$userCanDelete = false;
$userCanEdit = false;
$userCanDeleteMessages = false;
$userCanDeleteUser = false;

// If the role ID is set, fetch the corresponding permissions from the database
if (isset($roleID)) {
    $getPermissionStmt = $conn->prepare("
        SELECT Name, DeleteThread, RenameThread, DeleteMessages, DeleteUser 
        FROM roletable 
        WHERE ID = ?
    ");
    $getPermissionStmt->bind_param("i", $roleID);
    $getPermissionStmt->execute();
    $getPermissionStmt->bind_result(
        $roleName, 
        $userCanDelete, 
        $userCanEdit, 
        $userCanDeleteMessages, 
        $userCanDeleteUser
    );
    $getPermissionStmt->fetch();
    $getPermissionStmt->close();
}

// If the user ID is set, count their unread personal messages
if (isset($userID)):
    $getUnreadMessageCountStmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM personalMessageTable 
        WHERE User_ID_Receiver = ? AND `Read` = 0
    ");
    $getUnreadMessageCountStmt->bind_param("i", $userID);
    $getUnreadMessageCountStmt->execute();
    $getUnreadMessageCountStmt->bind_result($unreadCount);
    $getUnreadMessageCountStmt->fetch();
    $getUnreadMessageCountStmt->close();
endif;

?>
