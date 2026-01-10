// Initialize Lucide icons
lucide.createIcons();

// Function to show stock details
function showStockDetails(stockId) {
    // Show loading state
    document.getElementById('stock-details-modal').checked = true;
    document.getElementById('stock-details-content').innerHTML = `
        <div class="flex justify-center items-center py-10">
            <span class="loading loading-spinner loading-lg" style="color:#F7B32B;"></span>
            <span class="ml-2" style="color:#F7B32B;">Loading stock details...</span>
        </div>
    `;
    
    // Fetch stock details from server
    fetch(`../../M2/sub-modules/fetch_stock_details.php?id=${stockId}`)
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                const stock = response.stock;
                renderStockDetails(stock);
            } else {
                document.getElementById('stock-details-content').innerHTML = `
                    <div class="alert" style="background:#F7B32B; color:#001f54; display:flex; align-items:center; padding:0.75rem; border-radius:0.5rem;">
                        <i data-lucide="alert-circle" class="w-6 h-6 mr-2"></i>
                        <span>Failed to load stock details: ${response.message || 'Unknown error'}</span>
                    </div>
                    <div class="modal-action">
                        <label for="stock-details-modal" class="btn" style="background:#F7B32B; color:#001f54;">Close</label>
                    </div>
                `;
                lucide.createIcons();
            }
        })
        .catch(err => {
            console.error('[Stock Details Fetch Error]', err);
            document.getElementById('stock-details-content').innerHTML = `
                <div class="alert" style="background:#F7B32B; color:#001f54; display:flex; align-items:center; padding:0.75rem; border-radius:0.5rem;">
                    <i data-lucide="alert-circle" class="w-6 h-6 mr-2"></i>
                    <span>Network error loading stock details</span>
                </div>
                <div class="modal-action">
                    <label for="stock-details-modal" class="btn" style="background:#F7B32B; color:#001f54;">Close</label>
                </div>
            `;
            lucide.createIcons();
        });
}

function renderStockDetails(stock) {
    const statusClasses = {
        'In Stock': 'background:#001f54; color:#F7B32B;',
        'Low Stock': 'background:#001f54; color:#F7B32B;',
        'Out of Stock': 'background:#001f54; color:#F7B32B;',
        'Expired': 'background:#001f54; color:#F7B32B; font-weight:bold;'
    };
    
    let status = 'In Stock';
    if (stock.quantity <= 0) status = 'Out of Stock';
    else if (stock.quantity <= stock.critical_level) status = 'Low Stock';
    
    const today = new Date();
    const expiryDate = new Date(stock.expiry_date);
    if (stock.expiry_date && expiryDate < today) status = 'Expired';
    
    const lastUpdated = stock.last_updated ? new Date(stock.last_updated).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : 'N/A';
    
    const expiryFormatted = stock.expiry_date ? new Date(stock.expiry_date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric'
    }) : 'N/A';
    
    const stockDetails = `
        <div class="flex justify-between items-start mb-4">
            <div>
                <h4 class="text-lg font-semibold" style="color:#F7B32B;">${stock.item_name || 'N/A'}</h4>
                <p style="color:#F7B32B; font-size:0.875rem;">ID: ${stock.item_id}</p>
            </div>
            <span class="px-2 py-1 rounded-full" style="${statusClasses[status]}">${status}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Category</p>
                <p style="font-weight:500;">${stock.category || 'N/A'}</p>
            </div>
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Current Quantity</p>
                <p style="font-weight:500;">${stock.quantity || 0} ${stock.unit || 'units'}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Unit Price</p>
                <p style="font-weight:500;">$${(stock.unit_price || 0).toFixed(2)}</p>
            </div>
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Total Value</p>
                <p style="font-weight:500;">$${((stock.quantity || 0) * (stock.unit_price || 0)).toFixed(2)}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Critical Level</p>
                <p style="font-weight:500;">${stock.critical_level || 0}</p>
            </div>
            ${stock.expiry_date ? `
            <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem;">
                <p style="font-size:0.875rem;">Expiry Date</p>
                <p style="${status === 'Expired' ? 'color:#F7B32B; font-weight:bold;' : ''}">${expiryFormatted}</p>
            </div>` : ''}
        </div>

        ${stock.supplier ? `
        <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem; margin-bottom:1rem;">
            <p style="font-size:0.875rem;">Supplier</p>
            <p style="font-weight:500;">${stock.supplier}</p>
        </div>` : ''}

        <div style="background:#001f54; color:#F7B32B; padding:0.75rem; border-radius:0.5rem; margin-bottom:1.5rem;">
            <p style="font-size:0.875rem;">Last Updated</p>
            <p style="font-weight:500;">${lastUpdated}</p>
        </div>

        <div class="flex gap-3 mt-6">
            <button style="flex:1; background:#001f54; color:#F7B32B; border:1px solid #F7B32B;" class="btn flex items-center justify-center">
                <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Edit
            </button>
            <button style="flex:1; background:#F7B32B; color:#001f54;" class="btn flex items-center justify-center">
                <i data-lucide="package-plus" class="w-4 h-4 mr-1"></i> Restock
            </button>
        </div>
    `;
    
    lucide.createIcons();
}
