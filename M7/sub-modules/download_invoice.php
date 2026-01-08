<?php
session_start();

include("../../main_connection.php");

$db_name = "rest_m7_billing_payments";
if (!isset($connections[$db_name])) {
    die("Database connection not found");
}

$conn = $connections[$db_name];
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM billing_payments WHERE BP_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()) {
    // Set headers for JPEG download
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="invoice_' . $row['invoice_number'] . '.jpg"');
    
    // In a real implementation, you would generate actual JPEG here
    // For now, we'll output a simple message (you'd use a library like GD or Imagick)
    
    // Example using GD (requires GD library installed)
    // $image = imagecreate(600, 800);
    // $bgColor = imagecolorallocate($image, 255, 255, 255);
    // $textColor = imagecolorallocate($image, 0, 0, 0);
    // imagestring($image, 5, 50, 50, "Invoice #" . $row['invoice_number'], $textColor);
    // imagestring($image, 5, 50, 100, "Client: " . $row['client_name'], $textColor);
    // imagestring($image, 5, 50, 150, "Amount: ₱" . number_format($row['total_amount'], 2), $textColor);
    // imagejpeg($image);
    // imagedestroy($image);
    
    // For now, output a message
    echo "JPEG generation would appear here. Install GD library or wkhtmltoimage for actual JPEG generation.";
}
?>