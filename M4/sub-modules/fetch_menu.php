<?php
include("../../main_connection.php");

$db_name = "rest_m4_pos";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM menu WHERE 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND name LIKE ?";
    $params[] = "%" . $search . "%";
}

if (!empty($category) && strtolower($category) !== 'all items') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$stmt = $conn->prepare($sql);

if (count($params) === 1) {
    $stmt->bind_param("s", $params[0]);
} elseif (count($params) === 2) {
    $stmt->bind_param("ss", $params[0], $params[1]);
}

$stmt->execute();
$result_sql = $stmt->get_result();
?>

<!-- ✅ Display Section -->
<div class="menu-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="menu-items-grid">
    <?php if ($result_sql->num_rows === 0): ?>
        <p class="text-gray-500 text-center col-span-full">No menu items found.</p>
    <?php endif; ?>

    <?php while ($row = $result_sql->fetch_assoc()): ?>
        <div class="menu-item-card relative rounded-xl border p-5 shadow-sm transition-all duration-300 hover:shadow-md border-gray-200 bg-white flex flex-col h-full">
            <div class="mb-4">
                <img 
                    src="<?= htmlspecialchars($row['image_url'] ?? 'https://via.placeholder.com/400x250?text=No+Image') ?>" 
                    alt="<?= htmlspecialchars($row['name']) ?>" 
                    class="w-full h-40 object-cover rounded-lg border" 
                />
            </div>

            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="p-2 rounded-lg bg-gray-100 text-gray-600">
                        <i data-lucide="utensils" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-800 truncate"><?= htmlspecialchars($row['name']) ?></h3>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">ID: <?= $row['menu_id'] ?></p>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-2">
                <div class="text-sm text-gray-600 min-w-0">
                    <p class="text-xs text-gray-500 truncate">Price</p>
                    <p class="font-medium truncate">₱ <?= number_format($row['price'], 2) ?></p>
                </div>
                <div class="text-sm text-gray-600 min-w-0">
                    <p class="text-xs text-gray-500 truncate">Category</p>
                    <p class="font-medium truncate"><?= htmlspecialchars($row['category']) ?></p>
                </div>
            </div>

            <p class="text-sm text-gray-600 mt-3 line-clamp-2"><?= htmlspecialchars($row['description']) ?></p>

            <div class="mt-auto pt-3 border-t border-gray-200/50 flex justify-between items-center">
                <button 
                    onclick="showMenuItemDetails(<?= (int)$row['menu_id'] ?>)" 
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 transition-colors">
                    View details <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
                <span class="text-xs text-gray-500">
                    Updated: <?= date('M j, Y', strtotime($row['updated_at'])) ?>
                </span>
            </div>
        </div>
    <?php endwhile; ?>
</div>
