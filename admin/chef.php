<?php
/**
 * Chef Portal: Kitchen Hub View (Large cooking cards)
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="space-y-8">
    <!-- Header title -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Kitchen Hub</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Chef active workstation. Focus on pending items, read cooking instructions, and mark meals ready</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-bold text-slate-400">Auto-refresh: </span>
            <span class="px-2 py-0.5 rounded bg-brand-50 text-brand-600 font-extrabold text-[10px] tracking-wider">ACTIVE (10s)</span>
        </div>
    </div>

    <!-- Active cards grid -->
    <div id="chef-cards-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Loaded dynamically -->
        <div class="skeleton w-full h-64 rounded-3xl"></div>
        <div class="skeleton w-full h-64 rounded-3xl"></div>
        <div class="skeleton w-full h-64 rounded-3xl"></div>
    </div>

    <!-- Empty queue indicators -->
    <div id="chef-empty-queue" class="hidden text-center py-20 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl mb-4">
            <i class="fas fa-kitchen-set"></i>
        </div>
        <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">All caught up! No orders to cook</h3>
        <p class="text-xs mt-1">Sit back. Incoming orders will populate here automatically</p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadChefWorkstation();

    // Poll orders every 10 seconds
    const chefPoller = setInterval(loadChefWorkstation, 10000);
    window.addEventListener('beforeunload', () => clearInterval(chefPoller));
});

async function loadChefWorkstation() {
    try {
        // Fetch all active orders (received, confirmed, preparing, ready, out_of_delivery)
        const res = await apiRequest('/api/orders.php?status=active');
        
        if (res.status === 'success') {
            renderChefCards(res.orders);
        }
    } catch (err) {}
}

async function renderChefCards(orders) {
    const grid = document.getElementById('chef-cards-grid');
    const emptyContainer = document.getElementById('chef-empty-queue');

    if (orders.length === 0) {
        grid.innerHTML = '';
        emptyContainer.classList.remove('hidden');
        return;
    }

    emptyContainer.classList.add('hidden');
    
    // Fetch details for each order to list items on the cards
    const cardsHtml = await Promise.all(orders.map(async (order) => {
        try {
            const details = await apiRequest('/api/order-details.php?id=' + order.id);
            if (details.status === 'success') {
                const items = details.items;
                
                // Priority tagging based on delivery time vs current time
                const [h, m, s] = order.delivery_time.split(':');
                const deliveryDate = new Date(order.delivery_date);
                deliveryDate.setHours(h, m, s);
                
                const timeDiffMins = Math.round((deliveryDate - new Date()) / 60000);
                
                let priorityText = 'Normal';
                let priorityClass = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
                
                if (timeDiffMins <= 15) {
                    priorityText = 'CRITICAL';
                    priorityClass = 'bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400 animate-pulse';
                } else if (timeDiffMins <= 30) {
                    priorityText = 'HIGH';
                    priorityClass = 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400';
                }

                // Transition button configs
                let actionBtnHtml = '';
                if (order.status === 'received') {
                    actionBtnHtml = `<button onclick="chefTransition(${order.id}, 'confirmed')" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-2xl shadow transition-colors">Acknowledge</button>`;
                } else if (order.status === 'confirmed') {
                    actionBtnHtml = `<button onclick="chefTransition(${order.id}, 'preparing')" class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold rounded-2xl shadow transition-colors">Start Preparing</button>`;
                } else if (order.status === 'preparing') {
                    actionBtnHtml = `<button onclick="chefTransition(${order.id}, 'ready')" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-2xl shadow transition-colors">Mark Ready</button>`;
                } else if (order.status === 'ready') {
                    actionBtnHtml = `<button onclick="chefTransition(${order.id}, 'out_of_delivery')" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-2xl shadow transition-colors">Dispatch Delivery</button>`;
                } else if (order.status === 'out_of_delivery') {
                    actionBtnHtml = `<button onclick="chefTransition(${order.id}, 'delivered')" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-2xl shadow transition-colors">Mark Delivered</button>`;
                }

                // Format Time due
                const dueTime = new Date('1970-01-01T' + order.delivery_time + 'Z').toLocaleTimeString([], {
                    hour: '2-digit', minute:'2-digit', timeZone: 'UTC'
                });

                return `
                    <div class="premium-card p-5 bg-white dark:bg-slate-800 flex flex-col justify-between space-y-4">
                        <div>
                            <!-- Header: Order Ref & Priority -->
                            <div class="flex justify-between items-center mb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Order Ref</span>
                                    <span class="text-sm font-extrabold text-slate-800 dark:text-slate-100">${order.order_number}</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-xl text-[9px] font-extrabold tracking-wider uppercase ${priorityClass}">
                                    ${priorityText}
                                </span>
                            </div>

                            <!-- Details details -->
                            <div class="text-[10px] text-slate-400 space-y-1 py-2 border-y border-slate-100 dark:border-slate-700/50">
                                <div><i class="fas fa-location-dot mr-1 text-brand-600"></i>Floor ${order.floor}, Cab ${order.cabin || 'N/A'}, Dk ${order.desk_number || 'N/A'}</div>
                                <div><i class="fas fa-clock mr-1 text-amber-500"></i>Due By: <span class="font-bold text-slate-700 dark:text-slate-300">${dueTime}</span></div>
                            </div>

                            <!-- Cooking Items List (Highlighting qty) -->
                            <div class="space-y-2 mt-4">
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Dishes to Prepare</h4>
                                ${items.map(item => `
                                    <div class="flex justify-between items-start text-xs border-b border-slate-50 dark:border-slate-700/20 pb-1">
                                        <span class="font-extrabold text-slate-800 dark:text-slate-200">
                                            ${item.food_name} <span class="ml-1 px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 font-extrabold text-[10px]">x${item.quantity}</span>
                                        </span>
                                        ${item.special_notes ? `
                                            <span class="text-[9px] font-semibold text-red-500 italic block mt-0.5 max-w-[120px] truncate" title="${item.special_notes}">* ${item.special_notes}</span>
                                        ` : ''}
                                    </div>
                                `).join('')}
                            </div>

                            <!-- Special Instructions -->
                            ${order.special_instructions ? `
                                <div class="mt-4 p-2.5 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl text-[10px] text-red-700 dark:text-red-400 font-semibold leading-relaxed">
                                    <i class="fas fa-triangle-exclamation mr-1.5"></i>Instructions: ${order.special_instructions}
                                </div>
                            ` : ''}
                        </div>

                        <!-- Action transition CTA -->
                        <div class="pt-4 border-t border-slate-50 dark:border-slate-700/50">
                            ${actionBtnHtml}
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            return '';
        }
    }));

    grid.innerHTML = cardsHtml.join('');
}

async function chefTransition(orderId, nextStatus) {
    try {
        const res = await apiRequest('/api/update-order-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: nextStatus })
        }, true);

        if (res.status === 'success') {
            showToast('Order transitioned to ' + nextStatus, 'success');
            loadChefWorkstation();
        }
    } catch(err) {}
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
