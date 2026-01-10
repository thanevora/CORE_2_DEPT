

<section id="about" class="hero min-h-screen flex items-center justify-center text-white relative overflow-hidden">
    <!-- Parallax Background Layers -->
    <div class="absolute inset-0 bg-black/40 z-10"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/70 z-10"></div>
    
    <?php
    $baseUrl = '../images/'; // leading slash ensures it starts from web root
    ?>
    
    <div class="parallax-bg absolute inset-0 bg-cover bg-center" 
         style="background-image: url('<?php echo $baseUrl; ?>resto.png')">
    </div>

    <!-- Hero Content -->
    <div class="text-center px-4 z-20 relative max-w-6xl mx-auto">
        <!-- 5 Star Icons -->
        <div class="flex justify-center mb-5 gap-5 animate-fade-in opacity-0" style="animation-delay: 2s;">
            <!-- Using Heroicons (SVG) -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.879 1.48 8.295L12 18.896l-7.416 4.587 1.48-8.295L.0 9.309l8.332-1.151z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.879 1.48 8.295L12 18.896l-7.416 4.587 1.48-8.295L.0 9.309l8.332-1.151z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.879 1.48 8.295L12 18.896l-7.416 4.587 1.48-8.295L.0 9.309l8.332-1.151z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.879 1.48 8.295L12 18.896l-7.416 4.587 1.48-8.295L.0 9.309l8.332-1.151z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 .587l3.668 7.571 8.332 1.151-6.064 5.879 1.48 8.295L12 18.896l-7.416 4.587 1.48-8.295L.0 9.309l8.332-1.151z"/>
            </svg>
        </div>
        
        <!-- Hotel Name -->
        <div class="relative inline-block">
            <h2 data-aos="zoom-in" data-aos-delay="100" class="text-3xl md:text-5xl font-bold animate-fade-in opacity-0" style="animation-delay: 2.4s;">
                <span class="text-[#F7B32B]">SOLIERA</span> 
                <span>RESTAURANT & HOTEL</span>
            </h2>
            
            <!-- Tagline -->
            <h3 data-aos="zoom-in-up" data-aos-delay="200" class="text-xl md:text-2xl font-semibold text-white tracking-wide italic drop-shadow-sm">
                Savor The Stay, Dine With Elegance
            </h3>

            <!-- Description -->
            <p data-aos="zoom-in-up" data-aos-delay="300" class="text-lg md:text-xl text-gray-200 max-w-2xl mx-auto mt-6 animate-fade-in opacity-0" style="animation-delay: 2.6s;">
                Welcome to Soliera — where luxury meets comfort. 
                Experience world-class hospitality, exquisite dining, and unforgettable stays in the heart of the city.
            </p>
            
            <!-- Underline Animation -->
            <div class="absolute -bottom-2 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-amber-400 to-transparent animate-underline" style="animation-delay: 2.8s;"></div>
        </div>
        
<!-- CTA Buttons -->
<div class="mt-12 animate-fade-in opacity-0" style="animation-delay: 3.2s;">
  <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center items-center">
    
    <a data-aos="slide-right" 
       href="#rooms" 
       class="btn btn-primary text-sm sm:text-base md:text-lg py-2 px-4 sm:py-3 sm:px-6 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl text-center">
      Explore Taste
    </a>

    <label for="reservation-option-modal" 
           class="btn btn-modern btn-outline text-sm sm:text-base md:text-lg py-2 px-4 sm:py-3 sm:px-6 text-[#F7B32B] border-[#F7B32B] hover:bg-[#F7B32B] hover:text-white transform hover:scale-105 transition-all duration-300 cursor-pointer text-center">
      Reserve Now
    </label>

  </div>
</div>



    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 animate-bounce opacity-0" style="animation-delay: 4s;">
        <div class="w-8 h-12 border-2 border-white rounded-full flex justify-center">
            <div class="w-1 h-3 bg-white mt-2 rounded-full animate-scroll-indicator"></div>
        </div>
    </div>

    <style>
        /* Typewriter Effect */
        .typewriter {
            overflow: hidden;
            white-space: nowrap;
            border-right: 3px solid white;
            animation: typing 1.5s steps(20, end) forwards, blink-caret 0.75s step-end 3;
        }
        
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
        
        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: white }
        }
        
        /* Fade-in Animation */
        .animate-fade-in {
            animation: fadeIn 1s ease-in forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        /* Underline Animation */
        .animate-underline {
            animation: underlineGrow 1s ease-out forwards;
            transform: scaleX(0);
            transform-origin: center;
        }
        
        @keyframes underlineGrow {
            to { transform: scaleX(1); }
        }
        
        /* Scroll Indicator */
        .animate-scroll-indicator {
            animation: scrollIndicator 2s infinite;
        }
        
        @keyframes scrollIndicator {
            0% { transform: translateY(0); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(12px); opacity: 0; }
        }
        
        /* Parallax Effect */
        .parallax-bg {
            will-change: transform;
            transition: transform 0.4s ease-out;
        }
    </style>

    <script>
        // Parallax Effect
        document.addEventListener('scroll', function() {
            const parallaxBg = document.querySelector('.parallax-bg');
            const scrollPosition = window.pageYOffset;
            parallaxBg.style.transform = `translateY(${scrollPosition * 0.3}px)`;
        });
        
        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // This ensures all elements with animate-fade-in class will animate
            const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-underline');
            animatedElements.forEach(el => {
                // Already handled by CSS animations with delays
            });
        });
    </script>
