<!-- Add this to your existing HTML file -->
<section id="contact" class="py-20 bg-gray-50 relative" data-aos="fade-up">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center mb-16" data-aos="fade-down" data-aos-delay="100">
      <h2 class="text-4xl md:text-5xl font-bold mb-4">
        <span class="text-[#F7B32B]">Contact</span> Soliera Restaurant
      </h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Our concierge team is available 24/7 to assist with your inquiries
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      <!-- Contact Form -->
      <div class="contact-form" data-aos="fade-up" data-aos-delay="200">
        <div class="bg-white p-8 rounded-xl shadow-lg">
          <h3 class="text-2xl font-bold text-gray-800 mb-6">Send us a Message</h3>
          <form id="contactForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="firstName" class="block text-gray-700 font-medium mb-2">First Name</label>
                <input type="text" id="firstName" name="firstName" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input">
              </div>
              <div>
                <label for="lastName" class="block text-gray-700 font-medium mb-2">Last Name</label>
                <input type="text" id="lastName" name="lastName" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input">
              </div>
            </div>
            <div>
              <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
              <input type="email" id="email" name="email" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input">
            </div>
            <div>
              <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
              <input type="tel" id="phone" name="phone"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input">
            </div>
            <div>
              <label for="subject" class="block text-gray-700 font-medium mb-2">Subject</label>
              <select id="subject" name="subject" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input">
                <option value="" disabled selected>Select a subject</option>
            
                <option value="complaint">Complaint</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label for="message" class="block text-gray-700 font-medium mb-2">Message</label>
              <textarea id="message" name="message" rows="5" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent transition form-input"></textarea>
            </div>
            <button type="submit"
              class="w-full bg-[#F7B32B] text-white font-bold py-3 px-6 rounded-lg hover:bg-[#e6a025] transition btn-primary">
              Send Message
            </button>
          </form>
          <div id="formMessage" class="mt-4 text-center hidden"></div>
        </div>
      </div>

      <!-- Contact Info -->
      <div data-aos="fade-up" data-aos-delay="300">
        <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
          <h3 class="text-2xl font-bold text-gray-800 mb-6">Contact Information</h3>
          <div class="space-y-5">
            <div class="flex items-start contact-item" data-aos="fade-up" data-aos-delay="400">
              <div class="bg-blue-800/30 p-3 rounded-full mr-4 contact-icon">
                <!-- Location Icon -->
                <svg class="h-6 w-6 text-[#F7B32B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Address</h4>
                <p class="text-gray-600">Bayan Novaliches</p>
              </div>
            </div>
            <div class="flex items-start contact-item" data-aos="fade-up" data-aos-delay="500">
              <div class="bg-blue-800/30 p-3 rounded-full mr-4 contact-icon">
                <!-- Phone Icon -->
                <svg class="h-6 w-6 text-[#F7B32B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Phone</h4>
                <p class="text-gray-600">+63 2 8123 4567 (Main)</p>
                <p class="text-gray-600">+63 917 123 4567 (Mobile)</p>
              </div>
            </div>
            <div class="flex items-start contact-item" data-aos="fade-up" data-aos-delay="600">
              <div class="bg-blue-800/30 p-3 rounded-full mr-4 contact-icon">
                <!-- Email Icon -->
                <svg class="h-6 w-6 text-[#F7B32B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <h4 class="font-bold text-gray-800">Email</h4>
                <p class="text-gray-600">reservations@solierahotel.com</p>
                <p class="text-gray-600">info@solierahotel.com</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Map -->
        <div class="bg-white p-4 rounded-xl shadow-lg overflow-hidden map-container" data-aos="zoom-in" data-aos-delay="700">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.758036547544!2d120.9805133153266!3d14.554534589833028!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c90264a0ed01%3A0x2b066ed57830cace!2sManila%20Ocean%20Park!5e0!3m2!1sen!2sph!4v1623830287590!5m2!1sen!2sph"
            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" class="rounded-lg"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Add this to your existing script -->
<script>
  // Form submission handling
  document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = {
      firstName: document.getElementById('firstName').value,
      lastName: document.getElementById('lastName').value,
      email: document.getElementById('email').value,
      phone: document.getElementById('phone').value,
      subject: document.getElementById('subject').value,
      message: document.getElementById('message').value
    };
    
    // Simple validation
    if (!formData.firstName || !formData.lastName || !formData.email || !formData.subject || !formData.message) {
      showMessage('Please fill in all required fields.', 'error');
      return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      showMessage('Please enter a valid email address.', 'error');
      return;
    }
    
    // In a real application, you would send the data to your server here
    // For demonstration, we'll simulate a successful submission
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    submitBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
      // Show success message
      showMessage('Thank you for your message! We will get back to you soon.', 'success');
      
      // Reset form
      document.getElementById('contactForm').reset();
      
      // Reset button
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }, 2000);
  });
  
  function showMessage(message, type) {
    const messageEl = document.getElementById('formMessage');
    messageEl.textContent = message;
    messageEl.className = `mt-4 text-center ${type === 'success' ? 'text-green-600' : 'text-red-600'}`;
    messageEl.classList.remove('hidden');
    
    // Hide message after 5 seconds
    setTimeout(() => {
      messageEl.classList.add('hidden');
    }, 5000);
  }

  // Restaurant parallax effect
  document.addEventListener('scroll', function() {
    const restaurantBg = document.querySelector('.parallax-restaurant');
    const scrollPosition = window.pageYOffset;
    if (restaurantBg) {
      restaurantBg.style.transform = `translateY(${scrollPosition * 0.2}px)`;
    }
  });

  // Initialize animations for new sections
  document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-fade-in').forEach(el => {
      observer.observe(el);
    });
  });
</script>