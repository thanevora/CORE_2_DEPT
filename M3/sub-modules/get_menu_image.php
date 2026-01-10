<?php
// delete_menu_item.php
include("../../main_connection.php");

$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection not found']));
}
$conn = $connections[$db_name];

$menu_id = intval($_GET['menu_id'] ?? 0);

try {
    $conn->begin_transaction();
    
    // Delete from menu_ingredients first (foreign key constraint)
    $deleteIngredients = $conn->prepare("DELETE FROM menu_ingredients WHERE menu_id = ?");
    $deleteIngredients->bind_param("i", $menu_id);
    $deleteIngredients->execute();
    $deleteIngredients->close();
    
    // Delete from menu
    $deleteMenu = $conn->prepare("DELETE FROM menu WHERE menu_id = ?");
    $deleteMenu->bind_param("i", $menu_id);
    
    if ($deleteMenu->execute()) {
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Menu item deleted successfully'
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete menu item: ' . $conn->error
        ]);
    }
    
    $deleteMenu->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>