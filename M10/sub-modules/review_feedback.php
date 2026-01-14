<?php
session_start();
include("../../main_connection.php");

// Set timezone to Manila/Beijing (UTC+8)
date_default_timezone_set('Asia/Manila');

// Database connection for reviews
$db_name = "rest_m10_comments_review";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn_reviews = $connections[$db_name];

// Get order code from URL
$order_code = $_GET['order_code'] ?? '';

if (empty($order_code)) {
    die("❌ No order code provided. Please scan the QR code from your receipt.");
}

// Fetch order details from database
$order_details = null;
$order_items = [];

try {
    $sql = "SELECT * FROM customer_reviews WHERE order_code = ? LIMIT 1";
    $stmt = $conn_reviews->prepare($sql);
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Decode order items
        if (!empty($order_details['order_items'])) {
            $order_items = json_decode($order_details['order_items'], true);
            if (!is_array($order_items)) {
                $order_items = [];
            }
        }
    } else {
        // Try to get from POS database as fallback
        $conn_pos = $connections["rest_m4_pos"] ?? null;
        if ($conn_pos) {
            $sql_pos = "SELECT * FROM orders WHERE order_code = ? LIMIT 1";
            $stmt_pos = $conn_pos->prepare($sql_pos);
            $stmt_pos->bind_param("s", $order_code);
            $stmt_pos->execute();
            $result_pos = $stmt_pos->get_result();
            
            if ($result_pos->num_rows > 0) {
                $order_details = $result_pos->fetch_assoc();
                $order_details['table_name'] = 'Unknown';
                $order_details['order_items'] = '[]';
                $order_items = [];
            }
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
}

if (!$order_details) {
    die("❌ Order not found. Please check the order code and try again.");
}

// Handle form submission
$submission_success = false;
$submission_error = '';
$rating = 0;
$review_text = '';
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $submission_error = "Please select a rating between 1 and 5 stars.";
    } elseif (strlen($review_text) > 500) {
        $submission_error = "Review text is too long (maximum 500 characters).";
    } elseif (strlen($feedback) > 1000) {
        $submission_error = "Feedback is too long (maximum 1000 characters).";
    } else {
        try {
            // Update review record
            $submitted_at = date("Y-m-d H:i:s");
            $status = "submitted";
            
            $sql_update = "UPDATE customer_reviews 
                          SET rating = ?, 
                              review_text = ?, 
                              feedback = ?, 
                              status = ?, 
                              submitted_at = ?
                          WHERE order_code = ?";
            
            $stmt_update = $conn_reviews->prepare($sql_update);
            $stmt_update->bind_param(
                "isssss",
                $rating,
                $review_text,
                $feedback,
                $status,
                $submitted_at,
                $order_code
            );
            
            if ($stmt_update->execute()) {
                $submission_success = true;
                
                // Insert notification for admin
                $notification_title = "New Customer Review";
                $notification_message = "Customer " . htmlspecialchars($order_details['customer_name']) . 
                                      " submitted a " . $rating . "-star review for Order #" . $order_code;
                
                // Check if notification table exists in reviews database
                $checkNotifTable = $conn_reviews->query("SHOW TABLES LIKE 'notifications'");
                if ($checkNotifTable && $checkNotifTable->num_rows > 0) {
                    $notifQuery = $conn_reviews->prepare("
                        INSERT INTO notifications 
                        (title, message, status, date_sent, module)
                        VALUES (?, ?, 'Unread', NOW(), 'Customer Reviews')
                    ");
                    
                    if ($notifQuery) {
                        $notifQuery->bind_param("ss", $notification_title, $notification_message);
                        $notifQuery->execute();
                        $notifQuery->close();
                    }
                }
                
                if ($checkNotifTable) {
                    $checkNotifTable->free();
                }
            } else {
                $submission_error = "Failed to save your review. Please try again.";
            }
            
            $stmt_update->close();
        } catch (Exception $e) {
            $submission_error = "Error saving review: " . $e->getMessage();
            error_log("Review submission error: " . $e->getMessage());
        }
    }
}

