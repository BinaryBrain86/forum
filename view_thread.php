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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thread_name); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($thread_name); ?></h1>
        <nav>
            <a href="index.php" class="button">Zurück zum Forum</a>
            <?php if (isset($_SESSION['username'])): ?>
                <span>Hallo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="account.php" class="button">Mein Account</a>
                <a href="logout.php" class="button">Logout</a>
            <?php else: ?>
                <a href="login.html" class="button">Login</a>
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
                <h2>Neue Nachricht schreiben</h2>
                <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post">
                    <textarea name="message" required></textarea>
                    <button type="submit">Senden</button>
                </form>
            </div>
        <?php else: ?>
            <p>Bitte <a href="login.html">loggen Sie sich ein</a>, um eine Nachricht zu schreiben.</p>
        <?php endif; ?>
    </main>
</body>
</html>
