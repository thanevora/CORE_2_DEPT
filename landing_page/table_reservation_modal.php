<?php
$reservation_type = $_GET['type'] ?? 'unknown';
$title = ($reservation_type == 'table') ? 'Table Reservation' : 'Event Reservation';
$icon = ($reservation_type == 'table') ? '🍽️' : '🎉';
$logo_url = "https://restaurant.soliera-hotel-restaurant.com/images/tagline_no_bg.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Table Reservation | Soliera Restaurant</title>
  
  <!-- DaisyUI and Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#001f54',
            accent: '#F7B32B',
            secondary: '#00308a',
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          }
        }
      }
    }
  </script>
  
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    .floating-label {
      position: relative;
      margin-bottom: 1.5rem;
    }
    .floating-input {
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      padding: 1.1rem 1rem 0.5rem 1rem;
      font-size: 1rem;
      transition: all 0.2s ease;
      width: 100%;
      background: white;
      color: #000;
    }
    .floating-input::placeholder {
      color: #000;
    }
    .floating-label span {
      position: absolute;
      left: 1rem;
      top: 1.1rem;
      color: #6b7280;
      transition: all 0.2s ease;
      pointer-events: none;
    }
    .floating-input:focus + span,
    .floating-input:not(:placeholder-shown) + span {
      top: 0.25rem;
      left: 0.75rem;
      font-size: 0.75rem;
      color: #001f54;
    }
    .floating-input:focus {
      border-color: #001f54;
      box-shadow: 0 0 0 3px rgba(0, 31, 84, 0.15);
    }
    .menu-item {
      transition: all 0.3s ease;
    }
    .menu-item:hover {
      transform: translateY(-0.25rem);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="min-h-screen">
  <!-- Blurred background image -->
  <div style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('../images/hotel3.jpg') no-repeat center center / cover;
    filter: blur(8px);
    z-index: -10;
    opacity: 0.9;
  "></div>
  
  <!-- Dark overlay for better readability -->
  <div style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 31, 84, 0.3);
    z-index: -5;
  "></div>

  <!-- Header -->
  <div class="navbar bg-primary text-primary-content px-4 py-4 shadow-md">
    <div class="flex-1">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 flex-shrink-0">
          <img src="../images/s_with_bg.jpg" alt="Soliera Restaurant Logo" class="w-full h-full object-contain rounded-lg shadow-md">
        </div>
        <div>
          <h1 class="text-xl font-bold text-white">Soliera Restaurant</h1>
          <p class="text-xs text-accent">Fine Dining & Events</p>
        </div>
      </div>
    </div>
  </div>

  <div class="container mx-auto px-4 py-8 max-w-8xl shadow-6xl ">
    <!-- Page Title -->
  <div class="bg-[#001f54] border-2 border-[#F7B32B] rounded-lg max-w-xl mx-auto text-center p-6 mb-10 shadow-lg">
  <h2 class="text-3xl font-bold text-[#F7B32B] mb-2">Table Reservation</h2>
  <p class="text-gray-300 text-sm">Book your table for an exceptional dining experience</p>
</div>


    <div class="flex flex-col lg:flex-row gap-8 ">
      <!-- Left Column - Reservation Form -->
      <div class="w-full lg:w-2/3">
  <div class="bg-[#001f54] text-white rounded-lg p-6 mb-6 border-2 border-[#F7B32B]">
          <form id="table-form" action="../M1/create_reservation_main.php" method="POST">
            <input type="hidden" name="reservation_type" value="table">
            
            <!-- Customer Information -->
            <div class="mb-6">
<h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                <i class='bx bx-user text-accent text-xl'></i>
                Customer Information
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="floating-label">
                  <input type="text" id="name" name="name" class="border-2 border-[#F7B32B] floating-input" placeholder=" " >
<span class="text-[#F7B32B]">Full Name*</span>
                </div>

                <div class="floating-label">
                  <input type="text" id="contact" name="contact" class="border-2 border-[#F7B32B] floating-input" placeholder=" " >
<span class="text-[#F7B32B]">Contact Information*</span>
                </div>

                <div class="floating-label md:col-span-2">
                  <input type="email" id="email" name="email" class="border-2 border-[#F7B32B] floating-input" placeholder=" " >
<span class="text-[#F7B32B]">Email*</span>
                </div>
              </div>
            </div>
            
            <!-- Reservation Details -->
            <div class="mb-6">
