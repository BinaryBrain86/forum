<?php
session_start();
include 'db.php';

if (isset($_SESSION['username'])):
    $roleID = $_SESSION['role_id'];
    $userName = $_SESSION['username'];
endif;

if (isset($roleID)) {
    $stmt = $conn->prepare("SELECT Name, DeleteThread, RenameThread FROM roletable WHERE ID = ?");
    $stmt->bind_param("i", $roleID);
    $stmt->execute();
    $stmt->bind_result($roleName, $userCanDelete, $userCanEdit);
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

        function openEditModal(threadId, threadName) {
            document.getElementById('editThreadId').value = threadId;
            document.getElementById('editThreadNameInput').value = threadName;
            document.getElementById('editModal').style.display = 'block';
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
        <?php if (isset($userName)): 
            echo htmlspecialchars($userName); 
        endif; ?> !</h1> 
        <?php if (isset($userName)): ?>
        <a href="account.php" class="icon-button icon-button-settings"><img src="ressources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
        <?php endif; ?>
        <nav>
            <?php if (isset($userName)): ?>
                <button onclick="openModal('threadModal')" class="button">Create new thread</button>
                <?php if ($roleName == "Admin"): ?>
                    <a href="admin.php" class="button">Administration</a>
                <?php endif; ?>
                <a href="logout.php" class="button">Logout <?php if (isset($userName)): echo htmlspecialchars($userName); endif; ?></a>
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
            $threadID = $thread['ID'];
            $threadName = $thread['Name'];
            $amountOfMsg = null;
            $timeOfLastMsg = null;
            $userNameOfLastMsg = null;

            $stmt = $conn->prepare("SELECT * FROM threadsummary WHERE Thread_ID = ?");
            $stmt->bind_param("i", $threadID);
            $stmt->execute();
            $stmt->bind_result($threadID, $amountOfMsg, $timeOfLastMsg, $userNameOfLastMsg);
            $stmt->fetch();
            $stmt->close();?>

                <div class="thread">
                    <div class="thread-title">
                        <a href="view_thread.php?thread_id=<?php echo $threadID; ?>" class="thread-link">
                            <div class="thread-main-title">
                                <?php echo htmlspecialchars($threadName); ?>
                            </div>
                            <div class="thread-sub-title">
                                <?php if (!is_null($amountOfMsg)): ?>
                                    <div><?php echo $amountOfMsg?> message posted</div>
                                    <div class="thread-title-last-msg">
                                        <div>Last post by <?php echo $userNameOfLastMsg?></div>
                                        <div><?php echo $timeOfLastMsg?></div>
                                    </div>
                                <?php else: ?>
                                    <div>0 message posted</div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <div class="thread-info">
                        <div class="thread-createdBy">by <?php echo htmlspecialchars($thread['CreatedByUser']); ?></div>
                        <div class="thread-createdDate">on <?php echo htmlspecialchars($thread['Date_Time']); ?></div>
                    </div>
                <?php if (isset($userName)): ?>
                    <div class="thread-action">
                <?php if ($userCanEdit): ?>
                    <button class="icon-button icon-button-thread" onclick="openEditModal(<?php echo $threadID; ?>, '<?php echo htmlspecialchars(addslashes($threadName)); ?>')">
                        <img src="ressources/edit.png" alt="Edit Icon">
                        <div class="icon-button-thread-tooltip icon-button-tooltip">Edit thread</div>
                    </button>
                <?php endif; ?>
                <?php if ($userCanDelete): ?>
                    <button class="icon-button icon-button-thread" onclick="openDeleteModal(<?php echo $threadID; ?>, '<?php echo htmlspecialchars(addslashes($threadName)); ?>')">
                        <img src="ressources/trash.png" alt="Delete Icon">
                        <div class="icon-button-thread-tooltip icon-button-tooltip">Delete thread</div>
                    </button>
                <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </main>

    <?php if (isset($userName)): ?>
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

        <?php if ($userCanEdit) : ?>
            <!-- Modal for edit thread -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('editModal')">&times;</span>
                    <h2>Edit thread title</h2>
                    <form action="edit_thread.php" method="post">
                        <input type="hidden" id="editThreadId" name="thread_id">
                        <label for="editThreadNameInput">Thread name:</label>
                        <input type="text" id="editThreadNameInput" name="threadName" required>
                        <button type="submit">Save</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($userCanDelete) : ?>
            <!-- Modal for delete thread-->
            <div id="deleteModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                <h2>Delete thread</h2>
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
