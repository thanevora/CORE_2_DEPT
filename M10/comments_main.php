<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m10_comments_review";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch statistics data
$statsQuery = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN response_text IS NOT NULL AND response_text != '' THEN 1 ELSE 0 END) as responded_count,
    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_reviews
    FROM customer_feedback";

$statsResult = $conn->query($statsQuery);
$statsData = $statsResult->fetch_assoc();

$totalReviews = $statsData['total_reviews'] ?? 0;
$avgRating = round($statsData['avg_rating'] ?? 0, 1);
$responseRate = $totalReviews > 0 ? round(($statsData['responded_count'] / $totalReviews) * 100) : 0;
$positiveSentiment = $totalReviews > 0 ? round(($statsData['positive_reviews'] / $totalReviews) * 100) : 0;

// Fetch rating distribution
$ratingDistributionQuery = "SELECT 
    rating, 
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM customer_feedback)), 1) as percentage
    FROM customer_feedback 
    GROUP BY rating 
    ORDER BY rating DESC";

$distributionResult = $conn->query($ratingDistributionQuery);
$ratingDistribution = [];
while ($row = $distributionResult->fetch_assoc()) {
    $ratingDistribution[$row['rating']] = $row;
}

// Fetch all reviews
$reviewsQuery = "SELECT * FROM customer_feedback ORDER BY feedback_id DESC";
$reviewsResult = $conn->query($reviewsQuery);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback & Reviews | Soliera Restaurant</title>
   
    <style>
        :root {
            --primary: #8b0000;
            --primary-light: #a52a2a;
            --secondary: #d4af37;
            --accent: #4a4a4a;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
        }
        
        .display-font {
            font-family: 'Playfair Display', serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rating-stars {
            color: #FFD700;
        }
        
        .progress-bar {
            transition: width 1s ease-in-out;
            border-radius: 10px;
        }
        
        .sentiment-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .review-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: rgba(139, 0, 0, 0.1);
        }
        
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .category-tag {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .response-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #0ea5e9;
            border-radius: 8px;
        }
        
        .animate-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
<!-- Stats Overview -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Total Reviews Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Reviews</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $totalReviews ?? '0' ?></h3>
                <p class="text-xs text-gray-500 mt-1">From all customers</p>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="message-circle" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm text-gray-500">
            <i data-lucide="trending-up" class="w-4 h-4 text-green-500 mr-1"></i>
            <span>From last week</span>
        </div>
    </div>

    <!-- Average Rating Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Average Rating</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $avgRating ?? '0' ?> ★</h3>
                <p class="text-xs text-gray-500 mt-1">Customer ratings</p>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="star" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm text-gray-500">
            <?php
            $fullStars = floor($avgRating);
            $halfStar = ($avgRating - $fullStars) >= 0.5;
            for ($i = 0; $i < $fullStars; $i++) { echo '<i data-lucide="star" class="w-4 h-4 text-[#F7B32B] mr-1"></i>'; }
            if ($halfStar) { echo '<i data-lucide="star-half" class="w-4 h-4 text-[#F7B32B] mr-1"></i>'; $fullStars++; }
            for ($i = $fullStars; $i < 5; $i++) { echo '<i data-lucide="star" class="w-4 h-4 text-gray-300 mr-1"></i>'; }
            ?>
            <span><?= $avgRating ?? '0' ?>/5</span>
        </div>
    </div>

    <!-- Response Rate Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Response Rate</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $responseRate ?? '0' ?>%</h3>
                <p class="text-xs text-gray-500 mt-1">Average response</p>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="repeat" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-4 h-2 w-full bg-gray-200 rounded overflow-hidden">
            <div class="h-full" style="width: <?= $responseRate ?? 0 ?>%; background:#F7B32B;"></div>
        </div>
    </div>

    <!-- Positive Sentiment Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Positive Sentiment</p>
                <h3 class="text-3xl font-bold text-black mt-1"><?= $positiveSentiment ?? '0' ?>%</h3>
                <p class="text-xs text-gray-500 mt-1">Customer happiness</p>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="smile" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-4 h-2 w-full bg-gray-200 rounded overflow-hidden">
            <div class="h-full" style="width: <?= $positiveSentiment ?? 0 ?>%; background:#F7B32B;"></div>
        </div>
    </div>