</section>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Reservation</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
 
<body class="bg-gray-50">
  
    <!-- Reservation Option Modal -->
    <input type="checkbox" id="reservation-option-modal" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box max-w-md p-0 overflow-hidden relative rounded-lg">
            <div class="modal-action m-0 absolute right-4 top-4 z-30">
                <label for="reservation-option-modal" class="btn btn-sm btn-circle bg-white hover:bg-gray-200">✕</label>
            </div>
            
            <div class="bg-white p-8 rounded-md">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Choose Reservation Type</h3>
                    <p class="text-gray-600 mt-2">Select the type of reservation you'd like to make</p>
                </div>
                
            <div class="grid grid-cols-1 gap-4">
    <a href="landing_page/table_reservation_modal.php" target="_blank" rel="noopener noreferrer"
       class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer card">
        <i data-lucide="table" class="w-12 h-12 text-blue-600 mb-3"></i>
        <h4 class="text-xl font-semibold text-gray-800">Table Reservation</h4>
        <p class="text-gray-600 text-center mt-2">Reserve a table for dining</p>
    </a>
    
    <a href="landing_page/event_reservation_modal.php" target="_blank" rel="noopener noreferrer"
       class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all duration-200 cursor-pointer card">
        <i data-lucide="calendar" class="w-12 h-12 text-green-600 mb-3"></i>
        <h4 class="text-xl font-semibold text-gray-800">Event Reservation</h4>
        <p class="text-gray-600 text-center mt-2">Book an event or special occasion</p>
    </a>
</div>

            </div>
        </div>
        
        <!-- Modal backdrop -->
        <label class="modal-backdrop" for="reservation-option-modal">Close</label>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Close modal when a link is clicked (after a short delay for visual feedback)
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    document.getElementById('reservation-option-modal').checked = false;
                }, 300);
            });
        });
    </script>
</body>
</html>
<script>
    // Initialize Lucide Icons
    lucide.createIcons();
    
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.reservation-tab');
        const forms = document.querySelectorAll('.reservation-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const formId = this.getAttribute('data-form');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show selected form
                forms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === formId) {
                        form.classList.add('active');
                    }
                });
            });
        });
        
        // Event form price calculation
        const eventTypeSelect = document.getElementById('event_type');
        const venueSelect = document.getElementById('venue');
        const packageSelect = document.getElementById('event_package');
        const numGuestsInput = document.getElementById('num_guests');

        const eventTypePrice = document.getElementById('event_type_price');
        const venuePrice = document.getElementById('venue_price');
        const packagePrice = document.getElementById('package_price');
        const guestCount = document.getElementById('guest_count');
        const totalAmount = document.getElementById('total_amount');
        const calculatedTotal = document.getElementById('calculated_total');

        function calculateTotal() {
            const eventTypeValue = parseFloat(eventTypeSelect.options[eventTypeSelect.selectedIndex]?.dataset.price || 0);
            const venueValue = parseFloat(venueSelect.options[venueSelect.selectedIndex]?.dataset.price || 0);
            const packageValue = parseFloat(packageSelect.options[packageSelect.selectedIndex]?.dataset.price || 0);
            const guests = parseInt(numGuestsInput.value || 0);

            const packageTotal = packageValue * guests;
            const total = eventTypeValue + venueValue + packageTotal;

            eventTypePrice.textContent = `₱${eventTypeValue.toFixed(2)}`;
            venuePrice.textContent = `₱${venueValue.toFixed(2)}`;
            packagePrice.textContent = `₱${packageTotal.toFixed(2)}`;
            guestCount.textContent = guests;
            totalAmount.textContent = `₱${total.toFixed(2)}`;
            calculatedTotal.value = total.toFixed(2);
        }

        if (eventTypeSelect) {
            eventTypeSelect.addEventListener('change', calculateTotal);
            venueSelect.addEventListener('change', calculateTotal);
            packageSelect.addEventListener('change', calculateTotal);
            numGuestsInput.addEventListener('input', calculateTotal);
        }

        // Load menu items for both forms
        loadMenuItems();
    });

</script>

<style>
    /* Tab selection styling */
    .reservation-tab {
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }
    
    .reservation-tab.active {
        background-color: #f8fafc;
        color: #3b82f6;
        border-bottom: 3px solid #3b82f6;
    }
    
    /* Form transition */
    .reservation-form {
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .reservation-form.active {
        display: block;
        opacity: 1;
    }
    
    /* Menu item styling */
    .menu-item {
        transition: all 0.2s ease;
    }
    
    .menu-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
  
    
    .quantity-input::-webkit-outer-spin-button,
    .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>