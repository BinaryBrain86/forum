<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

// Check if the request method is GET and if a search query is present.
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_query'])) {
    $search = $_GET['search_query']; // Get the search query from the URL.
    
    // Prepare a SQL query that searches for threads and messages containing the search query.
    $stmt = $conn->prepare("
        -- Select threads that match the search query by the thread name
        SELECT 'thread' AS type, 
               t.ID AS thread_id, 
               NULL AS message_id, 
               t.Name AS thread_name,
               t.Name AS content, 
               t.CreatedByUser AS author, 
               t.Date_Time AS date,
               NULL AS user_id
        FROM threadtable t

        -- Filter threads where the name matches the search query
        WHERE t.Name LIKE ?

        -- Combine results with the next part of the query
        UNION 

        -- Select messages that match the search query by the message content
        SELECT 'message' AS type, 
               m.Thread_ID AS thread_id, 
               m.ID AS message_id, 
               t.Name AS thread_name,
               m.Message AS content, 
               m.UserName AS author, 
               m.Date_Time AS date,
               m.User_ID AS user_id
        FROM messagetable m

        -- Join with threadtable to get thread names
        LEFT JOIN threadtable t ON m.Thread_ID = t.ID

        -- Filter messages where the content matches the search query
        WHERE m.Message LIKE ?

        -- Order the results by date in descending order
        ORDER BY date DESC
    ");
    
    $search_param = "%" . $search . "%"; // Format the search query for use in SQL LIKE clause.
    $stmt->bind_param("ss", $search_param, $search_param); // Bind the search parameter twice, once for each LIKE clause.
    $stmt->execute(); // Execute the SQL query.
    $result = $stmt->get_result(); // Get the result set from the executed query.
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.ico"> <!-- Favicon link -->
    <link rel="stylesheet" href="styles.css"> <!-- Link to the external stylesheet -->
    <script>
        // Open a modal window by setting its display style to 'block'.
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        // Open a modal for deleting a thread, filling it with thread data.
        function openDeleteModal(threadId, threadName) {
            document.getElementById('deleteThreadId').value = threadId;
            document.getElementById('deleteThreadName').innerText = threadName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        // Open a modal for editing a thread, filling it with thread data.
        function openEditModal(threadId, threadName) {
            document.getElementById('editThreadId').value = threadId;
            document.getElementById('editThreadNameInput').value = threadName;
            document.getElementById('editModal').style.display = 'block';
        }

        // Close a modal window by setting its display style to 'none'.
        function closeModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Search Results for "<?php if (isset($search)): echo htmlspecialchars($search); endif; ?>"</h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <?php if (isset($result) && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php if ($row['type'] == 'thread'): // Check if the result is a thread ?>
                    <div class="thread">
                        <div class="thread-title">
                            <a href="view_thread.php?thread_id=<?php echo $row['thread_id']; ?>" class="thread-link">
                                <div class="thread-main-title">
                                    <?php echo htmlspecialchars($row['content']); // Display the thread name ?>
                                </div>
                                <div class="thread-sub-title">
                                    <div>by <?php echo htmlspecialchars($row['author']); ?> on <?php echo htmlspecialchars($row['date']); ?></div>
                                </div>
                            </a>
                        </div>
                        <?php if (isset($userName)): // Show thread actions if the user is logged in ?>
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
                <?php elseif ($row['type'] == 'message'): // If the result is a message 
                    // Fetch current user information
                    $userPic = null;
                    $userExists = false;

                    // Check if the username was deleted by counting rows for the user ID.
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE ID = ?");
                    $stmt->bind_param("i", $row['user_id']);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    if ($count > 0) {
                        $userExists = true;

                        // Fetch the user's profile picture.
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
                                    echo "<a href=\"account.php\">"; // Link to account if the message belongs to the current user.
                                endif;       
                                if ($userPic):
                                    echo "<img src=\"data:image/jpeg;base64,"; echo base64_encode($userPic); echo"\" alt=\"Profile Picture\">"; // Display user profile picture.
                                else:
                                    echo "<img src=\"resources\\frame.png\">"; // Default image if no picture is found.
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

    <?php if (isset($userName)): // If the user is logged in, show modals for editing/deleting threads ?>
        <?php if ($userCanEdit) : ?>
            <!-- Modal for edit thread -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('editModal')">&times;</span>
                    <h2>Edit thread title</h2>
                    <form action="edit_thread.php" method="post">
                        <div class="modal-input">
                            <input type="hidden" id="editThreadId" name="thread_id">
                            <label for="editThreadNameInput">Thread name:</label>
                            <input type="text" id="editThreadNameInput" name="threadName" required>
                        </div>
                        <div class="modal-button">
                            <button type="submit">Save</button>
                        </div>
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
                        <div class="modal-input">
                            <input type="hidden" id="deleteThreadId" name="thread_id">
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
        <!-- Modal for login if the user is not logged in -->
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
