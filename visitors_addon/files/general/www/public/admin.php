<?php
// Password authentication (Basic - Enhance for production)
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    $enteredPassword = $_POST['password'];

    if ($enteredPassword == $adminPassword) {
        // Database connection using PDO
        try {
            $conn = new PDO("sqlite:" . $dbfile);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }

        // Fetch and display visitor activity
        $sql = "SELECT * FROM visitors ORDER BY timestamp DESC LIMIT 100";
        $result = $conn->query($sql);
        
        // Output the HTML structure with the same theme as index.html
        ?><!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Facility visitor sign-in by Chill Division</title>
            <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    position: relative; 
                }
                .container {
                    width: 100%;
                    max-width: 100%;
                    padding: 1rem;
                    box-sizing: border-box;
                }
                @media (min-width: 769px) {
                    .container {
                        max-width: 1130px;
                        margin: 0 auto;
                    }
                }
                form {
                    margin-bottom: 2rem;
                }
                .admin-link {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    text-decoration: none;
                    color: #333; 
                }
                .admin-link:hover {
                    color: #000;
                }
                .material-icons {
                    font-size: 24px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #ccc;
                    padding: 0.5rem;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <main class="container">
                <h1>Visitor Activity</h1>
        <?php
        if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<table border='1'>";
            echo "<tr><th>Name</th><th>Contact #</th><th>Company</th><th>Visiting</th><th>Sign-In Time</th><th>Sign-Out Time</th></tr>";
            do {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["contact"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["company"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["visiting"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["timestamp"]) . "</td>";
                echo "<td>" .
                     ($row["sign_out_timestamp"] ? htmlspecialchars($row["sign_out_timestamp"]) : "N/A") . "</td>"; 
                echo "</tr>";
            } while ($row = $result->fetch(PDO::FETCH_ASSOC));
            echo "</table>";
        } else {
            echo "No visitor activity found.";
        }
        ?>
            </main>
        </body>
        </html>
        <?php
    } else {
        // Output the password prompt with the same theme
        ?><!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Facility visitor sign-in by Chill Division</title>
            <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    position: relative; 
                }
                .container {
                    width: 100%;
                    max-width: 100%;
                    padding: 1rem;
                    box-sizing: border-box;
                }
                @media (min-width: 769px) {
                    .container {
                        max-width: 1130px;
                        margin: 0 auto;
                    }
                }
                form {
                    margin-bottom: 2rem;
                }
                .admin-link {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    text-decoration: none;
                    color: #333; 
                }
                .admin-link:hover {
                    color: #000;
                }
                .material-icons {
                    font-size: 24px;
                }
            </style>
        </head>
        <body>
            <main class="container">
                <h1>Admin Access</h1>
                <form method="post">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit">Enter</button>
                </form>
            </main>
        </body>
        </html>
        <?php
    }
} 
?>
