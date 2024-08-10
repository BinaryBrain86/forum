<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.
require 'user_info.php'; // Include the script that fetches user-related information.

$updateSuccess = false; // Flag to indicate if the update was successful

// Check if the user is logged in and the form is submitted via POST
if ($userName && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $user_id = $_SESSION['user_id']; // Retrieve the user's ID from the session
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security

    // Handle the optional profile picture upload
    $picture = null;
    if (!empty($_FILES['pic']['tmp_name'])) {
        $picture = file_get_contents($_FILES['pic']['tmp_name']); // Convert the uploaded image to binary data
    }

    // Prepare the SQL statement based on whether a picture was uploaded
    if ($picture) {
        $stmt = $conn->prepare("UPDATE usertable SET Name = ?, FirstName = ?, EMail = ?, Pic = ?, PW_Hash = ? WHERE ID = ?");
        $stmt->bind_param("sssssi", $name, $firstname, $email, $picture, $password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE usertable SET Name = ?, FirstName = ?, EMail = ?, PW_Hash = ? WHERE ID = ?");
        $stmt->bind_param("ssssi", $name, $firstname, $email, $password, $user_id);
    }
    $stmt->execute(); // Execute the update query

    $updateSuccess = true; // Set the flag to true if the update was successful
}

// Fetch the current user information for display
$userData = null;
if ($userName) {
    $user_id = $_SESSION['user_id']; // Get the user's ID from the session
    $stmt = $conn->prepare("SELECT UserName, Name, FirstName, EMail, Pic FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc(); // Retrieve the user's data
    $stmt->close(); // Close the statement
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.png">
    <link rel="stylesheet" href="styles.css">

    <script>
        // Function to open a modal window
        function openModal() {
            document.getElementById('successModal').style.display = 'block';
        }

        // Function to close a modal window
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        <?php if ($updateSuccess): ?>
            // Automatically open the success modal if the update was successful
            window.onload = function() {
                openModal();
            }
        <?php endif; ?>

        // Function to handle file upload and display file name
        function uploadPic() {
            var fileInput = document.getElementById('pic');
            var fileName = document.getElementById('file-name');
            
            if (fileInput.files.length > 0) {
                fileName.textContent = fileInput.files[0].name;
                document.getElementById('file-upload-info').style.display = 'block';
            } else {
                fileName.textContent = 'No file chosen';
                document.getElementById('file-upload-info').style.display = 'none';
            }   
        }
    </script>
</head>
<body>
    <header>
        <h1>My Account</h1>
        <?php require 'header.php'; // Include the header file for consistent page layout. ?>
    </header>
    <main>
        <?php if ($updateSuccess): ?>
            <!-- Display a success modal if the account update was successful -->
            <div id="successModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Account updated successfully!</h2>
                    <a href="index.php" class="button">Back to main page</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($userData): ?>       
            <!-- Form for updating account details, including optional profile picture -->
            <form action="account.php" method="post" class="form-account" enctype="multipart/form-data">   
                <div class="form-pic">
                    <div class="form-pic-frame">
                        <?php if ($userData['Pic']): ?>
                            <!-- Display the current profile picture if it exists -->
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($userData['Pic']); ?>" alt="Profile Picture" class="profile-pic">
                        <?php else: ?>
                            <!-- Display a placeholder if no picture is available -->
                            <img src="resources\frame.png">
                        <?php endif; ?>    
                    </div>
                    <div class="form-pic-upload">
                        <div class="file-input-container">
                            <label for="pic" class="file-input-label">Choose file</label>
                            <input type="file" id="pic" name="pic" class="file-input" onchange="uploadPic()">
                        </div>
                        <div>
                            <p id="file-name" class="file-name">No file chosen</p>
                            <p class="form-pic-upload-info">Maximum size is 300 x 300 Pixel</p>
                            <p id="file-upload-info" class="file-upload-info form-pic-upload-info">Press 'Update' to change your picture!</p>
                        </div>
                    </div>
                </div>
                <div class="form-data">
                    <!-- Display and allow the user to update their account information -->
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['UserName']); ?>" disabled>
                    
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['Name']); ?>" required>
                    
                    <label for="firstname">First name:</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($userData['FirstName']); ?>" required>
                    
                    <label for="email">E-Mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['EMail']); ?>" required>
                    
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit">Update</button>
                </div>      
            </form>
        <?php else: ?>
            <!-- Error message if the user is not logged in -->
            <p class="error-message">You must be logged in to view this page.</p>
        <?php endif; ?>
    </main>
</body>
</html>
