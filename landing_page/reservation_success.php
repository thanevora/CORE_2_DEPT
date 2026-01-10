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
    <title>Reservation Successful - Soliera Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
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
                        display: ['Playfair Display', 'serif'],
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            position: relative;
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/hotel3.jpg') no-repeat center center / cover;
            filter: blur(8px);
            z-index: -10;
        }
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #F7B32B;
            opacity: 0.7;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>
<body class="font-sans min-h-screen flex items-center justify-center p-4 bg-primary/40">
    <!-- Confetti container -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0" id="confetti-container"></div>
    
    <!-- Animated background elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0 opacity-20">
        <div class="absolute top-0 left-0 w-full flex justify-between p-10">
            <i class="fas fa-utensils text-6xl text-accent"></i>
            <i class="fas fa-wine-glass-alt text-6xl text-accent"></i>
        </div>
    </div>
    
    <div class="relative z-10 w-full max-w-4xl">
        <!-- Mobile layout (vertical) -->
        <div class="lg:hidden card bg-primary text-white rounded-box shadow-2xl border border-accent transform transition-all duration-500 fade-in">
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-primary to-secondary p-6 text-center border-b border-accent">
                <!-- Logo -->
                <div class="flex justify-center mb-4">
                    <img src="<?php echo $logo_url; ?>" alt="Soliera Restaurant Logo" class="h-16 object-contain">
                </div>
                
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl"><?php echo $icon; ?></span>
                </div>
                <h1 class="font-display text-3xl text-accent font-bold mb-2">Reservation Confirmed!</h1>
                <p class="opacity-90">Your <?php echo $title; ?> at Soliera Restaurant is confirmed</p>
            </div>
            
            <!-- Content -->
            <div class="card-body p-6">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-success/20 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-center mb-6">We've sent a confirmation email with all the details. Please check your inbox.</p>
                
                <!-- What's Next Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-accent mb-4 font-display">What's Next?</h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-accent text-primary rounded-full w-8 h-8 flex items-center justify-center mr-4 flex-shrink-0 font-bold">1</div>
                            <div>
                                <p class="font-medium text-accent">Check Your Email</p>
                                <p class="text-sm opacity-80">Look for our confirmation message in your inbox</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-accent text-primary rounded-full w-8 h-8 flex items-center justify-center mr-4 flex-shrink-0 font-bold">2</div>
                            <div>
                                <p class="font-medium text-accent">Save the Date</p>
                                <p class="text-sm opacity-80">Add this reservation to your calendar</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-accent text-primary rounded-full w-8 h-8 flex items-center justify-center mr-4 flex-shrink-0 font-bold">3</div>
                            <div>
                                <p class="font-medium text-accent">Prepare for Your Visit</p>
                                <p class="text-sm opacity-80">Review any special instructions we've sent</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-base-200 rounded-box p-4 mb-6">
                    <div class="flex items-center justify-center text-sm">
                        <i class="fas fa-envelope text-accent mr-2"></i>
                        <span>A confirmation email is on its way</span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <a href="../index.php" class="btn btn-block bg-accent hover:bg-accent/90 border-none text-primary rounded-btn py-3 font-bold">
                        <i class="fas fa-home mr-2"></i> Return to Home page
                    </a>
                    <?php if ($reservation_type == 'table'): ?>
                    <a href="table_reservation_modal.php" class="btn btn-outline btn-block border-accent text-accent hover:bg-accent hover:border-accent hover:text-primary rounded-btn py-3 font-bold">
                        <i class="fas fa-calendar-plus mr-2"></i> Book Another Table
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-base-200 p-4 text-center text-sm rounded-b-box">
                <p>Need help? Contact us at <span class="text-accent">reservations@soliera.com</span></p>
            </div>
        </div>
        
        <!-- Desktop landscape layout (horizontal) -->
        <div class="hidden lg:flex card card-side bg-primary text-white rounded-box shadow-2xl border border-accent transform transition-all duration-500 fade-in h-auto">
            <!-- Left side - Confirmation details -->
            <div class="flex flex-col w-2/5 bg-gradient-to-b from-primary to-secondary p-8 justify-between">
                <div>
                    <!-- Logo -->
                    <div class="flex justify-center mb-6">
                        <img src="<?php echo $logo_url; ?>" alt="Soliera Restaurant Logo" class="h-16 object-contain">
                    </div>
                    
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-4xl"><?php echo $icon; ?></span>
                    </div>
                    <h1 class="font-display text-4xl text-accent font-bold mb-2 text-center">Reservation Confirmed!</h1>
                    <p class="opacity-90 text-center mb-8">Your <?php echo $title; ?> at Soliera Restaurant is confirmed</p>
                    
                    <div class="flex justify-center mb-8">
                        <div class="w-20 h-20 bg-success/20 rounded-full flex items-center justify-center mx-auto">
                            <svg class="w-10 h-10 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-base-200/20 rounded-box p-4">
                    <div class="flex items-center justify-center text-sm">
                        <i class="fas fa-envelope text-accent mr-2"></i>
                        <span>A confirmation email is on its way</span>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Next steps and actions -->
            <div class="flex flex-col w-3/5 p-8 justify-between">
                <div>
                    <p class="text-center mb-8">We've sent a confirmation email with all the details. Please check your inbox.</p>
                    
                    <!-- What's Next Section -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-accent mb-6 font-display text-center">What's Next?</h2>
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="bg-accent text-primary rounded-full w-10 h-10 flex items-center justify-center mr-4 flex-shrink-0 font-bold text-lg">1</div>
                                <div>
                                    <p class="font-medium text-accent text-lg">Check Your Email</p>
                                    <p class="opacity-80">Look for our confirmation message in your inbox</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-accent text-primary rounded-full w-10 h-10 flex items-center justify-center mr-4 flex-shrink-0 font-bold text-lg">2</div>
                                <div>
                                    <p class="font-medium text-accent text-lg">Save the Date</p>
                                    <p class="opacity-80">Add this reservation to your calendar</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-accent text-primary rounded-full w-10 h-10 flex items-center justify-center mr-4 flex-shrink-0 font-bold text-lg">3</div>
                                <div>
                                    <p class="font-medium text-accent text-lg">Prepare for Your Visit</p>
                                    <p class="opacity-80">Review any special instructions we've sent</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex space-x-4">
                        <a href="../index.php" class="btn flex-1 bg-accent hover:bg-accent/90 border-none text-primary rounded-btn py-3 font-bold">
                            <i class="fas fa-home mr-2"></i> Return Home
                        </a>
                        <?php if ($reservation_type == 'table'): ?>
                        <a href="table_reservation_modal.php" class="btn btn-outline flex-1 border-accent text-accent hover:bg-accent hover:border-accent hover:text-primary rounded-btn py-3 font-bold">
                            <i class="fas fa-calendar-plus mr-2"></i> Book Another
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-6 text-sm text-white">
            <p>© <?php echo date('Y'); ?> Soliera Restaurant. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Simple confetti animation
        document.addEventListener('DOMContentLoaded', function() {
            const colors = ['#F7B32B', '#001f54', '#ffffff', '#00308a'];
            const container = document.getElementById('confetti-container');
            
            for (let i = 0; i < 30; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = confetti.style.width;
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confetti.style.opacity = '0';
                container.appendChild(confetti);
                
                // Animate confetti
                setTimeout(() => {
                    confetti.style.transition = 'all 1s ease-out';
                    confetti.style.opacity = '0.7';
                    confetti.style.transform = `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`;
                }, 100);
                
                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }
        });
    </script>
</body>
</html>