<?php
/**
 * Admin Console: Canteen Inventory Management
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="space-y-8">
    
    <!-- Title Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Inventory Control</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Manage kitchen raw stock levels, set quick food counts, and toggle active catalog visibility</p>
        </div>
    </div>

    <!-- Search bar card -->
    <div class="premium-card p-4 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-stretch md:items-center justify-between">
        <div class="relative flex-grow max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-search text-xs"></i>
            </span>
            <input type="text" id="inventory-search" placeholder="Search food by name..." 
                   class="block w-full pl-9 pr-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-xs bg-slate-50 dark:bg-slate-900 transition-all">
        </div>
        <div class="text-xs text-slate-400 font-bold">
            Total active catalog: <span id="catalog-count" class="font-extrabold text-slate-800 dark:text-slate-100">0 items</span>
        </div>
    </div>

    <!-- Inventory table list -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-400 border-b border-slate-100 dark:border-slate-700/50 font-bold uppercase">
                        <th class="p-4">Dish Name</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Price</th>
                        <th class="p-4">Current Stock</th>
                        <th class="p-4">Availability status</th>
                        <th class="p-4">Last Restocked</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventory-tbody" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <!-- Skeletons -->
                    <tr><td colspan="7" class="p-4"><div class="skeleton w-full h-8 rounded"></div></td></tr>
                    <tr><td colspan="7" class="p-4"><div class="skeleton w-full h-8 rounded"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
let fullInventoryArray = [];

document.addEventListener('DOMContentLoaded', () => {
    loadInventoryData();

    // Bind local filter
    document.getElementById('inventory-search').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = fullInventoryArray.filter(item => item.name.toLowerCase().includes(query));
        renderInventoryTable(filtered);
    });
});

async function loadInventoryData() {
    try {
        const res = await apiRequest('/api/inventory.php', { method: 'GET' }, true);
        if (res.status === 'success') {
            fullInventoryArray = res.inventory;
            document.getElementById('catalog-count').textContent = fullInventoryArray.length + ' items';
            renderInventoryTable(fullInventoryArray);
        }
    } catch(err) {}
}

function renderInventoryTable(items) {
    const tbody = document.getElementById('inventory-tbody');
    
    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="p-12 text-center text-slate-400">No matching dishes in inventory.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(item => {
        let tagColor = 'bg-brand-50 text-brand-600 border-brand-100';
        if (item.stock_status === 'out_of_stock') {
            tagColor = 'bg-orange-50 text-orange-600 border-orange-100';
        } else if (item.stock_status === 'unavailable') {
            tagColor = 'bg-red-50 text-red-600 border-red-100';
        }

        const dateStr = item.last_restocked ? new Date(item.last_restocked).toLocaleString() : 'N/A';

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 text-slate-700 dark:text-slate-300 transition-colors">
                <td class="p-4 font-bold text-slate-800 dark:text-slate-100">
                    <span class="flex items-center space-x-2">
                        <i class="fas ${item.veg_nonveg === 'veg' ? 'fa-circle text-green-600' : 'fa-triangle-exclamation text-red-500'} text-[8px]"></i>
                        <span>${item.name}</span>
                    </span>
                </td>
                <td class="p-4">${item.category_name || 'MENU'}</td>
                <td class="p-4 font-bold">₹${parseFloat(item.price).toFixed(2)}</td>
                <td class="p-4">
                    <input type="number" id="stock-${item.food_id}" value="${item.current_stock ?? 0}" min="0" max="999" 
                           class="w-20 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-1 text-xs text-center font-bold">
                </td>
                <td class="p-4">
                    <select id="status-${item.food_id}" class="border border-slate-200 dark:border-slate-700 rounded-xl p-1 text-xs">
                        <option value="available" ${item.stock_status === 'available' ? 'selected' : ''}>Available</option>
                        <option value="out_of_stock" ${item.stock_status === 'out_of_stock' ? 'selected' : ''}>Out of Stock</option>
                        <option value="unavailable" ${item.stock_status === 'unavailable' ? 'selected' : ''}>Hidden / Off-Menu</option>
                    </select>
                </td>
                <td class="p-4 text-slate-400 text-[10px]">${dateStr}</td>
                <td class="p-4 text-right">
                    <button onclick="saveInventoryRow(${item.food_id})" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
                        Save
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

async function saveInventoryRow(foodId) {
    const qty = parseInt(document.getElementById('stock-' + foodId).value);
    const status = document.getElementById('status-' + foodId).value;

    if (isNaN(qty) || qty < 0) {
        showToast('Please enter a valid stock quantity.', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/api/inventory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ food_id: foodId, current_stock: qty, status: status })
        }, true);

        if (res.status === 'success') {
            showToast('Stock configurations updated.', 'success');
            loadInventoryData(); // Refresh list to update dates
        }
    } catch(err) {}
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
