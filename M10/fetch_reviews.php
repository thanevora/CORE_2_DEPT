<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m10_comments_review";
if (!isset($connections[$db_name])) {
    die(json_encode(["error" => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

// Fetch reviews (latest first)
$sql = "SELECT customer_name, category, rating, feedback_text, created_at 
        FROM customer_feedback 
        ORDER BY created_at DESC LIMIT 12";
$result = $conn->query($sql);

$reviews = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($reviews);
