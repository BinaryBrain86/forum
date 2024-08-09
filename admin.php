<?php
session_start();
require 'db.php';
require 'user_info.php';

// Verify that the user is an administrator
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: index.php'); // Redirect if not an administrator
    exit();
}

// Handle form submission for updating user roles
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_roles'])) {
    $user_roles = $_POST['user_roles'];
    foreach ($user_roles as $user_id => $role_id) {
        // Update user role
        $stmt = $conn->prepare("UPDATE usertable SET Role_ID = ? WHERE ID = ?");
        $stmt->bind_param("ii", $role_id, $user_id);
        $stmt->execute();
    }
}

// Handle form submission for updating role permissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['role_permissions'])) {
    $permissions = $_POST['role_permissions'];
    foreach ($permissions as $role_id => $settings) {
        $deleteThread = isset($settings['DeleteThread']) ? 1 : 0;
        $renameThread = isset($settings['RenameThread']) ? 1 : 0;
        $deleteMessage = isset($settings['DeleteMessage']) ? 1 : 0;
        $deleteUser = isset($settings['DeleteUser']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE roletable SET DeleteThread = ?, RenameThread = ?, DeleteMessages = ?, DeleteUser = ? WHERE ID = ?");
        $stmt->bind_param("iiiii", $deleteThread, $renameThread, $deleteMessage, $deleteUser, $role_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];
    $stmt = $conn->prepare("DELETE FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
}

// Retrieve all users
$result = $conn->query("SELECT ID, UserName, Name, FirstName, EMail, Role_ID FROM usertable ORDER BY UserName ASC");
$users = $result->fetch_all(MYSQLI_ASSOC);

// Retrieve all roles and their permissions
$rolesResult = $conn->query("SELECT ID, Name, DeleteThread, RenameThread, DeleteMessages, DeleteUser FROM roletable");
$roles = $rolesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Administration Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function openDeleteModal(userId, userName) {
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').innerText = userName;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchInput').addEventListener('keyup', function() {
                var input = document.getElementById('searchInput');
                var filter = input.value.toLowerCase();
                var table = document.querySelector('.user-management-table tbody');
                var rows = table.getElementsByTagName('tr');

                for (var i = 0; i < rows.length; i++) {
                    var usernameCell = rows[i].getElementsByTagName('td')[0]; // Username is in the first cell
                    var nameCell = rows[i].getElementsByTagName('td')[1]; // Name is in the second cell
                    var firstNameCell = rows[i].getElementsByTagName('td')[2]; // First Name is in the third cell
                    var emailCell = rows[i].getElementsByTagName('td')[3]; // Email is in the fourth cell
                    
                    // Check if any cell matches the filter
                    var username = usernameCell.textContent || usernameCell.innerText;
                    var name = nameCell.textContent || nameCell.innerText;
                    var firstName = firstNameCell.textContent || firstNameCell.innerText;
                    var email = emailCell.textContent || emailCell.innerText;
                    
                    if (username.toLowerCase().indexOf(filter) > -1 ||
                        name.toLowerCase().indexOf(filter) > -1 ||
                        firstName.toLowerCase().indexOf(filter) > -1 ||
                        email.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>Administration Dashboard</h1>
        <?php require 'header.php'; ?>
    </header>
    <main>
        <!-- Role Settings -->
        <h2>Role Settings</h2>
        <form method="post" action="admin.php">
            <table class="admin-tables permissions-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Delete Thread</th>
                        <th>Rename Thread</th>
                        <th>Delete Message</th>
                        <th>Delete User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($role['Name']); ?></td>
                        <td><input type="checkbox" name="role_permissions[<?php echo $role['ID']; ?>][DeleteThread]" <?php echo $role['DeleteThread'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" name="role_permissions[<?php echo $role['ID']; ?>][RenameThread]" <?php echo $role['RenameThread'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" name="role_permissions[<?php echo $role['ID']; ?>][DeleteMessage]" <?php echo $role['DeleteMessages'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" name="role_permissions[<?php echo $role['ID']; ?>][DeleteUser]" <?php echo $role['DeleteUser'] ? 'checked' : ''; ?>></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">Save Role Permissions</button>
        </form>

        <!-- User Management -->
        <h2>User Management</h2>
        <input type="text" id="searchInput" class="search-input" placeholder="User Search">
        <form method="post" action="admin.php">
            <table class="admin-tables user-management-table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['UserName']); ?></td>
                        <td><?php echo htmlspecialchars($user['Name']); ?></td>
                        <td><?php echo htmlspecialchars($user['FirstName']); ?></td>
                        <td><?php echo htmlspecialchars($user['EMail']); ?></td>
                        <td>
                            <div class="styled-select">
                                <select name="user_roles[<?php echo $user['ID']; ?>]">
                                    <option value="1" <?php echo $user['Role_ID'] == 1 ? 'selected' : ''; ?>>Admin</option>
                                    <option value="2" <?php echo $user['Role_ID'] == 2 ? 'selected' : ''; ?>>Moderator</option>
                                    <option value="3" <?php echo $user['Role_ID'] == 3 ? 'selected' : ''; ?>>User</option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <button type="button" onclick="openDeleteModal(<?php echo $user['ID']; ?>, '<?php echo htmlspecialchars($user['UserName']); ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">Save User Roles</button>
        </form>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
            <form method="post" action="admin.php">
                <div class="modal-input">
                    <input type="hidden" name="delete_user_id" id="deleteUserId">
                </div>
                <div class="modal-button">
                    <button type="submit">Delete</button>
                    <button type="button" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
