document.addEventListener("DOMContentLoaded", () => {
    const notifModal = document.getElementById("notifModal");
    const closeNotif = document.getElementById("closeNotif");
    const notifList = document.getElementById("notifList");
    const notifBadge = document.getElementById("notifBadge");
    const notifBtn = document.getElementById("notifBtn");
  
    let knownIds = new Set();
    let hideTimeout;
  
    closeNotif.addEventListener("click", () => hideModal());
  
    notifBtn.addEventListener("click", () => {
      notifModal.classList.remove("pointer-events-none");
      notifModal.style.opacity = "1";
      clearTimeout(hideTimeout);
  
      // Mark notifications as read when modal opens
      markNotificationsRead();
    });
  
    function showModal() {
      notifModal.classList.remove("pointer-events-none");
      notifModal.style.opacity = "1";
      clearTimeout(hideTimeout);
      hideTimeout = setTimeout(hideModal, 8000);
    }
  
    function hideModal() {
      notifModal.style.opacity = "0";
      notifModal.classList.add("pointer-events-none");
    }
  
    function createNotifElement(n) {
      const wrapper = document.createElement("div");
      wrapper.className =
        "p-2 bg-yellow-50 border border-yellow-200 rounded-md shadow-sm hover:bg-yellow-100 cursor-pointer";
      wrapper.innerHTML = `
        <p class="text-sm text-gray-800">${n.message}</p>
        <p class="text-xs text-gray-400">${n.date}</p>
      `;
      wrapper.dataset.id = n.id; // store ID on element
      return wrapper;
    }
  
    function fetchNotifications() {
      fetch(".././Main/notification.php")
        .then((res) => res.json())
        .then((data) => {
          if (data.error) {
            notifList.innerHTML = `<p class='text-center text-red-500'>${data.error}</p>`;
            notifBadge.classList.add("hidden");
            return;
          }
  
          if (data.length === 0) {
            notifList.innerHTML = "<p class='text-center text-gray-400'>No notifications.</p>";
            notifBadge.classList.add("hidden");
            knownIds.clear();
            return;
          }
  
          let newCount = 0;
  
          data.forEach((n) => {
            if (!knownIds.has(n.id)) {
              knownIds.add(n.id);
              newCount++;
              const el = createNotifElement(n);
              notifList.prepend(el);
            }
          });
  
          if (newCount > 0) {
            notifBadge.classList.remove("hidden");
            showModal();
          }
        })
        .catch(() => {
          notifList.innerHTML = "<p class='text-center text-red-500'>Unable to load notifications.</p>";
        });
    }
  
    // New: Mark all notifications as read by calling PHP endpoint
    function markNotificationsRead() {
      fetch("../../Main/mark_notif.php", { method: "POST" })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            console.warn("Failed to mark notifications as read:", data.error || "Unknown error");
            return;
          }
          // Clear badge visually
          notifBadge.classList.add("hidden");
        })
        .catch((err) => {
          console.error("Error marking notifications read:", err);
        });
    }
  
    setInterval(fetchNotifications, 8000);
    fetchNotifications();
  });
  