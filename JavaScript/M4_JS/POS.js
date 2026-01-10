// Initialize Lucide icons
lucide.createIcons();

// Current order state
let currentOrder = {
    tableId: null,
    tableName: null,
    customer: null,
    customerNotes: 'No special requests',
    items: [],
    notes: '',
    paymentMethod: 'cash',
    subtotal: 0,
    serviceCharge: 0,
    tax: 0,
    total: 0,
    lastUpdated: null
};

// --- Helpers ---
function getEmptyCartMessage() {
    let message = document.getElementById('empty-cart-message');
    if (!message) {
        message = document.createElement('div');
        message.id = 'empty-cart-message';
        message.textContent = 'Your cart is empty.';
        message.className = 'empty-cart-message text-center p-4 text-gray-500';
    }
    return message;
}

function saveOrderToStorage() {
    currentOrder.lastUpdated = Date.now();
    localStorage.setItem('solieraCurrentOrder', JSON.stringify(currentOrder));
}

function loadOrderFromStorage() {
    const saved = localStorage.getItem('solieraCurrentOrder');
    if (!saved) return;

    const parsed = JSON.parse(saved);
    const fourHoursAgo = Date.now() - (4 * 60 * 60 * 1000);
    if (parsed.lastUpdated && parsed.lastUpdated > fourHoursAgo) {
        currentOrder = parsed;
        updateOrderDisplay();
        checkOrderReady();

        if (currentOrder.tableId) {
            document.getElementById('current-table-badge').textContent = currentOrder.tableName;
            highlightSelectedTable();
        }
    } else {
        localStorage.removeItem('solieraCurrentOrder');
    }
}

function highlightSelectedTable() {
    document.querySelectorAll('.table-card').forEach(card => {
        card.classList.toggle('selected', currentOrder.tableName && card.textContent.includes(currentOrder.tableName));
    });
}

function updateTotals() {
    currentOrder.subtotal = currentOrder.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
    currentOrder.serviceCharge = currentOrder.subtotal * 0.1;
    currentOrder.tax = currentOrder.subtotal * 0.12;
    currentOrder.total = currentOrder.subtotal + currentOrder.serviceCharge + currentOrder.tax;

    ['subtotal','service-charge','tax','total'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.textContent = `₱ ${currentOrder[id.replace('-','')]?.toFixed(2)}`;
    });

    const totalInput = document.getElementById('total-amount-input');
    const tableInput = document.getElementById('table-id-input');
    const mopInput = document.getElementById('mop-input');
    const itemsInput = document.getElementById('order-items-json');

    if(totalInput) totalInput.value = currentOrder.total.toFixed(2);
    if(tableInput) tableInput.value = currentOrder.tableId || '';
    if(mopInput) mopInput.value = currentOrder.paymentMethod || 'cash';
    if(itemsInput) itemsInput.value = JSON.stringify(currentOrder.items);
}

function checkOrderReady() {
    const btn = document.getElementById('submit-order-btn');
    if(btn) btn.disabled = currentOrder.items.length === 0 || currentOrder.tableId === null;
}

// --- Table & Item Handling ---
function selectTable(id, name) {
    currentOrder.tableId = id;
    currentOrder.tableName = name;
    const badge = document.getElementById('current-table-badge');
    if(badge) badge.textContent = name;
    highlightSelectedTable();

    const tableModal = document.getElementById('table-modal');
    if(tableModal?.close) tableModal.close();

    checkOrderReady();
    saveOrderToStorage();
}

function addToOrder(menuId, name, price, qty = 1) {
    const idx = currentOrder.items.findIndex(i => i.menuId === menuId);
    if(idx >= 0) currentOrder.items[idx].quantity += qty;
    else currentOrder.items.push({menuId, name, price, quantity: qty});

    updateOrderDisplay();
    checkOrderReady();
    saveOrderToStorage();
    showToast(`Added ${qty} × ${name} to order`);
}

