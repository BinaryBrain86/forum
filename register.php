<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.

$registrationSuccess = false; // Initialize a flag to track registration status

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Check if the form is submitted
    $username = $_POST['reg_username'];
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = $_POST['reg_password'];
    $role_id = 3; // Assign default role ID for new users

    // Check if the username or email already exists in the database
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE UserName = ? OR EMail = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) { // If username or email already exists
        echo "Username or email already exists. Please choose a different one.";
    } else {
        // Hash the password securely
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user's data into the usertable
        $stmt = $conn->prepare("INSERT INTO usertable (UserName, Name, FirstName, EMail, Role_ID, PW_Hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $username, $name, $firstname, $email, $role_id, $passwordHash);
        $stmt->execute();
        $stmt->close();

        $registrationSuccess = true; // Set the flag to true on successful registration
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <link rel="stylesheet" href="styles.css">
    <script>
        // Function to open a modal window
        function openModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'block';
            }
        }

        // Function to close a modal window
        function closeModal(sender) {
            if (sender != null) {
                document.getElementById(sender).style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Sign up</h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <?php if ($registrationSuccess): // Check if registration was successful ?>
            <!-- Display a success modal if registration succeeded -->
            <div id="successModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('successModal')">&times;</span>
                    <h2>Registration succeeded!</h2>
                    <p>Your account was successfully created. You can login now.</p>
                    <a href="index.php" class="button">Back to main page</a>
                </div>
            </div>
            <script>
                // Automatically open the success modal after registration
                window.onload = function() {
                    openModal('successModal');
                }
            </script>
        <?php else: // If registration failed or the form hasn't been submitted ?>
            <!-- Display the registration form -->
            <form action="register.php" method="post">
                <label for="reg_username">Username:</label>
                <input type="text" id="reg_username" name="reg_username" required>
                
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="firstname">First name:</label>
                <input type="text" id="firstname" name="firstname" required>
                
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="reg_password">Password:</label>
                <input type="password" id="reg_password" name="reg_password" required>
                
                <button type="submit">Sign up</button>
            </form>
        <?php endif; ?>
    </main>
    <!-- Modal for login -->
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
</body>
</html>
