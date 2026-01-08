<?php
// api.php
session_start();
include("../../main_connection.php");

$db_name = "rest_m7_billing_payments";
$conn = $connections[$db_name] ?? die("❌ Connection not found");

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check if TCPDF exists
$tcpdf_paths = [
    __DIR__ . '/../tcpdf/tcpdf.php', // If TCPDF is in project root
    __DIR__ . '/../../tcpdf/tcpdf.php', // If TCPDF is in parent folder
    __DIR__ . '/tcpdf/tcpdf.php', // If TCPDF is in same folder
];

$tcpdf_loaded = false;
foreach ($tcpdf_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $tcpdf_loaded = true;
        break;
    }
}

if (!$tcpdf_loaded && ($action == 'export_single_pdf' || $action == 'export_all_pdf')) {
    echo json_encode(['error' => 'TCPDF library not found. Please install TCPDF.']);
    exit;
}

switch ($action) {
    case 'get_transaction':
        getTransaction($conn);
        break;
    case 'update_transaction':
        updateTransaction($conn);
        break;
    case 'delete_transaction':
        deleteTransaction($conn);
        break;
    case 'add_note':
        addNote($conn);
        break;
    case 'export_single_pdf':
        exportSinglePDF($conn);
        break;
    case 'export_all_pdf':
        exportAllPDF($conn);
        break;
    case 'search_transactions':
        searchTransactions($conn);
        break;
    case 'export_customized_pdf':
        exportCustomizedPDF($conn);
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
    echo json_encode($result->fetch_assoc());
}

