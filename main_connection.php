
<?php
// main_connection.php

$dbHost = "127.0.0.1";
$dbUser = "3206_CENTRALIZED_DATABASE";
$dbPass = "1234";

// ✅ List only the databases you want to connect to
$targetDatabases = [
    "rest_soliera_usm",
    "rest_core_2_usm", 
    "rest_m1_trs",        
    "rest_m2_inventory",   
    "rest_m3_menu",       
    "rest_m4_pos",
    "rest_m7_billing_payments",
    "rest_m6_kot",
    "rest_m8_table_turnover",
    "rest_m9_wait_staff",
    "rest_m10_comments_review",
    "rest_m11_event",
    "rest_analytics",
];

$connections = [];
$errors = [];

foreach ($targetDatabases as $dbName) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn) {
        $connections[$dbName] = $conn;
    } else {
        $errors[] = "❌ Failed to connect to <strong>$dbName</strong>: " . mysqli_connect_error();
    }
}

// Optional: Show connection errors (for debugging only)
if (!empty($errors)) {
    echo "<h2 style='color:red;'>❌ Connection Errors:</h2><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}
?>
