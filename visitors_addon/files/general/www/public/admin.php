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
        
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Admin Panel - Visitors Sign-in</title>";
        echo "<link rel='stylesheet' href='https://unpkg.com/@picocss/pico@latest/css/pico.min.css'>";
        echo "<link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>";
        echo "<style>";
        echo file_get_contents(__DIR__ . '/admin.php', false, null, strpos(file_get_contents(__DIR__ . '/admin.php'), '<style>') + 7, strpos(file_get_contents(__DIR__ . '/admin.php'), '</style>') - strpos(file_get_contents(__DIR__ . '/admin.php'), '<style>') - 7);
        echo "</style>";
        echo "</head>";
        echo "<body>";
        echo "<main class='container'>";
        echo "<div class='admin-header'>";
        echo "<h2>Visitor Activity</h2>";
        echo "<a href='?logout=1' class='logout-btn'><span class='material-icons'>logout</span> Logout</a>";
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
            echo "</main>";
            echo "</body>";
            echo "</html>";
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
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Access - Visitors Sign-in</title>
        <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <style>
            :root {
                --spacing: 1rem;
                --typography-spacing-vertical: 1.5rem;
            }
            
            body {
                margin: 0;
                padding: 0;
                background: var(--background-color);
                color: var(--color);
            }

            h1, h2, h3, h4, h5, h6 {
                color: var(--h1-color);
                margin-bottom: var(--typography-spacing-vertical);
            }

            .container {
                width: 100%;
                max-width: 100%;
                padding: var(--spacing);
                box-sizing: border-box;
            }

            @media (min-width: 769px) {
                .container {
                    max-width: 1130px;
                    margin: 0 auto;
                }
            }

            .login-container {
                max-width: 400px;
                margin: calc(var(--spacing) * 3) auto;
            }

            article {
                background: var(--card-background-color);
                padding: var(--block-spacing-vertical) var(--block-spacing-horizontal);
                border-radius: var(--border-radius);
            }

            .error-message {
                color: var(--del-color);
                margin-bottom: var(--spacing);
                padding: var(--spacing);
                background: var(--card-sectionning-background-color);
                border-radius: var(--border-radius);
            }

            .admin-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: calc(var(--spacing) * 2);
            }
            button .material-icons,
            .logout-btn .material-icons {
                font-size: 20px;
                margin-right: 0.5rem;
                vertical-align: text-bottom;
            }
            .logout-btn {
                display: inline-flex;
                align-items: center;
                padding: 0.75rem 1rem;
                background: var(--del-color);
                color: var(--contrast);
                border-radius: var(--border-radius);
                text-decoration: none;
                transition: background-color 0.2s ease;
            }
            .logout-btn:hover {
                background: var(--del-color-hover);
                color: var(--contrast);
            }
            button[type="submit"] {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .visitor-table {
                margin-top: calc(var(--spacing) * 2);
                width: 100%;
                border-collapse: collapse;
                background: var(--card-background-color);
                border-radius: var(--border-radius);
                overflow: hidden;
            }

            .visitor-table th, 
            .visitor-table td {
                text-align: left;
                padding: var(--spacing);
                border: 1px solid var(--card-border-color);
            }

            .visitor-table thead {
                background-color: var(--card-sectionning-background-color);
            }

            .visitor-table thead th {
                color: var(--h3-color);
                font-weight: 600;
            }

            .visitor-table tbody tr:nth-child(even) {
                background-color: var(--card-sectionning-background-color);
            }

            .visitor-table tbody tr:hover {
                background-color: var(--card-background-color);
            }
        </style>
    </head>
    <body>
        <main class="container">
            <div class="login-container">
                <article>
                    <h2>Admin Access</h2>
                    <?php if (isset($error_message)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="grid">
                            <label for="password">
                                Password
                                <input type="password" id="password" name="password" required>
                            </label>
                        </div>
                        <button type="submit" class="contrast">
                            <span class="material-icons">login</span>
                            Login
                        </button>
                    </form>
                </article>
            </div>
        </main>
    </body>
    </html>
    <?php
}
?>
