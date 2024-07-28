<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function openModal() {
            document.getElementById('threadModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('threadModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('threadModal')) {
                closeModal();
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Willkommen im Forum</h1>
        <nav>
            <?php if (isset($_SESSION['username'])): ?>
                <span>Hallo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <button onclick="openModal()" class="button">Neuen Thread erstellen</button>
                <a href="account.php" class="button">Mein Account</a>
                <a href="logout.php" class="button">Logout</a>
            <?php else: ?>
                <a href="login.html" class="button">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <h2>Alle Foren</h2>
        <?php
        $threads = $conn->query("SELECT * FROM threadtable");
        while ($thread = $threads->fetch_assoc()):
        ?>
            <div class="thread">
                <h3><?php echo htmlspecialchars($thread['Name']); ?></h3>
                <a href="view_thread.php?thread_id=<?php echo $thread['ID']; ?>" class="button">Anzeigen</a>
            </div>
        <?php endwhile; ?>
    </main>

    <?php if (isset($_SESSION['username'])): ?>
        <!-- Modal für neuen Thread -->
        <div id="threadModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Neuen Thread erstellen</h2>
                <form action="create_thread.php" method="post">
                    <label for="threadName">Thread Name:</label>
                    <input type="text" id="threadName" name="threadName" required>
                    <button type="submit">Erstellen</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
