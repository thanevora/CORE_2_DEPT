<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

$orderId = $_GET['id'] ?? 0;

$query = "SELECT * FROM kot_orders WHERE kot_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if ($order) {
    $status_class = '';
    $status_icon = '';
    if ($order['status'] == 'new') {
        $status_class = 'badge-new';
        $status_icon = 'inbox';
    } elseif ($order['status'] == 'preparing') {
        $status_class = 'badge-preparing';
        $status_icon = 'cooking-pot';
    } elseif ($order['status'] == 'ready') {
        $status_class = 'badge-ready';
        $status_icon = 'check-circle';
    } elseif ($order['status'] == 'urgent') {
        $status_class = 'badge-urgent';
        $status_icon = 'alert-triangle';
    }
    
    $order_time = date('h:i A', strtotime($order['created_at']));
    ?>
    <div>
        <div class="flex justify-between items-start mb-4">
            <div>
                <h4 class="font-bold text-lg">Order #<?= $order['order_id'] ?></h4>
                <p class="text-gray-500"><?= $order['table_number'] ? 'Table ' . $order['table_number'] : 'Takeaway' ?></p>
            </div>
            <div class="flex items-center gap-2">
                <span class="status-badge <?= $status_class ?>">
                    <i data-lucide="<?= $status_icon ?>" class="w-3 h-3 mr-1"></i>
                    <?= ucfirst($order['status']) ?>
                </span>
                <span class="text-gray-500"><?= $order_time ?></span>
            </div>
        </div>
        
        <div class="mb-4">
            <h5 class="font-medium mb-2">Items</h5>
            <div class="bg-gray-50 p-3 rounded-lg">
                <div class="flex justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-medium"><?= $order['quantity'] ?>x <?= $order['item_name'] ?></span>
                        <span class="item-category bg-blue-100 text-blue-800"><?= $order['status'] ?></span>
                    </div>
                </div>
                <?php if (!empty($order['special_instructions'])): ?>
                    <p class="text-sm text-gray-500 mt-2">Note: <?= $order['special_instructions'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
            <div class="mb-4">
                <h5 class="font-medium mb-2">Order Notes</h5>
                <div class="bg-yellow-50 p-3 rounded-lg text-yellow-800">
                    <p><?= $order['notes'] ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="flex justify-end gap-2 mt-6">
            <button class="btn btn-outline" onclick="printOrder('<?= $order['order_id'] ?>')">
                <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Print
            </button>
            <button class="btn btn-primary" onclick="completeOrder(<?= $order['kot_id'] ?>)">
                <i data-lucide="check" class="w-4 h-4 mr-2"></i> Complete Order
            </button>
        </div>
    </div>
    <?php
} else {
    echo '<div class="alert alert-error">
        <i data-lucide="alert-circle" class="w-6 h-6"></i>
        <span>Order not found</span>
    </div>';
}
?>