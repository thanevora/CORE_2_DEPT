<!-- header.php -->

<link rel="icon" href="/images/soliera_S.png?v=1" type="image/png">

  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- jsPDF CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Optional: if you want autoTable plugin for table layout -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<!-- Your main JS -->
<script src="your-pos-order.js"></script>

  <script src="JavaScript/sidebar.js"></script>

    <link rel="stylesheet" href="../CSS/calendar.css">
     <link rel="stylesheet" href="../CSS/sidebar.css">
    <link rel="stylesheet" href="../CSS/soliera.css">

    <style>
      .swal-confirm-button {
  padding: 10px 24px !important;
  border-radius: 8px !important;
  font-weight: 600 !important;
}
    </style>

    
<!-- Your existing notification script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const notifButton = document.getElementById("notification-button");
  const notifContainer = document.querySelector(".dropdown-content .max-h-96");
  const notifBadge = document.getElementById("notif-badge");
  const clearAllBtn = document.querySelector(".dropdown-content button.text-blue-300");
  const apiURL = "../notification_api.php";

  let currentNotifIds = new Set();
  let lastFetch = 0;

  // 📨 Fetch notifications smartly
  async function fetchNotifications() {
    try {
      const res = await fetch(apiURL);
      const data = await res.json();

      if (data.status !== "success") return;

      const notifications = data.notifications || [];
      const unreadCount = notifications.filter(n => n.status!=='Read').length;

      notifBadge.classList.toggle("hidden", unreadCount === 0);
      notifBadge.textContent = unreadCount;

      notifications.forEach(n => {
        if (!currentNotifIds.has(n.notification_id) && n.status!=='Read') {
          currentNotifIds.add(n.notification_id);
          displayNotification(n);
        }
      });

    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  function displayNotification(n) {
    const item = document.createElement("li");
    item.className = "notif-item border border-blue-900/40 rounded-xl bg-blue-950/30 px-4 py-3 flex flex-col transition-all duration-300 opacity-0";
    item.dataset.id = n.notification_id;
    item.innerHTML = `
      <div class="flex justify-between items-center">
        <span class="font-medium text-white">${n.employee_name || "System"}</span>
        <span class="text-xs text-gray-400">${formatDatePH(n.date_sent)}</span>
      </div>
      <p class="text-sm text-gray-300 mt-1">${n.message}</p>
      <span class="text-xs text-blue-300 mt-1">(Unread)</span>
    `;

    item.addEventListener("mouseenter", () => item.style.backgroundColor = "#1e40af66");
    item.addEventListener("mouseleave", () => item.style.backgroundColor = "#1e3a8a33");

    notifContainer.prepend(item);
    requestAnimationFrame(() => item.style.opacity = 1);

    item.addEventListener("click", () => markAsRead(n.notification_id, n.module, item));
  }

  function updateBadgeCount() {
    const unread = notifContainer.querySelectorAll("span.text-blue-300").length;
    notifBadge.textContent = unread;
    notifBadge.classList.toggle("hidden", unread===0);
  }

  async function markAsRead(id, module, item) {
    const formData = new FormData();
    formData.append("notif_id", id);
    formData.append("module", module);

    try {
      const res = await fetch(apiURL, { method: "POST", body: formData });
      const data = await res.json();
      if (data.status !== "success") console.warn(data.message);

      item.style.transition = "all 0.4s ease";
      item.style.opacity = 0;
      item.style.transform = "translateX(50px)";
      setTimeout(() => {
        item.remove();
        currentNotifIds.delete(id);
        updateBadgeCount();
      }, 400);
    } catch (err) {
      console.error("Mark read error:", err);
    }
  }

  // Clear all
  clearAllBtn.addEventListener("click", async () => {
    document.querySelectorAll(".notif-item").forEach(i => i.remove());
    notifBadge.classList.add("hidden");
    currentNotifIds.clear();

    const formData = new FormData();
    formData.append("clear_all", "1");
    try { await fetch(apiURL,{method:"POST",body:formData}); } catch(e){console.error(e);}
  });

  // PH-time formatter
  function formatDatePH(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleString("en-PH", { month:"short", day:"numeric", hour:"2-digit", minute:"2-digit" });
  }

  // ===== Real-time using requestAnimationFrame loop (smart polling) =====
  function realTimeLoop() {
    const now = Date.now();
    // Fetch every 5s
    if (now - lastFetch > 5000) {
      fetchNotifications();
      lastFetch = now;
    }
    requestAnimationFrame(realTimeLoop);
  }
  realTimeLoop();

  // Manual refresh on button click
  notifButton.addEventListener("click", fetchNotifications);
});
</script>   