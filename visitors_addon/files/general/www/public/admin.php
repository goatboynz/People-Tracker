<?php
// Password authentication (Basic - Enhance for production)
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    $enteredPassword = $_POST['password'];
    if ($enteredPassword == $adminPassword) {
        $conn = new SQLite3($dbfile);
        
        // Fetch and display visitor activity
        $sql = "SELECT * FROM visitors ORDER BY timestamp DESC LIMIT 100"; 
        $result = $conn->query($sql);

        if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<h2>Visitor Activity</h2>";
            echo "<table border='1'>";
            echo "<tr><th>Name</th><th>Contact #</th><th>Company</th><th>Visiting</th><th>Sign-In Time</th><th>Sign-Out Time</th></tr>";
            do {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["contact"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["company"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["visiting"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["timestamp"]) . "</td>";
                echo "<td>" . ($row["sign_out_timestamp"] ? htmlspecialchars($row["sign_out_timestamp"]) : "N/A") . "</td>"; 
                echo "</tr>";
            } while ($row = $result->fetchArray(SQLITE3_ASSOC));
            echo "</table>";
        } else {
            echo "No visitor activity found.";
        }
    } else {
        echo "Incorrect password. Access denied.";
    }
} else {
    // Display password prompt
    ?>
    <h2>Admin Access</h2>
    <form method="post">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Enter</button>
    </form>
    <?php
}
?>
