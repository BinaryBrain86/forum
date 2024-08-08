<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if the required parameters are set
if (!isset($_GET['user1']) || !isset($_GET['user2'])) {
    echo "Not a valid request.";
    exit;
}

$user1 = $_GET['user1'];
$user2 = $_GET['user2'];

$userID = $_SESSION['user_id'];
$roleID = $_SESSION['role_id'];
$userName = $_SESSION['username'];

if (isset($roleID)) {
    $getRoleNameStmt = $conn->prepare("SELECT Name FROM roletable WHERE ID = ?");
    $getRoleNameStmt->bind_param("i", $roleID);
    $getRoleNameStmt->execute();
    $getRoleNameStmt->bind_result($roleName);
    $getRoleNameStmt->fetch();
    $getRoleNameStmt->close();
}

// Fetch all messages between the two users
$pm_stmt = $conn->prepare("
    SELECT * 
    FROM personalMessageTable 
    WHERE (User_ID_Sender = ? AND User_ID_Receiver = ?) 
       OR (User_ID_Sender = ? AND User_ID_Receiver = ?)
    ORDER BY Date_Time ASC
");
$pm_stmt->bind_param("iiii", $user1, $user2, $user2, $user1);
$pm_stmt->execute();
$pm_stmt->bind_result($pm_id, $pm_user_id_sender, $pm_username_sender, $pm_user_id_receiver, $pm_user_username_receiver, $pm_message, $pm_date_time, $pm_read);

// Store messages in an array
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
$pm_stmt->close();

// Update the "Read" status for all messages in this conversation where the current user is the receiver
$updateStmt = $conn->prepare("
    UPDATE personalMessageTable 
    SET `Read` = 1 
    WHERE User_ID_Receiver = ? 
    AND ((User_ID_Sender = ? AND User_ID_Receiver = ?) 
        OR (User_ID_Sender = ? AND User_ID_Receiver = ?))
");
$updateStmt->bind_param("iiiii", $userID, $user1, $user2, $user2, $user1);
$updateStmt->execute();
$updateStmt->close();

// Count unread personal messages
$unreadCountStmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM personalMessageTable 
    WHERE User_ID_Receiver = ? AND `Read` = 0
");
$unreadCountStmt->bind_param("i", $userID);
$unreadCountStmt->execute();
$unreadCountStmt->bind_result($unreadCount);
$unreadCountStmt->fetch();
$unreadCountStmt->close();

$getSendersNameStmt = $conn->prepare("SELECT UserName FROM usertable WHERE ID = ?");
if($userID == $user1):
    $getSendersNameStmt->bind_param("i", $user2);
else:
    $getSendersNameStmt->bind_param("i", $user1);
endif;
$getSendersNameStmt->execute();
$getSendersNameStmt->bind_result($ConversationUserName);
$getSendersNameStmt->fetch();
$getSendersNameStmt->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Personal conversation</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="quill/quill.snow.css">
    <link rel="stylesheet" href="quill/atom-one-dark.min.css"/>
    <link rel="stylesheet" href="quill/katex.min.css" />
    
    <script src="quill/highlight.min.js"></script>
    <script src="quill/quill.js"></script>
    <script src="quill/katex.min.js"></script>
   
    <script>
        function submitForm() {
            const message = document.querySelector('input[name=message]');
            message.value = quill.root.innerHTML;
            return true;
        }
    </script>

</head>
<body>
    <header>
        <h1>Personal conversation with <?php echo (mb_strlen(trim($ConversationUserName)) === 0) ? "deleted user" : htmlspecialchars($ConversationUserName); ?></h1>
        <a href="account.php" class="icon-button icon-button-settings"><img src="resources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
        <div class="header-content">
            <div class="header-left">
                <form method="get" action="search_results.php" class="search-form">
                    <input type="text" name="search_query" placeholder="Suche" required>
                    <button type="submit" class="button">Go</button>
                </form>
            </div>
            <div class="header-right">
                <a href="personalMessages.php" class="button">PM
                <?php if ($unreadCount > 0): ?>
                        <span class="unread-count"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>  
                <a href="index.php" class="button">Back to overview</a>             
                <?php if ($roleName == "Admin"): ?>
                    <a href="admin.php" class="button">Administration</a>
                <?php endif; ?>
                <a href="logout.php" class="button">Logout <?php echo htmlspecialchars($userName); ?></a>
            </div>
        </div>
    </header>
    <main>
        <div class="messages">
            <?php foreach ($pms as $pm): 

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
                    <?php if ($userExists == false): ?>
                        <div class="message-info-userDeleted">User deleted</div>
                    <?php endif; ?>  
                </div>
                <div class="message-content">
                    <?php echo nl2br(($pm['Message'])); ?>
                </div>
                            
            </div>
            <?php endforeach; ?>
        </div>

        <div class="new-message">
            <h2>Answer</h2>
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
            <form action="send_personalMessage.php" method="post" class="pm-form header-content" onsubmit="return submitForm()">
                    <input type="hidden" name="receiver_id" value="<?php echo ($userID == $user1) ? $user2 : $user1; ?>">                       
                    <input type="hidden" id="message" name="message">
                    <input type="hidden" id="location_path" name="location_path" value="view_personalMessage.php?user1=<?php echo $user1; ?>&user2=<?php echo $user2; ?>">
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
    </main>
</body>
</html>

<?php
$conn->close();
?>