<h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                <i class='bx bx-calendar text-accent text-xl'></i>
                Reservation Details
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="floating-label">
                  <input type="date" id="reservation_date" name="reservation_date" class="border-2 border-[#F7B32B] floating-input" placeholder=" " >
<span class="text-[#F7B32B]">Date*</span>
                </div>

                <div class="floating-label">
                  <input type="number" id="party_size" name="party_size" min="1" class="border-2 border-[#F7B32B] floating-input" oninput="updateOrderSummary()">
<span class="text-[#F7B32B]">Party Size*</span>
                </div>

                <div class="floating-label">
                  <input type="time" id="start_time" name="start_time" class="border-2 border-[#F7B32B] floating-input" placeholder=" " >
<span class="text-[#F7B32B]">Start Time*</span>
                </div>
                
              
              </div>
            </div>
            
            <!-- Table Selection -->
            <div class="mb-6">
<h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                <i class='bx bx-table text-accent text-xl'></i>
                Table Selection
              </h3>
              
              <div class="floating-label">
                <select id="table_id" name="table_id" class="border-2 border-[#F7B32B] floating-input" >
                  <option value="" disabled selected>Select a table</option>
                  <?php
                  // PHP fetch tables
                  $db_name_tables = "rest_m3_tables";
                  if (isset($connections[$db_name_tables])) {
                    $conn_tables = $connections[$db_name_tables];
                    $tableCheck = $conn_tables->query("SHOW TABLES LIKE 'tables'");
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                      $query = "SELECT * FROM tables WHERE status = 'available' ORDER BY name";
                      $result = $conn_tables->query($query);
                      if ($result && $result->num_rows > 0) {
                        while ($table = $result->fetch_assoc()) {
                          echo '<option value="' . htmlspecialchars($table['id']) . '">' .
                            htmlspecialchars($table['name']) . ' • ' .
                            htmlspecialchars($table['capacity']) . ' pax max</option>';
                        }
                      } else {
                        echo '<option value="1">Standard</option>';
                        echo '<option value="2">Booth</option>';
                        echo '<option value="3">Premium</option>';
                        echo '<option value="5">Family</option>';
                      }
                    }
                    $conn_tables->close();
                  } else {
                    echo '<option value="1">Standard</option>';
                    echo '<option value="2">Booth</option>';
                    echo '<option value="3">Premium</option>';
                    echo '<option value="5">Family</option>';
                  }
                  ?>
                </select>
<span class="text-[#F7B32B]">Select Table*</span>
              </div>
              
              
            </div>

 <!-- Mode of Payment Section -->
<div class="mb-6">
  <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
    <i class='bx bx-credit-card text-[#F7B32B] text-xl'></i>
    Mode of Payment
  </h3>

  <!-- Payment Method Selection -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <!-- Cash Payment -->
    <div class="payment-card relative">
      <input type="radio" id="cash_payment" name="MOP" value="Cash" class="absolute opacity-0 -z-10">
      <label for="cash_payment" class="flex flex-col items-center p-4 border-2 border-[#F7B32B] rounded-xl cursor-pointer transition-all duration-200 hover:border-[#F7B32B] hover:shadow-md bg-white">
        <div class="w-12 h-12 bg-[#001f54] rounded-full flex items-center justify-center mb-2">
          <i class='bx bx-money text-[#F7B32B] text-2xl'></i>
        </div>
        <span class="font-medium text-[#001f54]">Pay with Cash</span>
        <span class="text-xs text-gray-500 mt-1">Pay at the restaurant</span>
      </label>
    </div>

    <!-- Online Payment -->
    <div class="payment-card relative">
      <input type="radio" id="online_payment" name="MOP" value="Online" class="absolute opacity-0 -z-10">
      <label for="online_payment" class="flex flex-col items-center p-4 border-2 border-[#F7B32B] rounded-xl cursor-pointer transition-all duration-200 hover:border-[#F7B32B] hover:shadow-md bg-white">
        <div class="w-12 h-12 bg-[#001f54] rounded-full flex items-center justify-center mb-2">
          <i class='bx bx-wifi text-[#F7B32B] text-2xl'></i>
        </div>
        <span class="font-medium text-[#001f54]">Online Payment</span>
        <span class="text-xs text-gray-500 mt-1">Pay now securely</span>
      </label>
    </div>
  </div>

 <!-- Online Payment Options (Initially Hidden) -->
