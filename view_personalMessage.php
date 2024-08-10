<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

// Check if the user is logged in by verifying the presence of the 'user_id' session variable.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to the login page if the user is not logged in.
    exit; // Stop executing the script.
}

// Check if the required parameters 'user1' and 'user2' are set in the query string.
if (!isset($_GET['user1']) || !isset($_GET['user2'])) {
    echo "Not a valid request."; // Display an error message if parameters are missing.
    exit; // Stop executing the script.
}

$user1 = $_GET['user1'];
$user2 = $_GET['user2'];

// Prepare a query to update the "Read" status for all messages in this conversation where the current user is the receiver.
$updateStmt = $conn->prepare("
    UPDATE personalMessageTable 
    SET `Read` = 1 
    WHERE User_ID_Receiver = ? 
    AND ((User_ID_Sender = ? AND User_ID_Receiver = ?) 
        OR (User_ID_Sender = ? AND User_ID_Receiver = ?))
");
$updateStmt->bind_param("iiiii", $userID, $user1, $user2, $user2, $user1);
$updateStmt->execute(); // Execute the update statement.
$updateStmt->close(); // Close the statement.

// Prepare a query to fetch all messages between the two users, ordered by date.
$pm_stmt = $conn->prepare("
    SELECT * 
    FROM personalMessageTable 
    WHERE (User_ID_Sender = ? AND User_ID_Receiver = ?) 
       OR (User_ID_Sender = ? AND User_ID_Receiver = ?)
    ORDER BY Date_Time ASC
");
$pm_stmt->bind_param("iiii", $user1, $user2, $user2, $user1);
$pm_stmt->execute(); // Execute the query.
$pm_stmt->bind_result($pm_id, $pm_user_id_sender, $pm_username_sender, $pm_user_id_receiver, $pm_user_username_receiver, $pm_message, $pm_date_time, $pm_read);

// Store messages in an array.
$pms = [];
while ($pm_stmt->fetch()) {
    $pms[] = [
        'ID' => $pm_id,
        'User_ID_Sender' => $pm_user_id_sender,
        'UserName_Sender' => $pm_username_sender,
        'User_ID_Receiver' => $pm_user_id_receiver,
        'UserName_Receiver' => $pm_user_username_receiver,
        'Message' => $pm_message,
        'Date_Time' => $pm_date_time,
        'Read' => $pm_read
    ];
}
$pm_stmt->close(); // Close the statement.

// Get the name of the conversation partner for the page title.
$getSendersNameStmt = $conn->prepare("SELECT UserName FROM usertable WHERE ID = ?");
if($userID == $user1):
    $getSendersNameStmt->bind_param("i", $user2); // Bind parameter if current user is 'user1'.
else:
    $getSendersNameStmt->bind_param("i", $user1); // Bind parameter if current user is 'user2'.
endif;
$getSendersNameStmt->execute(); // Execute the query.
$getSendersNameStmt->bind_result($ConversationUserName);
$getSendersNameStmt->fetch(); // Fetch the result.
$getSendersNameStmt->close(); // Close the statement.
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Personal conversation</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="quill/quill.snow.css">
    <link rel="stylesheet" href="quill/atom-one-dark.min.css"/>
    <link rel="stylesheet" href="quill/katex.min.css" />
    
    <script src="quill/highlight.min.js"></script>
    <script src="quill/quill.js"></script>
    <script src="quill/katex.min.js"></script>
   
    <script>
        // Function to handle form submission and update hidden input with the editor's content.
        function submitForm() {
            const message = document.querySelector('input[name=message]');
            message.value = quill.root.innerHTML; // Set the hidden input with the editor's content.
            return true; // Allow the form to submit.
        }
    </script>

</head>
<body>
    <header>
        <!-- Display the page header with the conversation partner's name -->
        <h1>Personal conversation with <?php echo (mb_strlen(trim($ConversationUserName)) === 0) ? " a deleted user" : htmlspecialchars($ConversationUserName); ?></h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <div class="messages">
            <?php foreach ($pms as $pm): 
                // Loop through each message 
                // Fetch current user information
                $userPic = null;
                $userExists = false;
                // Check if the username was deleted
                $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE ID = ?");
                $stmt->bind_param("i", $pm['User_ID_Sender']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
                if ($count > 0) {
                    $userExists = true;
                    $stmt = $conn->prepare("SELECT Pic FROM usertable WHERE ID = ?");
                    $stmt->bind_param("i", $pm['User_ID_Sender']);
                    $stmt->execute();
                    $stmt->bind_result($userPic);
                    $stmt->fetch();
                    $stmt->close();     
                }
            ?>
                <div class="message">
                    <div class="message-info">
                        <?php
                            // Display the sender's profile picture if available
                            if ($pm['User_ID_Sender'] == $userID):
                                echo "<a href=\"account.php\">";
                            endif;
                            
                            if ($userPic):
                                echo "<img src=\"data:image/jpeg;base64,"; echo base64_encode($userPic); echo"\" alt=\"Profile Picture\">";
                            else:
                                echo "<img src=\"resources\\frame.png\">";
                            endif;
                            
                            if ($pm['User_ID_Sender'] == $userID):
                                echo "</a>";
                            endif;
                        ?>
                        <div class="message-info-name"><?php echo htmlspecialchars($pm['UserName_Sender']); ?></div>
                        <div class="message-info-date"><?php echo $pm['Date_Time']; ?></div>
                        <?php if (!$userExists): ?>
                            <div class="message-info-userDeleted">User deleted</div>
                        <?php endif; ?>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br($pm['Message']); // Display the message content, converting newlines to <br> tags ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="new-message">
            <h2>Answer</h2>
            <div id="toolbar-container">
                <!-- Toolbar for rich text editor -->
                <span class="ql-formats">
                    <select class="ql-font"></select>
                    <select class="ql-size"></select>
                </span>
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>
                </span>
                <span class="ql-formats">
                    <select class="ql-color"></select>
                    <select class="ql-background"></select>
                </span>
                <span class="ql-formats">
                    <button class="ql-script" value="sub"></button>
                    <button class="ql-script" value="super"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-header" value="1"></button>
                    <button class="ql-header" value="2"></button>
                    <button class="ql-blockquote"></button>
                    <button class="ql-code-block"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-indent" value="-1"></button>
                    <button class="ql-indent" value="+1"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-direction" value="rtl"></button>
                    <select class="ql-align"></select>
                </span>
                <span class="ql-formats">
                    <button class="ql-link"></button>
                    <button class="ql-image"></button>
                    <button class="ql-video"></button>
                    <button class="ql-formula"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-clean"></button>
                </span>
            </div>
            <div id="editor" style="height: 200px"></div>
            <!-- Form to send a new message -->
            <form action="send_personalMessage.php" method="post" class="pm-form header-content" onsubmit="return submitForm()">
                <input type="hidden" name="receiver_id" value="<?php echo ($userID == $user1) ? $user2 : $user1; ?>"> <!-- Set the receiver ID -->
                <input type="hidden" id="message" name="message"> <!-- Hidden input to hold the message content -->
                <input type="hidden" id="location_path" name="location_path" value="view_personalMessage.php?user1=<?php echo $user1; ?>&user2=<?php echo $user2; ?>"> <!-- Hidden input for redirect path after sending the message -->
                <button type="submit">Send</button>
            </form>
        </div>
        
        <!-- Initialize Quill editor for rich text input -->
        <script>
            const quill = new Quill('#editor', {
                modules: {
                    syntax: true,
                    toolbar: '#toolbar-container',
                },
                placeholder: 'Write a message...',
                theme: 'snow',
            });
        </script>
    </main>
</body>
</html>

<?php
$conn->close(); // Close the database connection.
?>
