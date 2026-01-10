<?php
// role_permissions.php
return [
    
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
        'analytics',
        'user_management'  
    ],

    'Manager' => [
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

    'cashier' => [
        // 'pos_system',
        // 'billing',
        
        
        
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
        'user_management' 
        ],

    'security' => [
       
        // 'user_management'
        
        
        
        
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
        'user_management' 
    ],

    'reservation' => [
        // 'table_reservation',
        // 'event_management',
        // 'table_turnover',
        
        
        
        
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
        'user_management' 
      
    ],

    'inventory' => [
    
    //     'inventory',
    //   'menu_management',
      
      
      
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
        'user_management' 
    
       
    ],
     'waiter' => [
    
        'staff_management',
        'table_turnover',
        
        
        
        //  'table_reservation',
        // 'kitchen_orders',
        // 'inventory',
        // 'menu_management',
        // 'event_management',
        // 'table_turnover',
        // 'pos_system',
        // 'billing',
        // 'staff_management',
        // 'customer_feedback',
        // 'analytics',
        // 'user_management' 
    
       
    ],
     'waitress' => [
    
        'staff_management',
        'table_turnover',
        
        
        
        //  'table_reservation',
        // 'kitchen_orders',
        // 'inventory',
        // 'menu_management',
        // 'event_management',
        // 'table_turnover',
        // 'pos_system',
        // 'billing',
        // 'staff_management',
        // 'customer_feedback',
        // 'analytics',
        // 'user_management' 
    
       
    ],
     'head' => [
         
        // 'inventory',
        // 'kitchen_orders',
        // 'menu_management',
        
        
        
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
        'user_management' 

       
    ],
     
    
];