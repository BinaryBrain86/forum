<?php
session_start();
include 'db.php';

$userName = $_SESSION['username'];
$updateSuccess = false;

if ($userName && ($_SERVER["REQUEST_METHOD"] == "POST")) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Bild hochladen und als binäre Daten speichern
    $picture = null;
    if (!empty($_FILES['pic']['tmp_name'])) {
        $picture = file_get_contents($_FILES['pic']['tmp_name']);
    }

    // Update with or without picture
    if ($picture) {
        $stmt = $conn->prepare("UPDATE usertable SET Name = ?, FirstName = ?, EMail = ?, Pic = ?, PW_Hash = ? WHERE ID = ?");
        $stmt->bind_param("sssssi", $name, $firstname, $email, $picture, $password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE usertable SET Name = ?, FirstName = ?, EMail = ?, PW_Hash = ? WHERE ID = ?");
        $stmt->bind_param("ssssi", $name, $firstname, $email, $password, $user_id);
    }
    $stmt->execute();

    $updateSuccess = true;
}

// Fetch current user information
$userData = null;
if ($userName) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT UserName, Name, FirstName, EMail, Pic FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
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

        <?php if ($updateSuccess): ?>
            window.onload = function() {
                openModal();
            }
        <?php endif; ?>

        function uploadPic() {
            var fileInput = document.getElementById('pic');
            var fileName = document.getElementById('file-name');
            fileName.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file chosen';
        }
    </script>
</head>
<body>
<header>
        <h1>My Account</h1>
        <nav>
            <a href="index.php" class="button">Back to overview</a>
            <?php if (isset($userName)): ?>
                <a href="logout.php" class="button">Logout <?php if (isset($userName)): echo htmlspecialchars($userName); endif; ?></a>
            <?php else: ?>
                <button onclick="openModal('loginModal')" class="button">Login</button>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <?php if ($updateSuccess): ?>
            <!-- Success Modal -->
            <div id="successModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Account updated successfully!</h2>
                    <a href="index.php" class="button">Back to main page</a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($userData): ?>
            
            <form action="account.php" method="post" class="form-account" enctype="multipart/form-data">
            
                <div class="form-pic">
                    <div class="form-pic-frame">
                        <?php if ($userData['Pic']): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($userData['Pic']); ?>" alt="Profile Picture" class="profile-pic">
                        <?php else: ?>
                            <img src="ressources\frame.png">
                        <?php endif; ?>    
                    </div>
                    <div class="form-pic-upload">
                        <div class="file-input-container">
                            <label for="pic" class="file-input-label">Choose file</label>
                            <input type="file" id="pic" name="pic" class="file-input" onchange="uploadPic()">
                        </div>
                        <p id="file-name" class="file-name">No file chosen</p>
                        <p for="pic" class="form-pic-upload-info">Maximum size is 300 x 300 Pixel</p>
                    </div>
                </div>
                <div class="form-data">
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
            <p class="error-message">You must be logged in to view this page.</p>
        <?php endif; ?>
    </main>
</body>
</html>