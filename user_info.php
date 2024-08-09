<?php

$userID = null;
$roleID = null;
$userName = null;

if (isset($_SESSION['username'])):
    $userID = $_SESSION['user_id'];
    $roleID = $_SESSION['role_id'];
    $userName = $_SESSION['username'];
endif;

$roleName = false;
$userCanDelete = false;
$userCanEdit = false;
$userCanDeleteMessages = false;
$userCanDeleteUser = false;

if (isset($roleID)) {
    $getPermissionStmt = $conn->prepare("SELECT Name, DeleteThread, RenameThread, DeleteMessages, DeleteUser FROM roletable WHERE ID = ?");
    $getPermissionStmt->bind_param("i", $roleID);
    $getPermissionStmt->execute();
    $getPermissionStmt->bind_result($roleName, $userCanDelete, $userCanEdit, $userCanDeleteMessages, $userCanDeleteUser);
    $getPermissionStmt->fetch();
    $getPermissionStmt->close();
}

if (isset($userID)):
    // Count unread personal messages
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