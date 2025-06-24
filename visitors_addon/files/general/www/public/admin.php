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
    header('Location: index.html');
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
        
        // Get custom fields
        $customFields = $conn->query("SELECT id, field_name FROM custom_fields ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get visitors with custom field data
        $stmt = $conn->prepare("
            SELECT 
                v.*, 
                GROUP_CONCAT(vd.field_value, '|') as custom_values,
                GROUP_CONCAT(vd.field_id, '|') as field_ids
            FROM visitors v
            LEFT JOIN visitor_data vd ON v.id = vd.visitor_id
            GROUP BY v.id
            ORDER BY v.timestamp DESC 
            LIMIT 100
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Panel - Mediflower Visitors Sign-in</title>
            <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
            <style>
                :root {
                    --primary-color: #4CAF50;
                    --secondary-color: #E8F5E9;
                    --accent-color: #2E7D32;
                    --text-color: #333333;
                }

                .field-management {
                    background: var(--secondary-color);
                    padding: 20px;
                    border-radius: var(--border-radius);
                    margin-bottom: 30px;
                }

                .field-management form {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                }

                .field-management button {
                    margin-top: 20px;
                }

                .custom-fields-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    background: white;
                    border-radius: var(--border-radius);
                    overflow: hidden;
                }

                .custom-fields-table th {
                    background: var(--primary-color);
                    color: white;
                    padding: 12px;
                    text-align: left;
                }

                .custom-fields-table td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }

                .delete-btn {
                    background: var(--danger-color) !important;
                    padding: 5px 10px !important;
                    font-size: 14px !important;
                    width: auto !important;
                }

                .delete-btn:hover {
                    background: #c82333 !important;
                }

                .container {
                    /* max-width: 1100px; */
                    margin: 40px auto;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                }
                .visitor-table {
                    width: 100%;
                    border-collapse: collapse;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .visitor-table thead {
                    background: var(--primary-color);
                    color: white;
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }
                .visitor-table th, .visitor-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                .visitor-table tbody tr:nth-child(even) {
                    background: #f9f9f9;
                }
                .visitor-table tbody tr:hover {
                    background: #e1f5fe;
                }
                @media (max-width: 768px) {
                    .visitor-table th, .visitor-table td {
                        padding: 8px;
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="admin-header">
                    <h1>Admin Dashboard</h1>
                    <a href="?logout=1" class="contrast logout-btn">Logout</a>
                </div>
            </div>
            <main class="container">
                <h2>Custom Fields Management</h2>
                <div class="field-management">
                    <form id="add-field-form" method="post" action="process.php">
                        <input type="hidden" name="action" value="add_field">
                        <div class="grid">
                            <label>
                                Field Name:
                                <input type="text" name="field_name" required>
                            </label>
                            <label>
                                Field Type:
                                <select name="field_type" required>
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="email">Email</option>
                                    <option value="tel">Phone</option>
                                    <option value="date">Date</option>
                                </select>
                            </label>
                            <label>
                                Required:
                                <input type="checkbox" name="is_required">
                            </label>
                            <label>
                                Display Order:
                                <input type="number" name="display_order" required>
                            </label>
                        </div>
                        <button type="submit">Add Field</button>
                    </form>
                </div>

                <h3>Current Custom Fields</h3>
                <div class="custom-fields-list">
                    <?php
                    $stmt = $conn->query("SELECT * FROM custom_fields ORDER BY display_order");
                    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($fields) > 0): ?>
                        <table class="custom-fields-table">
                            <thead>
                                <tr>
                                    <th>Field Name</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fields as $field): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($field['field_name']) ?></td>
                                        <td><?= htmlspecialchars($field['field_type']) ?></td>
                                        <td><?= $field['is_required'] ? 'Yes' : 'No' ?></td>
                                        <td><?= htmlspecialchars($field['display_order']) ?></td>
                                        <td>
                                            <form method="post" action="process.php" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_field">
                                                <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                                                <button type="submit" class="delete-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No custom fields defined yet.</p>
                    <?php endif; ?>
                </div>

                <h2>Visitor Activity</h2>
                <div style="overflow-x:auto;">
                <?php if (count($results) > 0): ?>
                    <table class="visitor-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact #</th>
                                <th>Company</th>
                                <th>Visiting</th>
                                <?php foreach ($customFields as $field): ?>
                                    <th><?= htmlspecialchars($field['field_name']) ?></th>
                                <?php endforeach; ?>
                                <th>Photo</th>
                                <th>Signature</th>
                                <th>Sign-In Time</th>
                                <th>Sign-Out Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact']) ?></td>
                                    <td><?= htmlspecialchars($row['company']) ?></td>
                                    <td><?= htmlspecialchars($row['visiting']) ?></td>
                                    <?php 
                                    $customValues = $row['custom_values'] ? explode('|', $row['custom_values']) : [];
                                    $fieldIds = $row['field_ids'] ? explode('|', $row['field_ids']) : [];
                                    foreach ($customFields as $field): 
                                        $index = array_search($field['id'], $fieldIds);
                                        $value = ($index !== false && isset($customValues[$index])) ? $customValues[$index] : 'N/A';
                                    ?>
                                        <td><?= htmlspecialchars($value) ?></td>
                                    <?php endforeach; ?>
                                    <td>
                                        <?php if (!empty($row['photo_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['photo_path']) ?>" target="_blank">View Photo</a>
                                        <?php else: ?>
                                            No Photo
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['signature_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['signature_path']) ?>" target="_blank">View Signature</a>
                                        <?php else: ?>
                                            No Signature
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['timestamp']) ?></td>
                                    <td><?= !empty($row["sign_out_timestamp"]) ? htmlspecialchars($row["sign_out_timestamp"]) : "N/A" ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p>No visitor activity found.</p>
                    <?php endif; ?>
                </div>
            </main>
        </body>
        </html>
        <?php
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
        <title>Admin Access - Mediflower Visitors Sign-in</title>
        <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@latest/css/pico.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <style>
            :root {
                --spacing: 1rem;
                --typography-spacing-vertical: 1.5rem;
                --primary-color: #4CAF50;
                --secondary-color: #E8F5E9;
                --accent-color: #2E7D32;
                --text-align: center;
                --danger-color: #dc3545;
                --text-color: #333;
                --card-background: #fff;
                --border-radius: 8px;
                --table-header-bg: var(--primary-color);
                --table-header-color: #fff;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                background: var(--background-color);
                color: var(--text-color);
            }

            .container {
                width: 90%;
                max-width: 1100px;
                margin: 40px auto;
            }

            h2 {
                color: var(--primary-color);
                text-align: center;
            }

            .admin-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .logout-btn {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                background: var(--danger-color);
                color: white;
                border-radius: var(--border-radius);
                text-decoration: none;
                font-weight: bold;
                transition: 0.3s;
            }

            .logout-btn:hover {
                background: #c82333;
            }

            .material-icons {
                margin-right: 5px;
                vertical-align: middle;
            }

            .visitor-table {
                width: 100%;
                border-collapse: collapse;
                background: var(--card-background);
                border-radius: var(--border-radius);
                overflow: hidden;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            }

            .visitor-table th {
                background: var(--table-header-bg);
                color: var(--table-header-color);
                padding: 12px;
                text-align: left;
            }

            .visitor-table td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }

            .visitor-table tbody tr:nth-child(even) {
                background: #f9f9f9;
            }

            .visitor-table tbody tr:hover {
                background: #e1f5fe;
            }

            .login-container {
                max-width: 400px;
                margin: 80px auto;
                background: var(--card-background);
                padding: 20px;
                border-radius: var(--border-radius);
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            }

            input[type="password"] {
                width: 100%;
                padding: 10px;
                margin-top: 5px;
                border-radius: var(--border-radius);
                border: 1px solid #ccc;
            }

            button {
                width: 100%;
                background: var(--primary-color);
                color: white;
                padding: 12px;
                border: none;
                border-radius: var(--border-radius);
                cursor: pointer;
                font-size: 16px;
            }

            button:hover {
                background: var(--accent-color);
            }

            .error-message {
                color: var(--danger-color);
                background: #f8d7da;
                padding: 10px;
                border-radius: var(--border-radius);
                margin-bottom: 15px;
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
