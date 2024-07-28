<?php
session_start();
include 'db.php';

if (isset($_SESSION['role_id'])) {
    $role_id = $_SESSION['role_id'];  
    $stmt = $conn->prepare("SELECT DeleteThread, RenameThread FROM roletable WHERE ID = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $stmt->bind_result($userCanDelete, $userCanEdit);
    $stmt->fetch();
    $stmt->close();
}?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Forum</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        function openDeleteModal(threadId, threadName) {
            document.getElementById('deleteThreadId').value = threadId;
            document.getElementById('deleteThreadName').innerText = threadName;
            document.getElementById('deleteModal').style.display = 'block';
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
        <h1>Welcome to my forum 
        <?php 
            if (isset($_SESSION['username'])): 
            echo htmlspecialchars($_SESSION['username']); 
            endif; 
        ?>!</h1> 
        <nav>
            <?php if (isset($_SESSION['username'])): ?>
                <button onclick="openModal('threadModal')" class="button">Create new thread</button>
                <a href="account.php" class="button">My Account</a>
                <a href="logout.php" class="button">Logout <?php if (isset($_SESSION['username'])): echo htmlspecialchars($_SESSION['username']); endif; ?></a>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="button">Login</button>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <h2>Threads</h2>
        <?php
        $threads = $conn->query("SELECT * FROM threadtable");
        while ($thread = $threads->fetch_assoc()):
        ?>
            <div class="thread">
                <div class="thread-title"><h3><a href="view_thread.php?thread_id=<?php echo $thread['ID']; ?>"><?php echo htmlspecialchars($thread['Name']); ?></a></h3></div>
                <div class="thread-info">
                    <div class="thread-createdBy">by <?php echo htmlspecialchars($thread['CreatedByUser']); ?></div>
                    <div class="thread-createdDate">on <?php echo htmlspecialchars($thread['Date_Time']); ?></div>
                </div>
                <?php if (isset($_SESSION['username'])): ?>
                <div class="thread-action">
                    <?php if ($userCanEdit): ?>
                        <button class="icon-button" onclick="meineFunktion()">
                            <img src="ressources\edit.png" alt="Icon Button">
                        </button>
                    <?php endif; ?>
                    <?php if ($userCanDelete): ?>
                    <button class="icon-button" onclick="openDeleteModal(<?php echo $thread['ID']; ?>, '<?php echo htmlspecialchars($thread['Name']); ?>')">
                        <img src="ressources\trash.png" alt="Icon Button">
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </main>

    <?php if (isset($_SESSION['username'])): ?>
        <!-- Modal für neuen Thread -->
        <div id="threadModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('threadModal')">&times;</span>
                <h2>Create new thread</h2>
                <form action="create_thread.php" method="post">
                    <label for="threadName">Thread name:</label>
                    <input type="text" id="threadName" name="threadName" required>
                    <button type="submit">Create</button>
                </form>
            </div>
        </div>

        <?php if ($userCanDelete) : ?>
            <!-- Modal for delete -->
            <div id="deleteModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                <h2>Delete Thread</h2>
                <div class="deleteInfo">You are about to delete the thread >><b id="deleteThreadName"></b><< Are you sure?</div>
                <form action="delete_thread.php" method="post">
                    <input type="hidden" id="deleteThreadId" name="thread_id">
                    <button type="submit" class="button">Yes</button>
                    <button type="button" class="button" onclick="closeModal('deleteModal')">No</button>
                </form>
            </div>
        <?php endif; ?>
    <?php else: ?>
         <!-- Modal for login -->
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

                <p>Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
