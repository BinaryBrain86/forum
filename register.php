<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = 3; // Standard-User-Rolle

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO passwordtable (PW_Hash) VALUES (?)");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        $pw_id = $stmt->insert_id;

        $stmt = $conn->prepare("INSERT INTO usertable (UserName, Name, FirstName, EMail, PW_ID, Role_ID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $username, $name, $firstname, $email, $pw_id, $role_id);
        $stmt->execute();

        $conn->commit();
        echo "Registration successful!";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Registration failed: " . $e->getMessage();
    }
}
?>
