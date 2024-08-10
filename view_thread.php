<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

// Redirect to index page if thread_id is not provided
if (!isset($_GET['thread_id'])) {
    header("Location: index.php");
    exit();
}

$thread_id = $_GET['thread_id'];

// Process POST requests for message operations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    if (isset($_POST['msg_id'])) { // Handle message deletion
        $msg_id = $_POST['msg_id'];
        $stmt = $conn->prepare("DELETE FROM messagetable WHERE ID = ?");
        $stmt->bind_param("i", $msg_id);
        $stmt->execute();
        $stmt->close();
    } 
    // Handle new message submission
    elseif (isset($_POST['message'])) { 
        $message = $_POST['message'];
        $stmt = $conn->prepare("INSERT INTO messagetable (User_ID, UserName, Thread_ID, Message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $userID, $userName, $thread_id, $message);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect after processing
    header("Location: view_thread.php?thread_id=$thread_id");
    exit();
}

// Fetch thread details
$thread_stmt = $conn->prepare("SELECT Name FROM threadtable WHERE ID = ?");
$thread_stmt->bind_param("i", $thread_id);
$thread_stmt->execute();
$thread_stmt->bind_result($thread_name);
$thread_stmt->fetch();
$thread_stmt->close();

// Fetch messages in the thread
$msg_stmt = $conn->prepare("SELECT ID, User_ID, UserName, Date_Time, Message FROM messagetable WHERE Thread_ID = ? ORDER BY Date_Time ASC");
$msg_stmt->bind_param("i", $thread_id);
$msg_stmt->execute();
$msg_stmt->bind_result($msg_id, $msg_user_id, $msg_user_name, $msg_date_time, $msg_content);

// Store messages in an array
$messages = [];
while ($msg_stmt->fetch()) {
    $messages[] = [
        'id' => $msg_id,
        'user_id' => $msg_user_id,
        'user_name' => $msg_user_name,
        'date_time' => $msg_date_time,
        'content' => $msg_content
    ];
}
$msg_stmt->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <title><?php echo htmlspecialchars($thread_name); ?></title>
    
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="quill/quill.snow.css" >
    <link rel="stylesheet" href="quill/atom-one-dark.min.css"/>
    <link rel="stylesheet" href="quill/katex.min.css" />
    
    <script src="quill/highlight.min.js"></script>
    <script src="quill/quill.js"></script>
    <script src="quill/katex.min.js"></script>

    <script>
        // Open a modal window by setting its display style to 'block'.
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        // Close a modal window by setting its display style to 'none'.
        function closeModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'none';
            }
        }

        // Function to handle form submission and update hidden input with the editor's content.
        function submitForm() {
            const message = document.querySelector('input[name=message]');
            message.value = quill.root.innerHTML; // Set the hidden input with the editor's content.
            return true; // Allow the form to submit.
        }

        // Open a modal for deleting a message.
        function openDeleteModal(mgsId, userOfMessage) {
            document.getElementById('deleteMsgId').value = mgsId;
            document.getElementById('userOfMessage').innerText = userOfMessage;
            document.getElementById('deleteModal').style.display = 'block';
        }
    </script>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($thread_name); ?></h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <div class="messages">
            <?php foreach ($messages as $msg): 
                // Loop through each message of the respective thread
                // Fetch user information for each message
                $userPic = null;
                $userExists = false;
                
                // Check if user exist in database
                $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE ID = ?");
                $stmt->bind_param("i", $msg['user_id']);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                // User exists when count is '1', so the picture can be fetched
                if ($count == 1) {
                    $userExists = true;
                    $stmt = $conn->prepare("SELECT Pic FROM usertable WHERE ID = ?");
                    $stmt->bind_param("i", $msg['user_id']);
                    $stmt->execute();
                    $stmt->bind_result($userPic);
                    $stmt->fetch();
                    $stmt->close();     
                }
                ?>
                <div class="message">
                    <div class="message-info">
                        <?php
                            // Check if the current message belongs to the logged-in user
                            if ($msg['user_id'] == $userID):
                                echo "<a href=\"account.php\">";
                                $userCanDeleteMessages = true; // Allow message deletion for the user
                            endif;       

                            // Display user profile picture if available
                            if ($userPic):
                                echo "<img src=\"data:image/jpeg;base64,"; 
                                echo base64_encode($userPic); 
                                echo "\" alt=\"Profile Picture\">";
                            else:
                                // Display a default image if profile picture is not available
                                echo "<img src=\"resources\\frame.png\">";
                            endif;  

                            // Close the link if it was opened for the user's account
                            if ($msg['user_id'] == $userID):
                                echo "</a>";
                            endif; 
                        ?>  
                        <!-- Display the name of the user who posted the message -->
                        <div class="message-info-name"><?php echo htmlspecialchars($msg['user_name']); ?></div>
                        <!-- Display the date and time of the message -->
                        <div class="message-info-date"><?php echo $msg['date_time']; ?></div>
                        <?php if (!$userExists): ?>
                            <!-- Notify if the user who posted the message has been deleted -->
                            <div class="message-info-userDeleted">User deleted</div>
                        <?php endif; ?>  
                    </div>
                    <!-- Display the content of the message, preserving line breaks -->
                    <div class="message-content">
                        <?php echo nl2br(($msg['content'])); ?>
                    </div>
                    <?php if (isset($userCanDeleteMessages) && $userCanDeleteMessages): ?>
                    <div>
                        <!-- Button to open the delete confirmation modal -->
                        <button class="icon-button icon-button-thread" onclick="openDeleteModal(<?php echo $msg['id']; ?>, '<?php echo htmlspecialchars(addslashes($msg['user_name'])); ?>')">
                            <img src="resources/trash.png" alt="Delete Icon">
                            <div class="icon-button-thread-tooltip icon-button-tooltip">Delete message</div>
                        </button>
                    </div>  
                    <?php endif; ?>              
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (isset($_SESSION['username'])): ?>
            <div class="new-message">
                <h2>Write new post</h2>
                <div id="toolbar-container">
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
                <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post" onsubmit="return submitForm()">
                    <input type="hidden" name="message" id="hiddenMessage">
                    <button type="submit">Send</button>
                </form>
            </div>
           
            <!-- Initialize Quill editor -->
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
        <?php else: ?>
            <div class="footer-login-info"><button onclick="openModal('loginModal')" class="button">Login</button> to write a message.</div>
        <?php endif; ?>
    </main>
    <?php if (isset($_SESSION['username'])): ?>
        <?php if (isset($userCanDeleteMessages) && $userCanDeleteMessages) : ?>
            <!-- Modal for delete message -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                    <h2>Delete message</h2>
                    <div class="deleteInfo">You are about to delete the message from user >> <b id="userOfMessage"></b> << Are you sure?</div>
                    <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post">
                        <div class="modal-input">
                            <input type="hidden" id="deleteMsgId" name="msg_id">
                        </div>
                        <div class="modal-button">
                            <button type="submit" class="button">Yes</button>
                            <button type="button" class="button" onclick="closeModal('deleteModal')">No</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Modal for login -->
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('loginModal')">&times;</span>
                <h2>Login</h2>
                <form action="login.php" method="post">
                    <div class="modal-input">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="modal-button">
                        <button type="submit">Login</button>
                    </div>
                </form>
                <p>Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
