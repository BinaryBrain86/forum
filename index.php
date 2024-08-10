<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

// Check if the request method is POST and the user is logged in
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    
    // Handle thread creation
    if (isset($_POST['insertThreadName'])) {
        $threadName = $_POST['insertThreadName'];
        $userName = $_SESSION['username'];
        $stmt = $conn->prepare("INSERT INTO threadtable (Name, CreatedByUser) VALUES (?,?)");
        $stmt->bind_param("ss", $threadName, $userName);
        $stmt->execute();
        $stmt->close();
    } 
    // Handle thread editing if the user has edit permissions
    elseif ($userCanEdit && isset($_POST['editThreadName'])) {
        $threadName = $_POST['editThreadName'];
        $thread_id = $_POST['editThreadId'];
        $stmt = $conn->prepare("UPDATE threadtable SET Name = ? WHERE ID = ?");
        $stmt->bind_param("si", $threadName, $thread_id);
        $stmt->execute();
        $stmt->close();
    }
    // Handle thread deletion if the user has delete permissions
    elseif ($userCanDelete && isset($_POST['deleteThreadId'])) {
        $thread_id = $_POST['deleteThreadId'];
        $stmt = $conn->prepare("DELETE FROM threadtable WHERE ID = ?");
        $stmt->bind_param("i", $thread_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect after processing
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>My Forum</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <link rel="stylesheet" href="styles.css">
    <script>
        // Functions to handle opening and closing of modals
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        function openDeleteModal(threadId, threadName) {
            // Populate the delete modal with thread data
            document.getElementById('deleteThreadId').value = threadId;
            document.getElementById('deleteThreadName').innerText = threadName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function openEditModal(threadId, threadName) {
            // Populate the edit modal with thread data
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
        <?php if (isset($userName)): // Display username if logged in 
            echo htmlspecialchars($userName);    
        endif; ?>!</h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <h2>Threads</h2>
        <?php
        // Fetch all threads from the database
        $threads = $conn->query("SELECT * FROM threadtable");
        while ($thread = $threads->fetch_assoc()): 
            $threadID = $thread['ID'];
            $threadName = $thread['Name'];
            $amountOfMsg = null;
            
            // Fetch thread summary data (message count, last message info)
            $stmt = $conn->prepare("SELECT * FROM threadsummary WHERE Thread_ID = ?");
            $stmt->bind_param("i", $threadID);
            $stmt->execute();
            $stmt->bind_result($threadID, $amountOfMsg, $timeOfLastMsg, $userNameOfLastMsg);
            $stmt->fetch();
            $stmt->close();?>

                <div class="thread">
                    <div class="thread-title">
                        <!-- Link to view the thread details -->
                        <a href="view_thread.php?thread_id=<?php echo $threadID; ?>" class="thread-link">
                            <div class="thread-main-title">
                                <?php echo htmlspecialchars($threadName); ?>
                            </div>
                            <div class="thread-sub-title">
                                <!-- Display the number of messages and last post details if available -->
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
                <?php if (isset($userName)): // If user is logged in, show thread action buttons ?>
                    <div class="thread-action">
                <?php if ($userCanEdit): // Show edit button if user has edit permissions ?>
                    <button class="icon-button icon-button-thread" onclick="openEditModal(<?php echo $threadID; ?>, '<?php echo htmlspecialchars(addslashes($threadName)); ?>')">
                        <img src="resources/edit.png" alt="Edit Icon">
                        <div class="icon-button-thread-tooltip icon-button-tooltip">Edit thread</div>
                    </button>
                <?php endif; ?>
                <?php if ($userCanDelete): // Show delete button if user has delete permissions ?>
                    <button class="icon-button icon-button-thread" onclick="openDeleteModal(<?php echo $threadID; ?>, '<?php echo htmlspecialchars(addslashes($threadName)); ?>')">
                        <img src="resources/trash.png" alt="Delete Icon">
                        <div class="icon-button-thread-tooltip icon-button-tooltip">Delete thread</div>
                    </button>
                <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </main>

    <?php if (isset($userName)): // If logged in, display thread creation and edit/delete modals ?>
        <!-- Modal for creating a new thread -->
        <div id="threadModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('threadModal')">&times;</span>
                <h2>Create new thread</h2>
                <form action="index.php" method="post">
                    <div class="modal-input">
                        <label for="threadName">Thread name:</label>
                        <input type="text" id="threadName" name="insertThreadName" required>
                    </div>
                    <div class="modal-button">
                        <button type="submit">Create</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($userCanEdit) : ?>
            <!-- Modal for editing a thread -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('editModal')">&times;</span>
                    <h2>Edit thread title</h2>
                    <form action="index.php" method="post">
                        <div class="modal-input">
                            <input type="hidden" id="editThreadId" name="editThreadId">
                            <label for="editThreadNameInput">Thread name:</label>
                            <input type="text" id="editThreadNameInput" name="editThreadName" required>
                        </div>
                        <div class="modal-button">
                            <button type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($userCanDelete) : ?>
            <!-- Modal for deleting a thread-->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                    <h2>Delete thread</h2>
                    <div class="deleteInfo">You are about to delete the thread >> <b id="deleteThreadName"></b> << Are you sure?</div>
                    <form action="index.php" method="post">
                        <div class="modal-input">
                            <input type="hidden" id="deleteThreadId" name="deleteThreadId">
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
        <!-- Modal for login if user is not logged in -->
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
