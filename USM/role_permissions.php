<?php
// USM/role_permissions.php
return [
    'superviser' => [
        'table_reservation',
        'kitchen_orders',
        'inventory',
        'menu_management',
        'event_management',
        'table_turnover',
        'pos_system',
        'billing',
        'staff_management',
        'customer_feedback',
        'analytics',
        'user_management',
        'hr_management',
        'logistics_management',
        'admin_management',
        'hotel_management',
        'restaurant_management',
        'financials_management'
    ],
    
    'admin' => [
        'table_reservation',
        'kitchen_orders',
        'inventory',
        'menu_management',
        'event_management',
        'table_turnover',
        'pos_system',
        'billing',
        'staff_management',
        'customer_feedback',
        'analytics',
        'user_management',
        'hr_management',
        'logistics_management',
        'admin_management',
        'hotel_management',
        'restaurant_management',
        'financials_management'
    ],
    
    'supervisor' => [
        'table_reservation',
        'kitchen_orders',
        'inventory',
        'menu_management',
        'event_management',
        'table_turnover',
        'pos_system',
        'billing',
        'staff_management',
        'customer_feedback',
        'analytics'
    ],
    
    'hr_manager' => [
        'hr_management'
    ],
    
    'logistics_manager' => [
        'logistics_management'
    ],
    
    'admin_manager' => [
        'admin_management'
    ],
    
    'hotel_manager' => [
        'hotel_management'
    ],
    
    'restaurant_manager' => [
        'restaurant_management'
    ],
    
    'finance_manager' => [
        'financials_management'
    ],
    
    'staff' => [
        // Basic staff permissions
    ],
    
    'guest' => [
        // No permissions
    ]
];
?>