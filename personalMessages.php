<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

// Check if the user is logged in by verifying the presence of the 'user_id' session variable.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to the login page if the user is not logged in.
    exit; // Stop executing the script.
}

// Prepare a query to fetch the latest message from each conversation along with the unread message count.
$stmt = $conn->prepare("
    -- Select the latest message for each conversation and the unread message count
    SELECT pmt.*, 
           COALESCE(unread_counts.unreadCount, 0) AS unreadCount
    FROM personalMessageTable pmt

    -- Find the latest message in each conversation
    INNER JOIN (
        SELECT LEAST(User_ID_Receiver, User_ID_Sender) AS user1, 
               GREATEST(User_ID_Receiver, User_ID_Sender) AS user2, 
               MAX(ID) AS max_id
        FROM personalMessageTable
        WHERE User_ID_Receiver = ? OR User_ID_Sender = ?

        -- Group by the unique user pair to ensure we get the latest message for each conversation
        GROUP BY user1, user2
    ) grouped_pmt 
     
    -- Join on the latest message ID to get the most recent message for each conversation
    ON pmt.ID = grouped_pmt.max_id

    -- Left join to get the count of unread messages for each conversation
    LEFT JOIN (
        SELECT LEAST(User_ID_Receiver, User_ID_Sender) AS user1, 
               GREATEST(User_ID_Receiver, User_ID_Sender) AS user2, 
               COUNT(*) AS unreadCount
        FROM personalMessageTable
        WHERE User_ID_Receiver = ? AND `Read` = 0
        GROUP BY user1, user2
    ) unread_counts 
     
    -- Join on user pairs to associate the unread message count with the latest message
    ON unread_counts.user1 = grouped_pmt.user1 AND unread_counts.user2 = grouped_pmt.user2

    -- Order by the date and time of the messages in descending order
    ORDER BY pmt.Date_Time DESC
");

// Bind user ID to the prepared statement and execute the query.
$stmt->bind_param("iii", $userID, $userID, $userID);
$stmt->execute();
$result = $stmt->get_result(); // Fetch the results from the executed query.

// Fetch all users from the database for the dropdown menu, excluding the current user.
$usersResult = $conn->query("SELECT ID, username FROM usertable WHERE ID != $userID ORDER BY username ASC");

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Personal messages</title>
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
        <h1>Personal messages</h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <h2>Personal conversations</h2>
        <div class="messages">
            <?php while ($message = $result->fetch_assoc()): // Loop through each conversation ?>
                <div class="pm-conversation">
                    <div class="pm-info">  
                        <!-- Display the conversation partner and the last message details -->
                        <div>With <?php echo ($userID == $message['User_ID_Receiver']) ? $message['UserName_Sender'] : $message['UserName_Receiver'];?></div>   
                        <div class="pm-info-name">Last by <?php echo htmlspecialchars($message['UserName_Sender']); ?> on</div>
                        <div class="pm-info-date"><?php echo $message['Date_Time']; ?></div>
                    </div>
                    <div class="message-content">
                        <!-- Link to view the full conversation -->
                        <a href="view_personalMessage.php?user1=<?php echo $message['User_ID_Sender']; ?>&user2=<?php echo $message['User_ID_Receiver']; ?>" class="pm-content ellipsis">
                            <?php echo nl2br($message['Message']); // Display the last message, converting newlines to <br> tags ?>
                        </a>
                    </div>
                    <div class="pm-read-info">
                    <?php if ($message['unreadCount'] > 0 && $message['User_ID_Receiver'] == $userID): ?>
                        <!-- Display unread count if there are unread messages -->
                        <span class="unread-count"><?php echo $message['unreadCount'] ?></span>
                    <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="new-message">
            <h2>Write new personal message</h2>
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
            <!-- Form to send a new personal message -->
            <form action="send_personalMessage.php" method="post" class="pm-form header-content" onsubmit="return submitForm()">
                <div>
                    <label for="receiver">Receiver:</label>
                    <div class="styled-select">
                        <!-- Dropdown to select the recipient of the message -->
                        <select id="receiver" name="receiver_id" required>
                            <?php while ($user = $usersResult->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($user['ID']); ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <input type="hidden" id="message" name="message"> <!-- Hidden input to hold the message content -->
                    <input type="hidden" id="location_path" name="location_path" value="personalMessages.php"> <!-- Hidden input for redirect path after sending the message -->
                    <button type="submit">Send</button>
                </div>
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
$stmt->close(); // Close the prepared statement.
$conn->close(); // Close the database connection.
?>