<div id="online-options" class="hidden mt-6 p-4 bg-gray-50 rounded-xl border-2 border-[#F7B32B]">
  <p class="text-sm font-medium text-[#001f54] mb-3">Select Online Payment Method:</p>

  <div class="flex flex-wrap gap-4">
    <!-- Gcash -->
    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-[#F7B32B] rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
      <input type="radio" name="online_method" value="Gcash" class="hidden">
      <img src="../images/Gcash.png" alt="Gcash" class="w-16 h-16 object-contain" />
    </label>

    <!-- Maya -->
    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-[#F7B32B] rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
      <input type="radio" name="online_method" value="Maya" class="hidden">
      <img src="../images/Maya.png" alt="Maya" class="w-16 h-16 object-contain" />
    </label>

    <!-- Credit Card -->
    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-[#F7B32B] rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
      <input type="radio" name="online_method" value="Credit Card" class="hidden">
      <div class="w-16 h-16 bg-[#001f54] rounded-full flex items-center justify-center">
        <i class='bx bx-credit-card text-[#F7B32B] text-2xl'></i>
      </div>
      <span class="text-sm font-medium text-[#001f54]">Credit Card</span>
    </label>

    <!-- Debit Card -->
    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-[#F7B32B] rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
      <input type="radio" name="online_method" value="Debit Card" class="hidden">
      <div class="w-16 h-16 bg-[#001f54] rounded-full flex items-center justify-center">
        <i class='bx bx-card text-[#F7B32B] text-2xl'></i>
      </div>
      <span class="text-sm font-medium text-[#001f54]">Debit Card</span>
    </label>
  </div>

  <!-- Payment Instructions -->
  <div id="payment-instructions" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
    <p class="text-sm text-yellow-800">You will be redirected to a secure payment portal after submitting your reservation.</p>
  </div>
</div>


  <!-- Cash Payment Instructions -->
  <div id="cash-instructions" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
    <p class="text-sm text-blue-800">Please bring exact amount for your payment. Payment will be collected when you arrive at the restaurant.</p>
  </div>
</div>


            
            <!-- Special Requests -->
            <div class="mb-6">
<h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                <i class='bx bx-message-dots text-accent text-xl'></i>
                Special Requests
              </h3>
              <textarea id="request" name="request" class="bg-white text-black w-full h-32 p-4 border-2 border-[#F7B32B] rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 transition" placeholder="Any dietary restrictions or special arrangements..."></textarea>
            </div>
            
            <!-- Include Menu -->
            <div class="mb-6 flex items-center gap-3">
              <input type="checkbox" id="include_menu" class="checkbox checkbox-primary" onchange="toggleMenuSection()">
<label for="include_menu" class="text-sm font-medium text-[#F7B32B]">                Include menu in your reservation?
              </label>
            </div>
<?php
include("../main_connection.php");

$db_name = "rest_m3_menu"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

$categories = []; // <- important: initialize so foreach() won't warn

if (isset($connections[$db_name])) {
  $menu_conn = $connections[$db_name];
  $query = "SELECT * FROM menu ORDER BY category, name";
  $result = $menu_conn->query($query);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $cat = $row['category'] ?? 'Uncategorized';
      $categories[$cat][] = $row;
    }
  }
} else {
  echo '<div class="col-span-2 text-center py-6 text-gray-500">Database connection error.</div>';
}

// icon map (keeps your prior icons)
$iconMap = [
  'Appetizers'   => 'bx bx-leaf',
  'Main Courses' => 'bx bx-bowl-hot',
  'Desserts'     => 'bx bx-cake',
  'Drinks'       => 'bx bx-drink',
  'Soups'        => 'bx bx-bowl-rice'
];

// counters and storage for extras
$counter = 0;
$extraItemsGrouped = []; // category => [items...]

if (!empty($categories)) {
  echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';

  foreach ($categories as $category => $items) {
    $icon = $iconMap[$category] ?? 'bx bx-dish';
    echo '<div class="bg-gray-50 rounded-xl p-5 shadow-sm">';
    echo '  <div class="flex items-center gap-3 mb-4">';
    echo '    <div class="w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center">';
    echo "      <i class=\"{$icon} text-accent text-xl\"></i>";
    echo '    </div>';
    echo '    <h4 class="text-lg font-semibold text-primary">' . htmlspecialchars($category) . '</h4>';
    echo '  </div>';
    echo '  <div class="space-y-4">';

    foreach ($items as $item) {
      $counter++;
      // render first 5 items total
      if ($counter <= 5) {
        $menu_id = (int)($item['menu_id'] ?? 0);
        $name = htmlspecialchars($item['name'] ?? '');
        $price = htmlspecialchars($item['price'] ?? '0.00');
        $desc = htmlspecialchars($item['description'] ?? '');
        echo '<div class="menu-item bg-white p-4 rounded-lg border border-gray-200 shadow-sm">';
        echo '  <div class="flex justify-between items-start mb-2">';
        echo '    <h5 class="font-medium text-[#F7B32B] text-sm">' . $name . '</h5>';
        echo '    <span class="text-[#F7B32B] font-semibold text-sm">₱' . $price . '</span>';
        echo '  </div>';
        echo '  <p class="text-gray-300 text-xs mb-3">' . $desc . '</p>';
        echo '  <div class="flex items-center gap-2">';
        echo "    <button type=\"button\" class=\"quantity-btn decrease text-xs px-2 py-1 bg-[#F7B32B] text-[#001f54] rounded\" data-id=\"{$menu_id}\">-</button>";
        echo "    <input type=\"number\" name=\"menu_items[{$menu_id}]\" id=\"item_{$menu_id}\" class=\"quantity-input text-black bg-white w-12 h-7 text-center text-sm\" value=\"0\" min=\"0\" onchange=\"updateOrderSummary()\">";
        echo "    <button type=\"button\" class=\"quantity-btn increase text-xs px-2 py-1 bg-[#F7B32B] text-[#001f54] rounded\" data-id=\"{$menu_id}\">+</button>";
        echo '  </div>';
        echo '</div>';
      } else {
        // push extras grouped by category for modal later
        $extraItemsGrouped[$category][] = $item;
      }
    }

    echo '  </div>';
    echo '</div>';
  }

  echo '</div>'; // end grid

  // Show More button if more items exist
  $totalExtras = 0;
  foreach ($extraItemsGrouped as $cat => $arr) $totalExtras += count($arr);

  if ($totalExtras > 0) {
    echo '<div class="text-center mt-6">';
    echo '  <button type="button" class="btn btn-warning px-6 py-2 rounded-lg shadow-md" onclick="document.getElementById(\'menuModal\').showModal()">';
    echo "    Show More ({$totalExtras})";
    echo '  </button>';
    echo '</div>';
  }

  // Modal with extra items
  if ($totalExtras > 0) {
    echo '<dialog id="menuModal" class="modal">';
    echo '  <div class="modal-box max-w-4xl bg-[#001f54] text-white">';
    echo '    <h3 class="font-bold text-lg text-[#F7B32B] mb-4">More Menu Items</h3>';
    echo '    <div class="space-y-6 overflow-y-auto max-h-[65vh]">';

    foreach ($extraItemsGrouped as $cat => $items) {
      echo '<div class="bg-gray-50 rounded-xl p-5 shadow-sm text-black">';
      echo '  <div class="flex items-center gap-3 mb-4">';
      $icon = $iconMap[$cat] ?? 'bx bx-dish';
      echo "    <div class=\"w-10 h-10 bg-accent/20 rounded-full flex items-center justify-center\"><i class=\"{$icon} text-accent text-xl\"></i></div>";
      echo '    <h4 class="text-lg font-semibold text-primary">' . htmlspecialchars($cat) . '</h4>';
      echo '  </div>';

      echo '  <div class="space-y-4">';
      foreach ($items as $item) {
        $menu_id = (int)($item['menu_id'] ?? 0);
        $name = htmlspecialchars($item['name'] ?? '');
        $price = htmlspecialchars($item['price'] ?? '0.00');
        $desc = htmlspecialchars($item['description'] ?? '');
        echo '<div class="menu-item bg-white p-4 rounded-lg border border-gray-200 shadow-sm">';
        echo '  <div class="flex justify-between items-start mb-2">';
        echo '    <h5 class="font-medium text-[#F7B32B] text-sm">' . $name . '</h5>';
        echo '    <span class="text-[#F7B32B] font-semibold text-sm">₱' . $price . '</span>';
        echo '  </div>';
        echo '  <p class="text-gray-600 text-xs mb-3">' . $desc . '</p>';
        echo '  <div class="flex items-center gap-2">';
        echo "    <button type=\"button\" class=\"quantity-btn decrease text-xs px-2 py-1 bg-[#F7B32B] text-[#001f54] rounded\" data-id=\"{$menu_id}\">-</button>";
        echo "    <input type=\"number\" name=\"menu_items[{$menu_id}]\" id=\"item_{$menu_id}\" class=\"bg-white text-black quantity-input w-12 h-7 text-center text-sm\" value=\"0\" min=\"0\" onchange=\"updateOrderSummary()\">";
        echo "    <button type=\"button\" class=\"quantity-btn increase text-xs px-2 py-1 bg-[#F7B32B] text-[#001f54] rounded\" data-id=\"{$menu_id}\">+</button>";
        echo '  </div>';
        echo '</div>';
      }
      echo '  </div>';
      echo '</div>';
    }

    echo '    </div>'; // modal content
    echo '    <div class="modal-action">';
    echo '      <button class="btn btn-sm bg-[#F7B32B] text-[#001f54]" onclick="document.getElementById(\'menuModal\').close()">Close</button>';
    echo '    </div>';
    echo '  </div>';
    echo '</dialog>';
  }

} else {
  // no categories found
  echo '<div class="col-span-2 text-center py-6 text-gray-500">No menu items found.</div>';
}
?>


            
            <div class="mb-6">
  <label class="flex items-start gap-3 cursor-pointer">
    <input type="checkbox" id="terms_checkbox" class="checkbox checkbox-primary mt-1"  />
    <span class="text-sm text-[#F7B32B]">I agree to the <a href="#terms_modal" class="underline" onclick="document.getElementById('terms_modal').showModal()">Terms & Conditions</a></span>
  </label>
</div>
            
           <!-- Form Buttons -->
<div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
  <button type="reset" class="btn flex-1 border border-[#F7B32B] text-[#F7B32B] bg-[#001f54] hover:bg-[#F7B32B] hover:text-white">
    <i data-lucide="x" class="w-4 h-4 mr-2"></i> Clear Form
  </button>
  <button type="submit" class="btn flex-1 bg-[#F7B32B] text-white hover:bg-[#d99b20]">
    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Create Reservation
  </button>
</div>

          </form>
        </div>
      </div>
      
      <!-- Right Column - Order Summary -->
      <div class="w-full lg:w-1/3">
<div class="bg-[#001f54] border-2 border-[#F7B32B] text-white rounded-lg shadow-lg p-6 sticky top-6">
  <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
    <i class='bx bx-cart text-accent text-xl'></i>
    Reservation Bill Summary
  </h3>
  
  <div id="order-items" class="mb-4 max-h-80 overflow-y-auto">
    <p class="text-gray-500 text-center py-4">No items selected yet</p>
  </div>
  
  <div class="border-t border-[#F7B32B]/50 pt-4 space-y-2">
    <div class="flex justify-between items-center">
      <span class="text-sm">Subtotal:</span>
      <span id="subtotal" class="font-medium">₱0.00</span>
    </div>
    <div class="flex justify-between items-center">
      <span class="text-sm">Service Charge (8%):</span>
      <span id="service-charge" class="font-medium">₱0.00</span>
    </div>
    <div class="flex justify-between items-center">
      <span class="text-sm">VAT (12%):</span>
      <span id="tax" class="font-medium">₱0.00</span>
    </div>
    <div class="flex justify-between items-center">
      <span class="text-sm">Reservation Fee (₱200 x Person):</span>
      <span id="reservation-fee" class="font-medium">₱0.00</span>
    </div>
    <div class="flex justify-between items-center font-bold text-lg text-[#F7B32B] mt-4 pt-4 border-t border-[#F7B32B]/50">
      <span>Total:</span>
      <span id="total-amount">₱0.00</span>
    </div>
  </div>
</div>

      </div>
    </div>
  </div>

<!-- Terms & Conditions Modal -->
<dialog id="terms_modal" class="modal">
  <div class="modal-box max-w-5xl max-h-[80vh] overflow-hidden flex flex-col bg-white rounded-xl border-[#F7B32B]">
    
    <div class="p-6 pb-4 border-b">
      <h3 class="font-bold text-2xl text-primary mb-2">Soliera Hotel & Restaurant - Terms & Conditions</h3>
      <p class="text-gray-600">Please read and scroll to the bottom to accept</p>
    </div>
    
    <div class="p-6 pt-4 overflow-y-auto flex-grow text-gray-700">
      <h4 class="text-lg font-semibold text-primary">Reservation Terms & Conditions</h4>
      <p class="mb-4">Soliera Hotel & Restaurant is committed to providing refined hospitality and a seamless dining experience. By placing a reservation, you acknowledge and agree to the following policies:</p>
      
      <h5 class="font-semibold mt-4 text-primary">1. Reservation Policy</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>All reservations are subject to availability and confirmation by Soliera Hotel & Restaurant.</li>
        <li>A formal confirmation will be issued through email, SMS, or in-app notification once the reservation is secured.</li>
        <li>Guests are expected to arrive promptly. A grace period of fifteen (15) minutes shall be observed, after which the reserved table may be released to waiting guests.</li>
        <li>For large parties, private functions, or special events, a downpayment is  to guarantee the booking.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">2. Modification and Cancellation</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Once confirmed, all reservations are deemed final and may not be canceled.</li>
        <li>Any downpayment made shall be strictly non-refundable and non-transferable in accordance with the agreed terms.</li>
        <li>Failure to honor the booking ("No-Show") shall result in automatic forfeiture of both the reservation and downpayment.</li>
        <li>Soliera Hotel & Restaurant reserves the right to modify, reschedule, or cancel reservations solely in instances of force majeure, emergencies, or operational considerations.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">3. Guest Responsibilities</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Guests are expected to provide complete and accurate information at the time of reservation.</li>
        <li>Multiple fraudulent or misleading reservations are prohibited and may result in denial of service.</li>
        <li>Guests are responsible for advising the restaurant of any allergies, dietary restrictions, or special requirements in advance.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">4. Fees and Payments</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Reservations requiring a downpayment shall only be honored upon verified payment.</li>
        <li>Downpayments are irrevocably non-refundable and non-transferable.</li>
        <li>All electronic transactions comply with the provisions of Republic Act No. 8792 – Electronic Commerce Act of 2000, ensuring the validity and enforceability of payments.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">5. Privacy and Data Protection</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Personal information provided by guests shall be managed in accordance with the Data Privacy Act of 2012 (RA 10173).</li>
        <li>Collected information shall only be utilized for reservation, billing, service enhancement, and lawful reporting purposes.</li>
        <li>Guests may formally request access, correction, or deletion of their data, subject to applicable laws and operational requirements.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">6. Hotel & Restaurant Rights</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Management reserves the right to refuse service to individuals demonstrating disruptive conduct, policy violations, or health and safety risks.</li>
        <li>Management retains discretion in adjusting seating arrangements to ensure operational efficiency and guest comfort.</li>
        <li>Discounts, promotions, and privileges shall be honored only under terms consistent with Republic Act No. 7394 – Consumer Act of the Philippines.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">7. Limitation of Liability</h5>
      <ol class="list-decimal pl-5 mb-4">
        <li>Soliera Hotel & Restaurant shall not be held liable for any loss resulting from inaccurate or incomplete information provided by guests.</li>
        <li>The Hotel & Restaurant shall not be responsible for disruptions caused by events beyond its reasonable control, including force majeure, governmental directives, or natural calamities.</li>
      </ol>
      
      <h5 class="font-semibold mt-4 text-primary">8. Governing Law</h5>
      <p class="mb-4">This Agreement shall be governed and interpreted in accordance with the laws of the Republic of the Philippines, specifically:</p>
      <ul class="list-disc pl-5 mb-4">
        <li>RA 7394 – Consumer Act of the Philippines</li>
        <li>RA 10173 – Data Privacy Act of 2012</li>
        <li>RA 8792 – Electronic Commerce Act of 2000</li>
      </ul>
      
      <h5 class="font-semibold mt-4 text-primary">Acknowledgment</h5>
      <p class="mb-4">By confirming a reservation, the guest expressly affirms that they have read, understood, and agreed to these Terms and Conditions, including the non-cancellation and non-refundable downpayment policy.</p>
    </div>
    
    <div class="p-6 pt-4 border-t bg-gray-50">
  <div class="form-control">
    <label class="label justify-start cursor-pointer">
      <input 
        type="checkbox" 
        id="modal_terms_checkbox" 
        class="checkbox mr-3 border-[#F7B32B] checked:bg-[#F7B32B] checked:border-[#F7B32B]" 
      />
      <span class="label-text font-semibold text-[#F7B32B]">
        I have read and agree to the Terms & Conditions
      </span>
    </label>
  </div>
  <button 
    id="accept_terms_btn" 
    class="btn w-full mt-3 bg-[#F7B32B] text-white hover:bg-[#d99b20]" 
    disabled
  >
    Accept & Continue
  </button>
</div>

  </div>
  
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>



<script>
document.addEventListener("DOMContentLoaded", function() {
  // Payment method selection
  const paymentCards = document.querySelectorAll('.payment-card');
  const onlineOptions = document.getElementById('online-options');
  const paymentInstructions = document.getElementById('payment-instructions');
  const cashInstructions = document.getElementById('cash-instructions');
  const onlineMethodOptions = document.querySelectorAll('.online-method-option');
  
  // Initialize
  onlineOptions.classList.add('hidden');
  paymentInstructions.classList.add('hidden');
  cashInstructions.classList.add('hidden');
  
  // Payment card selection
  paymentCards.forEach(card => {
    card.addEventListener('click', () => {
      const input = card.querySelector('input[type="radio"]');
      input.checked = true;
      
      // Update visual selection
      paymentCards.forEach(c => {
        c.querySelector('label').classList.remove('border-[#F7B32B]', 'bg-[#F7B32B]/10');
      });
      card.querySelector('label').classList.add('border-[#F7B32B]', 'bg-[#F7B32B]/10');
      
      // Show/hide appropriate sections
      if (input.value === 'Online') {
        onlineOptions.classList.remove('hidden');
        paymentInstructions.classList.remove('hidden');
        cashInstructions.classList.add('hidden');
      } else if (input.value === 'Cash') {
        onlineOptions.classList.add('hidden');
        paymentInstructions.classList.add('hidden');
        cashInstructions.classList.remove('hidden');
      }
    });
  });
  
  // Online method selection
  onlineMethodOptions.forEach(option => {
    option.addEventListener('click', () => {
      const input = option.querySelector('input[type="radio"]');
      input.checked = true;
      
      // Update visual selection
      onlineMethodOptions.forEach(o => {
        o.classList.remove('border-[#F7B32B]', 'bg-[#F7B32B]/10');
      });
      option.classList.add('border-[#F7B32B]', 'bg-[#F7B32B]/10');
    });
  });
  
  // Form validation
  const form = document.getElementById('table-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const selectedPayment = document.querySelector('input[name="MOP"]:checked');
      if (!selectedPayment) {
        e.preventDefault();
        alert('Please select a payment method');
        return;
      }
      
      if (selectedPayment.value === 'Online') {
        const selectedOnlineMethod = document.querySelector('input[name="online_method"]:checked');
        if (!selectedOnlineMethod) {
          e.preventDefault();
          alert('Please select an online payment method');
          return;
        }
      }
    });
  }
});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const termsModal = document.getElementById('terms_modal');
  const acceptBtn = document.getElementById('accept_terms_btn');
  const modalCheckbox = document.getElementById('modal_terms_checkbox');
  const pageCheckbox = document.getElementById('terms_checkbox');

  if (!termsModal) return;

  // Function to open modal
  function openTermsModal() {
    termsModal.showModal();
    modalCheckbox.checked = false;  // reset checkbox
    acceptBtn.disabled = true;      // reset button
  }

  // Always show modal on page load
  openTermsModal();

  // Enable Accept button only if modal checkbox is checked
  modalCheckbox.addEventListener('change', () => {
    acceptBtn.disabled = !modalCheckbox.checked;
  });

  // Accept button closes modal and sets page checkbox
  acceptBtn.addEventListener('click', () => {
    if (modalCheckbox.checked) {
      termsModal.close();
      if (pageCheckbox) pageCheckbox.checked = true;
    }
  });

  // Close modal when clicking outside content
  termsModal.addEventListener('click', (e) => {
    if (e.target === termsModal) {
      termsModal.close();
    }
  });

  // Handle re-opening via link
  const openLinks = document.querySelectorAll('a[href="#terms_modal"]');
  openLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault(); // prevent default anchor behavior
      openTermsModal();
    });
  });
});

