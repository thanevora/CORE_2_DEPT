<?php
include '../main_connection.php';
require_once '../PHPMailer/PHPMailerAutoload.php';
session_start();

$db_name = "rest_m1_trs"; // Reservations DB
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

// Billing database
$billing_db_name = "rest_m7_billing_payments";
$billing_conn = $connections[$billing_db_name] ?? die("❌ Connection not found for $billing_db_name");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Collect inputs with defaults ---
        $reservation_type = $_POST['reservation_type'] ?? 'table';
        $name             = trim($_POST['name'] ?? '');
        $contact          = trim($_POST['contact'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $reservation_date = trim($_POST['reservation_date'] ?? '');
        $start_time       = trim($_POST['start_time'] ?? '');
        $end_time         = trim($_POST['end_time'] ?? '');
        $size             = isset($_POST['party_size']) ? intval($_POST['party_size']) : null;
        $type             = trim($_POST['type'] ?? '');
        $request          = trim($_POST['request'] ?? '');
        $note             = trim($_POST['note'] ?? '');
        $MOP              = trim($_POST['MOP'] ?? '');
        $table_id         = isset($_POST['table_id']) ? intval($_POST['table_id']) : null;

        $status           = 'Queued';
        $created_at       = date('Y-m-d H:i:s');
        $modify_at        = $created_at;

        // --- Get menu items ---
        $order_items = [];
        $total_amount = 0;

        if (!empty($_POST['menu_items']) && is_array($_POST['menu_items'])) {
            $menu_db_name = "rest_m3_menu";
            $menu_conn = $connections[$menu_db_name] ?? throw new Exception("Menu database connection not found.");

            foreach ($_POST['menu_items'] as $item_id => $quantity) {
                $quantity = intval($quantity);
                if ($quantity < 1) continue;

                $stmt = $menu_conn->prepare("SELECT menu_id, name, price FROM menu WHERE menu_id = ?");
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

        // --- Calculate Tax & Total Amount ---
        $tax_rate = 0.12;
        $tax_amount = $total_amount * $tax_rate;
        $grand_total = $total_amount + $tax_amount;

        // --- Begin transaction ---
        $conn->begin_transaction();

        // --- Prepare numeric fields safely for bind_param ---
        $size_val  = $size !== null ? $size : null;
        $table_val = $table_id !== null ? $table_id : null;

        // --- Insert reservation ---
        $stmt = $conn->prepare("INSERT INTO reservations (
            name, contact, email, reservation_date, start_time, end_time,
            size, status, request, type, created_at, modify_at, note, table_id, MOP
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // bind variables (all variables, no expressions!)
        $stmt->bind_param(
            "sssssssssssssss",
            $name,
            $contact,
            $email,
            $reservation_date,
            $start_time,
            $end_time,
            $size_val,
            $status,
            $request,
            $type,
            $created_at,
            $modify_at,
            $note,
            $table_val,
            $MOP
        );

        $stmt->execute();
        $reservation_id = $stmt->insert_id;
        $stmt->close();

  

        // --- Insert billing records ---
        $invoice_number = "INV" . date("Ymd") . str_pad($reservation_id, 4, "0", STR_PAD_LEFT);
        $invoice_date   = date("Y-m-d");
        $billing_status = "Pending";

        if (!empty($order_items)) {
            foreach ($order_items as $item) {
                $stmt_b = $billing_conn->prepare("INSERT INTO billing_payments (
                    client_name, client_email, client_contact, invoice_number, invoice_date,
                    status, description, quantity, unit_price, total_amount, payment_date, payment_amount, trans_ref, MOP
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                // bind each item safely
                $desc          = $item['name'];
                $quantity_item = $item['quantity'];
                $unit_price    = $item['price'];
                $total         = $item['total'];
                $payment_date  = '';
                $trans_ref     = '';

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
                    $total,
                    $payment_date,
                    $total,
                    $trans_ref,
                    $MOP
                );

                $stmt_b->execute();
                $stmt_b->close();
            }
        }

        // --- Commit transaction ---
        $conn->commit();

        header("Location: ../landing_page/reservation_success.php?type=table");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../error.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