function updateItemQuantity(index, change) {
    const newQty = currentOrder.items[index].quantity + change;
    if(newQty < 1) removeFromOrder(index);
    else {
        currentOrder.items[index].quantity = newQty;
        updateOrderDisplay();
        saveOrderToStorage();
    }
    checkOrderReady();
}

function removeFromOrder(index) {
    currentOrder.items.splice(index, 1);
    updateOrderDisplay();
    checkOrderReady();
    saveOrderToStorage();
}

function updateOrderDisplay() {
    const container = document.getElementById('order-items-container');
    if(!container) return;

    const emptyMsg = getEmptyCartMessage();

    if(currentOrder.items.length === 0) {
        container.innerHTML = '';
        container.appendChild(emptyMsg);
        document.getElementById('item-count').textContent = '0';
        updateTotals();
        return;
    }

    if(emptyMsg.parentNode === container) emptyMsg.remove();

    let html = '', count = 0;
    currentOrder.items.forEach((i, idx) => {
        count += i.quantity;
        html += `
            <div class="order-item bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium truncate">${i.name}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <button class="btn btn-xs btn-circle btn-ghost" onclick="updateItemQuantity(${idx}, -1)">
                            <i data-lucide="minus" class="w-3 h-3"></i>
                        </button>
                        <span class="text-sm text-gray-500">${i.quantity}</span>
                        <button class="btn btn-xs btn-circle btn-ghost" onclick="updateItemQuantity(${idx}, 1)">
                            <i data-lucide="plus" class="w-3 h-3"></i>
                        </button>
                        <span class="text-sm text-gray-500">× ₱ ${i.price.toFixed(2)}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-medium">₱ ${(i.price*i.quantity).toFixed(2)}</span>
                    <button class="btn btn-xs btn-circle btn-ghost text-red-500" onclick="removeFromOrder(${idx})">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
    const itemCount = document.getElementById('item-count');
    if(itemCount) itemCount.textContent = count;
    updateTotals();
    lucide.createIcons();
}

// --- Customer & Payment ---
document.addEventListener('DOMContentLoaded', () => {
    loadOrderFromStorage();
    updateTime();
    setInterval(updateTime, 1000);

    const nameInput = document.getElementById('customer-name-input');
    if(nameInput) {
        if(currentOrder.customer) nameInput.value = currentOrder.customer;
        nameInput.addEventListener('input', e => {
            currentOrder.customer = e.target.value.trim();
            saveOrderToStorage();
        });
    }
});

function updateTime() {
    const el = document.getElementById('current-time');
    if(el) el.textContent = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}

document.querySelectorAll('.payment-method').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentOrder.paymentMethod = this.dataset.method;
        saveOrderToStorage();
    });
});

// --- Clear / Reset ---
document.getElementById('clear-order-btn')?.addEventListener('click', () => {
    Swal.fire({
        title: 'Clear Order?',
        text: "This will remove all items from the current order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, clear it!'
    }).then(result => { if(result.isConfirmed) { resetOrder(); Swal.fire('Cleared!', 'Your order has been cleared.', 'success'); }});
});

function resetOrder() {
    currentOrder = {
        tableId: null, tableName: null, customer: 'Walk-in Customer', customerNotes: 'No special requests',
        items: [], notes:'', paymentMethod:'cash', subtotal:0, serviceCharge:0, tax:0, total:0, lastUpdated:null
    };
    localStorage.removeItem('solieraCurrentOrder');
    document.getElementById('current-table-badge').textContent = 'No Table Selected';
    document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
    document.querySelector('.payment-method[data-method="cash"]')?.classList.add('active');
    const input = document.getElementById('customer-name-input'); if(input) input.value='';
    const notes = document.getElementById('order-notes'); if(notes) notes.value='';
    highlightSelectedTable();
    updateOrderDisplay();
    checkOrderReady();
}

// --- Toast Helper ---
function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast toast-top toast-center';
    t.innerHTML = `<div class="alert alert-success animate__animated animate__bounceIn"><span>${msg}</span></div>`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(),2000);
}

// --- Sync across tabs ---
window.addEventListener('storage', e => { if(e.key==='solieraCurrentOrder') loadOrderFromStorage(); });
