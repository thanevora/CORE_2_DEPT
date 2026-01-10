function showMenuItemDetails(menuId) {
    const modalContent = document.getElementById('menu-item-details-content');
    modalContent.innerHTML = `
        <div class="flex justify-center items-center py-10">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        </div>
    `;

    document.getElementById('menu-item-details-modal').checked = true;

    fetch(`../../M3/sub-modules/get_menu_item.php?id=${menuId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const item = data.item;

                modalContent.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      
                        <div>
                            <h4 class="font-semibold text-gray-700">Menu ID</h4>
                            <p>${item.menu_id}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700">Status</h4>
                            <p>${item.status}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700">Price</h4>
                            <p>$${parseFloat(item.price).toFixed(2)}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700">Category</h4>
                            <p>${item.category}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700">Variant</h4>
                            <p>${item.variant || 'N/A'}</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700">Prep Time</h4>
                            <p>${item.prep_time} min</p>
                        </div>
                    </div>
                    <div class="pt-4">
                        <h4 class="font-semibold text-gray-700">Description</h4>
                        <p>${item.description}</p>
                    </div>
                    <div class="pt-4 flex justify-end gap-2">
                        <button onclick="deleteMenuItem(${item.menu_id})" class="btn btn-outline btn-error">Delete Item</button>
                        <button onclick="editMenuItem(${item.menu_id})" class="btn btn-primary">Edit Item</button>
                    </div>
                `;
            } else {
                modalContent.innerHTML = `<p class="text-red-500">Failed to load menu item details.</p>`;
            }
        })
        .catch(() => {
            modalContent.innerHTML = `<p class="text-red-500">Error loading data. Please try again.</p>`;
        });
}

// Add item messages
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has("success")) {
        Swal.fire({
            icon: 'success',
            title: 'Menu item added!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: true,
        });
    }

    if (urlParams.has("error")) {
        const errorType = urlParams.get("error");
        let message = "An error occurred.";

        if (errorType === "missing_fields") message = "Please fill in all required fields.";
        if (errorType === "insert_failed") message = "Failed to add menu item.";

        Swal.fire({
            icon: 'error',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: true,
        });
    }
});
