<?php
session_start(); // Start the session to access session variables like user ID.
require 'db.php'; // Include the database connection script.

// Check if the request method is POST (i.e., if the form was submitted)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the username and password from the submitted form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare a SQL statement to select the user ID, username, role ID, and password hash
    // from the usertable where the username matches the provided username
    $stmt = $conn->prepare("SELECT ID, UserName, Role_ID, PW_Hash FROM usertable WHERE UserName = ?");
    $stmt->bind_param("s", $username); // Bind the username parameter to the SQL query
    $stmt->execute(); // Execute the SQL query
    $stmt->bind_result($user_id, $user_name, $role_id, $pw_hash); // Bind the results to variables
    $stmt->fetch(); // Fetch the result row
    
    // Verify the provided password against the stored password hash
    if (password_verify($password, $pw_hash)) {
        // If the password is correct, store the user's ID, username, and role ID in the session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $user_name;
        $_SESSION['role_id'] = $role_id;
        
        // Redirect the user to the index page
        header("Location: index.php");
        exit(); // Ensure no further code is executed after the redirect
    } else {
        // If the password is incorrect, display an error message
        echo "Invalid credentials.";
    }
}
?>
