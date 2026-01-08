
 
<header class="bg-base-100 shadow-sm z-10 border-b border-base-300 dark:border-gray-700" data-theme="light">
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Sidebar Toggle -->
      <div class="flex items-center">
        <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm hover:bg-base-300 transition-all hover:scale-105">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Right Section -->
      <div class="flex items-center gap-4">
        <!-- Time Display -->
        <div class="animate-fadeIn">
          <span id="philippineTime" class="font-medium text-base max-md:text-sm text-gray-700"></span>
        </div>

        <!-- Notification Dropdown -->
        <div class="dropdown dropdown-end relative">
          <!-- Bell Button -->
          <button id="notification-button" tabindex="0"
            class="btn btn-ghost btn-circle btn-sm relative hover:scale-105 transition-transform duration-200">
            <i data-lucide="bell" class="w-5 h-5 text-gray-700"></i>
            <span id="notif-badge"
              class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
          </button>

          <!-- Dropdown Content -->
          <ul tabindex="0"
            class="dropdown-content w-80 md:w-96 mt-3 bg-[#001f54]/95 backdrop-blur-lg rounded-lg shadow-2xl border border-blue-900/30 overflow-hidden transform transition-all duration-200">
            
            <!-- Header -->
            <li
              class="px-4 py-3 border-b border-blue-900/40 flex justify-between items-center sticky top-0 bg-[#001f54]/90 backdrop-blur-md z-10">
              <div class="flex items-center gap-2">
                <i data-lucide="bell" class="w-5 h-5 text-blue-300"></i>
                <span class="font-semibold text-white tracking-wide">Notifications</span>
              </div>
              <button
                class="text-blue-300 hover:text-white text-xs flex items-center gap-1 transition-colors duration-150 hover:scale-105">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Clear All</span>
              </button>
            </li>

            <!-- Notification Items -->
            <div class="max-h-96 overflow-y-auto px-2 py-2 space-y-2 scrollbar-thin scrollbar-thumb-blue-800 scrollbar-track-transparent rounded-lg">
              <!-- Notification items will be dynamically loaded here -->
              <!-- Example Placeholder -->
              <!--
              <li class="p-3 bg-blue-950/30 hover:bg-blue-950/50 rounded-xl transition-all duration-150">
                <p class="text-sm text-gray-200"><span class="font-semibold text-blue-300">John Doe</span> added a new reservation.</p>
                <span class="text-xs text-blue-400">5 mins ago</span>
              </li>
              -->
            </div>

            <!-- Footer -->
            <li
              class="px-4 py-3 border-t border-blue-900/40 sticky bottom-0 bg-[#001f54]/90 backdrop-blur-md text-center">
              <a
                class="text-blue-300 hover:text-white text-sm flex items-center justify-center gap-1 transition-colors duration-150 hover:scale-105">
                <i data-lucide="list" class="w-4 h-4"></i>
                <span>View All Notifications</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const philippineTimeEl = document.getElementById("philippineTime");

  function updatePhilippineTime() {
    const now = new Date();

    // Convert to Philippine Time (UTC+8)
    const phTime = new Date(now.toLocaleString("en-US", { timeZone: "Asia/Manila" }));

    // Format: Wed, Oct 23, 2025 03:05 PM
    const options = {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: true
    };
    philippineTimeEl.textContent = phTime.toLocaleString("en-PH", options);
  }

  // Initial call
  updatePhilippineTime();

  // Update every second
  setInterval(updatePhilippineTime, 1000);
});
</script>

<style>
  /* Custom scrollbar styling for notification list */
  .scrollbar-thin::-webkit-scrollbar {
    width: 6px;
  }
  .scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: rgba(59, 130, 246, 0.5);
    border-radius: 10px;
  }

  /* Mobile dropdown alignment fix */
  @media (max-width: 767px) {
    .dropdown-content {
      left: 50% !important;
      transform: translateX(-80%) !important;
    }
  }
</style>