</script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    // ========================
    // Initialize Lucide Icons
    // ========================
    lucide.createIcons();

    // ========================
    // Elements
    // ========================
    const termsModal = document.getElementById('terms_modal');
    const acceptBtn = document.getElementById('accept_terms_btn');
    const modalCheckbox = document.getElementById('modal_terms_checkbox');
    const pageCheckbox = document.getElementById('terms_checkbox');
    const includeMenuCheckbox = document.getElementById('include_menu');
    const partyInput = document.getElementById("party_size");

    // ========================
    // Show Terms Modal once
    // ========================
    if (!localStorage.getItem('termsAccepted')) {
      if (termsModal) termsModal.showModal();
    }

    // Enable Accept button only if modal checkbox is checked
    modalCheckbox.addEventListener('change', () => {
      acceptBtn.disabled = !modalCheckbox.checked;
    });

    acceptBtn.addEventListener('click', () => {
      termsModal.close();
      localStorage.setItem('termsAccepted', 'true');
      if (pageCheckbox) pageCheckbox.checked = true;
      updateOrderSummary();
    });

    // ========================
    // Toggle Menu Section
    // ========================
    function toggleMenuSection() {
      const includeMenu = includeMenuCheckbox.checked;
      const menuSection = document.getElementById('menu-section');

      if (!includeMenu) {
        menuSection.classList.add('hidden');
        // Reset all quantities if menu excluded
        document.querySelectorAll('.quantity-input').forEach(input => {
          input.value = 0;
        });
      } else {
        menuSection.classList.remove('hidden');
        bindMenuListeners();
      }
      updateOrderSummary();
    }

    includeMenuCheckbox.addEventListener('change', toggleMenuSection);

    // ========================
    // Format Money Helper
    // ========================
    function formatMoney(amount) {
      return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ========================
    // Update Order Summary
    // ========================
    function updateOrderSummary() {
      let subtotal = 0;
      const orderItemsContainer = document.getElementById('order-items');
      orderItemsContainer.innerHTML = '';

      const includeMenu = includeMenuCheckbox.checked;

      // Compute subtotal if menu included
      if (includeMenu) {
        document.querySelectorAll('.quantity-input').forEach(input => {
          const qty = parseInt(input.value) || 0;
          if (qty > 0) {
            const itemContainer = input.closest('.menu-item');
            const itemName = itemContainer.querySelector('h5').innerText;
            const priceText = itemContainer.querySelector('span').innerText;
            const price = parseFloat(priceText.replace(/[₱,]/g, '')) || 0;

            const total = qty * price;
            subtotal += total;

            const itemElement = document.createElement('div');
            itemElement.className = 'flex justify-between items-center py-2 border-b';
            itemElement.innerHTML = `
              <div>
                <p class="font-medium text-sm">${itemName}</p>
                <p class="text-xs text-gray-500">${qty} x ${formatMoney(price)}</p>
              </div>
              <span class="font-medium text-sm">${formatMoney(total)}</span>
            `;
            orderItemsContainer.appendChild(itemElement);
          }
        });

        if (subtotal === 0) {
          orderItemsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No items selected yet</p>';
        }
      } else {
        orderItemsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No menu included in this reservation</p>';
      }

      // Reservation fee = ₱200 per person
      const persons = parseInt(partyInput?.value || 0);
      const reservationFee = persons * 200;

      // Service charge = 8% of subtotal (menu only)
      const serviceCharge = subtotal * 0.08;

      // Total before VAT
      const totalBeforeVAT = subtotal + serviceCharge + reservationFee;

      // VAT = 12% of totalBeforeVAT
      const vat = totalBeforeVAT * 0.12;

      // Total amount = subtotal + reservation fee + service charge + VAT
      const totalAmount = subtotal + reservationFee + serviceCharge + vat;

      // Update DOM
      document.getElementById('subtotal').innerText = formatMoney(subtotal);
      document.getElementById('service-charge').innerText = formatMoney(serviceCharge);
      document.getElementById('reservation-fee').innerText = formatMoney(reservationFee);
      document.getElementById('tax').innerText = formatMoney(vat);
      document.getElementById('total-amount').innerText = formatMoney(totalAmount);
    }

    // ========================
    // Bind Menu Buttons & Inputs
    // ========================
    function bindMenuListeners() {
      document.querySelectorAll(".quantity-btn").forEach(btn => {
        btn.onclick = () => {
          const input = document.getElementById(`item_${btn.dataset.id}`);
          let current = parseInt(input.value) || 0;
          if (btn.classList.contains("increase")) current++;
          else if (btn.classList.contains("decrease") && current > 0) current--;
          input.value = current;
          updateOrderSummary();
        };
      });

      document.querySelectorAll(".quantity-input").forEach(input => {
        input.oninput = () => {
          if (input.value < 0) input.value = 0;
          updateOrderSummary();
        };
      });
    }

    // ========================
    // Party Size Input Listener
    // ========================
    if (partyInput) partyInput.addEventListener("input", updateOrderSummary);

    // ========================
    // Initial bind
    // ========================
    bindMenuListeners();
    updateOrderSummary();
  });
</script>

</body>
</html>