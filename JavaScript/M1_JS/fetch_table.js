document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("tables-grid");
  const filterMenu = document.getElementById("table-filter");
  const paginationInfo = document.getElementById("pagination-info");
  const paginationControls = document.getElementById("pagination-controls");

  const itemsPerPage = 9;
  let allCards = [];
  let filteredCards = [];
  let currentPage = 1;

  // Set grid layout
  grid.className = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5";

  // Fetch and render tables
  fetch("../../M1/sub-modules/fetch_tables.php")
    .then((res) => res.json())
    .then((response) => {
      if (response.status !== "success") {
        console.error("❌ Failed to fetch tables");
        return;
      }

      // Create card elements
      allCards = response.tables.map((table) => createCard(table));
      filteredCards = [...allCards];
      renderPage(1);
    })
    .catch((err) => console.error("[Tables Fetch Error]", err));

  // Create a table card
  function createCard(table) {
    const card = document.createElement("div");
    card.dataset.status = table.status.toLowerCase();
    card.className =
      "rounded-xl p-0 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer flex flex-col gap-3";
    card.addEventListener("click", () => showTableDetails(table.table_id));

    // Status styles
    let badgeColor = "bg-gray-200 text-gray-700";
    let iconColor = "text-gray-400";
    let statusIcon = "square";

    switch (table.status.toLowerCase()) {
      case "available":
        badgeColor = "bg-green-600 text-white";
        iconColor = "text-green-400";
        statusIcon = "circle";
        break;
      case "queued":
        badgeColor = "bg-blue-600 text-white";
        iconColor = "text-blue-400";
        statusIcon = "clock";
        break;
      case "occupied":
        badgeColor = "bg-amber-600 text-white";
        iconColor = "text-amber-400";
        statusIcon = "users";
        break;
      case "maintenance":
        badgeColor = "bg-red-600 text-white";
        iconColor = "text-red-400";
        statusIcon = "wrench";
        break;
      case "unavailable":
        badgeColor = "bg-gray-500 text-white";
        iconColor = "text-gray-300";
        statusIcon = "slash";
        break;
    }

    card.innerHTML = `
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 text-black p-4 rounded-t-xl">
        <div class="flex items-center gap-3 min-w-0">
          <div class="p-2 rounded-lg ${badgeColor}">
            <i data-lucide="${statusIcon}" class="w-5 h-5 ${iconColor}"></i>
          </div>
          <div class="min-w-0">
            <h3 class="text-lg font-semibold truncate">${table.name}</h3>
            <p class="text-xs text-gray-300 truncate">Table #: ${table.table_id}</p>
          </div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full ${badgeColor} font-medium flex items-center gap-1.5 shrink-0">
          <i data-lucide="${statusIcon}" class="w-3 h-3"></i>
          ${table.status}
        </span>
      </div>

      <!-- Details -->
      <div class="grid grid-cols-2 gap-4 p-4 text-black rounded-b-xl">
        <div class="flex items-center gap-2 text-sm">
          <i data-lucide="table" class="w-4 h-4 text-gray-300"></i>
          <div>
            <p class="text-xs text-gray-300">Category</p>
            <p class="font-medium">${table.category}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <i data-lucide="users" class="w-4 h-4 text-gray-300"></i>
          <div>
            <p class="text-xs text-gray-300">Capacity</p>
            <p class="font-medium">${table.capacity} <span class="text-gray-300">people</span></p>
          </div>
        </div>
      </div>
    `;

    return card;
  }

  // Render a page
  function renderPage(page) {
    currentPage = page;
    const start = (page - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentItems = filteredCards.slice(start, end);

    grid.innerHTML = "";
    currentItems.forEach((card) => grid.appendChild(card));

    renderPagination();
    updatePaginationText();
    lucide.createIcons();
  }

  // Render pagination buttons
  function renderPagination() {
    const totalPages = Math.ceil(filteredCards.length / itemsPerPage);
    paginationControls.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.className = `join-item btn btn-sm ${
        i === currentPage
          ? "bg-blue-600 text-white"
          : "btn-outline border-gray-300 text-gray-700 hover:bg-gray-100"
      }`;
      btn.textContent = i;
      btn.addEventListener("click", () => renderPage(i));
      paginationControls.appendChild(btn);
    }
  }

  // Update pagination info text
  function updatePaginationText() {
    const total = filteredCards.length;
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(start + itemsPerPage - 1, total);
    paginationInfo.innerHTML = `Showing <span class="font-semibold">${start}-${end}</span> of <span class="font-semibold">${total}</span> tables`;
  }

  // Filtering
  if (filterMenu) {
    filterMenu.addEventListener("click", (e) => {
      if (e.target.tagName.toLowerCase() !== "a") return;
      e.preventDefault();

      const filter = e.target.getAttribute("data-filter");
      filteredCards =
        filter === "all"
          ? [...allCards]
          : allCards.filter((card) => card.dataset.status === filter);

      renderPage(1);
    });
  }

  // Format timestamp
  function formatTime(timestamp) {
    if (!timestamp) return "Just now";
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }

  // Show table details (modal)
  window.showTableDetails = (tableId) => {
    fetch(`../../M1/sub-modules/view_table.php?id=${tableId}`)
      .then((res) => res.json())
      .then((response) => {
        if (response.status !== "success") {
          console.error("❌ Failed to fetch table details");
          return;
        }

        const table = response.table;
        const content = `
          <p><strong>Name:</strong> ${table.name}</p>
          <p><strong>Category:</strong> ${table.category}</p>
          <p><strong>Capacity:</strong> ${table.capacity} people</p>
          <p><strong>Status:</strong> ${table.status}</p>
          <p><strong>Last updated:</strong> ${formatTime(table.last_updated)}</p>
        `;

        document.getElementById("view-table-content").innerHTML = content;
        document.getElementById("view-table-modal").checked = true;
      })
      .catch((err) => console.error("[Table Detail Error]", err));
  };
});
