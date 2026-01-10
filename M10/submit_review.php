<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m10_comments_review";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input data
    $customer_name = htmlspecialchars(trim($_POST['customer_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $rating = intval($_POST['rating']);
    $feedback_text = htmlspecialchars(trim($_POST['feedback_text']));
    $category = htmlspecialchars(trim($_POST['category']));
    
    // Validate required fields
    if (empty($customer_name) || empty($email) || empty($rating) || empty($feedback_text) || empty($category)) {
        die("⚠️ Please fill all required fields.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("⚠️ Invalid email format.");
    }
    
    if ($rating < 1 || $rating > 5) {
        die("⚠️ Invalid rating value.");
    }
    
    // Prepare and execute SQL statement using MySQLi
    $stmt = $conn->prepare("INSERT INTO customer_feedback (customer_name, email, phone, rating, feedback_text, category, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    if ($stmt === false) {
        die("❌ Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssiss", $customer_name, $email, $phone, $rating, $feedback_text, $category);
    
    if ($stmt->execute()) {
        // Success - redirect back with success message
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?review=success");
        exit();
    } else {
        echo "❌ Error: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Not a POST request, redirect to form
    header("Location: comments_main.php");
    exit();
}
?>