function updateTransaction($conn) {
    $id = $_POST['id'] ?? 0;
    $client_name = $_POST['client_name'] ?? '';
    $invoice_number = $_POST['invoice_number'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;
    $status = $_POST['status'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    
    $stmt = $conn->prepare("UPDATE billing_payments SET client_name = ?, invoice_number = ?, total_amount = ?, status = ?, due_date = ? WHERE BP_id = ?");
    $stmt->bind_param("ssdssi", $client_name, $invoice_number, $total_amount, $status, $due_date, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
}

function deleteTransaction($conn) {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("UPDATE billing_payments SET status = 'Cancelled' WHERE BP_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
}

function addNote($conn) {
    $id = $_POST['id'] ?? 0;
    $note = $_POST['note'] ?? '';
    
    // First check if notes column exists, if not, alter table
    $check = $conn->query("SHOW COLUMNS FROM billing_payments LIKE 'notes'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE billing_payments ADD COLUMN notes TEXT");
    }
    
    $stmt = $conn->prepare("UPDATE billing_payments SET notes = ? WHERE BP_id = ?");
    $stmt->bind_param("si", $note, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
}

function exportSinglePDF($conn) {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM billing_payments WHERE BP_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        echo "Transaction not found";
        exit;
    }
    
    // Create PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Billing System');
    $pdf->SetAuthor('Your Company');
    $pdf->SetTitle('Invoice #' . $transaction['invoice_number']);
    $pdf->SetSubject('Invoice');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Company Header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Invoice Details
    $pdf->SetFont('helvetica', '', 10);
    
    $html = '
    <style>
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #001f54; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .total { font-weight: bold; background-color: #f9f9f9; }
    </style>
    
    <table>
        <tr>
            <th colspan="2">Invoice Details</th>
        </tr>
        <tr>
            <td width="30%"><strong>Invoice Number:</strong></td>
            <td width="70%">#' . htmlspecialchars($transaction['invoice_number']) . '</td>
        </tr>
        <tr>
            <td><strong>Client Name:</strong></td>
            <td>' . htmlspecialchars($transaction['client_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Invoice Date:</strong></td>
            <td>' . htmlspecialchars($transaction['payment_date'] ?? date('Y-m-d')) . '</td>
        </tr>
        <tr>
            <td><strong>Due Date:</strong></td>
            <td>' . htmlspecialchars($transaction['due_date']) . '</td>
        </tr>
        <tr class="total">
            <td><strong>Total Amount:</strong></td>
            <td><strong>₱' . number_format($transaction['total_amount'], 2) . '</strong></td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>' . htmlspecialchars($transaction['status']) . '</td>
        </tr>
        <tr>
            <td><strong>Payment Method:</strong></td>
            <td>' . htmlspecialchars($transaction['MOP'] ?? 'N/A') . '</td>
        </tr>';
    
    if (!empty($transaction['notes'])) {
        $html .= '
        <tr>
            <td colspan="2"><strong>Notes:</strong><br>' . htmlspecialchars($transaction['notes']) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Footer
    $pdf->SetY(-30);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'C');
    
    // Output PDF
    $pdf->Output('invoice_' . $transaction['invoice_number'] . '.pdf', 'D');
}

function exportAllPDF($conn) {
    // Get customization parameters
    $include_logo = $_GET['include_logo'] ?? false;
    $include_terms = $_GET['include_terms'] ?? false;
    $paper_size = $_GET['paper_size'] ?? 'A4';
    $custom_header = $_GET['custom_header'] ?? 'All Transactions Report';
    $custom_footer = $_GET['custom_footer'] ?? 'Generated by Billing System';
    
    // Get all transactions
    $result = $conn->query("SELECT * FROM billing_payments ORDER BY payment_date DESC");
    
    // Create PDF with custom orientation based on paper size
    $orientation = ($paper_size == 'A4') ? 'L' : 'P';
    $pdf = new TCPDF($orientation, 'mm', $paper_size, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Billing System');
    $pdf->SetAuthor('Your Company');
    $pdf->SetTitle($custom_header);
    
    // Custom header
    $pdf->setHeaderData('', 0, $custom_header, 'Transaction Report');
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set margins
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Calculate totals
    $total_amount = 0;
    $paid_count = 0;
    $pending_count = 0;
    $overdue_count = 0;
    
    // Start HTML content
    $html = '<h2>' . htmlspecialchars($custom_header) . '</h2>';
    $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    $html .= '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #001f54; color: white;">
                <th width="15%">Invoice #</th>
                <th width="20%">Client</th>
                <th width="15%">Amount</th>
                <th width="10%">Status</th>
                <th width="15%">Due Date</th>
                <th width="15%">Payment Date</th>
                <th width="10%">Method</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($row = $result->fetch_assoc()) {
        $total_amount += $row['total_amount'];
        
        switch($row['status']) {
            case 'Paid': $paid_count++; break;
            case 'Pending': $pending_count++; break;
            case 'Overdue': $overdue_count++; break;
        }
        
        $row_color = '';
        switch($row['status']) {
            case 'Paid': $row_color = 'background-color: #d4edda;'; break;
            case 'Pending': $row_color = 'background-color: #fff3cd;'; break;
            case 'Overdue': $row_color = 'background-color: #f8d7da;'; break;
        }
        
        $html .= '<tr style="' . $row_color . '">
            <td>' . htmlspecialchars($row['invoice_number']) . '</td>
            <td>' . htmlspecialchars($row['client_name']) . '</td>
            <td>₱' . number_format($row['total_amount'], 2) . '</td>
            <td>' . htmlspecialchars($row['status']) . '</td>
            <td>' . htmlspecialchars($row['due_date']) . '</td>
            <td>' . htmlspecialchars($row['payment_date'] ?? 'N/A') . '</td>
            <td>' . htmlspecialchars($row['MOP'] ?? 'N/A') . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        <tfoot style="background-color: #e8f4fd; font-weight: bold;">
            <tr>
                <td colspan="2">Summary</td>
                <td>Total: ₱' . number_format($total_amount, 2) . '</td>
                <td>Paid: ' . $paid_count . '</td>
                <td>Pending: ' . $pending_count . '</td>
                <td>Overdue: ' . $overdue_count . '</td>
                <td>Total: ' . ($paid_count + $pending_count + $overdue_count) . '</td>
            </tr>
        </tfoot>
    </table>';
    
    // Add terms and conditions if requested
    if ($include_terms) {
        $html .= '<div style="margin-top: 20px;">
            <h3>Terms & Conditions</h3>
            <p>1. All payments are due within 30 days of invoice date.</p>
            <p>2. Late payments may be subject to a 1.5% monthly interest charge.</p>
            <p>3. Payment can be made via bank transfer, credit card, or cash.</p>
        </div>';
    }
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Custom footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, $custom_footer . ' | Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Output PDF
    $pdf->Output('all_transactions_' . date('Ymd_His') . '.pdf', 'D');
}

function exportCustomizedPDF($conn) {
    // This function handles customized PDF export with all parameters
    exportAllPDF($conn); // For now, use the same function
}

function searchTransactions($conn) {
    $search = $_GET['search'] ?? '';
    $search = "%$search%";
    
    $stmt = $conn->prepare("SELECT * FROM billing_payments 
                           WHERE invoice_number LIKE ? 
                           OR client_name LIKE ? 
                           OR status LIKE ?
                           ORDER BY payment_date DESC 
                           LIMIT 10");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode($transactions);
}
?>