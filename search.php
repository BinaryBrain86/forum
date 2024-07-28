<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $search = $_GET['search'];
    $stmt = $conn->prepare("SELECT * FROM threadtable WHERE Name LIKE ? UNION SELECT * FROM messagetable WHERE Message LIKE ?");
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "<p>" . (isset($row['Name']) ? $row['Name'] : '') . " - " . (isset($row['Message']) ? $row['Message'] : '') . "</p>";
    }
}
?>
