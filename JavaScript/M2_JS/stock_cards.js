document.addEventListener('DOMContentLoaded', function () {
  const grid = document.getElementById('stock-grid');
  const filterButtons = document.querySelectorAll('#stock-filter a');
  const deleteSelectedBtn = document.getElementById('delete-selected-btn');
  const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
  const deleteConfirmMessage = document.getElementById('delete-confirm-message');
  
  const itemsPerPage = 8;
  let allStocks = [];
  let filteredStocks = [];
  let allCards = [];
  let currentPage = 1;
  let currentFilter = 'all';
  let selectedItems = new Set(); // Track selected items for deletion

  // Fetch stock data
  fetch('../../M2/sub-modules/fetch_stocks.php')
    .then(res => res.json())
    .then(response => {
      if (response.status !== 'success') {
        console.error('Failed to fetch stock data');
        return;
      }

      allStocks = response.stocks;
      filteredStocks = [...allStocks];
      updateDisplay();
      setupFilterEvents();
      setupDeleteEvents();
    })
    .catch(err => console.error('[Stock Fetch Error]', err));

  function setupFilterEvents() {
    filterButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Update active state
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        // Apply filter
        const filter = this.getAttribute('data-filter');
        currentFilter = filter;
        applyFilter(filter);
      });
    });
  }

  function setupDeleteEvents() {
    // Delete selected button event
    deleteSelectedBtn.addEventListener('click', function() {
      if (selectedItems.size === 0) return;
      
      if (selectedItems.size === 1) {
        const itemId = Array.from(selectedItems)[0];
        const itemName = allStocks.find(stock => stock.item_id == itemId)?.item_name || 'this item';
        deleteConfirmMessage.textContent = `Are you sure you want to delete "${itemName}"? This action cannot be undone.`;
      } else {
        deleteConfirmMessage.textContent = `Are you sure you want to delete ${selectedItems.size} selected items? This action cannot be undone.`;
      }
      
      document.getElementById('delete-confirm-modal').checked = true;
    });
    
    // Confirm delete button event
    confirmDeleteBtn.addEventListener('click', function() {
      deleteSelectedItems();
    });
  }

  function applyFilter(filter) {
    if (filter === 'all') {
      filteredStocks = [...allStocks];
    } else {
      filteredStocks = allStocks.filter(stock => {
        // Convert both to lowercase for case-insensitive comparison
        const stockCategory = stock.category ? stock.category.toLowerCase() : '';
        const filterValue = filter.toLowerCase();
        
        return stockCategory.includes(filterValue);
      });
    }
    
    // Reset to first page when filtering
    currentPage = 1;
    updateDisplay();
  }

  function updateDisplay() {
    allCards = filteredStocks.map(stock => createCard(stock));
    renderPage(currentPage);
    setupPagination(filteredStocks.length);
    updateDeleteButton();
  }

  function createCard(stock) {
    const card = document.createElement('div');
    card.className = 'bg-white border border-gray-200 p-5 rounded-lg shadow hover:shadow-md transition-all flex flex-col gap-4';
    card.dataset.itemId = stock.item_id;
    
    // Determine stock status
    let status, statusClass;
    if (stock.quantity <= 0) {
      status = 'Out of Stock';
      statusClass = 'bg-red-100 text-red-700';
    } else if (stock.quantity <= stock.critical_level) {
      status = 'Low Stock';
      statusClass = 'bg-amber-100 text-amber-700';
    } else {
      status = 'In Stock';
      statusClass = 'bg-green-100 text-green-700';
    }
    
    // Check if expired
    const today = new Date();
    const expiryDate = new Date(stock.expiry_date);
    if (stock.expiry_date && expiryDate < today) {
      status = 'Expired';
      statusClass = 'bg-purple-100 text-purple-700';
    }
    
    const isSelected = selectedItems.has(stock.item_id.toString());
    
    card.innerHTML = `
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <input type="checkbox" class="checkbox checkbox-sm item-checkbox" ${isSelected ? 'checked' : ''} 
                 data-item-id="${stock.item_id}" />
          <h3 class="text-lg font-semibold text-gray-800 truncate">${stock.item_name}</h3>
        </div>
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full ${statusClass}">
          ${stock.quantity} ${stock.unit || 'pcs'}
        </span>
      </div>

      <div class="text-sm text-gray-600 space-y-1">
        <p><strong>Category:</strong> ${stock.category}</p>
        <p><strong>Critical Level:</strong> ${stock.critical_level}</p>
        <p><strong>Status:</strong> ${status}</p>
        ${stock.expiry_date ? `<p><strong>Expiry:</strong> ${new Date(stock.expiry_date).toLocaleDateString()}</p>` : ''}
      </div>

      <div class="mt-auto flex justify-between items-center">
        <button onclick="showStockDetails(${stock.item_id})"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 transition-colors">
          View Details
          <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
        <button class="text-red-600 hover:text-red-800 delete-item-btn" data-item-id="${stock.item_id}">
          <i data-lucide="trash-2" class="w-4 h-4"></i>
        </button>
      </div>
    `;

    // Add event listeners after creating the card
    setTimeout(() => {
      const checkbox = card.querySelector('.item-checkbox');
      const deleteBtn = card.querySelector('.delete-item-btn');
      
      if (checkbox) {
        checkbox.addEventListener('change', function() {
          toggleItemSelection(this.dataset.itemId, this.checked);
        });
      }
      
      if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          const itemId = this.dataset.itemId;
          const itemName = allStocks.find(stock => stock.item_id == itemId)?.item_name || 'this item';
          deleteConfirmMessage.textContent = `Are you sure you want to delete "${itemName}"? This action cannot be undone.`;
          document.getElementById('delete-confirm-modal').checked = true;
          
          // Set up single item deletion
          confirmDeleteBtn.onclick = function() {
            deleteItems([itemId]);
          };
        });
      }
    }, 0);

    return card;
  }

  function toggleItemSelection(itemId, isSelected) {
    if (isSelected) {
      selectedItems.add(itemId);
    } else {
      selectedItems.delete(itemId);
    }
    updateDeleteButton();
  }

  function updateDeleteButton() {
    if (selectedItems.size > 0) {
      deleteSelectedBtn.classList.remove('hidden');
      deleteSelectedBtn.querySelector('span').textContent = 
        `Delete Selected (${selectedItems.size})`;
    } else {
      deleteSelectedBtn.classList.add('hidden');
    }
  }

  function deleteSelectedItems() {
    const itemIds = Array.from(selectedItems);
    deleteItems(itemIds);
  }

  function deleteItems(itemIds) {
    // Show loading state
    confirmDeleteBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Deleting...';
    confirmDeleteBtn.disabled = true;
    
    fetch('../../M2/sub-modules/delete_stocks.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ item_ids: itemIds })
    })
    .then(res => res.json())
    .then(response => {
      if (response.status === 'success') {
        // Remove deleted items from all arrays
        allStocks = allStocks.filter(stock => !itemIds.includes(stock.item_id.toString()));
        filteredStocks = filteredStocks.filter(stock => !itemIds.includes(stock.item_id.toString()));
        
        // Clear selection
        itemIds.forEach(id => selectedItems.delete(id));
        
        // Update display
        updateDisplay();
        
        // Show success message
        showNotification(`${itemIds.length} item(s) deleted successfully`, 'success');
      } else {
        showNotification('Failed to delete items: ' + response.message, 'error');
      }
      
      // Close modal and reset button
      document.getElementById('delete-confirm-modal').checked = false;
      confirmDeleteBtn.innerHTML = 'Delete';
      confirmDeleteBtn.disabled = false;
    })
    .catch(err => {
      console.error('[Delete Error]', err);
      showNotification('Error deleting items', 'error');
      document.getElementById('delete-confirm-modal').checked = false;
      confirmDeleteBtn.innerHTML = 'Delete';
      confirmDeleteBtn.disabled = false;
    });
  }

  function renderPage(page) {
    currentPage = page;
    const start = (page - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentItems = allCards.slice(start, end);

    grid.innerHTML = '';
    
    if (currentItems.length === 0) {
      grid.innerHTML = `
        <div class="col-span-full text-center py-10">
          <i data-lucide="package-x" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
          <h3 class="text-lg font-medium text-gray-600">No items found</h3>
          <p class="text-gray-500">Try changing your filter criteria</p>
        </div>
      `;
    } else {
      currentItems.forEach(card => grid.appendChild(card));
    }

    lucide.createIcons();
  }

  function setupPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Remove existing pagination if any
    const existingPagination = document.getElementById('stock-pagination');
    if (existingPagination) {
      existingPagination.remove();
    }
    
    if (totalPages <= 1) return;
    
    const paginationContainer = document.createElement('div');
    paginationContainer.id = 'stock-pagination';
    paginationContainer.className = 'flex justify-center mt-6 gap-2';
    
    // Previous button
    const prevButton = document.createElement('button');
    prevButton.innerHTML = '<i data-lucide="chevron-left" class="w-4 h-4"></i>';
    prevButton.className = 'p-2 rounded-md border border-gray-300 hover:bg-gray-100 disabled:opacity-50';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
        updatePaginationButtons();
      }
    });
    
    paginationContainer.appendChild(prevButton);
    
    // Page buttons
    for (let i = 1; i <= totalPages; i++) {
      const pageButton = document.createElement('button');
      pageButton.textContent = i;
      pageButton.className = `px-3 py-2 rounded-md border ${currentPage === i ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-300 hover:bg-gray-100'}`;
      pageButton.addEventListener('click', () => {
        currentPage = i;
        renderPage(currentPage);
        updatePaginationButtons();
      });
      paginationContainer.appendChild(pageButton);
    }
    
    // Next button
    const nextButton = document.createElement('button');
    nextButton.innerHTML = '<i data-lucide="chevron-right" class="w-4 h-4"></i>';
    nextButton.className = 'p-2 rounded-md border border-gray-300 hover:bg-gray-100 disabled:opacity-50';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
      if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
        updatePaginationButtons();
      }
    });
    
    paginationContainer.appendChild(nextButton);
    
    grid.parentNode.appendChild(paginationContainer);
    
    function updatePaginationButtons() {
      prevButton.disabled = currentPage === 1;
      nextButton.disabled = currentPage === totalPages;
      
      // Update active page button
      const pageButtons = paginationContainer.querySelectorAll('button');
      pageButtons.forEach((button, index) => {
        if (index > 0 && index <= totalPages) {
          if (parseInt(button.textContent) === currentPage) {
            button.className = 'px-3 py-2 rounded-md border bg-blue-500 text-white border-blue-500';
          } else {
            button.className = 'px-3 py-2 rounded-md border border-gray-300 hover:bg-gray-100';
          }
        }
      });
    }
  }

  // Notification function
  function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `toast toast-top toast-end z-50`;
    notification.innerHTML = `
      <div class="alert ${type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info'} flex">
        <span>${message}</span>
        <button class="btn btn-sm btn-ghost" onclick="this.parentElement.parentElement.remove()">✕</button>
      </div>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
      if (notification.parentElement) {
        notification.remove();
      }
    }, 3000);
  }
  
  // Make notification function available globally
  window.showNotification = showNotification;
});

// ... (keep the existing showStockDetails function and other code)