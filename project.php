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
    <title>Projects</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <link rel="stylesheet" href="styles.css">

    <script src="resources/highcharts-gantt.js"></script>

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
        <h2>Projects</h2>
        <div id="myChart"></div>
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

    <script>
        // THE CHART
        Highcharts.ganttChart('myChart', {

            title: {
                text: 'Inline-Prediction'
            },

            xAxis: [{
                min: Date.UTC(2024, 05, 15),
                max: Date.UTC(2024, 11, 30)
            }],

            series: [{
                name: 'Inline-Prediction',
                data: [{
                    name: 'Start prototype',
                    start: Date.UTC(2024, 05, 15),
                    end: Date.UTC(2024, 08, 02)
                }, {
                    name: 'Start Model build',
                    start: Date.UTC(2024, 08, 21),
                    end: Date.UTC(2024, 10, 25)
                }, {
                    name: 'Develop interface to evo',
                    start: Date.UTC(2024, 09, 09),
                    end: Date.UTC(2024, 10, 03)
                }, {
                    name: 'Develop graphical user interface',
                    start: Date.UTC(2024, 10, 04),
                    end: Date.UTC(2024, 11, 10)
                }]
            }]
        });
    </script>
</body>
</html>
