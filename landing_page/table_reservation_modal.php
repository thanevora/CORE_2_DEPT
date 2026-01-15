<?php
include("../main_connection.php");

// Fetch hotel API token
$token = "uX8B1QqYJt7XqTf0sM3tKAh5nCjEjR1Xlqk4F8ZdD1mHq5V9y7oUj1QhUzPg5s";

$reservation_type = $_GET['type'] ?? 'unknown';
$title = ($reservation_type == 'table') ? 'Table Reservation' : 'Event Reservation';
$icon = ($reservation_type == 'table') ? '🍽️' : '🎉';
$logo_url = "https://restaurant.soliera-hotel-restaurant.com/images/tagline_no_bg.png";

// Handle hotel guest search
$hotel_guest = null;
$is_checked_in = false;
if (isset($_GET['search_room']) && !empty($_GET['search_room'])) {
    $room_number = $_GET['search_room'];
    $url = "https://hotel.soliera-hotel-restaurant.com/api/bookedrooms";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Accept: application/json"
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success'] === true && isset($data['data'])) {
            foreach ($data['data'] as $reservation) {
                // Check if roomID matches
                if (isset($reservation['roomID']) && $reservation['roomID'] == $room_number) {
                    $hotel_guest = $reservation;
                    // Check if guest is checked in
                    if (isset($reservation['reservation_bookingstatus']) && 
                        strtolower($reservation['reservation_bookingstatus']) == 'checked in') {
                        $is_checked_in = true;
                    }
                    break;
                }
            }
        }
    } else {
        error_log("Hotel API Error: HTTP Code $httpCode");
    }
}

// Fetch menu items for pagination
$db_name = "rest_m3_menu";
$menu_items = [];
$total_menu_items = 0;
$current_page = isset($_GET['menu_page']) ? max(1, intval($_GET['menu_page'])) : 1;
$items_per_page = 10;

if (isset($connections[$db_name])) {
    $menu_conn = $connections[$db_name];
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM menu";
    $count_result = $menu_conn->query($count_query);
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        $total_menu_items = $count_row['total'];
    }
    
    // Calculate offset
    $offset = ($current_page - 1) * $items_per_page;
    
    // Fetch paginated items
    $query = "SELECT * FROM menu ORDER BY category, name LIMIT $offset, $items_per_page";
    $result = $menu_conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $menu_items[] = $row;
        }
    }
}
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
    
    <!-- Country flags -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    
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
      .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 31, 84, 0.9);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        flex-direction: column;
      }
      .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #F7B32B;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .hotel-guest-badge {
        animation: pulse 2s infinite;
      }
      @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
      }
      .country-code-btn {
        min-width: 100px;
      }
      .menu-image-container {
        height: 200px;
        overflow: hidden;
        border-radius: 8px 8px 0 0;
      }
      .menu-card {
        transition: all 0.3s ease;
      }
      .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 31, 84, 0.15);
      }
      .input-with-icon {
        padding-left: 3rem !important;
      }
      .icon-inside-input {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
      }
      /* Custom scrollbar for modal */
      .modal-box::-webkit-scrollbar {
        width: 8px;
      }
      .modal-box::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
      }
      .modal-box::-webkit-scrollbar-thumb {
        background: #F7B32B;
        border-radius: 4px;
      }
      .modal-box::-webkit-scrollbar-thumb:hover {
        background: #d99b20;
      }
    </style>
</head>