// Check if already submitted
$already_submitted = !empty($order_details['submitted_at']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience - Soliera Hotel & Restaurant</title>
    
    <!-- Tailwind CSS & DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        
        /* Custom gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Rating stars colors */
        .rating-stars .star {
            color: #d1d5db; /* gray-300 */
            transition: all 0.3s ease;
        }
        .rating-stars .star:hover,
        .rating-stars .star.selected {
            color: #fbbf24; /* amber-400 */
        }
        .rating-stars .star:hover ~ .star,
        .rating-stars .star.selected ~ .star {
            color: #fbbf24;
        }
        
        /* Custom animation for success */
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .animate-checkmark {
            animation: checkmark 0.6s ease-out;
        }
        
        /* Character counter colors */
        .char-count.warning { color: #f59e0b; }
        .char-count.danger { color: #ef4444; }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="gradient-bg min-h-screen flex flex-col items-center justify-center p-4 md:p-6">
    <div class="container max-w-4xl mx-auto">
        <!-- Restaurant Logo -->
        <div class="flex justify-center mb-8">
            <img src="../../images/soliera_S.png" alt="Soliera Hotel & Restaurant" class="w-40 h-40 md:w-48 md:h-48 object-contain">
        </div>
        
        <!-- Header -->
        <div class="text-center text-white mb-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-3">Share Your Experience</h1>
            <p class="text-lg opacity-90">Your feedback helps us improve our service</p>
        </div>
        
        <!-- Main Card -->
        <div class="card bg-base-100 shadow-2xl rounded-2xl overflow-hidden mb-8">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white p-6 md:p-8 text-center">
                <h2 class="text-2xl md:text-3xl font-bold mb-2">Order #<?php echo htmlspecialchars($order_code); ?></h2>
                <p class="text-lg">Thank you for dining with us!</p>
            </div>
            
            <!-- Card Body -->
            <div class="p-6 md:p-8">
                <!-- Order Information -->
                <div class="bg-base-200 rounded-xl p-5 mb-8 border border-base-300">
                    <h3 class="text-xl font-bold mb-4 pb-2 border-b-2 border-amber-500">Your Order Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="space-y-1">
                            <div class="text-sm font-medium text-base-content/70 flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                Customer Name
                            </div>
                            <div class="font-semibold text-lg"><?php echo htmlspecialchars($order_details['customer_name'] ?? 'Guest'); ?></div>
                        </div>
                        
                        <div class="space-y-1">
                            <div class="text-sm font-medium text-base-content/70 flex items-center gap-2">
                                <i data-lucide="table" class="w-4 h-4"></i>
                                Table Number
                            </div>
                            <div class="font-semibold text-lg"><?php echo htmlspecialchars($order_details['table_name'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="space-y-1">
                            <div class="text-sm font-medium text-base-content/70 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                Order Date
                            </div>
                            <div class="font-semibold text-lg"><?php echo date('F j, Y', strtotime($order_details['created_at'] ?? 'now')); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($order_items)): ?>
                    <div class="mt-6">
                        <h4 class="text-lg font-bold mb-3">Order Items</h4>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr class="bg-base-300">
                                        <th class="font-bold">Item</th>
                                        <th class="font-bold">Quantity</th>
                                        <th class="font-bold">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="font-medium"><?php echo htmlspecialchars($item['name'] ?? $item['item_name'] ?? 'Item'); ?></td>
                                        <td><?php echo intval($item['quantity'] ?? $item['qty'] ?? 1); ?></td>
                                        <td class="font-bold">₱<?php echo number_format(floatval($item['price'] ?? $item['unit_price'] ?? 0), 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($submission_success): ?>
                <!-- Success Message -->
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 text-green-600 rounded-full mb-6 animate-checkmark">
                        <i data-lucide="check-circle" class="w-16 h-16"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Thank You for Your Feedback!</h3>
                    <p class="text-base-content/70 mb-8 max-w-2xl mx-auto">
                        Your review has been successfully submitted. We appreciate you taking the time to share your experience with us.
                    </p>
                    
                    <div class="bg-gradient-to-r from-base-200 to-base-300 rounded-xl p-6 mb-8 border-2 border-amber-500">
                        <h4 class="text-xl font-bold mb-3 flex items-center gap-2">
                            <i data-lucide="award" class="w-6 h-6"></i>
                            Your Feedback Matters
                        </h4>
                        <p class="text-base-content/80">
                            We value every review as it helps us understand what we're doing well and where we can improve. 
                            Our team will review your feedback and use it to enhance our service for future guests.
                        </p>
                    </div>
                    
                    <button onclick="window.location.href='<?php echo $_SERVER['HTTP_REFERER'] ?? '/'; ?>'" 
                            class="btn btn-primary btn-lg w-full max-w-xs">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        Return to Home
                    </button>
                </div>
                
                <?php elseif ($already_submitted): ?>
                <!-- Already Submitted -->
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-100 text-blue-600 rounded-full mb-6">
                        <i data-lucide="file-check" class="w-16 h-16"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Feedback Already Submitted</h3>
                    <p class="text-base-content/70 mb-8">Thank you! You have already submitted your feedback for this order.</p>
                    
                    <div class="bg-gradient-to-r from-base-200 to-base-300 rounded-xl p-6 mb-8 border-2 border-amber-500">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="rating rating-lg rating-half">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" 
                                           name="rating-display" 
                                           class="mask mask-star-2 bg-amber-500" 
                                           <?php echo ($i <= ($order_details['rating'] ?? 0)) ? 'checked' : 'disabled'; ?> />
                                <?php endfor; ?>
                            </div>
                            <span class="text-xl font-bold"><?php echo $order_details['rating'] ?? 0; ?> stars</span>
                        </div>
                        
                        <div class="text-left space-y-4">
                            <div>
                                <h5 class="font-bold mb-2 flex items-center gap-2">
                                    <i data-lucide="message-square" class="w-5 h-5"></i>
                                    Your Review:
                                </h5>
                                <p class="text-base-content/80 bg-base-100 p-4 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($order_details['review_text'] ?? 'No review provided')); ?>
                                </p>
                            </div>
                            
                            <?php if (!empty($order_details['feedback'])): ?>
                            <div>
                                <h5 class="font-bold mb-2 flex items-center gap-2">
                                    <i data-lucide="lightbulb" class="w-5 h-5"></i>
                                    Additional Feedback:
                                </h5>
                                <p class="text-base-content/80 bg-base-100 p-4 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($order_details['feedback'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-sm text-base-content/60 pt-4 border-t border-base-300">
                                <i data-lucide="clock" class="w-4 h-4 inline mr-2"></i>
                                Submitted on: <?php echo date('F j, Y \a\t g:i A', strtotime($order_details['submitted_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <button onclick="window.location.href='<?php echo $_SERVER['HTTP_REFERER'] ?? '/'; ?>'" 
                            class="btn btn-primary btn-lg w-full max-w-xs">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        Return to Home
                    </button>
                </div>
                
                <?php else: ?>
                <!-- Feedback Form -->
                <?php if ($submission_error): ?>
                <div class="alert alert-error mb-6">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                    <span><?php echo htmlspecialchars($submission_error); ?></span>
                </div>
                <?php endif; ?>
                
                <form id="feedbackForm" method="POST" action="" class="space-y-8">
                    <!-- Rating Section -->
                    <div class="text-center">
                        <h3 class="text-2xl font-bold mb-6">How would you rate your experience?</h3>
                        
                        <div class="rating-stars flex justify-center gap-2 mb-4" style="direction: rtl;">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" 
                                       id="star<?php echo $i; ?>" 
                                       name="rating" 
                                       value="<?php echo $i; ?>" 
                                       class="hidden" 
                                       <?php echo ($rating == $i) ? 'checked' : ''; ?> />
                                <label for="star<?php echo $i; ?>" 
                                       class="star cursor-pointer p-1" 
                                       data-value="<?php echo $i; ?>" 
                                       onclick="setRating(<?php echo $i; ?>)">
                                    <i data-lucide="star" class="w-12 h-12 md:w-14 md:h-14 <?php echo ($rating >= $i) ? 'fill-current' : ''; ?>"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="mb-2">
                            <div class="text-4xl mb-2" id="ratingEmoji">
                                <?php
                                $emojis = [
                                    1 => "😞",
                                    2 => "😐",
                                    3 => "🙂",
                                    4 => "😊",
                                    5 => "🤩"
                                ];
                                echo $rating ? $emojis[$rating] : "⭐";
                                ?>
                            </div>
                            <div class="text-lg font-medium min-h-[28px]" id="ratingLabel">
                                <?php
                                $labels = [
                                    1 => "Poor - Very Dissatisfied",
                                    2 => "Fair - Needs Improvement",
                                    3 => "Good - Met Expectations",
                                    4 => "Very Good - Exceeded Expectations",
                                    5 => "Excellent - Outstanding Experience"
                                ];
                                echo $rating ? $labels[$rating] : "Tap a star to rate";
                                ?>
                        </div>
                    </div>
                    
                    <!-- Review Text -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-lg font-semibold flex items-center gap-2">
                                <i data-lucide="message-square" class="w-5 h-5"></i>
                                Your Review
                            </span>
                        </label>
                        <textarea 
                            id="review_text" 
                            name="review_text" 
                            class="textarea textarea-bordered h-32 text-lg" 
                            placeholder="Tell us about your experience... What did you like? What could be improved?"
                            maxlength="500"
                            oninput="updateCharCount(this, 'reviewCount')"
                        ><?php echo htmlspecialchars($review_text); ?></textarea>
                        <div class="label">
                            <span class="label-text-alt"></span>
                            <span class="label-text-alt char-count" id="reviewCount">0/500</span>
                        </div>
                    </div>
                    
                    <!-- Additional Feedback -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-lg font-semibold flex items-center gap-2">
                                <i data-lucide="lightbulb" class="w-5 h-5"></i>
                                Additional Feedback (Optional)
                            </span>
                        </label>
                        <textarea 
                            id="feedback" 
                            name="feedback" 
                            class="textarea textarea-bordered h-24 text-lg" 
                            placeholder="Any additional comments, suggestions, or feedback..."
                            maxlength="1000"
                            oninput="updateCharCount(this, 'feedbackCount')"
                        ><?php echo htmlspecialchars($feedback); ?></textarea>
                        <div class="label">
                            <span class="label-text-alt"></span>
                            <span class="label-text-alt char-count" id="feedbackCount">0/1000</span>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-full text-lg h-16" id="submitBtn">
                        <i data-lucide="send" class="w-6 h-6"></i>
                        Submit Your Feedback
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-white opacity-80 mt-8 space-y-2">
            <p class="text-sm md:text-base">Soliera Hotel & Restaurant © <?php echo date('Y'); ?>. All rights reserved.</p>
            <p class="text-sm">Thank you for helping us improve our service!</p>
        </div>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Initialize character counts
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount(document.getElementById('review_text'), 'reviewCount');
            updateCharCount(document.getElementById('feedback'), 'feedbackCount');
            
            // Set initial star states
            let rating = <?php echo $rating; ?>;
            if (rating > 0) {
                highlightStars(rating);
                document.getElementById('submitBtn').disabled = false;
            }
        });
        
        // Rating functionality
        function setRating(value) {
            // Update hidden radio button
            document.querySelector(`input[name="rating"][value="${value}"]`).checked = true;
            
            // Highlight stars
            highlightStars(value);
            
            // Update label and emoji
            const labels = {
                1: "Poor - Very Dissatisfied",
                2: "Fair - Needs Improvement",
                3: "Good - Met Expectations",
                4: "Very Good - Exceeded Expectations",
                5: "Excellent - Outstanding Experience"
            };
            
            const emojis = {
                1: "😞",
                2: "😐",
                3: "🙂",
                4: "😊",
                5: "🤩"
            };
            
            document.getElementById('ratingLabel').textContent = labels[value];
            document.getElementById('ratingEmoji').textContent = emojis[value];
            
            // Enable submit button
            document.getElementById('submitBtn').disabled = false;
        }
        
        function highlightStars(value) {
            const stars = document.querySelectorAll('.star');
            stars.forEach(star => {
                const starValue = parseInt(star.getAttribute('data-value'));
                const icon = star.querySelector('i');
                
                if (starValue <= value) {
                    star.classList.add('selected');
                    icon.classList.add('fill-current');
                } else {
                    star.classList.remove('selected');
                    icon.classList.remove('fill-current');
                }
            });
        }
        
        // Character count functionality
        function updateCharCount(textarea, countId) {
            const count = textarea.value.length;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            const countElement = document.getElementById(countId);
            
            countElement.textContent = `${count}/${maxLength}`;
            
            // Update color based on usage
            countElement.classList.remove('warning', 'danger', 'text-base-content');
            
            if (count > maxLength * 0.9) {
                countElement.classList.add('danger');
            } else if (count > maxLength * 0.75) {
                countElement.classList.add('warning');
            } else {
                countElement.classList.add('text-base-content');
            }
        }
        
        // Hover effect for stars
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.getAttribute('data-value'));
                highlightStars(value);
                
                // Update label temporarily
                const labels = {
                    1: "Poor - Very Dissatisfied",
                    2: "Fair - Needs Improvement",
                    3: "Good - Met Expectations",
                    4: "Very Good - Exceeded Expectations",
                    5: "Excellent - Outstanding Experience"
                };
                
                const emojis = {
                    1: "😞",
                    2: "😐",
                    3: "🙂",
                    4: "😊",
                    5: "🤩"
                };
                
                document.getElementById('ratingLabel').textContent = labels[value];
                document.getElementById('ratingEmoji').textContent = emojis[value];
            });
            
            star.addEventListener('mouseout', function() {
                const rating = document.querySelector('input[name="rating"]:checked');
                if (rating) {
                    setRating(parseInt(rating.value));
                } else {
                    // Reset to default
                    document.querySelectorAll('.star').forEach(s => {
                        s.classList.remove('selected');
                        s.querySelector('i').classList.remove('fill-current');
                    });
                    document.getElementById('ratingLabel').textContent = "Tap a star to rate";
                    document.getElementById('ratingEmoji').textContent = "⭐";
                }
            });
        });
        
        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.querySelector('input[name="rating"]:checked');
            const reviewText = document.getElementById('review_text').value.trim();
            
            if (!rating) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Rating Required',
                    text: 'Please select a rating before submitting your feedback.',
                    confirmButtonColor: '#f59e0b'
                });
                return false;
            }
            
            if (reviewText.length < 10) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Review Too Short',
                    text: 'Please provide a more detailed review (at least 10 characters).',
                    confirmButtonColor: '#f59e0b'
                });
                return false;
            }
            
            // Show confirmation
            e.preventDefault();
            Swal.fire({
                title: 'Submit Your Feedback?',
                text: 'Are you sure you want to submit your review?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'Cancel',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable button and show loading
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `
                        <span class="loading loading-spinner loading-md"></span>
                        Submitting...
                    `;
                    
                    // Submit the form
                    e.target.submit();
                }
            });
        });
    </script>
</body>
</html>