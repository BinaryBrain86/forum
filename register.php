<?php
session_start();
include 'db.php';

$registrationSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role_id = 3; // Default role_id for users

    // Check if the username or email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usertable WHERE UserName = ? OR EMail = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "Username or email already exists. Please choose a different one.";
    } else {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert into the passwordtable
        $stmt = $conn->prepare("INSERT INTO passwordtable (PW_Hash) VALUES (?)");
        $stmt->bind_param("s", $passwordHash);
        $stmt->execute();
        $pw_id = $stmt->insert_id;
        $stmt->close();

        // Insert into the usertable
        $stmt = $conn->prepare("INSERT INTO usertable (UserName, Name, FirstName, EMail, PW_ID, Role_ID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $username, $name, $firstname, $email, $pw_id, $role_id);
        $stmt->execute();
        $stmt->close();

        $registrationSuccess = true;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function openModal() {
            document.getElementById('successModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('successModal')) {
                closeModal();
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Sign up</h1>
    </header>
    <main>
        <?php if ($registrationSuccess): ?>
            <!-- Success Modal -->
            <div id="successModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Registration succeeded!</h2>
                    <p>Your account was successfully created. You can login now.</p>
                    <a href="index.php" class="button">Back to main page</a>
                </div>
            </div>
            <script>
                // Open the modal after registration success
                window.onload = function() {
                    openModal();
                }
            </script>
        <?php else: ?>
            <form action="register.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="firstname">First name:</label>
                <input type="text" id="firstname" name="firstname" required>
                
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                
                <button type="submit">Sign up</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
