<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Reservation | Soliera Hotel & Restaurant</title>
  <!-- Tailwind CSS with DaisyUI -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1A2C5B',
            secondary: '#F7B32B',
            background: '#F9FAFC',
          }
        }
      }
    }
  </script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    #terms_modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 100;
    }
    .scroll-indicator {
      position: sticky;
      bottom: 0;
      background: linear-gradient(transparent, white);
      padding: 40px 0 20px;
      text-align: center;
      margin-top: -60px;
      pointer-events: none;
    }
    .checkbox-container {
      background-color: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
      position: sticky;
      bottom: 0;
      z-index: 10;
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
  <!-- Header -->
  
     <!-- Page Title -->
  <div class="bg-[#001f54] border-2 border-[#F7B32B] rounded-lg max-w-xl mx-auto text-center p-6 mb-10 shadow-lg">
  <h2 class="text-3xl font-bold text-[#F7B32B] mb-2">Event Reservation</h2>
  <p class="text-gray-300 text-sm">Book your special event with us. Fill out the form below to reserve your date.</p>
</div>
    
   <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <!-- Left Column: Reservation Form -->
  <div class="lg:col-span-2">
    <div class="bg-[#001f54] border-2 border-[#F7B32B] rounded-lg card bg-base-100 shadow-xl mb-10">
      <div class="card-body p-6 md:p-8">
        <!-- Event Reservation Form -->
        <form id="event-form" action="../M6/sub-modules/add_event_main.php" method="POST">
          <input type="hidden" name="reservation_type" value="event">

          <!-- Customer Information -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
              <i class='bx bx-user text-[#F7B32B] text-xl'></i>
              Customer Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="customer_name" class="block text-sm font-medium text-[#F7B32B]  mb-1">Customer Name*</label>
                <input type="text" id="customer_name" name="customer_name" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
              <div>
                <label for="customer_email" class="block text-sm font-medium text-[#F7B32B] mb-1">Customer Email*</label>
                <input type="email" id="customer_email" name="customer_email" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
              <div>
                <label for="customer_phone" class="block text-sm font-medium text-[#F7B32B] mb-1">Customer Phone*</label>
                <input type="text" id="customer_phone" name="customer_phone" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
              <div>
                <label for="event_name" class="block text-sm font-medium text-[#F7B32B] mb-1">Event Name*</label>
                <input type="text" id="event_name" name="event_name" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
            </div>
          </div>

          <!-- Event Details -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
              <i class='bx bx-calendar text-[#F7B32B] text-xl'></i>
              Event Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="event_type" class="block text-sm font-medium text-[#F7B32B] mb-1">Event Type*</label>
                <select id="event_type" name="event_type" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
                  <option value="" disabled selected>Select event type</option>
                  <option value="Wedding" data-price="500">Wedding (₱500)</option>
                  <option value="Birthday" data-price="300">Birthday (₱300)</option>
                  <option value="Corporate" data-price="400">Corporate (₱400)</option>
                  <option value="Conference" data-price="600">Conference (₱600)</option>
                </select>
              </div>
              <div>
                <label for="venue" class="block text-sm font-medium text-[#F7B32B] mb-1">Venue*</label>
                <select id="venue" name="venue" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
                  <option value="" disabled selected>Select venue</option>
                  <option value="Conference Hall A" data-price="600">Conference Hall A (₱600)</option>
                  <option value="Executive Room" data-price="400">Executive Room (₱400)</option>
                </select>
              </div>
              <div>
                <label for="event_package" class="block text-sm font-medium text-[#F7B32B] mb-1">Event Package*</label>
                <select id="event_package" name="event_package" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
                  <option value="" disabled selected>Select package</option>
                  <option value="Basic" data-price="50">Basic Package (₱50/guest)</option>
                  <option value="Silver" data-price="50">Silver Package (₱100/guest)</option>
                  <option value="Gold" data-price="50">Gold Package (₱100/guest)</option>
                  <option value="Premium" data-price="100">Premium Package (₱150/guest)</option>
                </select>
              </div>
              <div>
                <label for="num_guests" class="block text-sm font-medium text-[#F7B32B] mb-1">Number of Guests*</label>
                <input type="number" id="num_guests" name="num_guests" min="1" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
              <div>
                <label for="event_date" class="block text-sm font-medium text-[#F7B32B] mb-1">Event Date*</label>
                <input type="date" id="event_date" name="event_date" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
              <div>
                <label for="event_time" class="block text-sm font-medium text-[#F7B32B] mb-1">Event Time*</label>
                <input type="time" id="event_time" name="event_time" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-2 text-black focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" required>
              </div>
            </div>
          </div>

          <!-- Special Requests -->
          <div class="mb-6">
            <h3 class="text-xl font-semibold text-[#F7B32B] mb-4 flex items-center gap-2">
              <i class='bx bx-message-dots text-[#F7B32B] text-xl'></i>
              Special Requests
            </h3>
            <textarea id="special_requests" name="special_requests" class="w-full bg-white border border-[#F7B32B] rounded-lg px-4 py-3 text-black h-32 focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" placeholder="Any dietary restrictions, setup preferences, or additional notes..."></textarea>
          </div>

          <!-- Info -->
          <div class="alert alert-info mb-6">
            <i data-lucide="info" class="w-5 h-5 mr-2"></i>
            <span>Total will be calculated automatically based on your selections</span>
          </div>

          <!-- Terms & Conditions -->
          <div class="form-control mb-6">
            <label class="label justify-start cursor-pointer">
              <input type="checkbox" id="terms_checkbox" class="checkbox checkbox-primary mr-3" required />
              <span class="label-text">I agree to the <a href="#terms_modal" class="link link-primary" onclick="document.getElementById('terms_modal').showModal()">Terms & Conditions</a></span>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex flex-col md:flex-row justify-end gap-4">
            <button type="reset" class="btn btn-outline btn-primary mb-4 md:mb-0">Reset Form</button>
            <button type="submit" class="btn bg-secondary border-secondary text-white hover:bg-secondary/90 hover:border-secondary/90">
              <i data-lucide="save" class="w-5 h-5 mr-2"></i> Save Reservation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Right Column: Pricing Summary -->
<div class="lg:col-span-1">
  <div class="bg-[#001f54] text-white border-2 border-[#F7B32B] p-6 rounded-lg shadow-md sticky top-6">
    <h2 class="flex items-center gap-2 text-xl font-semibold text-[#F7B32B] mb-4">
      <i class='bx bx-cart text-[#F7B32B] text-2xl'></i>
      Reservation Bill Summary
    </h2>

    <div class="flex justify-between mb-2">
      <span>Event Type:</span>
      <span id="event_type_price">₱0.00</span>
    </div>
    <div class="flex justify-between mb-2">
      <span>Venue:</span>
      <span id="venue_price">₱0.00</span>
    </div>
    <div class="flex justify-between mb-2">
      <span>Package (<span id="guest_count">0</span> guests):</span>
      <span id="package_price">₱0.00</span>
    </div>

    <!-- VAT (highlight only) -->
    <div class="flex justify-between mb-2 text-[#F7B32B] font-medium">
      <span>VAT (12% of total):</span>
      <span id="vat_display">₱0.00</span>
    </div>

    <!-- Service charge (added to bill) -->
    <div class="flex justify-between mb-2">
      <span>Service Charge (8%):</span>
      <span id="service_charge">₱0.00</span>
    </div>

    <div class="flex justify-between border-t-2 border-[#F7B32B] pt-2 mt-2 font-bold text-lg text-[#F7B32B]">
      <span>Total Amount:</span>
      <span id="total_amount">₱0.00</span>
    </div>
  </div>
</div>
</div>


<!-- Hidden field for backend processing -->
<input type="hidden" name="calculated_total" id="calculated_total" value="0" />
  <!-- Terms & Conditions Modal -->
  <dialog id="terms_modal" class="modal">
    <div class="bg-white text-black modal-box max-w-5xl max-h-[80vh] overflow-y-auto p-0">
      <div class="sticky top-0 bg-white p-6 pb-4 border-b z-10">
        <h3 class="font-bold text-2xl text-primary mb-2">Soliera Hotel & Restaurant - Terms & Conditions</h3>
        <p class="text-gray-600">Please read and scroll to the bottom to accept</p>
      </div>
      
      <div class="prose max-w-none p-6 pt-4">
        <h4 class="text-lg font-semibold">Reservation Terms & Conditions</h4>
        <p class="mb-4">Soliera Hotel & Restaurant is committed to providing refined hospitality and a seamless dining experience. By placing a reservation, you acknowledge and agree to the following policies:</p>
        
        <h5 class="font-semibold mt-4">1. Reservation Policy</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>All reservations are subject to availability and confirmation by Soliera Hotel & Restaurant.</li>
          <li>A formal confirmation will be issued through email, SMS, or in-app notification once the reservation is secured.</li>
          <li>Guests are expected to arrive promptly. A grace period of fifteen (15) minutes shall be observed, after which the reserved table may be released to waiting guests.</li>
          <li>For large parties, private functions, or special events, a downpayment is required to guarantee the booking.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">2. Modification and Cancellation</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Once confirmed, all reservations are deemed final and may not be canceled.</li>
          <li>Any downpayment made shall be strictly non-refundable and non-transferable in accordance with the agreed terms.</li>
          <li>Failure to honor the booking ("No-Show") shall result in automatic forfeiture of both the reservation and downpayment.</li>
          <li>Soliera Hotel & Restaurant reserves the right to modify, reschedule, or cancel reservations solely in instances of force majeure, emergencies, or operational considerations.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">3. Guest Responsibilities</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Guests are expected to provide complete and accurate information at the time of reservation.</li>
          <li>Multiple fraudulent or misleading reservations are prohibited and may result in denial of service.</li>
          <li>Guests are responsible for advising the restaurant of any allergies, dietary restrictions, or special requirements in advance.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">4. Fees and Payments</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Reservations requiring a downpayment shall only be honored upon verified payment.</li>
          <li>Downpayments are irrevocably non-refundable and non-transferable.</li>
          <li>All electronic transactions comply with the provisions of Republic Act No. 8792 – Electronic Commerce Act of 2000, ensuring the validity and enforceability of payments.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">5. Privacy and Data Protection</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Personal information provided by guests shall be managed in accordance with the Data Privacy Act of 2012 (RA 10173).</li>
          <li>Collected information shall only be utilized for reservation, billing, service enhancement, and lawful reporting purposes.</li>
          <li>Guests may formally request access, correction, or deletion of their data, subject to applicable laws and operational requirements.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">6. Hotel & Restaurant Rights</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Management reserves the right to refuse service to individuals demonstrating disruptive conduct, policy violations, or health and safety risks.</li>
          <li>Management retains discretion in adjusting seating arrangements to ensure operational efficiency and guest comfort.</li>
          <li>Discounts, promotions, and privileges shall be honored only under terms consistent with Republic Act No. 7394 – Consumer Act of the Philippines.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">7. Limitation of Liability</h5>
        <ol class="list-decimal pl-5 mb-4">
          <li>Soliera Hotel & Restaurant shall not be held liable for any loss resulting from inaccurate or incomplete information provided by guests.</li>
          <li>The Hotel & Restaurant shall not be responsible for disruptions caused by events beyond its reasonable control, including force majeure, governmental directives, or natural calamities.</li>
        </ol>
        
        <h5 class="font-semibold mt-4">8. Governing Law</h5>
        <p class="mb-4">This Agreement shall be governed and interpreted in accordance with the laws of the Republic of the Philippines, specifically:</p>
        <ul class="list-disc pl-5 mb-4">
          <li>RA 7394 – Consumer Act of the Philippines</li>
          <li>RA 10173 – Data Privacy Act of 2012</li>
          <li>RA 8792 – Electronic Commerce Act of 2000</li>
        </ul>
        
        <h5 class="font-semibold mt-4">Acknowledgment</h5>
        <p class="mb-4">By confirming a reservation, the guest expressly affirms that they have read, understood, and agreed to these Terms and Conditions, including the non-cancellation and non-refundable downpayment policy.</p>
      </div>
      
      <div class="scroll-indicator" id="scroll_indicator">
        <div class="animate-bounce text-primary">
          <i data-lucide="chevron-down" class="w-6 h-6 mx-auto"></i>
          <p class="mt-2 font-semibold">Scroll down to continue</p>
        </div>
      </div>
      
      <div class="checkbox-container">
        <div class="form-control">
          <label class="label justify-start cursor-pointer">
            <input type="checkbox" id="modal_terms_checkbox" class="checkbox checkbox-primary mr-3" />
            <span class="label-text font-semibold">I have read and agree to the Terms & Conditions</span>
          </label>
        </div>
        <button id="accept_terms_btn" class="btn bg-secondary border-secondary text-white hover:bg-secondary/90 w-[500px] mt-3 mx-auto block" disabled>
          Accept & Continue
        </button>
      </div>
    </div>
  </dialog>



<script>
  document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    const eventTypeSelect = document.getElementById('event_type');
    const venueSelect = document.getElementById('venue');
    const packageSelect = document.getElementById('event_package');
    const numGuestsInput = document.getElementById('num_guests');

    const eventTypePrice = document.getElementById('event_type_price');
    const venuePrice = document.getElementById('venue_price');
    const packagePrice = document.getElementById('package_price');
    const guestCount = document.getElementById('guest_count');
    const vatDisplay = document.getElementById('vat_display');
    const serviceChargeEl = document.getElementById('service_charge');
    const totalAmount = document.getElementById('total_amount');
    const calculatedTotal = document.getElementById('calculated_total');

    function calculateTotal() {
      const eventTypeValue = parseFloat(eventTypeSelect.options[eventTypeSelect.selectedIndex]?.dataset.price || 0);
      const venueValue = parseFloat(venueSelect.options[venueSelect.selectedIndex]?.dataset.price || 0);
      const packageValue = parseFloat(packageSelect.options[packageSelect.selectedIndex]?.dataset.price || 0);
      const guests = parseInt(numGuestsInput.value || 0);

      const packageTotal = packageValue * guests;
      const baseTotal = eventTypeValue + venueValue + packageTotal;

      // Compute VAT (just display)
      const vat = baseTotal * 0.12;

      // Compute Service Charge (added)
      const serviceCharge = baseTotal * 0.08;

      // Final total (base + service charge, VAT excluded from addition)
      const finalTotal = baseTotal + serviceCharge;

      // Update UI
      eventTypePrice.textContent = `₱${eventTypeValue.toFixed(2)}`;
      venuePrice.textContent = `₱${venueValue.toFixed(2)}`;
      packagePrice.textContent = `₱${packageTotal.toFixed(2)}`;
      guestCount.textContent = guests;

      vatDisplay.textContent = `₱${vat.toFixed(2)}`;
      serviceChargeEl.textContent = `₱${serviceCharge.toFixed(2)}`;
      totalAmount.textContent = `₱${finalTotal.toFixed(2)}`;
      calculatedTotal.value = finalTotal.toFixed(2);
    }

    if (eventTypeSelect) {
      eventTypeSelect.addEventListener('change', calculateTotal);
      venueSelect.addEventListener('change', calculateTotal);
      packageSelect.addEventListener('change', calculateTotal);
      numGuestsInput.addEventListener('input', calculateTotal);
      calculateTotal();
    }

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('event_date').setAttribute('min', today);
  });
</script>
</body>
</html>