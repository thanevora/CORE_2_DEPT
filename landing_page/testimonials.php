<section id="reviews" class="py-20 bg-base-200" data-aos="fade-up">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-4xl font-bold text-center mb-16" data-aos="fade-down" data-aos-delay="100">
            Dining <span class="text-[#F7B32B]">Experiences</span>
        </h2>
        
        <div id="reviews-container" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Reviews will be dynamically loaded here -->
        </div>

        <!-- Add Review Button -->
        <div class="text-center mt-12" data-aos="fade-up" data-aos-delay="500">
            <button class="btn bg-[#F7B32B] hover:bg-amber-600 border-none text-white px-8" onclick="review_modal.showModal()">
                Add Your Review
            </button>
        </div>
    </div>
</section>

<!-- Review Modal -->
<dialog id="review_modal" class="modal">
    <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-2xl mb-6">Share Your Dining Experience</h3>
        <form method="POST" action="../M10/submit_review.php" id="review-form">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Your Name</span>
                    </label>
                    <input type="text" name="customer_name" placeholder="Full Name" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" placeholder="Email Address" class="input input-bordered" required />
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Phone Number</span>
                    </label>
                    <input type="tel" name="phone" placeholder="Phone Number" class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Category</span>
                    </label>
                    <select name="category" class="select select-bordered" required>
                        <option value="" disabled selected>Select Category</option>
                        <option value="Dine-in">Dine-in</option>
                        <option value="Takeout">Takeout</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Catering">Catering</option>
                    </select>
                </div>
            </div>
            
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Rating</span>
                </label>
                <div class="rating rating-lg" id="rating-stars">
                    <input type="radio" name="rating" value="1" class="mask mask-star-2 bg-orange-400" />
                    <input type="radio" name="rating" value="2" class="mask mask-star-2 bg-orange-400" />
                    <input type="radio" name="rating" value="3" class="mask mask-star-2 bg-orange-400" />
                    <input type="radio" name="rating" value="4" class="mask mask-star-2 bg-orange-400" />
                    <input type="radio" name="rating" value="5" class="mask mask-star-2 bg-orange-400" checked />
                </div>
            </div>
            
            <div class="form-control mb-6">
                <label class="label">
                    <span class="label-text">Your Feedback</span>
                </label>
                <textarea name="feedback_text" class="textarea textarea-bordered h-24" placeholder="Share your experience..." required></textarea>
            </div>
            
            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="review_modal.close()">Cancel</button>
                <button type="submit" class="btn bg-[#F7B32B] hover:bg-amber-600 border-none text-white">Submit Review</button>
            </div>
        </form>
    </div>
    <div class="modal-backdrop">
        <button onclick="review_modal.close()">close</button>
    </div>
</dialog>
<script>
async function loadReviews() {
    const reviewsContainer = document.getElementById('reviews-container');
    try {
        const response = await fetch("../M10/fetch_reviews.php");
        const reviews = await response.json();

        if (reviews.length === 0) {
            reviewsContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-600 text-lg">✨ No reviews yet. Be the first to share your experience!</p>
                </div>`;
            return;
        }

        reviewsContainer.innerHTML = reviews.map(review => `
            <div class="p-6 bg-white rounded-2xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                <!-- Reviewer Info -->
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 h-12 w-12 rounded-full bg-gradient-to-tr from-yellow-400 to-pink-500 flex items-center justify-center text-white font-bold">
                        ${review.customer_name.charAt(0).toUpperCase()}
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold text-gray-800">${review.customer_name}</p>
                        <span class="text-sm text-gray-500">${review.category}</span>
                    </div>
                </div>

                <!-- Rating -->
                <div class="flex items-center mb-3">
                    ${renderStars(review.rating)}
                </div>

                <!-- Feedback -->
                <blockquote class="italic text-gray-700 mb-4">“${review.feedback_text}”</blockquote>

                <!-- Date -->
                <p class="text-xs text-gray-400">Posted on ${new Date(review.created_at).toLocaleDateString()}</p>
            </div>
        `).join('');
    } catch (error) {
        reviewsContainer.innerHTML = `<p class="text-red-500 text-center">⚠️ Failed to load reviews.</p>`;
    }
}

function renderStars(rating) {
    let stars = "";
    for (let i = 1; i <= 5; i++) {
        stars += `
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-5 w-5 ${i <= rating ? 'text-yellow-400' : 'text-gray-300'} fill-current" 
                 viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 
                         3.292a1 1 0 00.95.69h3.462c.969 
                         0 1.371 1.24.588 1.81l-2.8 
                         2.034a1 1 0 00-.364 1.118l1.07 
                         3.292c.3.921-.755 1.688-1.54 
                         1.118l-2.8-2.034a1 1 0 
                         00-1.175 0l-2.8 
                         2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 
                         1 0 00-.364-1.118L2.98 
                         8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 
                         1 0 00.951-.69l1.07-3.292z"/>
            </svg>`;
    }
    return `<div class="flex">${stars}</div>`;
}

document.addEventListener('DOMContentLoaded', loadReviews);
</script>
