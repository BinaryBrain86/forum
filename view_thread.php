<?php
session_start();
include 'db.php';

if (!isset($_GET['thread_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

$thread_id = $_GET['thread_id'];

// Fetch thread details
$thread_stmt = $conn->prepare("SELECT Name FROM threadtable WHERE ID = ?");
$thread_stmt->bind_param("i", $thread_id);
$thread_stmt->execute();
$thread_stmt->bind_result($thread_name);
$thread_stmt->fetch();
$thread_stmt->close();

// Handle new message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['username'];

    $stmt = $conn->prepare("INSERT INTO messagetable (User_ID, UserName, Thread_ID, Message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $user_name, $thread_id, $message);
    $stmt->execute();
    $stmt->close();

    header("Location: view_thread.php?thread_id=$thread_id");
    exit();
}

// Fetch messages in the thread
$msg_stmt = $conn->prepare("SELECT User_ID, UserName, Date_Time, Message FROM messagetable WHERE Thread_ID = ? ORDER BY Date_Time ASC");
$msg_stmt->bind_param("i", $thread_id);
$msg_stmt->execute();
$msg_stmt->bind_result($msg_user_id, $msg_user_name, $msg_date_time, $msg_content);

// Store messages in an array
$messages = [];
while ($msg_stmt->fetch()) {
    $messages[] = [
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
    <title><?php echo htmlspecialchars($thread_name); ?></title>
    
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="quill/quill.snow.css" >
    <link rel="stylesheet" href="quill/atom-one-dark.min.css"/>
    <link rel="stylesheet" href="quill/katex.min.css" />
    
    <script src="quill/highlight.min.js"></script>
    <script src="quill/quill.js"></script>
    <script src="quill/katex.min.js"></script>

    <script>
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        function closeModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'none';
            }
        }

        function submitForm() {
            const message = document.querySelector('input[name=message]');
            message.value = quill.root.innerHTML;
            return true;
        }   
    </script>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($thread_name); ?></h1>
        <?php if (isset($_SESSION['username'])): ?>
        <a href="account.php" class="icon-button icon-button-settings"><img src="ressources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
        <?php endif; ?>
        <nav>
            <a href="index.php" class="button">Back to overview</a>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="logout.php" class="button">Logout <?php if (isset($_SESSION['username'])): echo htmlspecialchars($_SESSION['username']); endif; ?></a>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="button">Login</button>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <div class="messages">
            <?php foreach ($messages as $msg): 
                // Fetch current user information
                $userPic = null;
                if ($msg['user_id']) {
                    $stmt = $conn->prepare("SELECT Pic FROM usertable WHERE ID = ?");
                    $stmt->bind_param("i", $msg['user_id']);
                    $stmt->execute();
                    $stmt->bind_result($userPic);
                    $stmt->fetch();
                    $stmt->close();
                }?>
                <div class="message">
                    <div class="message-info">
                        <?php
                            if ($msg['user_id'] == $user_id):
                                echo "<a href=\"account.php\">";
                            endif;       
                            if ($userPic):
                                echo "<img src=\"data:image/jpeg;base64,"; echo base64_encode($userPic); echo"\" alt=\"Profile Picture\">";
                            else:
                                echo "<img src=\"ressources\\frame.png\">";
                            endif;  
                            if ($msg['user_id'] == $user_id):
                                echo "</a>";
                            endif; 
                        ?>  
                        <div class="message-info-name"><?php echo htmlspecialchars($msg['user_name']); ?></div>
                        <div class="message-info-date"><?php echo $msg['date_time']; ?></div>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(($msg['content'])); ?>
                    </div>               
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
                placeholder: 'Compose an epic...',
                theme: 'snow',
            });
            </script>
        <?php else: ?>
            <p><button onclick="openModal('loginModal')" class="button">Login</button>, to write a message.</p>
        <?php endif; ?>
    </main>
    <?php if (isset($_SESSION['username']) == null): ?>
         <!-- Modal für neuen Thread -->
         <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('loginModal')">&times;</span>
                <h2>Login</h2>
                <form action="login.php" method="post">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    
                    <button type="submit">Login</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
