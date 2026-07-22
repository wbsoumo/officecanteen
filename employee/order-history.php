<?php
/**
 * Employee Order History Page
 */
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header Title -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Your Orders History</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Search, track progress, download receipts, and reorder previous meals</p>
        </div>
    </div>

    <!-- Filters and Search panel -->
    <div class="premium-card p-4 mb-8 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-stretch md:items-center justify-between">
        
        <!-- Search bar -->
        <div class="relative flex-grow max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-search text-xs"></i>
            </span>
            <input type="text" id="history-search" placeholder="Search order reference..." 
                   class="block w-full pl-9 pr-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-xs bg-slate-50 dark:bg-slate-900 transition-all">
        </div>

        <!-- Filters group -->
        <div class="flex flex-wrap items-center gap-2">
            <select id="history-status" class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-xs bg-slate-50 dark:bg-slate-900">
                <option value="">All Statuses</option>
                <option value="active">Active Orders</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
            
            <button onclick="applyFilters()" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
                Apply Filters
            </button>
        </div>

    </div>

    <!-- Orders History List -->
    <div id="history-list" class="space-y-4">
        <!-- Skeleton Loaders -->
        <div class="skeleton w-full h-24 rounded-3xl"></div>
        <div class="skeleton w-full h-24 rounded-3xl"></div>
        <div class="skeleton w-full h-24 rounded-3xl"></div>
    </div>

    <!-- Pagination -->
    <div id="pagination-container" class="mt-8 flex justify-center space-x-2">
        <!-- Injected via JS -->
    </div>

</div>

<!-- Details & Receipt Print Modal -->
<div id="receipt-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-md w-full shadow-2xl border border-slate-100 dark:border-slate-700 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-200 p-6 relative">
        <button onclick="closeReceiptModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <i class="fas fa-times text-base"></i>
        </button>
        
        <div id="receipt-modal-content">
            <!-- Dynamically populated invoice receipt -->
        </div>

        <div class="mt-6 border-t border-slate-100 dark:border-slate-700/50 pt-4 flex space-x-3">
            <button onclick="printInvoice()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-colors">
                <i class="fas fa-print mr-1.5"></i>Print Receipt
            </button>
            <button onclick="closeReceiptModal()" class="flex-1 py-3 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    loadOrderHistory();
});

// Load orders via API
async function loadOrderHistory(page = 1) {
    currentPage = page;
    const searchVal = document.getElementById('history-search').value;
    const statusVal = document.getElementById('history-status').value;
    
    const container = document.getElementById('history-list');
    container.innerHTML = `
        <div class="skeleton w-full h-24 rounded-3xl"></div>
        <div class="skeleton w-full h-24 rounded-3xl"></div>
    `;

    try {
        const res = await apiRequest(`/api/orders.php?page=${page}&limit=10&search=${encodeURIComponent(searchVal)}&status=${statusVal}`);
        if (res.status === 'success') {
            renderHistoryList(res.orders);
            renderPagination(res.pagination);
        }
    } catch (err) {}
}

function applyFilters() {
    loadOrderHistory(1);
}

