  // Function to filter orders by status
    function filterOrders(status) {
        // Remove active class from all cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('border-2', 'border-primary', 'ring-2', 'ring-primary-100');
        });
        
        // Add active class to clicked card
        const activeCard = document.querySelector(`.stat-card[data-filter="${status}"]`);
        if (activeCard) {
            activeCard.classList.add('border-2', 'border-primary', 'ring-2', 'ring-primary-100');
        }
        
        // Show loading state
        const ordersContainer = document.getElementById('orders-container');
        ordersContainer.innerHTML = `
            <div class="col-span-full flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
            </div>
        `;
        
        // AJAX call to fetch filtered orders
        fetch('../M5/sub-modules/get_filtered_orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.text())
        .then(html => {
            ordersContainer.innerHTML = html;
            lucide.createIcons(); // Refresh icons in the new content
        })
        .catch(error => {
            console.error('Error:', error);
            ordersContainer.innerHTML = `
                <div class="col-span-full text-center py-12 text-gray-400">
                    <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i>
                    <p class="text-lg font-medium">Error loading orders</p>
                    <p class="text-sm">Please try again</p>
                </div>
            `;
            lucide.createIcons();
        });
    }
    
    // Initialize with all orders shown
    document.addEventListener('DOMContentLoaded', function() {
        // Click the "All Orders" card by default
        const allOrdersCard = document.querySelector('.stat-card[data-filter="all"]');
        if (allOrdersCard) {
            allOrdersCard.click();
        }
    });