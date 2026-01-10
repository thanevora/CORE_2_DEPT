<?php
include '../../main_connection.php';
require_once '../../PHPMailer/PHPMailerAutoload.php';
session_start();

header('Content-Type: application/json');

// --- Database connections ---
$event_db = "rest_m11_event"; 
if (!isset($connections[$event_db])) die("❌ Connection not found for $event_db");
$event_conn = $connections[$event_db];

$billing_db = "rest_m7_billing_payments";
if (!isset($connections[$billing_db])) die("❌ Connection not found for $billing_db");
$billing_conn = $connections[$billing_db];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Collect Inputs ---
        $customer_name     = $_POST['customer_name'] ?? '';
        $customer_email    = $_POST['customer_email'] ?? '';
        $customer_phone    = $_POST['customer_phone'] ?? '';
        $event_name        = $_POST['event_name'] ?? '';
        $event_type        = $_POST['event_type'] ?? '';
        $event_date        = $_POST['event_date'] ?? '';
        $event_time        = $_POST['event_time'] ?? '';
        $venue             = $_POST['venue'] ?? '';
        $num_guests        = intval($_POST['num_guests'] ?? 0);
        $special_requests  = $_POST['special_requests'] ?? '';
        $event_package     = $_POST['event_package'] ?? '';
        $MOP               = $_POST['MOP'] ?? '';

        $reservation_status = "Queued";
        $payment_status     = "Pending";
        $created_at         = date('Y-m-d H:i:s');
        $updated_at          = $created_at;

        // --- Validation ---
        if (empty($customer_name) || empty($customer_email) || empty($event_name) || empty($event_date) || empty($event_time)) {
            throw new Exception("⚠️ Required fields missing.");
        }

        // --- Financials ---
        $total_amount = isset($_POST['calculated_total']) ? floatval($_POST['calculated_total']) : 0;
        $amount_paid  = $total_amount * 0.20; // 20% downpayment

        // --- Transaction ---
        $event_conn->begin_transaction();

        // --- Insert into Event Reservations ---
        $stmt = $event_conn->prepare("INSERT INTO event_reservations 
            (customer_name, customer_email, customer_phone, event_name, event_type, event_date, event_time, venue, num_guests, special_requests, reservation_status, payment_status, total_amount, amount_paid, event_package, created_at, updated_at, MOP) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssssisssddssss",
            $customer_name, $customer_email, $customer_phone,
            $event_name, $event_type, $event_date, $event_time,
            $venue, $num_guests, $special_requests,
            $reservation_status, $payment_status,
            $total_amount, $amount_paid, $event_package,
            $created_at, $updated_at, $MOP
        );
        $stmt->execute();
        $reservation_id = $stmt->insert_id;
        $stmt->close();

        // --- Insert Billing Record ---
        $invoice_number = "EVT" . date("Ymd") . str_pad($reservation_id, 4, "0", STR_PAD_LEFT);
        $invoice_date   = date("Y-m-d");
        $billing_status = "Pending";

        $stmt_b = $billing_conn->prepare("INSERT INTO billing_payments (
            client_name, client_email, client_contact, invoice_number, invoice_date,
            status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $desc = "Event: $event_name ($event_package)";
        $payment_date = '';
        $trans_ref    = '';
        $stmt_b->bind_param(
            "ssssssssddssss",
            $customer_name, $customer_email, $customer_phone,
            $invoice_number, $invoice_date, $billing_status,
            $desc, $num_guests, $total_amount, $total_amount,
            $payment_date, $amount_paid, $trans_ref, $MOP
        );
        $stmt_b->execute();
        $stmt_b->close();

                    // --- Insert Billing Record ---
            $invoice_number = "EVT" . date("Ymd") . str_pad($reservation_id, 4, "0", STR_PAD_LEFT);
            $invoice_date   = date("Y-m-d");
            $billing_status = "Pending"; // full payment still pending
            $desc           = "Event: $event_name ($event_package)";

            // Billing fields
            $payment_date = '';  // blank until payment is made
            $trans_ref    = '';  // no reference yet

            $stmt_b = $billing_conn->prepare("INSERT INTO billing_payments (
                client_name, client_email, client_contact, invoice_number, invoice_date,
                status, description, quantity, unit_price, total_amount,
                payment_date, payment_amount, trans_ref, MOP
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // values
            $quantity_item = $num_guests;   // number of guests
            $unit_price    = $total_amount / max(1, $num_guests); // per guest estimate
            $total         = $total_amount;

            $stmt_b->bind_param(
                "ssssssssddssss",
                $customer_name, $customer_email, $customer_phone,
                $invoice_number, $invoice_date, $billing_status,
                $desc, $quantity_item, $unit_price, $total,
                $payment_date, $amount_paid, $trans_ref, $MOP
            );

            $stmt_b->execute();
            $stmt_b->close();


        $event_conn->commit();

        // --- Email Setup ---
        $subject = "Your Event Reservation Request - Soliera Restaurant";
        $logo_url = "https://restaurant.soliera-hotel-restaurant.com/images/tagline_no_bg.png";

        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', sans-serif; background:#f9f9f9; color:#333; }
                .container { max-width:600px; margin:auto; background:#fff; border-radius:8px; overflow:hidden; }
                .header { background:#1a1a1a; text-align:center; padding:20px; }
                .logo { max-width:180px; }
                .content { padding:30px; }
                .highlight { color:#c8a97e; font-weight:bold; }
                .details-box { background:#f9f9f9; padding:15px; border-left:4px solid #c8a97e; margin:20px 0; }
                .footer { background:#1a1a1a; color:#999; padding:15px; text-align:center; font-size:12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='$logo_url' class='logo' alt='Soliera Logo'>
                </div>
                <div class='content'>
                    <h2>Event Reservation Request Received</h2>
                    <p>Dear <strong>$customer_name</strong>,</p>
                    <p>Thank you for booking your event with <span class='highlight'>Soliera Restaurant</span>. We’ve received your reservation request:</p>
                    
                    <div class='details-box'>
                        <p><strong>Event:</strong> $event_name</p>
                        <p><strong>Type:</strong> $event_type</p>
                        <p><strong>Date:</strong> $event_date</p>
                        <p><strong>Time:</strong> $event_time</p>
                        <p><strong>Venue:</strong> $venue</p>
                        <p><strong>Guests:</strong> $num_guests</p>
                        <p><strong>Package:</strong> $event_package</p>
                        <p><strong>Total:</strong> ₱" . number_format($total_amount, 2) . "</p>
                        <p><strong>Downpayment (20%):</strong> ₱" . number_format($amount_paid, 2) . "</p>
                    </div>
                    
                    <p>Our events team will contact you within <strong>24 hours</strong> to confirm details.</p>
                </div>
                <div class='footer'>
                    <p>Soliera Restaurant Events • Metro Manila</p>
                    <p>📧 events@soliera.com | 🌐 www.soliera.com</p>
                </div>
            </div>
        </body>
        </html>";

        // --- Send Email ---
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'VehicleReservationManagement@gmail.com'; 
        $mail->Password   = 'fzja ezgo ojdu fobc'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('Soliera_Hotel&Restaurant@gmail.com', 'Soliera Events');
        $mail->addAddress($customer_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->send();

       header("Location: ../../landing_page/reservation_success_event.php?type=event&id=" . $reservation_id);
exit();



    } catch (Exception $e) {
        if ($event_conn->errno) $event_conn->rollback();
        error_log("❌ Event reservation error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
