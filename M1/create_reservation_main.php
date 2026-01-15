<?php
include '../main_connection.php';
require_once '../PHPMailer/PHPMailerAutoload.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request for debugging
error_log("=== RESERVATION API CALLED ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

$db_name = "rest_m1_trs"; // Reservations DB
if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'message' => 'Connection not found for reservations DB']));
}
$conn = $connections[$db_name];

// Billing database
$billing_db_name = "rest_m7_billing_payments";
if (!isset($connections[$billing_db_name])) {
    die(json_encode(['success' => false, 'message' => 'Connection not found for billing DB']));
}
$billing_conn = $connections[$billing_db_name];

// Function to send reservation confirmation email
function sendReservationConfirmation($email, $reservationDetails) {
    try {
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'soliera.restaurant@gmail.com';
        $mail->Password = 'rpyo ncni ulhv lhpx';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 0;

        $mail->setFrom('soliera.restaurant@gmail.com', 'Soliera Hotel & Restaurant');
        $mail->addAddress($email);
        $mail->Subject = 'Reservation Confirmation - Soliera Hotel & Restaurant';

        // Email content
        $header = "<div style='background-color: #001f54; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; color: #F7B32B;'>Soliera Hotel & Restaurant</h1>
                    <p style='margin: 5px 0; font-size: 16px;'>Reservation Confirmation</p>
                   </div>
                   <hr style='border: 2px solid #F7B32B; margin: 0;'>";
        
        $message = "<div style='padding: 20px; font-family: Arial, sans-serif;'>
                        <p style='font-size: 16px;'>Dear <strong>{$reservationDetails['name']}</strong>,</p>
                        <p style='font-size: 16px;'>Thank you for choosing Soliera Hotel & Restaurant! Your reservation has been confirmed.</p>
                        
                        <div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #F7B32B; margin: 20px 0;'>
                            <h3 style='color: #001f54; margin-top: 0;'>Reservation Details:</h3>
                            <p><strong>Reservation ID:</strong> #{$reservationDetails['reservation_id']}</p>
                            <p><strong>Date:</strong> {$reservationDetails['reservation_date']}</p>
                            <p><strong>Time:</strong> {$reservationDetails['start_time']}</p>
                            <p><strong>Party Size:</strong> {$reservationDetails['party_size']} person(s)</p>
                            <p><strong>Table:</strong> {$reservationDetails['table_name']}</p>
                            <p><strong>Status:</strong> {$reservationDetails['status']}</p>";
        
        if ($reservationDetails['parts'] == 'hotel') {
            $message .= "<p><strong>Room Number:</strong> {$reservationDetails['room_id']}</p>
                         <p><strong>Guest Type:</strong> Hotel Guest (10% discount applied)</p>";
        } else {
            $message .= "<p><strong>Guest Type:</strong> Restaurant Guest</p>";
        }
        
        $message .= "</div>";
        
        if (!empty($reservationDetails['order_items'])) {
            $message .= "<div style='margin: 20px 0;'>
                            <h3 style='color: #001f54;'>Order Summary:</h3>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <thead>
                                    <tr style='background-color: #001f54; color: white;'>
                                        <th style='padding: 10px; text-align: left;'>Item</th>
                                        <th style='padding: 10px; text-align: center;'>Qty</th>
                                        <th style='padding: 10px; text-align: right;'>Price</th>
                                        <th style='padding: 10px; text-align: right;'>Total</th>
                                    </tr>
                                </thead>
                                <tbody>";
            
            foreach ($reservationDetails['order_items'] as $item) {
                $message .= "<tr style='border-bottom: 1px solid #ddd;'>
                                <td style='padding: 10px;'>{$item['name']}</td>
                                <td style='padding: 10px; text-align: center;'>{$item['quantity']}</td>
                                <td style='padding: 10px; text-align: right;'>₱" . number_format($item['price'], 2) . "</td>
                                <td style='padding: 10px; text-align: right;'>₱" . number_format($item['total'], 2) . "</td>
                             </tr>";
            }
            
            if ($reservationDetails['parts'] == 'hotel' && $reservationDetails['hotel_discount'] > 0) {
                $message .= "<tr style='background-color: #f0fff0;'>
                                <td colspan='3' style='padding: 10px; text-align: right;'><strong>Hotel Guest Discount (10%):</strong></td>
                                <td style='padding: 10px; text-align: right; color: green;'><strong>-₱" . number_format($reservationDetails['hotel_discount'], 2) . "</strong></td>
                             </tr>";
            }
            
            $message .= "</tbody></table></div>";
        }
        
        $message .= "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #001f54;'>Payment Information:</h3>
                        <p><strong>Total Amount:</strong> ₱" . number_format($reservationDetails['total_amount'], 2) . "</p>
                        <p><strong>Payment Method:</strong> {$reservationDetails['MOP']}</p>
                        <p><strong>Invoice Number:</strong> {$reservationDetails['invoice_number']}</p>
                     </div>";
        
        $footer = "<hr style='border: 1px solid #ddd; margin: 20px 0;'>
                   <div style='text-align: center; color: #666; font-size: 14px;'>
                        <p><strong>Important Notes:</strong></p>
                        <p>• Please arrive 15 minutes before your reservation time.</p>";
        
        if ($reservationDetails['parts'] == 'hotel') {
            $footer .= "<p>• Please bring your room key for verification.</p>
                        <p>• Charges can be billed directly to your room upon request.</p>";
        }
        
        $footer .= "<p>• For cancellations or modifications, please contact us at least 24 hours in advance.</p>
                        <p>• Bring this confirmation email or your reservation ID when you arrive.</p>
                   </div>
                   <div style='background-color: #001f54; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; margin-top: 20px;'>
                        <p style='margin: 0;'>📞 Contact: +63-900-123-4567 | 📧 support@soliera.com</p>
                        <p style='margin: 5px 0 0 0; font-size: 12px;'>123 Restaurant Street, Manila, Philippines</p>
                        <p style='margin: 5px 0 0 0; font-size: 12px; color: #F7B32B;'>We look forward to serving you!</p>
                   </div>";
        
        $mail->isHTML(true);
        $mail->Body = $header . $message . $footer;
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Debug: Log POST data
        error_log("POST Data Received: " . print_r($_POST, true));
        
        // --- Validate required fields ---
        $required_fields = ['name', 'contact', 'email', 'reservation_date', 'start_time', 'party_size', 'table_id', 'MOP', 'parts'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
        }
        
        // --- Collect inputs with validation ---
        $reservation_type = $_POST['reservation_type'] ?? 'table';
        $name             = trim($_POST['name']);
        $contact          = trim($_POST['contact']);
        $email            = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception("Invalid email address");
        }
        
        $reservation_date = $_POST['reservation_date'];
        $start_time       = $_POST['start_time'];
        $size             = intval($_POST['party_size']);
        if ($size < 1) {
            throw new Exception("Party size must be at least 1");
        }
        
        $type             = 'Table Reservation';
        $request          = trim($_POST['request'] ?? '');
        $note             = trim($_POST['note'] ?? '');
        $MOP              = $_POST['MOP'];
        $table_id         = intval($_POST['table_id']);
        $online_method    = $_POST['online_method'] ?? '';
        $parts            = $_POST['parts'] ?? 'resto'; // 'hotel' or 'resto'
        $room_id          = $_POST['room_id'] ?? null;
        $hotel_discount   = floatval($_POST['hotel_discount'] ?? 0);

        // Validate date is not in the past
        $today = date('Y-m-d');
        if ($reservation_date < $today) {
            throw new Exception("Reservation date cannot be in the past");
        }

        $status           = 'Queued';
        $created_at       = date('Y-m-d H:i:s');
        $payment_status   = 'Pending';
        $compliance       = 'Pending';
        $payment_type     = $MOP == "Cash" ? "Cash" : "Online";

        // --- Get table name ---
        $table_name = "Standard Table";
        if ($table_id && isset($connections['rest_m3_tables'])) {
            $table_conn = $connections['rest_m3_tables'];
            $stmt_table = $table_conn->prepare("SELECT name FROM tables WHERE id = ?");
            if (!$stmt_table) {
                throw new Exception("Table query preparation failed: " . $table_conn->error);
            }
            $stmt_table->bind_param("i", $table_id);
            $stmt_table->execute();
            $result_table = $stmt_table->get_result();
            if ($row = $result_table->fetch_assoc()) {
                $table_name = $row['name'];
            }
            $stmt_table->close();
        }

        // --- Get menu items ---
        $order_items = [];
        $total_amount = 0;

        // Check if menu_items is a JSON string (from JavaScript) or array
        if (!empty($_POST['menu_items'])) {
            if (is_string($_POST['menu_items'])) {
                // Decode JSON string
                $menu_items_json = json_decode($_POST['menu_items'], true);
                if ($menu_items_json && is_array($menu_items_json)) {
                    $_POST['menu_items'] = $menu_items_json;
                }
            }
            
            if (is_array($_POST['menu_items'])) {
                $menu_db_name = "rest_m3_menu";
                if (!isset($connections[$menu_db_name])) {
                    throw new Exception("Menu database connection not found");
                }
                
                $menu_conn = $connections[$menu_db_name];

                foreach ($_POST['menu_items'] as $item_id => $quantity) {
                    $quantity = intval($quantity);
                    if ($quantity < 1) continue;

                    $stmt = $menu_conn->prepare("SELECT menu_id, name, price FROM menu WHERE menu_id = ?");
                    if (!$stmt) {
                        throw new Exception("Menu query preparation failed: " . $menu_conn->error);
                    }
                    $stmt->bind_param("i", $item_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($item = $result->fetch_assoc()) {
                        $item_price = floatval($item['price']);
                        $item_total = $item_price * $quantity;
                        $order_items[] = [
                            'id' => $item['menu_id'],
                            'name' => $item['name'],
                            'price' => $item_price,
                            'quantity' => $quantity,
                            'total' => $item_total
                        ];
                        $total_amount += $item_total;
                    }
                    $stmt->close();
                }
            }
        }

        // --- Calculate additional fees ---
        $reservation_fee = $size * 200; // ₱200 per person
        $service_charge = $total_amount * 0.08; // 8% service charge
        
        // Apply hotel discount if applicable
        if ($parts == 'hotel' && $hotel_discount > 0) {
            $total_amount -= $hotel_discount;
            if ($total_amount < 0) $total_amount = 0;
        }
        
        $vat = ($total_amount + $reservation_fee + $service_charge) * 0.12; // 12% VAT
        $grand_total = $total_amount + $reservation_fee + $service_charge + $vat;

        error_log("Parts: $parts, Hotel Discount: $hotel_discount, Menu total: $total_amount, Grand total: $grand_total");

        // --- Begin transaction ---
        $conn->begin_transaction();

        // --- Prepare variables for bind_param ---
        $size_val  = $size;
        $table_val = $table_id;
        $grand_total_val = (string)$grand_total; // Convert to string for bind_param
        $room_id_val = $room_id ? $room_id : null;

        // Check if parts column exists
        $check_parts = $conn->query("SHOW COLUMNS FROM reservations LIKE 'parts'");
        $has_parts_column = ($check_parts && $check_parts->num_rows > 0);
        
        // Check if room_id column exists
        $check_room_id = $conn->query("SHOW COLUMNS FROM reservations LIKE 'room_id'");
        $has_room_id_column = ($check_room_id && $check_room_id->num_rows > 0);

        // --- Insert reservation ---
        if ($has_parts_column && $has_room_id_column) {
            // Both columns exist
            $stmt = $conn->prepare("INSERT INTO reservations (
                name, contact, email, reservation_date, start_time,
                size, status, request, type, created_at, note, table_id, MOP,
                compliance, amount, payment_type, payment_status, parts, room_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Reservation query preparation failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssisssssisssssss",
                $name,
                $contact,
                $email,
                $reservation_date,
                $start_time,
                $size_val,
                $status,
                $request,
                $type,
                $created_at,
                $note,
                $table_val,
                $MOP,
                $compliance,
                $grand_total_val,
                $payment_type,
                $payment_status,
                $parts,
                $room_id_val
            );
        } elseif ($has_parts_column) {
            // Only parts column exists
            $stmt = $conn->prepare("INSERT INTO reservations (
                name, contact, email, reservation_date, start_time,
                size, status, request, type, created_at, note, table_id, MOP,
                compliance, amount, payment_type, payment_status, parts
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Reservation query preparation failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssisssssissssss",
                $name,
                $contact,
                $email,
                $reservation_date,
                $start_time,
                $size_val,
                $status,
                $request,
                $type,
                $created_at,
                $note,
                $table_val,
                $MOP,
                $compliance,
                $grand_total_val,
                $payment_type,
                $payment_status,
                $parts
            );
        } else {
            // Neither column exists
            $stmt = $conn->prepare("INSERT INTO reservations (
                name, contact, email, reservation_date, start_time,
                size, status, request, type, created_at, note, table_id, MOP,
                compliance, amount, payment_type, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Reservation query preparation failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssisssssisssss",
                $name,
                $contact,
                $email,
                $reservation_date,
                $start_time,
                $size_val,
                $status,
                $request,
                $type,
                $created_at,
                $note,
                $table_val,
                $MOP,
                $compliance,
                $grand_total_val,
                $payment_type,
                $payment_status
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Reservation insertion failed: " . $stmt->error);
        }
        
        $reservation_id = $stmt->insert_id;
        $stmt->close();

        // --- Insert billing records ---
        $invoice_number = "INV" . date("Ymd") . str_pad($reservation_id, 4, "0", STR_PAD_LEFT);
        $invoice_date   = date("Y-m-d");
        $billing_status = $MOP == "Online" ? "Paid" : "Pending";

        if (!empty($order_items)) {
            foreach ($order_items as $item) {
                // Check if billing_payments table has reservation_id column
                $check_column = $billing_conn->query("SHOW COLUMNS FROM billing_payments LIKE 'reservation_id'");
                
                // Prepare variables for binding
                $desc          = $item['name'];
                $quantity_item = $item['quantity'];
                $unit_price    = (string)$item['price']; // Convert to string
                $total_item    = (string)$item['total']; // Convert to string
                $payment_date  = $MOP == "Online" ? date('Y-m-d H:i:s') : null;
                $trans_ref     = $MOP == "Online" ? "ONLINE_" . uniqid() : null;
                
                if ($check_column && $check_column->num_rows > 0) {
                    // Table has reservation_id column
                    $stmt_b = $billing_conn->prepare("INSERT INTO billing_payments (
                        client_name, client_email, client_contact, invoice_number, invoice_date,
                        status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP, reservation_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt_b) {
                        $stmt_b->bind_param(
                            "ssssssssddssssi",
                            $name,
                            $email,
                            $contact,
                            $invoice_number,
                            $invoice_date,
                            $billing_status,
                            $desc,
                            $quantity_item,
                            $unit_price,
                            $total_item,
                            $payment_date,
                            $total_item,
                            $trans_ref,
                            $MOP,
                            $reservation_id
                        );

                        if (!$stmt_b->execute()) {
                            throw new Exception("Billing insertion failed: " . $stmt_b->error);
                        }
                        $stmt_b->close();
                    }
                } else {
                    // Table does NOT have reservation_id column
                    $stmt_b = $billing_conn->prepare("INSERT INTO billing_payments (
                        client_name, client_email, client_contact, invoice_number, invoice_date,
                        status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt_b) {
                        $stmt_b->bind_param(
                            "ssssssssddssss",
                            $name,
                            $email,
                            $contact,
                            $invoice_number,
                            $invoice_date,
                            $billing_status,
                            $desc,
                            $quantity_item,
                            $unit_price,
                            $total_item,
                            $payment_date,
                            $total_item,
                            $trans_ref,
                            $MOP
                        );

                        if (!$stmt_b->execute()) {
                            throw new Exception("Billing insertion failed: " . $stmt_b->error);
                        }
                        $stmt_b->close();
                    }
                }
            }
        }

        // Also add reservation fee as separate billing item
        if ($reservation_fee > 0) {
            // Check if billing_payments table has reservation_id column
            $check_column = $billing_conn->query("SHOW COLUMNS FROM billing_payments LIKE 'reservation_id'");
            
            // Prepare variables for binding
            $desc2 = "Reservation Fee (₱200 x {$size} person(s))";
            $payment_date2 = $MOP == "Online" ? date('Y-m-d H:i:s') : null;
            $trans_ref2 = $MOP == "Online" ? "RESFEE_" . uniqid() : null;
            $unit_price_fee = "200.00"; // String value
            $reservation_fee_str = (string)$reservation_fee; // Convert to string
            
            if ($check_column && $check_column->num_rows > 0) {
                // Table has reservation_id column
                $stmt_b2 = $billing_conn->prepare("INSERT INTO billing_payments (
                    client_name, client_email, client_contact, invoice_number, invoice_date,
                    status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP, reservation_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt_b2) {
                    $stmt_b2->bind_param(
                        "ssssssssddssssi",
                        $name,
                        $email,
                        $contact,
                        $invoice_number,
                        $invoice_date,
                        $billing_status,
                        $desc2,
                        $size_val,
                        $unit_price_fee,
                        $reservation_fee_str,
                        $payment_date2,
                        $reservation_fee_str,
                        $trans_ref2,
                        $MOP,
                        $reservation_id
                    );

                    if (!$stmt_b2->execute()) {
                        throw new Exception("Reservation fee billing insertion failed: " . $stmt_b2->error);
                    }
                    $stmt_b2->close();
                }
            } else {
                // Table does NOT have reservation_id column
                $stmt_b2 = $billing_conn->prepare("INSERT INTO billing_payments (
                    client_name, client_email, client_contact, invoice_number, invoice_date,
                    status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt_b2) {
                    $stmt_b2->bind_param(
                        "ssssssssddssss",
                        $name,
                        $email,
                        $contact,
                        $invoice_number,
                        $invoice_date,
                        $billing_status,
                        $desc2,
                        $size_val,
                        $unit_price_fee,
                        $reservation_fee_str,
                        $payment_date2,
                        $reservation_fee_str,
                        $trans_ref2,
                        $MOP
                    );

                    if (!$stmt_b2->execute()) {
                        throw new Exception("Reservation fee billing insertion failed: " . $stmt_b2->error);
                    }
                    $stmt_b2->close();
                }
            }
        }

        // --- Commit transaction ---
        if (!$conn->commit()) {
            throw new Exception("Transaction commit failed");
        }

        error_log("Reservation #{$reservation_id} created successfully for {$name} (Parts: {$parts})");

        // --- Send confirmation email ---
        $reservationDetails = [
            'reservation_id' => $reservation_id,
            'name' => $name,
            'reservation_date' => date('F j, Y', strtotime($reservation_date)),
            'start_time' => date('g:i A', strtotime($start_time)),
            'party_size' => $size,
            'table_name' => $table_name,
            'status' => $status,
            'order_items' => $order_items,
            'total_amount' => $grand_total,
            'MOP' => $MOP,
            'invoice_number' => $invoice_number,
            'parts' => $parts,
            'room_id' => $room_id,
            'hotel_discount' => $hotel_discount
        ];

        // Send email
        $email_sent = sendReservationConfirmation($email, $reservationDetails);
        
        // Store in session for success page
        $_SESSION['reservation_success'] = [
            'reservation_id' => $reservation_id,
            'name' => $name,
            'email' => $email,
            'date' => $reservation_date,
            'time' => $start_time,
            'party_size' => $size,
            'table_name' => $table_name,
            'total_amount' => number_format($grand_total, 2),
            'invoice_number' => $invoice_number,
            'email_sent' => $email_sent,
            'MOP' => $MOP,
            'parts' => $parts,
            'room_id' => $room_id
        ];
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully',
            'reservation_id' => $reservation_id,
            'email_sent' => $email_sent,
            'parts' => $parts
        ]);
        exit();

    } catch (Exception $e) {
        // Rollback transaction if it was started
        if (isset($conn) && $conn && $conn instanceof mysqli) {
            $conn->rollback();
        }
        
        error_log("Reservation Error: " . $e->getMessage());
        error_log("Error Trace: " . $e->getTraceAsString());
        
        // Return JSON error
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}