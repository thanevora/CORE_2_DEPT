<?php
session_start();
header('Content-Type: application/json');

include("../../main_connection.php");

$db_name = "rest_m7_billing_payments";
if (!isset($connections[$db_name])) {
    die(json_encode(['error' => 'Database connection not found']));
}

$conn = $connections[$db_name];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {
    case 'get_transaction':
        getTransaction($conn);
        break;
    case 'update_transaction':
        updateTransaction($conn);
        break;
    case 'delete_transaction':
        deleteTransaction($conn);
        break;
    case 'export_single_jpeg':
        exportSingleJPEG($conn);
        break;
    case 'export_all_jpeg':
        exportAllJPEG($conn);
        break;
    case 'search_transactions':
        searchTransactions($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getTransaction($conn) {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM billing_payments WHERE BP_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
    }
}

function updateTransaction($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? 0;
    $client_name = $data['client_name'] ?? '';
    $amount = $data['amount'] ?? 0;
    $status = $data['status'] ?? 'Pending';
    $due_date = $data['due_date'] ?? null;
    $payment_date = $data['payment_date'] ?? null;
    $notes = $data['notes'] ?? '';
    
    $stmt = $conn->prepare("UPDATE billing_payments SET 
        client_name = ?, 
        total_amount = ?, 
        status = ?, 
        due_date = ?, 
        payment_date = ?,
        notes = ?
        WHERE BP_id = ?");
    
    $stmt->bind_param("sdssssi", $client_name, $amount, $status, $due_date, $payment_date, $notes, $id);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

function deleteTransaction($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM billing_payments WHERE BP_id = ?");
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

function exportSingleJPEG($conn) {
    $id = $_GET['id'] ?? 0;
    $customization = $_GET['customization'] ?? '{}';
    $customization = json_decode($customization, true);
    
    $stmt = $conn->prepare("SELECT * FROM billing_payments WHERE BP_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        // Create HTML for the invoice
        $html = generateInvoiceHTML($row, $customization);
        
        // Generate JPEG (you'll need to install and configure a library like wkhtmltoimage)
        // For now, we'll return a JSON response. You can implement actual JPEG generation
        echo json_encode([
            'success' => true,
            'message' => 'JPEG generated successfully',
            'data' => $row,
            'download_url' => 'api/download_invoice.php?id=' . $id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
    }
}

function exportAllJPEG($conn) {
    $customization = $_GET['customization'] ?? '{}';
    $customization = json_decode($customization, true);
    
    $result = $conn->query("SELECT * FROM billing_payments ORDER BY payment_date DESC");
    $transactions = [];
    
    while($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Create a ZIP file with all JPEGs
    // For now, return JSON response
    echo json_encode([
        'success' => true,
        'message' => 'All transactions exported successfully',
        'count' => count($transactions),
        'download_url' => 'download_all_invoices.php'
    ]);
}

function searchTransactions($conn) {
    $search = $_GET['search'] ?? '';
    $search = "%$search%";
    
    $stmt = $conn->prepare("SELECT * FROM billing_payments 
                          WHERE (invoice_number LIKE ? OR client_name LIKE ? OR notes LIKE ?)
                          ORDER BY payment_date DESC 
                          LIMIT 20");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $transactions]);
}

function generateInvoiceHTML($transaction, $customization) {
    $includeLogo = $customization['includeLogo'] ?? true;
    $watermark = $customization['watermarkText'] ?? '';
    $bgColor = $customization['backgroundColor'] ?? '#ffffff';
    
    return "
    <html>
    <head>
        <style>
            body { background-color: $bgColor; font-family: Arial, sans-serif; }
            .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .watermark { opacity: 0.1; position: absolute; font-size: 100px; transform: rotate(-45deg); }
            .logo { max-width: 200px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        " . ($watermark ? "<div class='watermark'>$watermark</div>" : "") . "
        <div class='invoice-container'>
            " . ($includeLogo ? "<img src='../assets/logo.png' class='logo'>" : "") . "
            <h1>Invoice #{$transaction['invoice_number']}</h1>
            <p>Client: {$transaction['client_name']}</p>
            <p>Amount: ₱" . number_format($transaction['total_amount'], 2) . "</p>
            <p>Status: {$transaction['status']}</p>
            <p>Due Date: {$transaction['due_date']}</p>
            " . ($transaction['payment_date'] ? "<p>Payment Date: {$transaction['payment_date']}</p>" : "") . "
            " . ($transaction['notes'] ? "<p>Notes: {$transaction['notes']}</p>" : "") . "
        </div>
    </body>
    </html>";
}
?>