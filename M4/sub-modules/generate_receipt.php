<?php
include("../../main_connection.php");

function generateReceipt($order_id) {
    // Fetch order details
    $conn_pos = $connections["rest_m4_pos"];
    
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn_pos->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return false;
    }
    
    // Decode order items
    $order_items = json_decode($order['orders'], true) ?? [];
    
    // Create receipt HTML
    $receipt_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Receipt - {$order['order_code']}</title>
        <style>
            body { font-family: 'Courier New', monospace; margin: 0; padding: 20px; background: white; }
            .receipt { max-width: 300px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
            .item { display: flex; justify-content: space-between; margin: 5px 0; }
            .total { border-top: 2px dashed #000; padding-top: 10px; margin-top: 10px; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='receipt'>
            <div class='header'>
                <h2>RESTAURANT NAME</h2>
                <p>Order #: {$order['order_code']}</p>
                <p>Date: " . date('M j, Y g:i A', strtotime($order['created_at'])) . "</p>
            </div>
            
            <div class='customer'>
                <p><strong>Customer:</strong> {$order['customer_name']}</p>
                <p><strong>Table:</strong> {$order['table_id']}</p>
            </div>
            
            <div class='items'>
                <h3>ITEMS</h3>";
                
                $subtotal = 0;
                foreach ($order_items as $item) {
                    $item_total = $item['price'] * $item['quantity'];
                    $subtotal += $item_total;
                    $receipt_html .= "
                    <div class='item'>
                        <span>{$item['quantity']}x {$item['name']}</span>
                        <span>₱ " . number_format($item_total, 2) . "</span>
                    </div>";
                }
                
                $service_charge = $subtotal * 0.02;
                $vat = $subtotal * 0.12;
                $total = $subtotal + $service_charge + $vat;
                
                $receipt_html .= "
            </div>
            
            <div class='total'>
                <div class='item'><span>Subtotal:</span><span>₱ " . number_format($subtotal, 2) . "</span></div>
                <div class='item'><span>Service Charge (2%):</span><span>₱ " . number_format($service_charge, 2) . "</span></div>
                <div class='item'><span>VAT (12%):</span><span>₱ " . number_format($vat, 2) . "</span></div>
                <div class='item'><span>TOTAL:</span><span>₱ " . number_format($total, 2) . "</span></div>
            </div>
            
            <div class='payment'>
                <p><strong>Payment Method:</strong> " . strtoupper($order['MOP']) . "</p>
            </div>
            
            <div class='footer'>
                <p>Thank you for dining with us!</p>
                <p>***</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $receipt_html;
}

// Handle receipt generation request
if (isset($_GET['order_id'])) {
    $receipt_html = generateReceipt($_GET['order_id']);
    
    if ($receipt_html) {
        // For image generation, you would need a library like wkhtmltoimage
        // For now, we'll return HTML that can be printed
        header('Content-Type: text/html');
        echo $receipt_html;
    } else {
        http_response_code(404);
        echo "Order not found";
    }
}
?>