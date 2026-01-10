<script>
document.addEventListener("DOMContentLoaded", () => {
  const notifButton = document.getElementById("notification-button");
  const notifContainer = document.querySelector(".dropdown-content .max-h-96");
  const notifBadge = notifButton.querySelector("span");
  const clearAllBtn = document.querySelector(
    ".dropdown-content button.text-blue-300"
  );

  // ✅ List all modules you want to fetch
  const modules = ['module_1', 'module_2', 'module_3']; // Add more as mapped in API
  const apiURL = "../notification_api.php"; // Adjust if stored elsewhere

  // 📨 Fetch and render notifications from all modules
  async function fetchNotifications() {
    try {
      // Fetch each module's notifications
      const promises = modules.map(module => fetch(`${apiURL}?module=${module}`).then(res => res.json()));
      const results = await Promise.all(promises);

      // Merge all notifications into one array
      let notifications = [];
      results.forEach(res => {
        if (res.status === "success") notifications = notifications.concat(res.notifications || []);
      });

      // Sort by date_sent descending
      notifications.sort((a, b) => new Date(b.date_sent) - new Date(a.date_sent));

      // Display
      if (notifications.length === 0) {
        notifContainer.innerHTML = `<li class="px-4 py-3 text-center text-gray-300">No new notifications</li>`;
        notifBadge.classList.add("hidden");
        return;
      }

      // Count unread
      const unreadCount = notifications.filter(n => n.status === "Unread").length;
      if (unreadCount > 0) notifBadge.classList.remove("hidden");
      else notifBadge.classList.add("hidden");

      // Build HTML
      notifContainer.innerHTML = notifications.map(n => `
        <li class="border-b border-blue-800 last:border-0">
          <button data-id="${n.notification_id}" data-module="${n.module || 'module_1'}"
            class="notif-item w-full text-left px-4 py-3 hover:bg-blue-900/40 transition-all flex flex-col">
            <div class="flex justify-between items-center">
              <span class="font-medium text-white">${n.employee_name || "System"}</span>
              <span class="text-xs text-gray-400">${formatDate(n.date_sent)}</span>
            </div>
            <p class="text-sm text-gray-300 mt-1">${n.message}</p>
            ${n.status === "Unread" ? `<span class="text-xs text-blue-300 mt-1">(Unread)</span>` : ""}
          </button>
        </li>`).join("");

      // Attach click listeners for mark-as-read
      document.querySelectorAll(".notif-item").forEach(btn => {
        btn.addEventListener("click", () => markAsRead(btn.dataset.id, btn, btn.dataset.module));
      });

    } catch (err) {
      console.error("Fetch error:", err);
      notifContainer.innerHTML = `<li class="px-4 py-3 text-center text-gray-300">Error loading notifications</li>`;
    }
  }

  // 🕓 Format date
  function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString("en-PH", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit"
    });
  }

  // ✅ Mark a notification as read
  async function markAsRead(id, element, module) {
    try {
      const formData = new FormData();
      formData.append("notif_id", id);

      const res = await fetch(`${apiURL}?module=${module}`, {
        method: "POST",
        body: formData
      });

      const data = await res.json();
      if (data.status === "success") {
        element.querySelector("span.text-blue-300")?.remove();
        element.classList.add("opacity-70");
        fetchNotifications();
      } else {
        console.warn("Failed to mark read:", data.message);
      }
    } catch (err) {
      console.error("Error marking read:", err);
    }
  }

  // ✅ Clear all notifications (frontend + backend)
  clearAllBtn.addEventListener("click", async () => {
    try {
      const promises = modules.map(module => {
        const formData = new FormData();
        formData.append("clear_all", 1);
        return fetch(`${apiURL}?module=${module}`, {
          method: "POST",
          body: formData
        }).then(res => res.json());
      });
      await Promise.all(promises);

      notifContainer.innerHTML = `<li class="px-4 py-3 text-center text-gray-300">All notifications cleared</li>`;
      notifBadge.classList.add("hidden");

    } catch (err) {
      console.error("Error clearing notifications:", err);
    }
  });

  // 🔁 Initial fetch + refresh every 30s
  fetchNotifications();
  setInterval(fetchNotifications, 30000);

  // Refresh when dropdown opens
  notifButton.addEventListener("click", () => fetchNotifications());
});