</div>



            <!-- Rating Distribution -->
            <div class="glass-card p-6 mb-8 animate-in" style="animation-delay: 0.6s;">
                <h2 class="text-xl font-semibold text-slate-800 mb-6 display-font">Rating Distribution</h2>
                <div class="space-y-5">
                    <?php
                    for ($i = 5; $i >= 1; $i--) {
                        $percentage = isset($ratingDistribution[$i]) ? $ratingDistribution[$i]['percentage'] : 0;
                        $count = isset($ratingDistribution[$i]) ? $ratingDistribution[$i]['count'] : 0;
                        
                        echo '<div class="flex items-center">';
                        echo '<div class="w-16 text-sm font-medium text-slate-600">' . $i . ' <i class="fas fa-star text-amber-400"></i></div>';
                        echo '<div class="flex-1 mx-4">';
                        echo '<div class="w-full bg-slate-200 rounded-full h-2.5">';
                        echo '<div class="bg-amber-400 h-2.5 rounded-full progress-bar" style="width: ' . $percentage . '%"></div>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="w-20 text-right text-sm font-medium text-slate-700">' . $percentage . '% (' . $count . ')</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="glass-card p-6 mb-8 animate-in" style="animation-delay: 0.7s;">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h2 class="text-xl font-semibold text-slate-800 display-font">Customer Reviews</h2>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-slate-600">Sort by:</span>
                        <select class="select select-bordered select-sm bg-white">
                            <option>Newest</option>
                            <option>Highest rated</option>
                            <option>Lowest rated</option>
                        </select>
                        
                    </div>
                </div>

                <?php if ($reviewsResult->num_rows > 0): ?>
                    <div class="space-y-6">
                        <?php while ($review = $reviewsResult->fetch_assoc()): 
                            $sentimentClass = $review['rating'] >= 4 ? 'bg-green-100 text-green-800' : 
                                            ($review['rating'] >= 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                            $sentimentText = $review['rating'] >= 4 ? 'Positive' : 
                                            ($review['rating'] >= 3 ? 'Neutral' : 'Negative');
                            $avatarColor = 'bg-' . ['purple', 'blue', 'green', 'red', 'amber', 'indigo'][rand(0,5)] . '-500';
                        ?>
                        <div class="review-card p-5 bg-white">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4">
                                    <div class="avatar <?php echo $avatarColor; ?>">
                                        <?php echo substr($review['customer_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-slate-800"><?php echo htmlspecialchars($review['customer_name']); ?></h3>
                                        <div class="flex items-center mt-1 flex-wrap gap-2">
                                            <div class="rating-stars mr-2">
                                                <?php
                                                $rating = $review['rating'];
                                                for ($j = 1; $j <= 5; $j++) {
                                                    if ($j <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span class="text-sm text-slate-500"><?php echo $review['rating']; ?>/5</span>
                                            <span class="sentiment-badge <?php echo $sentimentClass; ?>"><?php echo $sentimentText; ?></span>
                                        </div>
                                        <?php if (!empty($review['category'])): ?>
                                        <div class="mt-2">
                                            <span class="category-tag"><?php echo htmlspecialchars($review['category']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-sm text-slate-500"><?php echo date('M j, Y', strtotime($review['created_at'] ?? 'now')); ?></div>
                            </div>
                            
                            <div class="mt-4">
                                <p class="text-slate-700"><?php echo nl2br(htmlspecialchars($review['feedback_text'])); ?></p>
                            </div>
                            
                            <?php if (!empty($review['response_text'])): ?>
                            <div class="mt-4 response-box p-4">
                                <div class="flex items-start">
                                    <div class="mr-3 text-blue-600 mt-1">
                                        <i class="fas fa-reply"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-blue-800">Soliera Response</h4>
                                        <p class="mt-1 text-blue-700"><?php echo nl2br(htmlspecialchars($review['response_text'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="max-w-md mx-auto">
                            <div class="w-16 h-16 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-comment-alt text-slate-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium mt-4 text-slate-700">No reviews yet</h3>
                            <p class="text-slate-500 mt-1">Be the first to share your experience!</p>
                            <button class="btn btn-primary mt-6" onclick="addReviewModal.showModal()">
                                <i class="fas fa-plus mr-2"></i> Add Review
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Review Modal -->
            <dialog id="addReviewModal" class="modal">
                <div class="modal-box max-w-2xl p-0 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#8b0000] to-[#a52a2a] text-white p-6">
                        <h3 class="font-bold text-lg text-white">Write a Review</h3>
                    </div>
                    <div class="p-6">
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text">Your Rating</span>
                            </label>
                            <div class="rating rating-lg">
                                <input type="radio" name="rating-6" class="mask mask-star-2 bg-orange-400" />
                                <input type="radio" name="rating-6" class="mask mask-star-2 bg-orange-400" />
                                <input type="radio" name="rating-6" class="mask mask-star-2 bg-orange-400" />
                                <input type="radio" name="rating-6" class="mask mask-star-2 bg-orange-400" checked />
                                <input type="radio" name="rating-6" class="mask mask-star-2 bg-orange-400" />
                            </div>
                        </div>
                        
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text">Review Title</span>
                            </label>
                            <input type="text" placeholder="Summarize your experience" class="input input-bordered w-full" />
                        </div>
                        
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text">Your Review</span>
                            </label>
                            <textarea class="textarea textarea-bordered h-24" placeholder="Share details of your experience..."></textarea>
                        </div>
                        
                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text">Add Photos (Optional)</span>
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-base-200 hover:bg-base-300">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt text-2xl mb-2 text-base-content/50"></i>
                                        <p class="text-sm text-base-content/50">Drag & drop photos here, or click to select</p>
                                    </div>
                                    <input id="dropzone-file" type="file" class="hidden" multiple />
                                </label>
                            </div> 
                        </div>
                        
                        <div class="form-control">
                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" checked="checked" class="checkbox checkbox-primary" />
                                <span class="label-text">Post as anonymous</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-action bg-slate-50 p-4">
                        <form method="dialog">
                            <button class="btn btn-ghost">Cancel</button>
                        </form>
                        <button class="btn btn-primary">Submit Review</button>
                    </div>
                </div>
            </dialog>

            <script>
                // Animate progress bars on page load
                document.addEventListener('DOMContentLoaded', function() {
                    const progressBars = document.querySelectorAll('.progress-bar');
                    progressBars.forEach(bar => {
                        const width = bar.style.width;
                        bar.style.width = '0';
                        setTimeout(() => {
                            bar.style.width = width;
                        }, 100);
                    });
                });
            </script>

        </main>
    </div>
</div>

<script src="../JavaScript/sidebar.js"></script>

</body>
</html>