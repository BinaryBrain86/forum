<?php
session_start();
include 'db.php';

// Verifizieren, dass der Benutzer ein Administrator ist
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: index.php'); // Falls nicht Administrator, umleiten
    exit();
}

// Verarbeiten der Formularübermittlung zur Aktualisierung der Benutzerrollen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_roles'])) {
    $user_roles = $_POST['user_roles'];
    foreach ($user_roles as $user_id => $role_id) {
        // Benutzerrolle aktualisieren
        $stmt = $conn->prepare("UPDATE usertable SET Role_ID = ? WHERE ID = ?");
        $stmt->bind_param("ii", $role_id, $user_id);
        $stmt->execute();
    }
}

// Verarbeiten der Formularübermittlung zur Aktualisierung der Rollenberechtigungen
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

// Verarbeiten der Benutzerlöschung
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];
    $stmt = $conn->prepare("DELETE FROM usertable WHERE ID = ?");
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
}

// Alle Benutzer abrufen
$result = $conn->query("SELECT ID, UserName, Name, FirstName, EMail, Role_ID FROM usertable");
$users = $result->fetch_all(MYSQLI_ASSOC);

// Alle Rollen und ihre Berechtigungen abrufen
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
    </script>

</head>
<body>
    <header>
        <h1>Administration Dashboard</h1>
        <a href="account.php" class="icon-button icon-button-settings"><img src="resources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
        <div class="header-content">
            <div class="header-left">
            </div>
            <div class="header-right">
                <a href="index.php" class="button">Back to overview</a>
                <a href="logout.php" class="button">Logout <?php echo htmlspecialchars($_SESSION['username']); ?></a>
            </div>
        </div>
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
        <form method="post" action="admin.php">
            <table class="admin-tables">
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
                <input type="hidden" name="delete_user_id" id="deleteUserId">
                <button type="submit">Delete</button>
                <button type="button" onclick="closeDeleteModal()">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>
