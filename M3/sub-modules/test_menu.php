<?php
// test_menu.php - Simple test to check if PHP is working
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "message" => "Test endpoint is working",
    "sample_data" => [
        "desserts" => [
            [
                "menu_id" => "24",
                "name" => "Akeem Grimes", 
                "description" => "Ratione nihil et exc",
                "category" => "desserts",
                "variant" => "Exercitationem duis",
                "price" => "63",
                "image_url" => "Wagyu_Beef_Tapa_with_Duck_Egg.png"
            ]
        ]
    ]
]);
exit();
?>