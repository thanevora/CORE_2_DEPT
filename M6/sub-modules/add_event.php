<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m11_event"; // ✅ Event DB

if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'message' => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name]; // ✅ DB connection

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // --- Collect Event Inputs ---
        $customer_name     = trim($_POST['customer_name'] ?? '');
        $customer_email    = trim($_POST['customer_email'] ?? '');
        $customer_phone    = trim($_POST['customer_phone'] ?? '');
        $event_name        = trim($_POST['event_name'] ?? '');
        $event_type        = trim($_POST['event_type'] ?? '');
        $event_date        = trim($_POST['event_date'] ?? '');
        $event_time        = trim($_POST['event_time'] ?? '');
        $venue             = trim($_POST['venue'] ?? '');
        $num_guests        = (int) ($_POST['num_guests'] ?? 0);
        $special_requests  = trim($_POST['special_requests'] ?? '');
        $event_package     = trim($_POST['event_package'] ?? '');
        $reservation_status = "Under review";
        $payment_status     = "Unpaid";
        $MOP               = trim($_POST['MOP'] ?? 'Cash');
        $image_url         = '';

        // --- Handle Image Upload ---
        if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../M6/images/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['valid_id']['name']);
            $targetPath = $uploadDir . $fileName;
            
            // Validate image file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($_FILES['valid_id']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['valid_id']['tmp_name'], $targetPath)) {
                $image_url = $fileName;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }

        // --- Validation ---
        $errors = [];
        
        if (empty($customer_name)) $errors[] = "Customer name is required";
        if (empty($customer_email)) $errors[] = "Customer email is required";
        if (empty($customer_phone)) $errors[] = "Customer phone is required";
        if (empty($event_name)) $errors[] = "Event name is required";
        if (empty($event_type)) $errors[] = "Event type is required";
        if (empty($event_date)) $errors[] = "Event date is required";
        if (empty($event_time)) $errors[] = "Event time is required";
        if (empty($venue)) $errors[] = "Venue is required";
        if ($num_guests < 1) $errors[] = "Number of guests must be at least 1";
        if (empty($event_package)) $errors[] = "Event package is required";
        
        // Validate email format
        if (!empty($customer_email) && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Validate date is not in the past
        if (!empty($event_date)) {
            $currentDate = date('Y-m-d');
            if ($event_date < $currentDate) {
                $errors[] = "Event date cannot be in the past";
            }
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(". ", $errors));
        }

        // --- Financials ---
        $total_amount = isset($_POST['calculated_total']) ? floatval($_POST['calculated_total']) : 0;
        
        // Validate total amount
        if ($total_amount <= 0) {
            throw new Exception("Invalid total amount calculated. Please check your selections.");
        }
        
        $amount_paid = $total_amount * 0.20; // ✅ Auto 20% downpayment

        // --- Insert into DB ---
        $sql = "INSERT INTO event_reservations 
                (customer_name, customer_email, customer_phone, event_name, 
                 event_type, event_date, event_time, venue, num_guests, 
                 special_requests, reservation_status, payment_status, 
                 total_amount, amount_paid, event_package, created_at, MOP, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssssssisssddsss",
            $customer_name, $customer_email, $customer_phone,
            $event_name, $event_type, $event_date, $event_time,
            $venue, $num_guests, $special_requests,
            $reservation_status, $payment_status,
            $total_amount, $amount_paid, $event_package,
            $MOP, $image_url
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $reservation_id = $stmt->insert_id;
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully!',
            'reservation_id' => $reservation_id
        ]);

    } catch (Exception $e) {
        error_log("❌ Event reservation error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>