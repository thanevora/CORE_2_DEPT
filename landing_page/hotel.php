<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soliera Hotel - Luxury Accommodations</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        h1, h2, h3, h4, h5 {
            font-family: 'Playfair Display', serif;
        }
        
        .room-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .amenity-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b0000 0%, #a52a2a 100%);
            margin-bottom: 15px;
        }
        
        .bg-luxury {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .testimonial-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 4px solid #8b0000;
        }
        
        .room-image-container {
            height: 220px;
            position: relative;
            overflow: hidden;
        }
        
        .price-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #8b0000;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .hotel-room-card {
            height: 380px;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Hotel Experience Section -->
<section id="hotel" class="py-16 bg-white relative overflow-hidden">
    <div class="absolute inset-0 bg-black/5 z-0"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Hotel Intro -->
        <div class="text-center mb-12" data-aos="fade-up" data-aos-delay="100">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                <span class="text-[#8b0000]">Luxury</span> Hotel Experience
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Discover unparalleled comfort and elegance at Soliera's premium hotel accommodations
            </p>
        </div>

        <!-- Hotel Image + Details -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center mb-16">
            <!-- Hotel Image -->
            <div class="relative w-full h-[500px] rounded-xl overflow-hidden shadow-xl" data-aos="fade-right" data-aos-delay="200">
                <div class="absolute inset-0 bg-cover bg-center"
                     style="background-image: url('https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=800&q=80')">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h3 class="text-2xl font-bold">Soliera Hotel</h3>
                    <p class="text-amber-300">Five-Star Luxury Accommodations</p>
                </div>
            </div>

            <!-- Hotel Details -->
            <div data-aos="fade-left" data-aos-delay="300">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Unmatched Comfort</h3>
                    <p class="text-gray-600 mb-4">
                        Our award-winning hotel offers exquisite rooms and suites designed for the discerning traveler, featuring premium amenities and breathtaking views.
                    </p>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Check-in: 3:00 PM | Check-out: 12:00 PM</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">24-hour concierge and room service</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Complimentary breakfast for all guests</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Booking CTA -->
<div class="bg-amber-50 p-6 rounded-2xl border border-amber-200 text-center shadow-md">
  <h4 class="text-xl font-bold text-gray-800 mb-2">Book Your Stay</h4>
  <p class="text-gray-600 mb-4">Reserve your room for an unforgettable luxury experience.</p>
  <a href="https://soliera-hotelandrestaurant.store/" 
     target="_blank" 
     rel="noopener noreferrer"
     class="inline-block bg-[#8B0000] text-white px-6 py-3 rounded-lg font-semibold 
            hover:bg-[#6D0000] transition-colors duration-300 text-base shadow-lg">
    Book Now
  </a>
</div>

            </div>
        </div>

        <!-- Room Types -->
        <div class="mb-16" data-aos="fade-up" data-aos-delay="400">
            <h3 class="text-2xl font-bold text-center mb-8">Room <span class="text-[#8b0000]">Categories</span></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Room 1 -->
                <div class="relative group rounded-2xl shadow-lg overflow-hidden bg-white hover:shadow-2xl transition-all duration-500 hotel-room-card"
                     data-aos="fade-up" data-aos-delay="100"
                     style="background-image: url('<?php echo $baseUrl; ?>../images/3lux.png'); 
                            background-size: cover; background-position: center;">
                    <!-- Gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                    <!-- Price tag -->
                    <div class="price-tag">₱5,500/night</div>
                    <!-- Text content -->
                    <div class="absolute bottom-0 left-0 w-full p-6 text-white backdrop-blur-sm bg-white/10 rounded-t-2xl">
                        <h3 class="text-xl font-bold mb-2">Deluxe Room</h3>
                        <p class="text-sm text-gray-200 mb-4">Elegant room with king-sized bed and stunning city views</p>
                        <a href="#" class="text-white font-semibold text-sm flex items-center">
                            View Details <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>

                <!-- Room 2 -->
                <div class="relative group rounded-2xl shadow-lg overflow-hidden bg-white hover:shadow-2xl transition-all duration-500 hotel-room-card"
                     data-aos="fade-up" data-aos-delay="150"
                     style="background-image: url('../images/luxu.jpg'); 
                            background-size: cover; background-position: center;">
                    <!-- Gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                    <!-- Price tag -->
                    <div class="price-tag">₱8,900/night</div>
                    <!-- Text content -->
                    <div class="absolute bottom-0 left-0 w-full p-6 text-white backdrop-blur-sm bg-white/10 rounded-t-2xl">
                        <h3 class="text-xl font-bold mb-2">Luxury Suite</h3>
                        <p class="text-sm text-gray-200 mb-4">Spacious suite with separate living area and premium amenities</p>
                        <a href="#" class="text-white font-semibold text-sm flex items-center">
                            View Details <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>

                <!-- Room 3 -->
                <div class="relative group rounded-2xl shadow-lg overflow-hidden bg-white hover:shadow-2xl transition-all duration-500 hotel-room-card"
                     data-aos="fade-up" data-aos-delay="200"
                     style="background-image: url('../images/standard.png'); 
                            background-size: cover; background-position: center;">
                    <!-- Gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                    <!-- Price tag -->
                    <div class="price-tag">₱3,800/night</div>
                    <!-- Text content -->
                    <div class="absolute bottom-0 left-0 w-full p-6 text-white backdrop-blur-sm bg-white/10 rounded-t-2xl">
                        <h3 class="text-xl font-bold mb-2">Standard Room</h3>
                        <p class="text-sm text-gray-200 mb-4">Comfortable room with all essential amenities for a pleasant stay</p>
                        <a href="#" class="text-white font-semibold text-sm flex items-center">
                            View Details <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

       
    </div>
</section>

<!-- Special Offers Banner -->
<section class="bg-luxury py-12 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-3" data-aos="fade-up">Special Offers</h2>
        <p class="text-lg mb-6 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Enjoy exclusive deals and packages for your next stay at Soliera Hotel
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white/10 backdrop-blur-sm p-5 rounded-lg" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-lg font-bold mb-2">Weekend Getaway</h3>
                <p class="mb-3 text-sm">Stay 2 nights and get 15% off</p>
                <a href="#" class="inline-block bg-white text-[#8b0000] px-3 py-1 rounded text-xs font-semibold">Learn More</a>
            </div>
            <div class="bg-white/10 backdrop-blur-sm p-5 rounded-lg" data-aos="fade-up" data-aos-delay="300">
                <h3 class="text-lg font-bold mb-2">Honeymoon Package</h3>
                <p class="mb-3 text-sm">Complimentary spa treatment and champagne</p>
                <a href="#" class="inline-block bg-white text-[#8b0000] px-3 py-1 rounded text-xs font-semibold">Learn More</a>
            </div>
            <div class="bg-white/10 backdrop-blur-sm p-5 rounded-lg" data-aos="fade-up" data-aos-delay="400">
                <h3 class="text-lg font-bold mb-2">Business Travel</h3>
                <p class="mb-3 text-sm">Free airport transfers and meeting room access</p>
                <a href="#" class="inline-block bg-white text-[#8b0000] px-3 py-1 rounded text-xs font-semibold">Learn More</a>
            </div>
        </div>
    </div>

    
</section>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
    });
</script>

</body>
</html>