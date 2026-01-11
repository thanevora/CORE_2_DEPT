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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience - Soliera Hotel & Restaurant</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #F7B32B 0%, #e6a117 100%);
            padding: 25px;
            color: white;
            text-align: center;
        }
        
        .card-header h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .card-header p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .order-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .order-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
            border-bottom: 2px solid #F7B32B;
            padding-bottom: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .items-table th {
            background: #e9ecef;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #555;
        }
        
        .items-table tr:hover {
            background: #f8f9fa;
        }
        
        .rating-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .rating-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            direction: rtl;
        }
        
        .star {
            font-size: 3rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .star:hover,
        .star:hover ~ .star,
        .star.selected,
        .star.selected ~ .star {
            color: #FFD700;
        }
        
        .star-label {
            font-size: 1.1rem;
            color: #666;
            margin-top: 10px;
            min-height: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: vertical;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #F7B32B;
            box-shadow: 0 0 0 3px rgba(247, 179, 43, 0.1);
        }
        
        .char-count {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .submit-btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #F7B32B 0%, #e6a117 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(247, 179, 43, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .success-message {
            text-align: center;
            padding: 50px 30px;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .success-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        .thank-you-note {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            border: 2px solid #F7B32B;
        }
        
        .thank-you-note h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .thank-you-note p {
            color: #555;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .restaurant-logo {
            max-width: 150px;
            margin: 0 auto 20px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .card {
                border-radius: 15px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .star {
                font-size: 2.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        .rating-emoji {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Restaurant Logo -->
        <img src="../../images/soliera_S.png" alt="Soliera Hotel & Restaurant" class="restaurant-logo">
        
        <!-- Header -->
        <div class="header">
            <h1>Share Your Experience</h1>
            <p>Your feedback helps us improve our service</p>
        </div>
        
        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h2>Order #<?php echo htmlspecialchars($order_code); ?></h2>
                <p>Thank you for dining with us!</p>
            </div>
            
            <div class="card-body">
                <!-- Order Information -->
                <div class="order-info">
                    <h3>Your Order Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Customer Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($order_details['customer_name'] ?? 'Guest'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Table Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($order_details['table_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Date</span>
                            <span class="info-value"><?php echo date('F j, Y', strtotime($order_details['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($order_items)): ?>
                    <h3 style="margin-top: 25px;">Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name'] ?? $item['item_name'] ?? 'Item'); ?></td>
                                <td><?php echo intval($item['quantity'] ?? $item['qty'] ?? 1); ?></td>
                                <td>₱<?php echo number_format(floatval($item['price'] ?? $item['unit_price'] ?? 0), 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                
                <?php if ($submission_success): ?>
                <!-- Success Message -->
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="success-title">Thank You for Your Feedback!</h3>
                    <p class="success-text">Your review has been successfully submitted. We appreciate you taking the time to share your experience with us.</p>
                    
                    <div class="thank-you-note">
                        <h4>Your Feedback Matters</h4>
                        <p>We value every review as it helps us understand what we're doing well and where we can improve. Our team will review your feedback and use it to enhance our service for future guests.</p>
                    </div>
                    
                    <button onclick="window.location.href='<?php echo $_SERVER['HTTP_REFERER'] ?? '/'; ?>'" class="submit-btn" style="margin-top: 30px;">
                        <i class="fas fa-home"></i> Return to Home
                    </button>
                </div>
                
                <?php elseif ($already_submitted): ?>
                <!-- Already Submitted -->
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="success-title">Feedback Already Submitted</h3>
                    <p class="success-text">Thank you! You have already submitted your feedback for this order.</p>
                    
                    <div class="thank-you-note">
                        <h4>Rating: <?php echo $order_details['rating'] ?? 0; ?> stars</h4>
                        <p><strong>Review:</strong> <?php echo nl2br(htmlspecialchars($order_details['review_text'] ?? 'No review provided')); ?></p>
                        <?php if (!empty($order_details['feedback'])): ?>
                        <p style="margin-top: 15px;"><strong>Additional Feedback:</strong> <?php echo nl2br(htmlspecialchars($order_details['feedback'])); ?></p>
                        <?php endif; ?>
                        <p style="margin-top: 15px; font-size: 0.9rem; color: #777;">
                            Submitted on: <?php echo date('F j, Y \a\t g:i A', strtotime($order_details['submitted_at'])); ?>
                        </p>
                    </div>
                    
                    <button onclick="window.location.href='<?php echo $_SERVER['HTTP_REFERER'] ?? '/'; ?>'" class="submit-btn" style="margin-top: 30px;">
                        <i class="fas fa-home"></i> Return to Home
                    </button>
                </div>
                
                <?php else: ?>
                <!-- Feedback Form -->
                <?php if ($submission_error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($submission_error); ?>
                </div>
                <?php endif; ?>
                
                <form id="feedbackForm" method="POST" action="">
                    <!-- Rating Section -->
                    <div class="rating-section">
                        <h3 class="rating-title">How would you rate your experience?</h3>
                        
                        <div class="rating-stars">
                            <input type="radio" id="star5" name="rating" value="5" style="display: none;" <?php echo ($rating == 5) ? 'checked' : ''; ?>>
                            <label for="star5" class="star" data-value="5" onclick="setRating(5)">
                                <i class="fas fa-star"></i>
                            </label>
                            
                            <input type="radio" id="star4" name="rating" value="4" style="display: none;" <?php echo ($rating == 4) ? 'checked' : ''; ?>>
                            <label for="star4" class="star" data-value="4" onclick="setRating(4)">
                                <i class="fas fa-star"></i>
                            </label>
                            
                            <input type="radio" id="star3" name="rating" value="3" style="display: none;" <?php echo ($rating == 3) ? 'checked' : ''; ?>>
                            <label for="star3" class="star" data-value="3" onclick="setRating(3)">
                                <i class="fas fa-star"></i>
                            </label>
                            
                            <input type="radio" id="star2" name="rating" value="2" style="display: none;" <?php echo ($rating == 2) ? 'checked' : ''; ?>>
                            <label for="star2" class="star" data-value="2" onclick="setRating(2)">
                                <i class="fas fa-star"></i>
                            </label>
                            
                            <input type="radio" id="star1" name="rating" value="1" style="display: none;" <?php echo ($rating == 1) ? 'checked' : ''; ?>>
                            <label for="star1" class="star" data-value="1" onclick="setRating(1)">
                                <i class="fas fa-star"></i>
                            </label>
                        </div>
                        
                        <div class="star-label" id="ratingLabel">
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
                        
                        <div class="rating-emoji" id="ratingEmoji">
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
                    </div>
                    
                    <!-- Review Text -->
                    <div class="form-group">
                        <label for="review_text" class="form-label">
                            <i class="fas fa-comment"></i> Your Review
                        </label>
                        <textarea 
                            id="review_text" 
                            name="review_text" 
                            class="form-control" 
                            rows="4" 
                            placeholder="Tell us about your experience... What did you like? What could be improved?"
                            maxlength="500"
                            oninput="updateCharCount(this, 'reviewCount')"
                        ><?php echo htmlspecialchars($review_text); ?></textarea>
                        <div class="char-count">
                            <span id="reviewCount">0</span>/500 characters
                        </div>
                    </div>
                    
                    <!-- Additional Feedback -->
                    <div class="form-group">
                        <label for="feedback" class="form-label">
                            <i class="fas fa-lightbulb"></i> Additional Feedback (Optional)
                        </label>
                        <textarea 
                            id="feedback" 
                            name="feedback" 
                            class="form-control" 
                            rows="3" 
                            placeholder="Any additional comments, suggestions, or feedback..."
                            maxlength="1000"
                            oninput="updateCharCount(this, 'feedbackCount')"
                        ><?php echo htmlspecialchars($feedback); ?></textarea>
                        <div class="char-count">
                            <span id="feedbackCount">0</span>/1000 characters
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Your Feedback
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Soliera Hotel & Restaurant © <?php echo date('Y'); ?>. All rights reserved.</p>
            <p>Thank you for helping us improve our service!</p>
        </div>
    </div>
    
    <script>
        // Initialize character counts
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount(document.getElementById('review_text'), 'reviewCount');
            updateCharCount(document.getElementById('feedback'), 'feedbackCount');
            
            // Set initial star states
            let rating = <?php echo $rating; ?>;
            if (rating > 0) {
                highlightStars(rating);
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
            
            // Enable submit button if rating is selected
            document.getElementById('submitBtn').disabled = false;
        }
        
        function highlightStars(value) {
            const stars = document.querySelectorAll('.star');
            stars.forEach(star => {
                const starValue = parseInt(star.getAttribute('data-value'));
                if (starValue <= value) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
        
        // Character count functionality
        function updateCharCount(textarea, countId) {
            const count = textarea.value.length;
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            document.getElementById(countId).textContent = count;
            
            // Change color when approaching limit
            const countElement = document.getElementById(countId);
            if (count > maxLength * 0.9) {
                countElement.style.color = '#dc3545';
            } else if (count > maxLength * 0.75) {
                countElement.style.color = '#ffc107';
            } else {
                countElement.style.color = '#666';
            }
        }
        
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
                    confirmButtonColor: '#F7B32B'
                });
                return false;
            }
            
            if (reviewText.length < 10) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Review Too Short',
                    text: 'Please provide a more detailed review (at least 10 characters).',
                    confirmButtonColor: '#F7B32B'
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
                confirmButtonColor: '#F7B32B',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable button and show loading
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                    
                    // Submit the form
                    e.target.submit();
                }
            });
        });
        
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
                    document.querySelectorAll('.star').forEach(s => s.classList.remove('selected'));
                    document.getElementById('ratingLabel').textContent = "Tap a star to rate";
                    document.getElementById('ratingEmoji').textContent = "⭐";
                }
            });
        });
    </script>
</body>
</html>