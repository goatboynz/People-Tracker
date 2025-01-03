<?php
require_once 'config.php';

$conn = new SQLite3($dbfile);

// Create tables if they do not exist
$initSchema = "
CREATE TABLE IF NOT EXISTS visitors (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    contact TEXT NOT NULL,
    company TEXT,
    visiting TEXT NOT NULL,
    timestamp DATETIME NOT NULL,
    sign_out_timestamp DATETIME
);

CREATE TABLE IF NOT EXISTS terms (
    id INTEGER PRIMARY KEY,
    term_text TEXT NOT NULL
);

INSERT OR IGNORE INTO terms (term_text) VALUES
('I understand that as a guest, I am required to be always escorted by staff while on the premises, unless there is NO cannabis biomass on site.'),
('I will maintain the same high level of health and hygiene standards as staff members, including wearing gloves and other PPE items as instructed.'),
('I acknowledge that I will not be issued any form of security clearance such as PINs or key fobs.'),
('I agree that any items I bring into the facility may be subject to search before I leave the premises.'),
('If I am a contractor, I will bring only the minimum tools necessary to perform my job and understand that toolboxes may be subject to search.'),
('I will not access any areas containing cannabis biomass without an escort.'),
('If I need to leave a room where maintenance is being performed, I understand that I must leave with my escort and wait in a designated area.'),
('I agree to sign out and record the date and time of my departure before leaving the premises.'),
('I understand that a search of my belongings may be conducted before I depart, based on the duration of my visit, potential access to cannabis, and other relevant circumstances.'),
('I will comply with all instructions given by my escort and adhere to all facility rules and regulations.');
";
$conn->exec($initSchema);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'get_terms') {
        $sql = "SELECT term_text FROM terms";
        $result = $conn->query($sql);

        if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
            do {
                echo "<label><input type='checkbox' name='terms[]' value='{$row['term_text']}' required> {$row['term_text']}</label><br>";
            } while ($row = $result->fetchArray(SQLITE3_ASSOC));
        } else {
            echo "No terms found.";
        }
    } 
    elseif ($action == 'sign_in') {
        $name = $conn->escapeString($_POST['name']);
        $contact = $conn->escapeString($_POST['contact']);
        $company = $conn->escapeString($_POST['company']);
        $visiting = $conn->escapeString($_POST['visiting']);
        $timestamp = date('Y-m-d H:i:s');

        $sql = "INSERT INTO visitors (name, contact, company, visiting, timestamp) 
                VALUES ('$name', '$contact', '$company', '$visiting', '$timestamp')";

        if ($conn->exec($sql)) {
            echo "Sign-in successful!";
        } else {
            echo "Error: " . $conn->lastErrorMsg();
        }
    }
    elseif ($action == 'search_for_sign_out') {
        $searchTerm = $conn->escapeString($_POST['searchTerm']);
        $today = date('Y-m-d');

        $sql = "SELECT id, name FROM visitors 
                WHERE name LIKE '$searchTerm%' AND DATE(timestamp) = '$today' AND sign_out_timestamp IS NULL"; 
        $result = $conn->query($sql);

        if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
            do {
                echo "<button type='button' class='sign-out-button' data-visitor-id='{$row['id']}'>{$row['name']}</button><br>";
            } while ($row = $result->fetchArray(SQLITE3_ASSOC));
        } else {
            echo "No matching visitors found.";
        }
    }
    elseif ($action == 'sign_out') {
        $visitorId = (int)$_POST['visitorId'];
        $signoutTimestamp = date('Y-m-d H:i:s');

        $sql = "UPDATE visitors SET sign_out_timestamp = '$signoutTimestamp' WHERE id = $visitorId";

        if ($conn->exec($sql)) {
            echo "Sign-out successful!"; 
        } else {
            echo "Error: " . $conn->lastErrorMsg();
        }
    }
}

$conn->close();
?>
