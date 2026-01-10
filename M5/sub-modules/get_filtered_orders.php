<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

$status = $_GET['status'] ?? 'all';

if ($status === 'all') {
    $query = "SELECT * FROM kot_orders ORDER BY created_at DESC";
} else {
    $query = "SELECT * FROM kot_orders WHERE status = ? ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);
if ($status !== 'all') {
    $stmt->bind_param("s", $status);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
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
        <div class="p-4 border-b border-gray-200 last:border-b-0">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-medium">Order #<?= $order['order_id'] ?></h4>
                    <p class="text-sm text-gray-500"><?= $order['table_number'] ? 'Table ' . $order['table_number'] : 'Takeaway' ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge <?= $status_class ?>">
                        <i data-lucide="<?= $status_icon ?>" class="w-3 h-3 mr-1"></i>
                        <?= ucfirst($order['status']) ?>
                    </span>
                    <span class="text-sm text-gray-500"><?= $order_time ?></span>
                </div>
            </div>
            
            <div class="mt-3">
                <div class="flex items-center gap-2">
                    <span class="font-medium"><?= $order['quantity'] ?>x <?= $order['item_name'] ?></span>
                </div>
                <?php if (!empty($order['special_instructions'])): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= $order['special_instructions'] ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 flex justify-end gap-2">
                <button class="btn btn-sm btn-outline" onclick="printOrder('<?= $order['order_id'] ?>')">
                    <i data-lucide="printer" class="w-3 h-3 mr-1"></i> Print
                </button>
                <button class="btn btn-sm btn-primary" onclick="completeOrder(<?= $order['kot_id'] ?>)">
                    <i data-lucide="check" class="w-3 h-3 mr-1"></i> Complete
                </button>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="text-white text-center py-8 text-gray-400">
        <i data-lucide="utensils-crossed" class="w-12 h-12 mx-auto mb-4"></i>
        <p class="text-lg font-medium">No orders found</p>
    </div>';
}
?>