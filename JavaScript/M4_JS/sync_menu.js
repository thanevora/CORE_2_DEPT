document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("menu-items-grid");
    const searchInput = document.querySelector("input[placeholder='Search menu...']");
    const categoryTabs = document.querySelectorAll(".category-tab");

    let selectedCategory = "All Items";

    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function fetchMenuItems(search = "", category = "All Items") {
        container.innerHTML = `<div class="text-center py-6">Loading...</div>`;

        const params = new URLSearchParams({ search, category });

        fetch(`../../M4/sub-modules/fetch_menu.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    if (data.items.length === 0) {
                        container.innerHTML = `<p class="text-center text-gray-500">No menu items found.</p>`;
                        return;
                    }

                    container.innerHTML = data.items.map(row => `
                        <div class="menu-item-card relative rounded-xl border p-5 shadow-sm transition-all duration-300 hover:shadow-md border-gray-200 bg-white">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="p-2 rounded-lg bg-gray-100 text-gray-600">
                                        <i data-lucide="utensils" class="w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-800 truncate">${escapeHTML(row.name)}</h3>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">ID: ${row.menu_id}</p>
                                    </div>
                                </div>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 font-medium">
                                    ${escapeHTML(row.status)}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-2">
                                <div class="flex items-center gap-2 text-sm text-gray-600 min-w-0">
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500 truncate">Price</p>
                                        <p class="font-medium truncate">₱ ${parseFloat(row.price).toFixed(2)}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600 min-w-0">
                                    <i data-lucide="tag" class="w-4 h-4 text-gray-500 shrink-0"></i>
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500 truncate">Category</p>
                                        <p class="font-medium truncate">${escapeHTML(row.category)}</p>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mt-3 line-clamp-2">${escapeHTML(row.description)}</p>

                            <div class="mt-auto pt-3 border-t border-gray-200/50 flex justify-between items-center">
                                <button onclick="showMenuItemDetails(${row.menu_id})" 
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 transition-colors shrink-0">
                                    View details
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </button>
                                <span class="text-xs text-gray-500">
                                    Updated: ${new Date(row.updated_at).toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric'
                                    })}
                                </span>
                            </div>
                        </div>
                    `).join("");

                    lucide.createIcons(); // Re-render icons
                } else {
                    container.innerHTML = `<p class="text-red-500">Failed to load menu.</p>`;
                }
            })
            .catch(() => {
                container.innerHTML = `<p class="text-red-500">Error loading menu items.</p>`;
            });
    }

    // Initial fetch
    fetchMenuItems();

    // Search
    searchInput.addEventListener("input", (e) => {
        fetchMenuItems(e.target.value, selectedCategory);
    });

    // Category tabs
    categoryTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            categoryTabs.forEach(t => t.classList.remove("active", "bg-blue-100", "text-blue-700"));
            tab.classList.add("active", "bg-blue-100", "text-blue-700");
            selectedCategory = tab.textContent.trim();
            fetchMenuItems(searchInput.value, selectedCategory);
        });
    });
});