// Render historical items
function renderHistoryList(orders) {
    const container = document.getElementById('history-list');
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="premium-card p-12 text-center flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                <i class="fas fa-calendar-times text-4xl mb-3"></i>
                <p class="text-sm font-bold">No orders matched your filters</p>
                <p class="text-xs mt-1">Try resetting the status filter or search parameters</p>
            </div>
        `;
        return;
    }

    container.innerHTML = orders.map(order => {
        let tagColor = 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500';
        if (order.status === 'cancelled') {
            tagColor = 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-500';
        } else if (order.status === 'received') {
            tagColor = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        } else if (order.status === 'preparing') {
            tagColor = 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-500';
        } else if (order.status === 'ready') {
            tagColor = 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-500';
        }

        return `
            <div class="premium-card p-5 sm:p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex-grow min-w-0">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-xs font-bold text-slate-400">${new Date(order.created_at).toLocaleDateString([], {day:'2-digit', month:'short', year:'numeric'})}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${tagColor}">
                            ${order.status.replace('_', ' ')}
                        </span>
                    </div>
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">Ref: ${order.order_number}</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Floor ${order.floor} &bull; Cabin ${order.cabin || 'N/A'} &bull; Total Paid: ₹${parseFloat(order.grand_total).toFixed(2)}
                    </p>
                </div>

                <div class="flex items-center space-x-2.5 flex-shrink-0 w-full sm:w-auto">
                    <button onclick="reorderMeal(${order.id})" class="flex-1 sm:flex-initial px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-all transform active:scale-95">
                        <i class="fas fa-rotate-right mr-1"></i>Reorder
                    </button>
                    <button onclick="openReceiptModal(${order.id})" class="flex-grow sm:flex-initial px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition-colors">
                        <i class="fas fa-receipt mr-1"></i>Receipt
                    </button>
                    ${['delivered', 'cancelled'].includes(order.status) ? '' : `
                        <a href="/employee/track.php?id=${order.id}" class="px-4 py-2.5 bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-500 text-xs font-bold rounded-xl transition-colors">
                            <i class="fas fa-location-crosshairs"></i>
                        </a>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

// Render pagination buttons
function renderPagination(paging) {
    const container = document.getElementById('pagination-container');
    if (paging.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let buttons = '';
    for (let i = 1; i <= paging.total_pages; i++) {
        buttons += `
            <button onclick="loadOrderHistory(${i})" class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all ${paging.current_page === i ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50'}">
                ${i}
            </button>
        `;
    }
    container.innerHTML = buttons;
}

// Reorder helper
async function reorderMeal(orderId) {
    const confirm = await showConfirm('Reorder Meal', 'Add these exact items to your cart and place order?', 'Copy & Order');
    if (!confirm) return;

    try {
        const res = await apiRequest('/api/order-details.php?id=' + orderId, { method: 'GET' }, true);
        if (res.status === 'success') {
            const items = res.items.map(item => ({
                food_id: item.food_id,
                quantity: item.quantity,
                special_notes: item.special_notes
            }));

            const profileRes = await apiRequest('/api/profile.php', { method: 'GET' });
            const profile = profileRes.profile;

            const orderData = {
                floor: profile.floor || 1,
                cabin: profile.cabin || '',
                desk_number: profile.desk_number || '',
                delivery_date: new Date().toISOString().split('T')[0],
                delivery_time: '13:00:00',
                special_instructions: 'Reordered from history',
                is_agreed: 1,
                csrf_token: '<?php echo get_csrf_token(); ?>',
                items: items
            };

            const orderRes = await apiRequest('/api/place-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            }, true);

            if (orderRes.status === 'success') {
                showToast('Order Placed successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/employee/track.php?id=' + orderRes.order_id;
                }, 1000);
            }
        }
    } catch (err) {}
}

// Open invoice details
async function openReceiptModal(orderId) {
    try {
        const res = await apiRequest('/api/order-details.php?id=' + orderId, { method: 'GET' }, true);
        if (res.status === 'success') {
            const order = res.order;
            const items = res.items;
            const content = document.getElementById('receipt-modal-content');

            content.innerHTML = `
                <!-- Invoice design -->
                <div class="text-center pb-4 border-b border-slate-100 dark:border-slate-700/50 mb-4">
                    <h2 class="text-lg font-black text-slate-800 dark:text-white">PixelTech Canteen</h2>
                    <p class="text-[10px] text-slate-400">GSTIN: 09AAPCS1023B1ZS &bull; Canteen Receipt</p>
                </div>

                <div class="space-y-2 text-xs mb-4">
                    <div class="flex justify-between"><span class="text-slate-400">Invoice No:</span><span class="font-bold text-slate-800 dark:text-slate-200">${order.order_number}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Date/Time:</span><span class="font-bold text-slate-800 dark:text-slate-200">${new Date(order.created_at).toLocaleString()}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Location:</span><span class="font-bold text-slate-800 dark:text-slate-200">Floor ${order.floor}, Cabin ${order.cabin || 'N/A'}, Desk ${order.desk_number || 'N/A'}</span></div>
                    <div class="flex justify-between"><span class="text-slate-400">Employee:</span><span class="font-bold text-slate-800 dark:text-slate-200">${order.employee_name} (${order.emp_code})</span></div>
                </div>

                <table class="w-full text-xs mb-4">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 text-left text-slate-400"><th class="pb-2">Item</th><th class="pb-2 text-center">Qty</th><th class="pb-2 text-right">Price</th></tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr class="border-b border-slate-50 dark:border-slate-700/30 text-slate-700 dark:text-slate-300">
                                <td class="py-2 font-bold">${item.food_name}</td>
                                <td class="py-2 text-center font-bold">${item.quantity}</td>
                                <td class="py-2 text-right font-black">₹${(item.price * item.quantity).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="space-y-1.5 text-xs border-t border-slate-100 dark:border-slate-700/50 pt-3">
                    <div class="flex justify-between text-slate-400"><span>Subtotal:</span><span>₹${parseFloat(order.subtotal).toFixed(2)}</span></div>
                    <div class="flex justify-between text-slate-400"><span>GST (5%):</span><span>₹${parseFloat(order.gst).toFixed(2)}</span></div>
                    <div class="flex justify-between font-black text-slate-800 dark:text-white text-sm border-t border-slate-200 dark:border-slate-700 pt-1.5">
                        <span>Grand Total:</span><span>₹${parseFloat(order.grand_total).toFixed(2)}</span>
                    </div>
                </div>
            `;

            const modal = document.getElementById('receipt-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
            }, 10);
        }
    } catch (err) {}
}

function closeReceiptModal() {
    const modal = document.getElementById('receipt-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function printInvoice() {
    const originalContent = document.body.innerHTML;
    const printContent = document.getElementById('receipt-modal-content').innerHTML;

    document.body.innerHTML = `
        <div style="padding: 40px; font-family: sans-serif; max-width: 400px; margin: 0 auto;">
            ${printContent}
        </div>
    `;
    window.print();
    // restore original view after print
    document.body.innerHTML = originalContent;
    window.location.reload();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
