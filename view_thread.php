<?php
session_start();
include 'db.php';

if (!isset($_GET['thread_id'])) {
    header("Location: index.php");
    exit();
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
$msg_stmt = $conn->prepare("SELECT UserName, Date_Time, Message FROM messagetable WHERE Thread_ID = ? ORDER BY Date_Time ASC");
$msg_stmt->bind_param("i", $thread_id);
$msg_stmt->execute();
$msg_stmt->bind_result($msg_user_name, $msg_date_time, $msg_content);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($thread_name); ?></title>
    <link rel="stylesheet" href="styles.css">
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
    </script>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($thread_name); ?></h1>
        <nav>
            <a href="index.php" class="button">Back to overview</a>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="account.php" class="button">My Account</a>
                <a href="logout.php" class="button">Logout <?php if (isset($_SESSION['username'])): echo htmlspecialchars($_SESSION['username']); endif; ?></a>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="button">Login</button>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <div class="messages">
            <?php while ($msg_stmt->fetch()): ?>
                <div class="message">
                    <p><strong><?php echo htmlspecialchars($msg_user_name); ?></strong> <em><?php echo $msg_date_time; ?></em></p>
                    <p><?php echo nl2br(htmlspecialchars($msg_content)); ?></p>
                </div>
            <?php endwhile; ?>
            <?php $msg_stmt->close(); ?>
        </div>
        <?php if (isset($_SESSION['username'])): ?>
            <div class="new-message">
                <h2>Write new message</h2>
                <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post">
                    <textarea name="message" required></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>
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
