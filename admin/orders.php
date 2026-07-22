<?php
/**
 * Admin Console: Orders Queue Management
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="space-y-8">
    <!-- Header title -->
    <div class="flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Orders Queue</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Live feed of client requests. Control statuses, assign priorities, and print invoices</p>
        </div>
        
        <button onclick="exportOrdersToCSV()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-colors">
            <i class="fas fa-file-csv mr-1.5 text-brand-600"></i>Export Orders CSV
        </button>
    </div>

    <!-- Live Filters bar -->
    <div class="premium-card p-4 flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4 items-stretch lg:items-center justify-between">
        
        <!-- Search bar -->
        <div class="relative flex-grow max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-search text-xs"></i>
            </span>
            <input type="text" id="order-search" placeholder="Search Order ID, Name, Department..." 
                   class="block w-full pl-9 pr-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-xs bg-slate-50 dark:bg-slate-900 transition-all">
        </div>

        <!-- Status Filter tabs -->
        <div class="flex overflow-x-auto gap-1 pb-1 lg:pb-0 scrollbar-none">
            <button onclick="filterQueueStatus('')" id="tab-all" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-brand-600 text-white transition-colors">All</button>
            <button onclick="filterQueueStatus('received')" id="tab-received" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Received</button>
            <button onclick="filterQueueStatus('confirmed')" id="tab-confirmed" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Confirmed</button>
            <button onclick="filterQueueStatus('preparing')" id="tab-preparing" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Preparing</button>
            <button onclick="filterQueueStatus('ready')" id="tab-ready" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Ready</button>
            <button onclick="filterQueueStatus('out_of_delivery')" id="tab-out_of_delivery" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Out For Delivery</button>
            <button onclick="filterQueueStatus('delivered')" id="tab-delivered" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Delivered</button>
            <button onclick="filterQueueStatus('cancelled')" id="tab-cancelled" class="px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancelled</button>
        </div>

    </div>

    <!-- Live Orders table -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-400 border-b border-slate-100 dark:border-slate-700 font-bold uppercase">
                        <th class="p-4">Order Ref</th>
                        <th class="p-4">Employee</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">Location</th>
                        <th class="p-4">Delivery Time</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="orders-tbody" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <!-- Skeletons -->
                    <tr><td colspan="8" class="p-4"><div class="skeleton w-full h-8 rounded"></div></td></tr>
                    <tr><td colspan="8" class="p-4"><div class="skeleton w-full h-8 rounded"></div></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Empty indicator -->
        <div id="empty-queue-container" class="hidden text-center py-12 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
            <i class="fas fa-list-check text-4xl mb-3"></i>
            <p class="text-sm font-bold">No active orders in this queue filter</p>
        </div>

        <!-- Pagination footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-700/20 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <span id="queue-pagination-info" class="text-xs text-slate-400">Showing page 1 of 1</span>
            <div id="queue-pagination" class="flex space-x-1.5">
                <!-- pagination buttons -->
            </div>
        </div>
    </div>

</div>

<script>
let currentStatusFilter = '';
let currentSearchQuery = '';
let currentQueuePage = 1;
let loadedOrdersArray = [];

document.addEventListener('DOMContentLoaded', () => {
    loadOrdersQueue();

    // Bind Search Input
    document.getElementById('order-search').addEventListener('input', (e) => {
        currentSearchQuery = e.target.value;
        loadOrdersQueue(1);
    });
});

async function loadOrdersQueue(page = 1) {
    currentQueuePage = page;
    const tbody = document.getElementById('orders-tbody');
    const emptyContainer = document.getElementById('empty-queue-container');

    try {
        const res = await apiRequest(`/api/orders.php?page=${page}&limit=15&status=${currentStatusFilter}&search=${encodeURIComponent(currentSearchQuery)}`);
        
        if (res.status === 'success') {
            loadedOrdersArray = res.orders;

            if (res.orders.length === 0) {
                tbody.innerHTML = '';
                emptyContainer.classList.remove('hidden');
                document.getElementById('queue-pagination-info').textContent = 'Showing 0 items';
                document.getElementById('queue-pagination').innerHTML = '';
                return;
            }

            emptyContainer.classList.add('hidden');
            renderQueueTable(res.orders);
            renderQueuePagination(res.pagination);
        }
    } catch(err) {}
}

function filterQueueStatus(status) {
    currentStatusFilter = status;
    
    // Toggle active classes on tabs
    const allStatuses = ['', 'received', 'confirmed', 'preparing', 'ready', 'out_of_delivery', 'delivered', 'cancelled'];
    allStatuses.forEach(s => {
        const btnId = s === '' ? 'tab-all' : 'tab-' + s;
        const btn = document.getElementById(btnId);
        if (s === status) {
            btn.className = "px-3.5 py-2 text-xs font-bold rounded-xl bg-brand-600 text-white transition-colors";
        } else {
            btn.className = "px-3.5 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors";
        }
    });

    loadOrdersQueue(1);
}

function renderQueueTable(orders) {
    const tbody = document.getElementById('orders-tbody');
    
    tbody.innerHTML = orders.map(order => {
        let tagColor = 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500';
        if (order.status === 'cancelled') {
            tagColor = 'bg-red-50 text-red-600 dark:bg-red-500/10';
        } else if (order.status === 'received') {
            tagColor = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        } else if (order.status === 'preparing') {
            tagColor = 'bg-orange-50 text-orange-600 dark:bg-orange-500/10';
        } else if (order.status === 'ready') {
            tagColor = 'bg-amber-50 text-amber-600 dark:bg-amber-500/10';
        }

        // Map status buttons
        let actionsHtml = `<button onclick="viewOrderDetails(${order.id})" class="px-2.5 py-1.5 bg-slate-150 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 text-slate-700 text-[10px] font-bold rounded-lg shadow-sm"><i class="fas fa-eye mr-1"></i>View</button>`;
        
        let updateBtn = '';
        if (order.status === 'received') {
            updateBtn = `<button onclick="updateOrderStatus(${order.id}, 'confirmed')" class="ml-1.5 px-2.5 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-bold rounded-lg shadow">Confirm</button>`;
        } else if (order.status === 'confirmed') {
            updateBtn = `<button onclick="updateOrderStatus(${order.id}, 'preparing')" class="ml-1.5 px-2.5 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-[10px] font-bold rounded-lg shadow">Prepare</button>`;
        } else if (order.status === 'preparing') {
            updateBtn = `<button onclick="updateOrderStatus(${order.id}, 'ready')" class="ml-1.5 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold rounded-lg shadow">Ready</button>`;
        } else if (order.status === 'ready') {
            updateBtn = `<button onclick="updateOrderStatus(${order.id}, 'out_of_delivery')" class="ml-1.5 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold rounded-lg shadow">Out Delivery</button>`;
        } else if (order.status === 'out_of_delivery') {
            updateBtn = `<button onclick="updateOrderStatus(${order.id}, 'delivered')" class="ml-1.5 px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold rounded-lg shadow">Deliver</button>`;
        }
        actionsHtml += updateBtn;

        // Add Cancel option for uncompleted
        if (!['delivered', 'cancelled'].includes(order.status)) {
            actionsHtml += `<button onclick="updateOrderStatus(${order.id}, 'cancelled')" class="ml-1.5 px-2 py-1.5 bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-500 text-[10px] font-bold rounded-lg">Cancel</button>`;
        }

        // Format time
        const delTime = new Date('1970-01-01T' + order.delivery_time + 'Z').toLocaleTimeString([], {
            hour: '2-digit', minute:'2-digit', timeZone: 'UTC'
        });

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 text-slate-700 dark:text-slate-300 transition-colors">
                <td class="p-4 font-bold text-slate-800 dark:text-slate-100">${order.order_number}</td>
                <td class="p-4 font-semibold">${order.employee_name} (${order.emp_code})</td>
                <td class="p-4">${order.department}</td>
                <td class="p-4">Floor ${order.floor}, Cab ${order.cabin || 'N/A'}, Dk ${order.desk_number || 'N/A'}</td>
                <td class="p-4 font-bold text-slate-600 dark:text-slate-400">${delTime}</td>
                <td class="p-4 font-black">₹${parseFloat(order.grand_total).toFixed(2)}</td>
                <td class="p-4"><span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider ${tagColor}">${order.status.replace('_', ' ')}</span></td>
                <td class="p-4 text-right flex justify-end items-center">${actionsHtml}</td>
            </tr>
        `;
    }).join('');
}

function renderQueuePagination(paging) {
    document.getElementById('queue-pagination-info').textContent = `Showing page ${paging.current_page} of ${paging.total_pages} (${paging.total_items} items)`;
    
    const container = document.getElementById('queue-pagination');
    if (paging.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let buttons = '';
    for (let i = 1; i <= paging.total_pages; i++) {
        buttons += `
            <button onclick="loadOrdersQueue(${i})" class="px-3 py-1 rounded-lg text-xs font-bold border ${paging.current_page === i ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-700 hover:bg-slate-50'}">
                ${i}
            </button>
        `;
    }
    container.innerHTML = buttons;
}

async function updateOrderStatus(orderId, nextStatus) {
    const confirm = await showConfirm('Update Order', `Are you sure you want to transition this order status to '${nextStatus}'?`, 'Yes, Transition');
    if (!confirm) return;

    try {
        const res = await apiRequest('/api/update-order-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: nextStatus })
        }, true);

        if (res.status === 'success') {
            showToast('Order transitioned to ' + nextStatus, 'success');
            loadOrdersQueue(currentQueuePage);
        }
    } catch(err) {}
}

/**
 * Export loaded queue to CSV format immediately on the client side
 */