<body class="min-h-screen">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
      <div class="spinner mb-4"></div>
      <p class="text-white text-xl font-semibold">Processing your reservation...</p>
      <p class="text-gray-300 mt-2">Please wait a moment</p>
    </div>

    <!-- Terms & Conditions Modal (Shows first) -->
    <div class="modal modal-open" id="terms_modal">
      <div class="modal-box max-w-5xl max-h-[80vh] overflow-hidden flex flex-col bg-white rounded-xl border-[#F7B32B] relative">
        <div class="p-6 pb-4 border-b">
          <h3 class="font-bold text-2xl text-primary mb-2">Soliera Hotel & Restaurant - Terms & Conditions</h3>
          <p class="text-gray-600">Please read and scroll to the bottom to accept</p>
        </div>
        
        <div class="p-6 pt-4 overflow-y-auto flex-grow text-gray-700">
          <h4 class="text-lg font-semibold text-primary">Reservation Terms & Conditions</h4>
          <p class="mb-4">Soliera Hotel & Restaurant is committed to providing refined hospitality and a seamless dining experience. By placing a reservation, you acknowledge and agree to the following policies:</p>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">1. Reservations & Confirmations</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Reservations are confirmed upon receipt of a valid confirmation email.</li>
            <li>We hold tables for 15 minutes past the reservation time.</li>
            <li>For hotel guests, reservations are automatically linked to your room.</li>
            <li>All reservations require a valid email address and phone number.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">2. Cancellations & Modifications</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Cancellations must be made at least 24 hours in advance.</li>
            <li>No-shows may incur a charge equivalent to the reservation fee.</li>
            <li>Modifications are subject to availability and must be requested at least 12 hours in advance.</li>
            <li>Changes to party size may affect table assignment.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">3. Payment & Charges</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>All reservations require a ₱200 per person reservation fee.</li>
            <li>Hotel guests enjoy a 10% discount on food and beverages (excluding alcohol).</li>
            <li>Service charge (8%) and VAT (12%) apply to all orders.</li>
            <li>Online payments are processed securely through our payment partners.</li>
            <li>All prices are in Philippine Peso (₱) and inclusive of applicable taxes.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">4. Hotel Guest Privileges</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Hotel guests must present their room key or valid ID for verification.</li>
            <li>Charges can be billed directly to your room upon request.</li>
            <li>Special requests for hotel guests will be prioritized.</li>
            <li>Discount applies only to current hotel guests with valid check-in status.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">5. Dining Policies</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Maximum table capacity: 10 persons per reservation.</li>
            <li>Dress code: Smart casual attire is required.</li>
            <li>Outside food and beverages are not permitted.</li>
            <li>Children under 12 must be accompanied by an adult at all times.</li>
            <li>We reserve the right to seat incomplete parties after a 15-minute grace period.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">6. Special Requests & Dietary Requirements</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Special dietary requests must be indicated during reservation.</li>
            <li>While we endeavor to accommodate dietary needs, we cannot guarantee allergen-free preparation.</li>
            <li>Special occasion requests (birthdays, anniversaries) should be made in advance.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">7. Liability & Responsibility</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Soliera Restaurant is not responsible for lost or stolen personal items.</li>
            <li>Guests are responsible for any damage caused to restaurant property.</li>
            <li>Management reserves the right to refuse service to anyone.</li>
            <li>In case of force majeure, reservations may be rescheduled or refunded.</li>
          </ul>
          
          <h5 class="font-semibold text-primary mt-4 mb-2">8. Privacy Policy</h5>
          <ul class="list-disc pl-5 mb-4 space-y-1">
            <li>Personal information collected is used solely for reservation purposes.</li>
            <li>We do not share your information with third parties without consent.</li>
            <li>You may request deletion of your data by contacting our privacy officer.</li>
          </ul>
          
          <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-700"><strong>Note:</strong> By proceeding with your reservation, you confirm that you have read, understood, and agree to all the terms and conditions stated above. These terms are subject to change without prior notice.</p>
          </div>
        </div>
        
        <div class="p-6 pt-4 border-t bg-gray-50">
          <div class="form-control">
            <label class="label justify-start cursor-pointer">
              <input type="checkbox" id="modal_terms_checkbox" class="checkbox mr-3 border-[#F7B32B] checked:bg-[#F7B32B] checked:border-[#F7B32B]" />
              <span class="label-text font-semibold text-[#F7B32B]">
                I have read and agree to the Terms & Conditions
              </span>
            </label>
          </div>
          <button id="accept_terms_btn" class="btn w-full mt-3 bg-[#F7B32B] text-white hover:bg-[#d99b20]" disabled>
            Accept & Continue
          </button>
        </div>
      </div>
    </div>

    <!-- Modal for Check-in Prompt (Shows after Terms) -->
    <div class="modal" id="checkinModal">
      <div class="modal-box bg-[#001f54] text-white border-2 border-[#F7B32B]">
        <h3 class="font-bold text-2xl text-[#F7B32B] mb-4">Are you a Hotel Guest?</h3>
        <p class="py-4">Are you currently checked in at Soliera Hotel? If yes, you can search your room number to pre-fill your information and receive hotel guest benefits.</p>
        <div class="modal-action">
          <button class="btn bg-[#F7B32B] text-[#001f54] hover:bg-[#d99b20]" onclick="showSearchBar()">
            Yes, I'm a Hotel Guest
          </button>
          <button class="btn bg-gray-600 text-white hover:bg-gray-700" onclick="closeCheckinModal()">
            No, I'm a Restaurant Guest
          </button>
        </div>
      </div>
    </div>

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
      
      <!-- Hotel Guest Search - Right Side (Initially Hidden) -->
      <div id="searchBarContainer" class="flex-none hidden">
        <div class="relative">
          <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="type" value="table">
            <div class="relative">
              <input type="text" 
                     name="search_room" 
                     value="<?php echo isset($_GET['search_room']) ? htmlspecialchars($_GET['search_room']) : ''; ?>"
                     placeholder="Enter Room #"
                     class="w-48 px-4 py-2 rounded-lg border-2 border-[#F7B32B] bg-white text-[#001f54] placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#F7B32B] text-sm">
              <i class='bx bx-search absolute right-3 top-2.5 text-[#001f54]'></i>
            </div>
            <button type="submit" class="px-3 py-2 bg-[#F7B32B] text-[#001f54] font-semibold rounded-lg hover:bg-[#d99b20] transition-colors text-sm">
              Search
            </button>
            <?php if(isset($_GET['search_room'])): ?>
              <a href="?type=table" class="px-3 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors text-sm">
                Clear
              </a>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>

    <!-- Hotel Guest Notification -->
    <div class="container mx-auto px-4 max-w-8xl">
      <?php if($hotel_guest): ?>
      <div class="mt-4 p-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg text-white hotel-guest-badge">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-bold text-lg">Hotel Guest Found!</h4>
            <p class="text-sm">
              <span class="font-semibold">Name:</span> <?php echo htmlspecialchars($hotel_guest['guestname'] ?? 'N/A'); ?> | 
              <span class="font-semibold">Room:</span> <?php echo htmlspecialchars($hotel_guest['roomID'] ?? 'N/A'); ?> | 
              <span class="font-semibold">Status:</span> <?php echo htmlspecialchars($hotel_guest['reservation_bookingstatus'] ?? 'N/A'); ?>
            </p>
            <p class="text-xs mt-1">
              <span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($hotel_guest['guestphonenumber'] ?? 'N/A'); ?> | 
              <span class="font-semibold">Email:</span> <?php echo htmlspecialchars($hotel_guest['guestemailaddress'] ?? 'N/A'); ?> | 
              <span class="font-semibold">Check-in:</span> <?php echo date('M d, Y', strtotime($hotel_guest['reservation_checkin'] ?? '')); ?>
            </p>
          </div>
          <div class="bg-white text-green-600 px-3 py-1 rounded-full font-bold">
            Hotel Guest
          </div>
        </div>
        <p class="text-xs mt-2 text-green-100">
          <?php if($is_checked_in): ?>
            Guest information has been auto-filled and cannot be edited. Reservation will be marked as "Hotel" with 10% discount.
          <?php else: ?>
            Guest found but not currently checked in. Please proceed with normal reservation.
          <?php endif; ?>
        </p>
      </div>
      <?php elseif(isset($_GET['search_room']) && !$hotel_guest): ?>
      <div class="mt-4 p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg text-white">
        <div class="flex items-center gap-2">
          <i class='bx bx-error-circle text-xl'></i>
          <span class="font-semibold">No hotel guest found for room <?php echo htmlspecialchars($_GET['search_room']); ?></span>
        </div>
        <p class="text-xs mt-1 text-amber-100">Please proceed with normal reservation. Reservation will be marked as "Resto"</p>
      </div>
      <?php endif; ?>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-8xl shadow-6xl">
      <!-- Page Title -->
      <div class="bg-[#001f54] border-2 border-[#F7B32B] rounded-lg max-w-4xl mx-auto p-6 mb-10 shadow-lg">
        <div class="text-center">
          <h2 class="text-3xl font-bold text-[#F7B32B] mb-2">Table Reservation</h2>
          <p class="text-gray-300 text-sm">Book your table for an exceptional dining experience</p>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Column - Reservation Form -->
        <div class="w-full lg:w-2/3">
          <div class="bg-[#001f54] text-white rounded-lg p-6 mb-6 border-2 border-[#F7B32B]">
            <form id="table-form" method="POST">
              <input type="hidden" name="reservation_type" value="table">
              <input type="hidden" name="MOP" value="Online">
              
              <!-- Hidden fields -->
              <input type="hidden" id="parts" name="parts" value="<?php echo $hotel_guest && $is_checked_in ? 'hotel' : 'resto'; ?>">
              <?php if($hotel_guest && $is_checked_in): ?>
                <input type="hidden" id="room_id" name="room_id" value="<?php echo htmlspecialchars($hotel_guest['roomID'] ?? ''); ?>">
                <input type="hidden" id="reservation_id" name="reservation_id" value="<?php echo htmlspecialchars($hotel_guest['reservationID'] ?? ''); ?>">
                <input type="hidden" id="booking_id" name="booking_id" value="<?php echo htmlspecialchars($hotel_guest['bookingID'] ?? ''); ?>">
              <?php endif; ?>
              
              <!-- Customer Information -->
              <div class="mb-6">
                <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                  <i class='bx bx-user text-accent text-xl'></i>
                  Customer Information
                  <?php if($hotel_guest && $is_checked_in): ?>
                    <span class="ml-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full">Hotel Guest</span>
                  <?php endif; ?>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="floating-label">
                    <input type="text" id="name" name="name" 
                           value="<?php echo $hotel_guest && $is_checked_in ? htmlspecialchars($hotel_guest['guestname'] ?? '') : ''; ?>"
                           class="border-2 border-[#F7B32B] floating-input input-with-icon" placeholder=" " 
                           <?php echo ($hotel_guest && $is_checked_in) ? 'readonly' : 'required'; ?>>
                    <i class='bx bx-user icon-inside-input'></i>
                    <span class="text-[#F7B32B]">Full Name*</span>
                  </div>

                  <div class="floating-label">
                    <div class="flex gap-2">
                      <div class="relative flex-1">
                        <select id="country_code" name="country_code" 
                                class="w-full border-2 border-[#F7B32B] bg-white text-[#001f54] rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#F7B32B]"
                                <?php echo ($hotel_guest && $is_checked_in) ? 'disabled' : ''; ?>>
                          <option value="">Select Country</option>
                          <option value="+63" selected>🇵🇭 Philippines (+63)</option>
                          <option value="+1">🇺🇸 United States (+1)</option>
                          <option value="+44">🇬🇧 United Kingdom (+44)</option>
                          <option value="+61">🇦🇺 Australia (+61)</option>
                          <option value="+65">🇸🇬 Singapore (+65)</option>
                          <option value="+60">🇲🇾 Malaysia (+60)</option>
                          <option value="+62">🇮🇩 Indonesia (+62)</option>
                          <option value="+81">🇯🇵 Japan (+81)</option>
                          <option value="+82">🇰🇷 South Korea (+82)</option>
                          <option value="+86">🇨🇳 China (+86)</option>
                          <option value="+91">🇮🇳 India (+91)</option>
                          <option value="+33">🇫🇷 France (+33)</option>
                          <option value="+49">🇩🇪 Germany (+49)</option>
                          <option value="+39">🇮🇹 Italy (+39)</option>
                          <option value="+34">🇪🇸 Spain (+34)</option>
                          <option value="+55">🇧🇷 Brazil (+55)</option>
                          <option value="+52">🇲🇽 Mexico (+52)</option>
                          <option value="+971">🇦🇪 UAE (+971)</option>
                          <option value="+966">🇸🇦 Saudi Arabia (+966)</option>
                          <option value="+974">🇶🇦 Qatar (+974)</option>
                          <option value="+64">🇳🇿 New Zealand (+64)</option>
                          <option value="+27">🇿🇦 South Africa (+27)</option>
                        </select>
                      </div>
                      <div class="relative flex-1">
                        <input type="text" id="contact" name="contact" 
                               value="<?php echo $hotel_guest && $is_checked_in ? htmlspecialchars($hotel_guest['guestphonenumber'] ?? '') : ''; ?>"
                               class="border-2 border-[#F7B32B] floating-input input-with-icon" placeholder=" "
                               <?php echo ($hotel_guest && $is_checked_in) ? 'readonly' : 'required'; ?>>
                        <i class='bx bx-phone icon-inside-input'></i>
                        <span class="text-[#F7B32B]">Phone Number*</span>
                      </div>
                    </div>
                  </div>

                  <div class="floating-label md:col-span-2">
                    <input type="email" id="email" name="email" 
                           value="<?php echo $hotel_guest && $is_checked_in ? htmlspecialchars($hotel_guest['guestemailaddress'] ?? '') : ''; ?>"
                           class="border-2 border-[#F7B32B] floating-input input-with-icon" placeholder=" "
                           <?php echo ($hotel_guest && $is_checked_in) ? 'readonly' : 'required'; ?>>
                    <i class='bx bx-envelope icon-inside-input'></i>
                    <span class="text-[#F7B32B]">Email*</span>
                  </div>
                </div>
              </div>
              
              <!-- Reservation Details - Side by side -->
              <div class="mb-6">
                <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                  <i class='bx bx-calendar text-accent text-xl'></i>
                  Reservation Details
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="floating-label">
                    <input type="date" id="reservation_date" name="reservation_date" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           class="border-2 border-[#F7B32B] floating-input input-with-icon" placeholder=" " required>
                    <i class='bx bx-calendar icon-inside-input'></i>
                    <span class="text-[#F7B32B]">Date*</span>
                  </div>

                  <div class="floating-label">
                    <input type="time" id="reservation_time" name="reservation_time" 
                           class="border-2 border-[#F7B32B] floating-input input-with-icon" placeholder=" " required>
                    <i class='bx bx-time icon-inside-input'></i>
                    <span class="text-[#F7B32B]">Reservation Time*</span>
                  </div>

                  <div class="floating-label">
                    <input type="number" id="headcount" name="headcount" min="1" max="10"
                           class="border-2 border-[#F7B32B] floating-input input-with-icon" 
                           oninput="updateOrderSummary()" required>
                    <i class='bx bx-group icon-inside-input'></i>
                    <span class="text-[#F7B32B]">Headcount* (Max: 10)</span>
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
                  <select id="table_id" name="table_id" class="border-2 border-[#F7B32B] floating-input" required>
                    <option value="" disabled selected>Select a table</option>
                    <?php
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

                <!-- Online Payment Only -->
                <div class="mt-6 p-4 bg-gray-50 rounded-xl border-2 border-[#F7B32B]">
                  <p class="text-sm font-medium text-[#001f54] mb-3">Select Online Payment Method:</p>

                  <div class="flex flex-wrap gap-4">
                    <!-- Gcash -->
                    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
                      <input type="radio" name="online_method" value="Gcash" class="hidden" required>
                      <img src="../images/Gcash.png" alt="Gcash" class="w-16 h-16 object-contain" />
                    </label>

                    <!-- Maya -->
                    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
                      <input type="radio" name="online_method" value="Maya" class="hidden" required>
                      <img src="../images/Maya.png" alt="Maya" class="w-16 h-16 object-contain" />
                    </label>

                    <!-- Credit Card -->
                    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
                      <input type="radio" name="online_method" value="Credit Card" class="hidden" required>
                      <div class="w-16 h-16 bg-[#001f54] rounded-full flex items-center justify-center">
                        <i class='bx bx-credit-card text-[#F7B32B] text-2xl'></i>
                      </div>
                      <span class="text-sm font-medium text-[#001f54]">Credit Card</span>
                    </label>

                    <!-- Debit Card -->
                    <label class="online-method-option flex items-center gap-3 p-3 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md bg-white">
                      <input type="radio" name="online_method" value="Debit Card" class="hidden" required>
                      <div class="w-16 h-16 bg-[#001f54] rounded-full flex items-center justify-center">
                        <i class='bx bx-card text-[#F7B32B] text-2xl'></i>
                      </div>
                      <span class="text-sm font-medium text-[#001f54]">Debit Card</span>
                    </label>
                  </div>

                  <!-- Payment Instructions -->
                  <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">You will be redirected to a secure payment portal after submitting your reservation.</p>
                  </div>
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
                <input type="checkbox" id="include_menu" class="checkbox checkbox-primary" onchange="openMenuModal()">
                <label for="include_menu" class="text-sm font-medium text-[#F7B32B]">Include menu in your reservation?</label>
              </div>

              <!-- Selected Menu Items Display -->
              <div id="selected-menu-items" class="hidden mb-6">
                <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
                  <i class='bx bx-food-menu text-accent text-xl'></i>
                  Selected Menu Items
                </h3>
                <div id="selected-items-container" class="space-y-3 max-h-60 overflow-y-auto p-3 bg-gray-50 rounded-lg">
                  <!-- Selected items will appear here -->
                </div>
              </div>

              <div class="mb-6">
                <label class="flex items-start gap-3 cursor-pointer">
                  <input type="checkbox" id="terms_checkbox" class="checkbox checkbox-primary mt-1" required />
                  <span class="text-sm text-[#F7B32B]">I agree to the Terms & Conditions</span>
                </label>
              </div>
              
              <!-- Form Buttons -->
              <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                <button type="button" id="clear-btn" class="btn flex-1 border border-[#F7B32B] text-[#F7B32B] bg-[#001f54] hover:bg-[#F7B32B] hover:text-white">
                  <i data-lucide="x" class="w-4 h-4 mr-2"></i> Clear Form
                </button>
                <button type="button" id="submit-btn" class="btn flex-1 bg-[#F7B32B] text-white hover:bg-[#d99b20]">
                  <i data-lucide="plus" class="w-4 h-4 mr-2"></i> 
                  <?php echo ($hotel_guest && $is_checked_in) ? 'Create Hotel Guest Reservation' : 'Create Reservation'; ?>
                </button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Right Column - Order Summary -->
        <div class="w-full lg:w-1/3">
          <div class="bg-[#001f54] border-2 border-[#F7B32B] text-white rounded-lg shadow-lg p-6 sticky top-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-semibold text-[#F7B32B] flex items-center gap-2">
                <i class='bx bx-cart text-accent text-xl'></i>
                Reservation Bill Summary
              </h3>
              <?php if($hotel_guest && $is_checked_in): ?>
                <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full font-bold">
                  HOTEL GUEST
                </span>
              <?php else: ?>
                <span class="px-3 py-1 bg-blue-500 text-white text-xs rounded-full font-bold">
                  RESTAURANT
                </span>
              <?php endif; ?>
            </div>
            
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
                <span class="text-sm">Reservation Fee (₱200 x Headcount):</span>
                <span id="reservation-fee" class="font-medium">₱0.00</span>
              </div>
              <?php if($hotel_guest && $is_checked_in): ?>
              <div class="flex justify-between items-center text-green-300">
                <span class="text-sm">Hotel Guest Discount (10%):</span>
                <span id="hotel-discount" class="font-medium">-₱0.00</span>
              </div>
              <?php endif; ?>
              <div class="flex justify-between items-center font-bold text-lg text-[#F7B32B] mt-4 pt-4 border-t border-[#F7B32B]/50">
                <span>Total:</span>
                <span id="total-amount">₱0.00</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Menu Selection Modal -->
    <dialog id="menuModal" class="modal modal-bottom sm:modal-middle">
      <div class="modal-box max-w-7xl max-h-[90vh] overflow-hidden flex flex-col bg-white rounded-xl border-[#F7B32B] p-0">
        <div class="p-6 pb-4 border-b sticky top-0 bg-white z-10">
          <h3 class="font-bold text-3xl text-primary mb-2">Select Menu Items</h3>
          <p class="text-gray-600 mb-4">Browse our menu and select items for your reservation</p>
          
          <!-- Menu Filters -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <div class="relative">
                <input type="text" id="menu-search" placeholder="Search menu items..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-[#F7B32B]">
                <i class='bx bx-search absolute right-3 top-2.5 text-gray-400'></i>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <select id="menu-category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-[#F7B32B]">
                <option value="" selected>All Categories</option>
                <option value="appetizers">Appetizers</option>
                <option value="mains">Main Courses</option>
                <option value="desserts">Desserts</option>
                <option value="drinks">Drinks</option>
                <option value="specials">Specials</option>
                <option value="sides">Sides</option>
                <option value="bundle">Bundle</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Variant</label>
              <select id="menu-variant" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-[#F7B32B]">
                <option value="" selected>All Variants</option>
                <option value="Breakfast">Breakfast</option>
                <option value="Lunch">Lunch</option>
                <option value="Dinner">Dinner</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
              <select id="menu-sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-[#F7B32B]">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="price_asc">Price (Low to High)</option>
                <option value="price_desc">Price (High to Low)</option>
                <option value="category">Category</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="p-6 pt-4 overflow-y-auto flex-grow">
          <div id="menu-items-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Menu items will be loaded here -->
          </div>
          
          <!-- Pagination -->
          <div id="menu-pagination" class="mt-6 flex justify-center items-center gap-2">
            <!-- Pagination will be loaded here -->
          </div>
        </div>
        
        <div class="p-6 pt-4 border-t bg-gray-50 sticky bottom-0">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h4 class="font-bold text-lg text-primary">Selected Items</h4>
              <p class="text-sm text-gray-600" id="selected-count">0 items selected</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-600">Subtotal</p>
              <p class="font-bold text-xl text-[#F7B32B]" id="modal-subtotal">₱0.00</p>
            </div>
          </div>
          <div class="flex gap-3">
            <button class="btn flex-1 bg-gray-600 text-white hover:bg-gray-700" onclick="closeMenuModal()">
              Cancel
            </button>
            <button class="btn flex-1 bg-[#F7B32B] text-white hover:bg-[#d99b20]" onclick="saveMenuSelection()">
              Save Selection (Total: <span id="selected-total">₱0.00</span>)
            </button>
          </div>
        </div>
      </div>
    </dialog>

    <script>
// Global variables
let selectedMenuItems = {};
let allMenuItems = [];
let currentMenuPage = 1;
const itemsPerPage = 10;

// Helper function for formatting money
function formatMoney(amount) {
  return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener("DOMContentLoaded", function() {
  // Initialize Lucide Icons
  lucide.createIcons();

  // Elements
  const termsModal = document.getElementById('terms_modal');
  const acceptBtn = document.getElementById('accept_terms_btn');
  const modalCheckbox = document.getElementById('modal_terms_checkbox');
  const pageCheckbox = document.getElementById('terms_checkbox');
  const includeMenuCheckbox = document.getElementById('include_menu');
  const headcountInput = document.getElementById("headcount");
  const form = document.getElementById('table-form');
  const submitBtn = document.getElementById('submit-btn');
  const clearBtn = document.getElementById('clear-btn');
  const loadingOverlay = document.getElementById('loadingOverlay');

  // Online method selection
  const onlineMethodOptions = document.querySelectorAll('.online-method-option');
  
  // Initialize online method selection
  onlineMethodOptions.forEach(option => {
    option.addEventListener('click', () => {
      const input = option.querySelector('input[type="radio"]');
      input.checked = true;
      
      onlineMethodOptions.forEach(o => {
        o.classList.remove('border-[#F7B32B]', 'bg-[#F7B32B]/10');
      });
      option.classList.add('border-[#F7B32B]', 'bg-[#F7B32B]/10');
    });
  });

  // Terms Modal - Accept button
  modalCheckbox.addEventListener('change', () => {
    acceptBtn.disabled = !modalCheckbox.checked;
  });

  acceptBtn.addEventListener('click', () => {
    // Close the modal properly
    termsModal.classList.remove('modal-open');
    
    // Store acceptance in localStorage
    localStorage.setItem('termsAccepted', 'true');
    
    // Check the main form checkbox
    if (pageCheckbox) pageCheckbox.checked = true;
    
    // Show check-in modal after terms
    setTimeout(() => {
      document.getElementById('checkinModal').classList.add('modal-open');
    }, 300);
  });

  // Update Order Summary
  function updateOrderSummary() {
    let subtotal = 0;
    const orderItemsContainer = document.getElementById('order-items');
    orderItemsContainer.innerHTML = '';

    const includeMenu = includeMenuCheckbox.checked;
    const isHotelGuest = document.getElementById('parts').value === 'hotel';

    if (includeMenu && Object.keys(selectedMenuItems).length > 0) {
      Object.entries(selectedMenuItems).forEach(([menuId, item]) => {
        if (item.quantity > 0) {
          const total = item.price * item.quantity;
          subtotal += total;

          const itemElement = document.createElement('div');
          itemElement.className = 'flex justify-between items-center py-2 border-b';
          itemElement.innerHTML = `
            <div>
              <p class="font-medium text-sm">${item.name}</p>
              <p class="text-xs text-gray-500">${item.quantity} x ${formatMoney(item.price)}</p>
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

    const persons = parseInt(headcountInput?.value || 0);
    const reservationFee = persons * 200;
    const serviceCharge = subtotal * 0.08;
    const vat = (subtotal + serviceCharge + reservationFee) * 0.12;
    
    // Apply hotel discount if applicable
    let hotelDiscount = 0;
    let totalAmount = subtotal + reservationFee + serviceCharge + vat;
    
    if (isHotelGuest) {
      // 10% discount on subtotal only (not on fees)
      hotelDiscount = subtotal * 0.10;
      totalAmount = (subtotal - hotelDiscount) + reservationFee + serviceCharge + vat;
      
      // Update hotel discount display
      const hotelDiscountElement = document.getElementById('hotel-discount');
      if (hotelDiscountElement) {
        hotelDiscountElement.textContent = formatMoney(-hotelDiscount);
      }
    }

    document.getElementById('subtotal').innerText = formatMoney(subtotal);
    document.getElementById('service-charge').innerText = formatMoney(serviceCharge);
    document.getElementById('reservation-fee').innerText = formatMoney(reservationFee);
    document.getElementById('tax').innerText = formatMoney(vat);
    document.getElementById('total-amount').innerText = formatMoney(totalAmount);
  }

  // Headcount Input Listener
  if (headcountInput) headcountInput.addEventListener("input", function() {
    if (this.value > 10) this.value = 10;
    if (this.value < 1) this.value = 1;
    updateOrderSummary();
  });

  // Clear Form
  clearBtn.addEventListener('click', function() {
    if (confirm('Are you sure you want to clear the form? This will remove all entered data.')) {
      form.reset();
      selectedMenuItems = {};
      document.getElementById('selected-menu-items').classList.add('hidden');
      includeMenuCheckbox.checked = false;
      onlineMethodOptions.forEach(option => {
        option.classList.remove('border-[#F7B32B]', 'bg-[#F7B32B]/10');
      });
      updateOrderSummary();
    }
  });

  // Form Validation
  function validateForm() {
    const selectedOnlineMethod = document.querySelector('input[name="online_method"]:checked');
    if (!selectedOnlineMethod) {
      alert('Please select an online payment method');
      return false;
    }

    if (!pageCheckbox.checked) {
      alert('Please agree to the Terms & Conditions');
      return false;
    }

    return true;
  }

  // Submit Form
  submitBtn.addEventListener('click', async function(e) {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    // Show loading overlay
    loadingOverlay.style.display = 'flex';
    
    // Collect form data
    const formData = new FormData(form);
    
    // Add country code to contact
    const countryCode = document.getElementById('country_code').value;
    const phoneNumber = document.getElementById('contact').value;
    formData.set('contact', countryCode + phoneNumber);
    
    // Add menu items
    Object.entries(selectedMenuItems).forEach(([menuId, item]) => {
      if (item.quantity > 0) {
        formData.append(`menu_items[${menuId}]`, item.quantity);
      }
    });
    
    // Add hotel discount calculation
    const isHotelGuest = document.getElementById('parts').value === 'hotel';
    if (isHotelGuest) {
      let subtotal = 0;
      Object.values(selectedMenuItems).forEach(item => {
        subtotal += item.price * item.quantity;
      });
      const hotelDiscount = subtotal * 0.10;
      formData.append('hotel_discount', hotelDiscount.toFixed(2));
    }
    
    console.log("Sending form data:", Object.fromEntries(formData));
    
    try {
      // Send data to API
      const response = await fetch('../M1/create_reservation_main.php', {
        method: 'POST',
        body: formData
      });
      
      // Get response text first
      const responseText = await response.text();
      console.log("Raw response:", responseText);
      
      // Try to parse as JSON
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (e) {
        console.error("Failed to parse JSON:", e);
        throw new Error("Invalid JSON response from server: " + responseText);
      }
      
      if (result.success) {
        // Redirect to success page
        window.location.href = '../landing_page/reservation_success.php?type=table';
      } else {
        loadingOverlay.style.display = 'none';
        alert('Error: ' + result.message);
      }
    } catch (error) {
      loadingOverlay.style.display = 'none';
      console.error('Full Error:', error);
      
      if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
        alert('Network error. Please check your connection and try again.');
      } else {
        alert('Error: ' + error.message);
      }
    }
  });

  // Set minimum date to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('reservation_date').min = today;
  
  // Set default time to next hour
  const now = new Date();
  now.setHours(now.getHours() + 1);
  const nextHour = now.getHours().toString().padStart(2, '0') + ':00';
  document.getElementById('reservation_time').value = nextHour;
  
  // Initialize order summary
  updateOrderSummary();
});

// Menu Modal Functions
function loadMenuItems(page = 1) {
  const search = document.getElementById('menu-search').value.toLowerCase();
  const category = document.getElementById('menu-category').value;
  const variant = document.getElementById('menu-variant').value;
  const sort = document.getElementById('menu-sort').value;
  
  const container = document.getElementById('menu-items-container');
  container.innerHTML = '';
  
  // Filter items
  let filteredItems = [...allMenuItems];
  
  if (search) {
    filteredItems = filteredItems.filter(item => 
      item.name.toLowerCase().includes(search) || 
      item.description.toLowerCase().includes(search)
    );
  }
  
  if (category) {
    filteredItems = filteredItems.filter(item => 
      item.category.toLowerCase() === category.toLowerCase()
    );
  }
  
  if (variant) {
    filteredItems = filteredItems.filter(item => 
      item.variant === variant
    );
  }
  
  // Sort items
  filteredItems.sort((a, b) => {
    switch(sort) {
      case 'name_asc':
        return a.name.localeCompare(b.name);
      case 'name_desc':
        return b.name.localeCompare(a.name);
      case 'price_asc':
        return parseFloat(a.price) - parseFloat(b.price);
      case 'price_desc':
        return parseFloat(b.price) - parseFloat(a.price);
      case 'category':
        return a.category.localeCompare(b.category) || a.name.localeCompare(b.name);
      default:
        return 0;
    }
  });
  
  // Calculate pagination
  const totalItems = filteredItems.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage);
  const startIndex = (page - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedItems = filteredItems.slice(startIndex, endIndex);
  
  // Display items
  if (paginatedItems.length === 0) {
    container.innerHTML = `
      <div class="col-span-3 text-center py-12">
        <i data-lucide="search" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
        <p class="text-gray-500 text-lg">No menu items found</p>
        <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
      </div>
    `;
  } else {
    paginatedItems.forEach(item => {
      const itemElement = document.createElement('div');
      itemElement.className = 'menu-card bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden';
      itemElement.innerHTML = `
        <div class="menu-image-container">
          ${item.image_url ? `
            <img src="../M3/Menu_uploaded/menu_images/original/${item.image_url}" 
                 alt="${item.name}" 
                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+';">
          ` : `
            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
              <i data-lucide="utensils" class="w-16 h-16 text-gray-400 mb-3"></i>
              <p class="text-sm text-gray-500 font-medium">No image</p>
            </div>
          `}
        </div>
        <div class="p-4">
          <div class="flex justify-between items-start mb-2">
            <h5 class="font-bold text-lg text-[#F7B32B]">${item.name}</h5>
            <span class="text-[#F7B32B] font-bold text-lg">₱${parseFloat(item.price).toFixed(2)}</span>
          </div>
          <p class="text-gray-600 text-sm mb-3 line-clamp-2">${item.description}</p>
          <div class="flex flex-wrap gap-1 mb-3">
            <span class="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full">${item.category}</span>
            ${item.variant ? `<span class="px-2 py-1 bg-accent/10 text-accent text-xs rounded-full">${item.variant}</span>` : ''}
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <button type="button" class="quantity-btn decrease w-8 h-8 bg-gray-200 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-300" data-id="${item.menu_id}">
                <i data-lucide="minus" class="w-4 h-4"></i>
              </button>
              <input type="number" name="modal_menu_items[${item.menu_id}]" 
                     id="modal_item_${item.menu_id}" 
                     class="quantity-input text-center w-12 h-8 border border-gray-300 rounded" 
                     value="${selectedMenuItems[item.menu_id] ? selectedMenuItems[item.menu_id].quantity : 0}" 
                     min="0" readonly>
              <button type="button" class="quantity-btn increase w-8 h-8 bg-[#F7B32B] text-white rounded-full flex items-center justify-center hover:bg-[#d99b20]" data-id="${item.menu_id}">
                <i data-lucide="plus" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
        </div>
      `;
      container.appendChild(itemElement);
    });
  }
  
  // Update pagination controls
  updatePaginationControls(page, totalPages);
  
  // Bind quantity buttons
  bindModalMenuListeners();
  
  // Update modal summary
  updateModalOrderSummary();
}

function updatePaginationControls(currentPage, totalPages) {
  const paginationContainer = document.getElementById('menu-pagination');
  
  if (totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  
  let html = '';
  
  // Previous button
  if (currentPage > 1) {
    html += `<button onclick="changeMenuPage(${currentPage - 1})" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
              </button>`;
  }
  
  // Page numbers
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
  
  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }
  
  for (let i = startPage; i <= endPage; i++) {
    if (i === currentPage) {
      html += `<button class="px-3 py-2 bg-[#F7B32B] text-white rounded-lg font-medium">${i}</button>`;
    } else {
      html += `<button onclick="changeMenuPage(${i})" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">${i}</button>`;
    }
  }
  
  // Next button
  if (currentPage < totalPages) {
    html += `<button onclick="changeMenuPage(${currentPage + 1})" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
              </button>`;
  }
  
  paginationContainer.innerHTML = html;
  lucide.createIcons();
}

function changeMenuPage(page) {
  currentMenuPage = page;
  loadMenuItems(page);
}

function bindModalMenuListeners() {
  document.querySelectorAll(".quantity-btn").forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(`modal_item_${btn.dataset.id}`);
      let current = parseInt(input.value) || 0;
      if (btn.classList.contains("increase")) {
        current++;
      } else if (btn.classList.contains("decrease") && current > 0) {
        current--;
      }
      input.value = current;
      updateModalOrderSummary();
    });
  });
}

function updateModalOrderSummary() {
  let subtotal = 0;
  let itemCount = 0;
  
  document.querySelectorAll('.quantity-input').forEach(input => {
    const quantity = parseInt(input.value) || 0;
    if (quantity > 0) {
      const menuId = input.id.replace('modal_item_', '');
      const itemCard = input.closest('.menu-card');
      const priceText = itemCard.querySelector('span.text-lg').innerText;
      const price = parseFloat(priceText.replace(/[₱,]/g, '')) || 0;
      subtotal += price * quantity;
      itemCount += quantity;
    }
  });
  
  document.getElementById('selected-count').textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '') + ' selected';
  document.getElementById('modal-subtotal').textContent = formatMoney(subtotal);
  document.getElementById('selected-total').textContent = formatMoney(subtotal);
}

// Modal Functions
function showSearchBar() {
  document.getElementById('searchBarContainer').classList.remove('hidden');
  document.getElementById('checkinModal').classList.remove('modal-open');
}

function closeCheckinModal() {
  document.getElementById('checkinModal').classList.remove('modal-open');
}

async function openMenuModal() {
  const includeMenu = document.getElementById('include_menu').checked;
  if (!includeMenu) {
    // Clear selections if menu is not included
    selectedMenuItems = {};
    document.getElementById('selected-menu-items').classList.add('hidden');
    updateOrderSummary();
    return;
  }
  
  // Fetch menu data
  try {
    const response = await fetch('get_menu_data.php');
    const data = await response.json();
    
    if (data.success) {
      allMenuItems = data.data;
      
      // Store in global variable for filtering
      window.allMenuItems = allMenuItems;
      
      // Show modal and load items
      const menuModal = document.getElementById('menuModal');
      menuModal.showModal();
      
      // Initialize filter event listeners
      document.getElementById('menu-search').addEventListener('input', () => {
        currentMenuPage = 1;
        loadMenuItems(1);
      });
      
      document.getElementById('menu-category').addEventListener('change', () => {
        currentMenuPage = 1;
        loadMenuItems(1);
      });
      
      document.getElementById('menu-variant').addEventListener('change', () => {
        currentMenuPage = 1;
        loadMenuItems(1);
      });
      
      document.getElementById('menu-sort').addEventListener('change', () => {
        loadMenuItems(currentMenuPage);
      });
      
      // Load first page
      loadMenuItems(1);
    } else {
      alert('Error loading menu data: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
    // Fallback to using PHP data
    allMenuItems = <?php echo json_encode($menu_items); ?>;
    window.allMenuItems = allMenuItems;
    
    // Show modal with fallback data
    const menuModal = document.getElementById('menuModal');
    menuModal.showModal();
    
    // Initialize filter event listeners
    document.getElementById('menu-search').addEventListener('input', () => {
      currentMenuPage = 1;
      loadMenuItems(1);
    });
    
    document.getElementById('menu-category').addEventListener('change', () => {
      currentMenuPage = 1;
      loadMenuItems(1);
    });
    
    document.getElementById('menu-variant').addEventListener('change', () => {
      currentMenuPage = 1;
      loadMenuItems(1);
    });
    
    document.getElementById('menu-sort').addEventListener('change', () => {
      loadMenuItems(currentMenuPage);
    });
    
    loadMenuItems(1);
  }
}

function closeMenuModal() {
  const menuModal = document.getElementById('menuModal');
  menuModal.close();
  document.getElementById('include_menu').checked = false;
}

function saveMenuSelection() {
  selectedMenuItems = {};
  let hasSelection = false;
  
  document.querySelectorAll('.quantity-input').forEach(input => {
    const quantity = parseInt(input.value) || 0;
    if (quantity > 0) {
      const menuId = input.id.replace('modal_item_', '');
      const itemCard = input.closest('.menu-card');
      const name = itemCard.querySelector('h5').innerText;
      const priceText = itemCard.querySelector('span.text-lg').innerText;
      const price = parseFloat(priceText.replace(/[₱,]/g, '')) || 0;
      
      selectedMenuItems[menuId] = {
        name: name,
        price: price,
        quantity: quantity
      };
      hasSelection = true;
    }
  });
  
  if (hasSelection) {
    // Update selected items display
    const container = document.getElementById('selected-items-container');
    container.innerHTML = '';
    
    Object.values(selectedMenuItems).forEach(item => {
      if (item.quantity > 0) {
        const itemElement = document.createElement('div');
        itemElement.className = 'flex justify-between items-center p-3 bg-white rounded-lg border border-gray-200';
        itemElement.innerHTML = `
          <div>
            <p class="font-medium text-[#001f54]">${item.name}</p>
            <p class="text-xs text-gray-500">${item.quantity} x ${formatMoney(item.price)}</p>
          </div>
          <span class="font-bold text-[#F7B32B]">${formatMoney(item.price * item.quantity)}</span>
        `;
        container.appendChild(itemElement);
      }
    });
    
    document.getElementById('selected-menu-items').classList.remove('hidden');
  } else {
    document.getElementById('selected-menu-items').classList.add('hidden');
  }
  
  // Update the main order summary
  updateOrderSummary();
  
  // Close the modal
  const menuModal = document.getElementById('menuModal');
  menuModal.close();
}
    </script>
</body>
</html>