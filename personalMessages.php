<?php
session_start();
require 'db.php';
require 'user_info.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch the latest message from each conversation along with unread count
$stmt = $conn->prepare("
    SELECT pmt.*, 
           COALESCE(unread_counts.unreadCount, 0) AS unreadCount
    FROM personalMessageTable pmt
    INNER JOIN (
        SELECT LEAST(User_ID_Receiver, User_ID_Sender) AS user1, 
               GREATEST(User_ID_Receiver, User_ID_Sender) AS user2, 
               MAX(ID) AS max_id
        FROM personalMessageTable
        WHERE User_ID_Receiver = ? OR User_ID_Sender = ?
        GROUP BY user1, user2
    ) grouped_pmt ON pmt.ID = grouped_pmt.max_id
    LEFT JOIN (
        SELECT LEAST(User_ID_Receiver, User_ID_Sender) AS user1, 
               GREATEST(User_ID_Receiver, User_ID_Sender) AS user2, 
               COUNT(*) AS unreadCount
        FROM personalMessageTable
        WHERE User_ID_Receiver = ? AND `Read` = 0
        GROUP BY user1, user2
    ) unread_counts ON unread_counts.user1 = grouped_pmt.user1 AND unread_counts.user2 = grouped_pmt.user2
    ORDER BY pmt.Date_Time DESC
");

$stmt->bind_param("iii", $userID, $userID, $userID);
$stmt->execute();
$result = $stmt->get_result();


// Fetch all users for the dropdown
$usersResult = $conn->query("SELECT ID, username FROM usertable WHERE ID != $userID ORDER BY username ASC");

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Personal messages</title>
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
        <h1>Personal messages</h1>
        <?php require 'header.php'; ?>
    </header>
    <main>
        <h2>Personal conversations</h2>
        <div class="messages">
            <?php while ($message = $result->fetch_assoc()): ?>
                <div class="pm-conversation">
                    <div class="pm-info">  
                        <div>With <?php echo ($userID == $message['User_ID_Receiver']) ? $message['UserName_Sender'] : $message['UserName_Receiver'];?></div>   
                        <div class="pm-info-name">Last by <?php echo htmlspecialchars($message['UserName_Sender']); ?> on</div>
                        <div class="pm-info-date"><?php echo $message['Date_Time']; ?></div>
                    </div>
                    <div class="message-content">
                        <a href="view_personalMessage.php?user1=<?php echo $message['User_ID_Sender']; ?>&user2=<?php echo $message['User_ID_Receiver']; ?>" class="pm-content ellipsis">
                            <?php echo nl2br($message['Message']); ?>
                        </a>
                    </div>
                    <div class="pm-read-info">
                    <?php if ($message['unreadCount'] > 0 && $message['User_ID_Receiver'] == $userID): ?>
                        <span class="unread-count"><?php echo $message['unreadCount'] ?></span>
                    <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="new-message">
            <h2>Write new personal message</h2>
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
                <div>
                    <label for="receiver">Receiver:</label>
                    <div class="styled-select">
                        <select id="receiver" name="receiver_id" required>
                            <?php while ($user = $usersResult->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($user['ID']); ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <input type="hidden" id="message" name="message">
                    <input type="hidden" id="location_path" name="location_path" value="personalMessages.php">
                    <button type="submit">Send</button>
                </div>
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
$stmt->close();
$conn->close();
?>