function exportOrdersToCSV() {
    if (loadedOrdersArray.length === 0) {
        showToast('No orders loaded to export.', 'warning');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Order Number,Employee,Department,Location,Delivery Time,Grand Total,Status,Date Placed\r\n";

    loadedOrdersArray.forEach(order => {
        const loc = `Floor ${order.floor} Cabin ${order.cabin || ''} Desk ${order.desk_number || ''}`.replace(/,/g, ' ');
        const row = [
            order.order_number,
            order.employee_name,
            order.department,
            loc,
            order.delivery_time,
            order.grand_total,
            order.status,
            order.created_at
        ].join(",");
        csvContent += row + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `Canteen_Orders_Report_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('CSV Report Downloaded!', 'success');
}

let currentViewingOrderId = 0;

async function viewOrderDetails(orderId) {
    currentViewingOrderId = orderId;
    try {
        const res = await apiRequest(`/api/order-details.php?id=${orderId}`, {}, true);
        if (res.status === 'success') {
            const order = res.order;
            const items = res.items;

            document.getElementById('modal-order-number').textContent = `Order ${order.order_number}`;
            document.getElementById('modal-order-date').textContent = `Placed on ${new Date(order.created_at).toLocaleString()}`;
            
            document.getElementById('modal-employee-name').textContent = `${order.employee_name} (${order.emp_code})`;
            document.getElementById('modal-employee-contact').textContent = `${order.employee_email} | ${order.employee_phone}`;
            
            document.getElementById('modal-delivery-location').textContent = `Floor ${order.floor}, Cabin ${order.cabin || 'N/A'}, Desk ${order.desk_number || 'N/A'}`;
            
            const delTime = new Date('1970-01-01T' + order.delivery_time + 'Z').toLocaleTimeString([], {
                hour: '2-digit', minute:'2-digit', timeZone: 'UTC'
            });
            document.getElementById('modal-delivery-time').textContent = `Slot: ${delTime} on ${order.delivery_date}`;
            
            document.getElementById('modal-special-instructions').textContent = order.special_instructions || 'None';
            
            document.getElementById('modal-subtotal').textContent = `₹${parseFloat(order.subtotal).toFixed(2)}`;
            document.getElementById('modal-gst').textContent = `₹${parseFloat(order.gst).toFixed(2)}`;
            document.getElementById('modal-grand-total').textContent = `₹${parseFloat(order.grand_total).toFixed(2)}`;
            
            // Set current status badge
            let tagColor = 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500';
            if (order.status === 'cancelled') {
                tagColor = 'bg-red-50 text-red-600 dark:bg-red-500/10';
            } else if (order.status === 'received') {
                tagColor = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
            } else if (order.status === 'preparing') {
                tagColor = 'bg-orange-50 text-orange-600 dark:bg-orange-500/10';
            } else if (order.status === 'ready') {
                tagColor = 'bg-amber-50 text-amber-600 dark:bg-amber-500/10';
            }
            const statusBadge = document.getElementById('modal-current-status-badge');
            statusBadge.className = `px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider ${tagColor}`;
            statusBadge.textContent = order.status.replace('_', ' ');

            // Set current status select value
            document.getElementById('modal-status-select').value = order.status;

            // Render Items with Images
            const itemsContainer = document.getElementById('modal-items-container');
            itemsContainer.innerHTML = items.map(item => {
                const vegBadge = item.veg_nonveg === 'veg' 
                    ? '<span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-green-50 text-green-600 border border-green-200">Veg</span>' 
                    : '<span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-red-50 text-red-600 border border-red-200">Non-Veg</span>';
                
                return `
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center space-x-3">
                            <img src="${item.image_url}" alt="${item.food_name}" class="w-12 h-12 rounded-xl object-cover border border-slate-100 dark:border-slate-700 shadow-sm">
                            <div>
                                <div class="flex items-center space-x-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">${item.food_name}</span>
                                    ${vegBadge}
                                </div>
                                <span class="text-[10px] text-slate-400">Qty: ${item.quantity} × ₹${parseFloat(item.price).toFixed(2)}</span>
                                ${item.special_notes ? `<p class="text-[9px] text-amber-500 italic mt-0.5">Note: ${item.special_notes}</p>` : ''}
                            </div>
                        </div>
                        <span class="text-xs font-black text-slate-700 dark:text-slate-300">₹${parseFloat(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `;
            }).join('');

            // Show Modal
            const modal = document.getElementById('order-details-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
            }, 10);
        }
    } catch(err) {
        showToast('Failed to load order details', 'error');
    }
}

function closeOrderDetailsModal() {
    const modal = document.getElementById('order-details-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Bind modal status update button
document.getElementById('modal-status-update-btn').addEventListener('click', async () => {
    const select = document.getElementById('modal-status-select');
    const newStatus = select.value;
    
    // Call existing updateOrderStatus function
    await updateOrderStatus(currentViewingOrderId, newStatus);
    
    // Refresh modal info
    closeOrderDetailsModal();
});
</script>

<!-- Order Details Modal -->
<div id="order-details-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 dark:border-slate-700 transform scale-95 opacity-0 transition-all duration-200">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <div>
                <h3 class="text-base font-bold text-slate-800 dark:text-white" id="modal-order-number">Order Details</h3>
                <p class="text-[10px] text-slate-400 font-semibold" id="modal-order-date"></p>
            </div>
            <button onclick="closeOrderDetailsModal()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh]">
            <!-- Row: User and location info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Employee Details</span>
                    <p class="text-xs font-extrabold text-slate-800 dark:text-slate-100" id="modal-employee-name"></p>
                    <p class="text-[10px] text-slate-500" id="modal-employee-contact"></p>
                </div>
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Delivery Location & Slot</span>
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200" id="modal-delivery-location"></p>
                    <p class="text-[10px] text-slate-500" id="modal-delivery-time"></p>
                </div>
            </div>

            <!-- Ordered Items List -->
            <div>
                <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-2">Ordered Items</h4>
                <div class="divide-y divide-slate-100 dark:divide-slate-700/50" id="modal-items-container">
                    <!-- Dynamic rows -->
                </div>
            </div>

            <!-- Special instructions & Totals -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                <div class="space-y-1">
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Special Instructions</span>
                    <p class="text-xs italic text-slate-500 bg-slate-50 dark:bg-slate-900/30 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800" id="modal-special-instructions">None</p>
                </div>
                <div class="space-y-1.5 bg-slate-50 dark:bg-slate-900/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>Subtotal</span>
                        <span id="modal-subtotal">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>GST (5%)</span>
                        <span id="modal-gst">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-xs font-black text-slate-800 dark:text-slate-100 pt-1.5 border-t border-slate-200 dark:border-slate-700">
                        <span>Grand Total</span>
                        <span id="modal-grand-total">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer status updater -->
        <div class="p-6 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-900/30 rounded-b-3xl flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-500">Status:</span>
                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider" id="modal-current-status-badge"></span>
            </div>
            
            <div class="flex items-center space-x-2" id="modal-status-update-container">
                <select id="modal-status-select" class="text-xs border border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-850 focus:outline-none">
                    <option value="received">Received</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="out_of_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button id="modal-status-update-btn" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
                    Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
