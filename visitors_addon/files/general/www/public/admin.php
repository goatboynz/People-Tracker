<?php
require_once 'config.php';

// Start session for login management
session_start();

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

function check_login_attempts() {
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $time_passed = time() - $_SESSION['last_attempt_time'];
        if ($time_passed < LOGIN_TIMEOUT) {
            $wait_time = LOGIN_TIMEOUT - $time_passed;
            die("Too many failed attempts. Please wait " . ceil($wait_time/60) . " minutes before trying again.");
        } else {
            // Reset attempts after timeout
            $_SESSION['login_attempts'] = 0;
        }
    }
}

function process_login($password) {
    global $adminPassword;
    
    // Record attempt time
    $_SESSION['last_attempt_time'] = time();
    
    // Use constant-time comparison
    if (hash_equals(hash('sha256', $adminPassword), hash('sha256', $password))) {
        $_SESSION['authenticated'] = true;
        $_SESSION['login_attempts'] = 0;
        return true;
    }
    
    $_SESSION['login_attempts']++;
    return false;
}

// Check if logged out
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    check_login_attempts();
    
    $enteredPassword = sanitize_input($_POST['password']);
    
    if (!process_login($enteredPassword)) {
        $error_message = "Incorrect password. Access denied.";
    }
}

// If authenticated, show admin panel
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    try {
        $conn = new PDO("sqlite:" . $dbfile);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Use prepared statement for fetching visitors
        $stmt = $conn->prepare("SELECT * FROM visitors ORDER BY timestamp DESC LIMIT 100");
        $stmt->execute();
        
        echo "<div class='admin-header'>";
        echo "<h2>Visitor Activity</h2>";
        echo "<a href='?logout=1' class='logout-btn'>Logout</a>";
        echo "</div>";
        
        if ($stmt->rowCount() > 0) {
            echo "<table class='visitor-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Name</th>";
            echo "<th>Contact #</th>";
            echo "<th>Company</th>";
            echo "<th>Visiting</th>";
            echo "<th>Sign-In Time</th>";
            echo "<th>Sign-Out Time</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                foreach (['name', 'contact', 'company', 'visiting', 'timestamp'] as $field) {
                    echo "<td>" . htmlspecialchars($row[$field], ENT_QUOTES, 'UTF-8') . "</td>";
                }
                echo "<td>" . 
                     ($row["sign_out_timestamp"] ? 
                      htmlspecialchars($row["sign_out_timestamp"], ENT_QUOTES, 'UTF-8') : 
                      "N/A") . 
                     "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            
            // Add basic CSS for better presentation
            echo "<style>
                  .admin-header { display: flex; justify-content: space-between; align-items: center; }
                  .logout-btn { padding: 10px; background: #f44336; color: white; text-decoration: none; border-radius: 4px; }
                  .visitor-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                  .visitor-table th, .visitor-table td { padding: 12px; text-align: left; border: 1px solid #ddd; }
                  .visitor-table thead { background-color: #f5f5f5; }
                  .visitor-table tr:nth-child(even) { background-color: #f9f9f9; }
                  </style>";
        } else {
            echo "<p>No visitor activity found.</p>";
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo "<p class='error'>An error occurred while fetching visitor data.</p>";
    }
} else {
    // Display login form with error handling
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Access</title>
        <style>
            .login-container {
                max-width: 400px;
                margin: 50px auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .error-message {
                color: #f44336;
                margin-bottom: 15px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            label {
                display: block;
                margin-bottom: 5px;
            }
            input[type="password"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            button {
                background: #4CAF50;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            button:hover {
                background: #45a049;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>Admin Access</h2>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
