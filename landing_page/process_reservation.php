<?php
include '../main_connection.php';

require_once '../PHPMailer/PHPMailerAutoload.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ---- Reservation Type Handling ----
    $reservation_type = $_POST['reservation_type'] ?? '';

    if ($reservation_type === 'table') {
        // Table reservation fields
        $name             = $_POST['name'];
        $contact          = $_POST['contact'];
        $email            = $_POST['email'];
        $reservation_date = $_POST['reservation_date'];
        $size             = $_POST['size'];
        $start_time       = $_POST['start_time'];
        $end_time         = $_POST['end_time'];
        $table_id         = $_POST['table_id'];
        $type             = $_POST['type'];
        $request          = $_POST['request'];

        // TODO: Save to database here...

        // Email content
        $subject = "Table Reservation Confirmation - Soliera Restaurant";
        $message = "
            <h2>Table Reservation Confirmation</h2>
            <p>Dear $name,</p>
            <p>Your table reservation at Soliera Restaurant has been confirmed with the following details:</p>
            
            <table style='width: 100%; border-collapse: collapse;'>
                <tr><td><strong>Reservation Date:</strong></td><td>$reservation_date</td></tr>
                <tr><td><strong>Time:</strong></td><td>$start_time to $end_time</td></tr>
                <tr><td><strong>Party Size:</strong></td><td>$size people</td></tr>
                <tr><td><strong>Reservation Type:</strong></td><td>$type</td></tr>
                <tr><td><strong>Table:</strong></td><td>$table_id</td></tr>
            </table>
            
            <p><strong>Special Requests:</strong> " . (!empty($request) ? $request : "None") . "</p>
            <p>We look forward to serving you at Soliera Restaurant.</p>
            <hr>
            <p style='font-size: 12px; color: #777;'>
                Soliera Restaurant<br>
                📞 +63-900-123-4567<br>
                📧 reservations@soliera.com<br>
                <em>This is an automated message. Please do not reply directly to this email.</em>
            </p>
        ";
        $recipient_email = $email;

    } elseif ($reservation_type === 'event') {
        // Event reservation fields
        $customer_name    = $_POST['customer_name'];
        $customer_email   = $_POST['customer_email'];
        $customer_phone   = $_POST['customer_phone'];
        $event_name       = $_POST['event_name'];
        $event_type       = $_POST['event_type'];
        $venue            = $_POST['venue'];
        $event_package    = $_POST['event_package'];
        $num_guests       = $_POST['num_guests'];
        $event_date       = $_POST['event_date'];
        $event_time       = $_POST['event_time'];
        $special_requests = $_POST['special_requests'];
        $calculated_total = $_POST['calculated_total'];

        // TODO: Save to database here...

        // Email content
        $subject = "Event Reservation Confirmation - Soliera Restaurant";
        $message = "
            <h2>Event Reservation Confirmation</h2>
            <p>Dear $customer_name,</p>
            <p>Your event reservation at Soliera Restaurant has been confirmed:</p>
            
            <table style='width: 100%; border-collapse: collapse;'>
                <tr><td><strong>Event Name:</strong></td><td>$event_name</td></tr>
                <tr><td><strong>Event Type:</strong></td><td>$event_type</td></tr>
                <tr><td><strong>Venue:</strong></td><td>$venue</td></tr>
                <tr><td><strong>Package:</strong></td><td>$event_package</td></tr>
                <tr><td><strong>Guests:</strong></td><td>$num_guests</td></tr>
                <tr><td><strong>Event Date:</strong></td><td>$event_date</td></tr>
                <tr><td><strong>Event Time:</strong></td><td>$event_time</td></tr>
                <tr><td><strong>Total:</strong></td><td>₱$calculated_total</td></tr>
            </table>
            
            <p><strong>Special Requests:</strong> " . (!empty($special_requests) ? $special_requests : "None") . "</p>
            <p>Our event coordinator will contact you within 24 hours.</p>
            <hr>
            <p style='font-size: 12px; color: #777;'>
                Soliera Restaurant Events Team<br>
                📞 +63-900-123-4567<br>
                📧 events@soliera.com<br>
                <em>This is an automated message. Please do not reply directly to this email.</em>
            </p>
        ";
        $recipient_email = $customer_email;
    }

    // ---- Send email using PHPMailer ----
    $mail = new PHPMailer(true);

    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'VehicleReservationManagement@gmail.com'; // ✅ your Gmail
        $mail->Password   = 'fzja ezgo ojdu fobc';             // ✅ your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('Soliera_Hotel&Restaurant@gmail.com', 'Soliera Restaurant');
        $mail->addAddress($recipient_email);
        $mail->addReplyTo('reservations@soliera.com', 'Reservations');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();

        // Redirect on success
        header("Location: reservation_success.php?type=" . $reservation_type);
        exit();

    } catch (Exception $e) {
        error_log("❌ Mailer Error: {$mail->ErrorInfo}");
        header("Location: reservation_error.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
