<?php
session_start();
include 'db.php';

$userID = null;
if (isset($_SESSION['username'])):
    $userID = $_SESSION['user_id'];
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
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_query'])) {
    $search = $_GET['search_query'];
    $stmt = $conn->prepare("
        SELECT 'thread' AS type, 
               t.ID AS thread_id, 
               NULL AS message_id, 
               t.Name AS thread_name,
               t.Name AS content, 
               t.CreatedByUser AS author, 
               t.Date_Time AS date,
               NULL AS user_id
        FROM threadtable t
        WHERE t.Name LIKE ?
        UNION
        SELECT 'message' AS type, 
               m.Thread_ID AS thread_id, 
               m.ID AS message_id, 
               t.Name AS thread_name,
               m.Message AS content, 
               m.UserName AS author, 
               m.Date_Time AS date,
               m.User_ID AS user_id
        FROM messagetable m
        LEFT JOIN threadtable t ON m.Thread_ID = t.ID
        WHERE m.Message LIKE ?
        ORDER BY date DESC
    ");
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
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
        <h1>Search Results</h1>
        <?php if (isset($_SESSION['username'])): ?>
        <a href="account.php" class="icon-button icon-button-settings"><img src="resources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
        <?php endif; ?>
        <div class="header-content">
            <div class="header-left">
            <form method="get" action="search_results.php" class="search-form">
                    <input type="text" name="search_query" placeholder="Search" required>
                    <button type="submit">Go</button>
                </form>
            </div>
            <div class="header-right">
                <a href="index.php" class="button">Back to overview</a>
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="logout.php" class="button">Logout <?php if (isset($_SESSION['username'])): echo htmlspecialchars($_SESSION['username']); endif; ?></a>
                <?php else: ?>
                    <button onclick="openModal('loginModal')" class="button">Login</button>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>
        <?php if (isset($result) && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php if ($row['type'] == 'thread'): ?>
                    <div class="thread">
                        <div class="thread-title">
                            <a href="view_thread.php?thread_id=<?php echo $row['thread_id']; ?>" class="thread-link">
                                <div class="thread-main-title">
                                    <?php echo htmlspecialchars($row['content']); ?>
                                </div>
                                <div class="thread-sub-title">
                                    <div>by <?php echo htmlspecialchars($row['author']); ?> on <?php echo htmlspecialchars($row['date']); ?></div>
                                </div>
                            </a>
                        </div>
                        <?php if (isset($userName)): ?>
                        <div class="thread-action">
                        <?php if ($userCanEdit): ?>
                            <button class="icon-button icon-button-thread" onclick="openEditModal(<?php echo $row['thread_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['content'])); ?>')">
                                <img src="resources/edit.png" alt="Edit Icon">
                                <div class="icon-button-thread-tooltip icon-button-tooltip">Edit thread</div>
                            </button>
                        <?php endif; ?>
                        <?php if ($userCanDelete): ?>
                            <button class="icon-button icon-button-thread" onclick="openDeleteModal(<?php echo $row['thread_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['content'])); ?>')">
                                <img src="resources/trash.png" alt="Delete Icon">
                                <div class="icon-button-thread-tooltip icon-button-tooltip">Delete thread</div>
                            </button>
                        <?php endif; ?>
                        </div>
                <?php endif; ?>
                    </div>
                <?php elseif ($row['type'] == 'message'): 
                    // Fetch current user information
                    $userPic = null;
                    $userExists = false;
                    // Check if the username was deleted
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE ID = ?");
                    $stmt->bind_param("i", $row['user_id']);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    if ($count > 0) {
                        $userExists = true;

                        $stmt = $conn->prepare("SELECT Pic FROM usertable WHERE ID = ?");
                        $stmt->bind_param("i", $row['user_id']);
                        $stmt->execute();
                        $stmt->bind_result($userPic);
                        $stmt->fetch();
                        $stmt->close();     
                    }?>
                    <div class="message">
                        <div class="message-info">
                            <?php
                                if ($row['user_id'] == $userID):
                                    echo "<a href=\"account.php\">";
                                endif;       
                                if ($userPic):
                                    echo "<img src=\"data:image/jpeg;base64,"; echo base64_encode($userPic); echo"\" alt=\"Profile Picture\">";
                                else:
                                    echo "<img src=\"resources\\frame.png\">";
                                endif;  
                                if ($row['user_id'] == $userID):
                                    echo "</a>";
                                endif; 
                            ?>  
                            <div class="message-info-name"><?php echo htmlspecialchars($row['author']); ?></div>
                            <div class="message-info-date"><?php echo htmlspecialchars($row['date']); ?></div>  
                            <?php if ($userExists == false): ?>
                                <div class="message-info-userDeleted">User deleted</div>
                            <?php endif; ?>           
                        </div>
                        <div class="search-content">
                        <div class="search-content-title">Found in <i><a href="view_thread.php?thread_id=<?php echo $row['thread_id']; ?>"><?php echo htmlspecialchars($row['thread_name']); ?></i></a> thread.</div>
                            <div class="message-content ellipsis">    
                                <div>
                                    <?php echo ($row['content']); ?>
                                </div>
                            </div> 
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
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
                    <div class="deleteInfo">You are about to delete the thread >> <b id="deleteThreadName"></b> << Are you sure?</div>
                    <form action="delete_thread.php" method="post">
                        <input type="hidden" id="deleteThreadId" name="thread_id">
                        <button type="submit" class="button">Yes</button>
                        <button type="button" class="button" onclick="closeModal('deleteModal')">No</button>
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
