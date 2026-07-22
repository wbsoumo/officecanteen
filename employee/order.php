<?php
/**
 * Employee Food Ordering Page
 */
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
    
    <!-- Top Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-8">
        <!-- Search Input -->
        <div class="relative flex-grow max-w-lg">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-search text-base"></i>
            </span>
            <input type="text" id="food-search" placeholder="Search delicious food..." 
                   class="block w-full pl-10 pr-4 py-3 border border-slate-200 dark:border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-sm bg-white dark:bg-slate-800 transition-all">
        </div>
        
        <!-- Toggle Veg/Non-Veg -->
        <div class="flex space-x-2 bg-white dark:bg-slate-800 p-1 rounded-2xl border border-slate-100 dark:border-slate-700/50">
            <button onclick="filterType('all')" id="type-all" class="px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 text-white transition-colors">All</button>
            <button onclick="filterType('veg')" id="type-veg" class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Veg Only</button>
            <button onclick="filterType('nonveg')" id="type-nonveg" class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Non-Veg</button>
        </div>
    </div>

    <!-- Category Chips Horizontal Slider -->
    <div class="mb-8">
        <h3 class="text-xs font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">Categories</h3>
        <div id="category-container" class="flex space-x-3 overflow-x-auto pb-3 -mx-4 px-4 sm:mx-0 sm:px-0">
            <!-- Loaded dynamically via JS -->
            <div class="skeleton w-24 h-10 rounded-full flex-shrink-0"></div>
            <div class="skeleton w-24 h-10 rounded-full flex-shrink-0"></div>
            <div class="skeleton w-24 h-10 rounded-full flex-shrink-0"></div>
            <div class="skeleton w-24 h-10 rounded-full flex-shrink-0"></div>
        </div>
    </div>

    <!-- Food Cards Grid -->
    <div id="food-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Skeleton loaders -->
        <?php for($i=0; $i<8; $i++): ?>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-4 border border-slate-100 dark:border-slate-700/50 flex flex-col justify-between space-y-4">
            <div class="skeleton w-full h-44 rounded-2xl"></div>
            <div class="space-y-2">
                <div class="skeleton w-2/3 h-5 rounded"></div>
                <div class="skeleton w-full h-4 rounded"></div>
                <div class="skeleton w-1/2 h-4 rounded"></div>
            </div>
            <div class="flex justify-between items-center">
                <div class="skeleton w-16 h-6 rounded"></div>
                <div class="skeleton w-24 h-10 rounded-xl"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

</div>

<!-- Food Details Modal -->
<div id="food-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 dark:border-slate-700 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-200 relative">
        <button onclick="closeFoodModal()" class="absolute top-4 right-4 z-10 w-9 h-9 bg-black/45 text-white hover:bg-black/60 rounded-full flex items-center justify-center transition-colors">
            <i class="fas fa-times"></i>
        </button>

        <div id="modal-content">
            <!-- Dynamic food details injected by JS -->
        </div>
    </div>
</div>

<!-- Floating Shopping Cart Button (Mobile view helper) -->
<button onclick="toggleCartDrawer()" class="fixed bottom-6 right-6 z-40 bg-brand-600 hover:bg-brand-700 text-white p-4 rounded-full shadow-2xl transition-all transform active:scale-95 flex items-center space-x-2 md:space-x-3 border border-brand-500">
    <div class="relative">
        <i class="fas fa-shopping-bag text-xl"></i>
        <span id="cart-floating-badge" class="absolute -top-2.5 -right-2.5 bg-amber-500 text-white font-extrabold text-[10px] w-5 h-5 rounded-full flex items-center justify-center border border-brand-600 hidden">0</span>
    </div>
    <span class="text-sm font-bold pr-1" id="cart-floating-total">₹0.00</span>
</button>

<!-- Slide-over Cart Drawer -->
<div id="cart-drawer" class="fixed inset-y-0 right-0 z-50 max-w-md w-full bg-white dark:bg-slate-800 shadow-2xl border-l border-slate-100 dark:border-slate-700 flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-in-out">
    <!-- Drawer Header -->
    <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50 dark:bg-slate-700/20">
        <div class="flex items-center space-x-2">
            <i class="fas fa-shopping-bag text-brand-600 text-lg"></i>
            <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">Your Order Cart</h2>
        </div>
        <button onclick="toggleCartDrawer()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <i class="fas fa-arrow-right text-base"></i>
        </button>
    </div>

    <!-- Drawer items list -->
    <div id="cart-items-container" class="flex-grow p-6 overflow-y-auto space-y-4">
        <!-- Rendered dynamically -->
        <div class="text-center py-12 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
            <i class="fas fa-basket-shopping text-4xl mb-3"></i>
            <p class="text-sm font-bold">Your cart is currently empty</p>
            <p class="text-xs mt-1">Add items from the menu to build your meal</p>
        </div>
    </div>

    <!-- Drawer Footer / Checkout summary -->
    <div class="p-6 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-700/20 space-y-4">
        <div class="space-y-2">
            <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400 font-medium">
                <span>Subtotal:</span>
                <span id="cart-subtotal">₹0.00</span>
            </div>
            <div class="flex justify-between text-sm text-slate-500 dark:text-slate-400 font-medium">
                <span>GST (5%):</span>
                <span id="cart-gst">₹0.00</span>
            </div>
            <div class="flex justify-between text-base text-slate-800 dark:text-white font-extrabold border-t border-slate-200 dark:border-slate-700 pt-2">
                <span>Grand Total:</span>
                <span id="cart-grandtotal">₹0.00</span>
            </div>
        </div>
        
        <button onclick="openCheckoutModal()" id="checkout-btn" disabled 
                class="w-full py-3.5 bg-brand-600 disabled:bg-slate-300 disabled:cursor-not-allowed hover:bg-brand-700 text-white font-bold rounded-2xl shadow-lg transition-colors flex justify-center items-center">
            Proceed to Checkout
        </button>
    </div>
</div>

<!-- Checkout Form Modal -->
<div id="checkout-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 dark:border-slate-700 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-200">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white"><i class="fas fa-truck-ramp-box text-brand-600 mr-2"></i>Checkout Details</h3>
            <button onclick="closeCheckoutModal()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="checkout-form" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

            <!-- Delivery Settings Group -->
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Floor *</label>
                    <input type="number" id="chk-floor" required min="1" max="10" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Cabin</label>
                    <input type="text" id="chk-cabin" placeholder="e.g. 402" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Desk Number</label>
                    <input type="text" id="chk-desk" placeholder="e.g. 402A" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm">
                </div>
            </div>

            <!-- Date and Time selector -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Date *</label>
                    <input type="date" id="chk-date" required 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Time *</label>
                    <select id="chk-time" required 
                            class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm">
                        <option value="12:30:00">12:30 PM (Lunch Slot A)</option>
                        <option value="13:00:00" selected>01:00 PM (Lunch Slot B)</option>
                        <option value="13:30:00">01:30 PM (Lunch Slot C)</option>
                        <option value="14:00:00">02:00 PM (Lunch Slot D)</option>
                        <option value="14:30:00">02:30 PM (Lunch Slot E)</option>
                    </select>
                </div>
            </div>

            <!-- Special instructions -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Special Instructions</label>
                <textarea id="chk-instructions" rows="2" placeholder="e.g. Make it extra spicy / No onions / Cutlery requested..." 
                          class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm"></textarea>
            </div>

            <!-- Payment selection -->
            <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl flex items-center justify-between border border-slate-100 dark:border-slate-700">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-hand-holding-dollar text-green-600 text-xl"></i>
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Cash on Delivery</p>
                        <p class="text-[10px] text-slate-400">Pay directly when food is delivered</p>
                    </div>
                </div>
                <input type="checkbox" checked disabled class="h-5 w-5 text-brand-600 rounded">
            </div>

            <!-- Terms selection -->
            <div class="flex items-center">
                <input type="checkbox" id="chk-agree" required class="h-4.5 w-4.5 text-brand-600 rounded">
                <label for="chk-agree" class="ml-2 block text-xs text-slate-500 font-semibold">I agree that this order will be delivered to my desk and I will pay Cash on Delivery.</label>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 flex space-x-3">
                <button type="button" onclick="closeCheckoutModal()" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-2xl transition-colors">
                    Back to Cart
                </button>
                <button type="submit" id="submit-order-btn" class="flex-1 py-3 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-lg transition-colors">
                    Confirm Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Frontend Global State
let foodsList = [];
let categoriesList = [];
let cart = [];
let selectedCategoryId = 0;
let selectedType = 'all';
let searchQuery = '';

document.addEventListener('DOMContentLoaded', () => {
    // Load local cart
    loadCart();
    // Load categories
    fetchCategories();
    // Load foods
    fetchFoods();

    // Bind search event
    document.getElementById('food-search').addEventListener('input', (e) => {
        searchQuery = e.target.value;
        renderFoods();
    });

    // Default checkout date to today
    document.getElementById('chk-date').value = new Date().toISOString().split('T')[0];

    // Prefill checkout details if profile settings exist
    fetchProfileDefaults();
});

// Load cart state from localStorage
function loadCart() {
    const saved = localStorage.getItem('canteen_cart');
    if (saved) {
        try {
            cart = JSON.parse(saved);
        } catch (e) {
            cart = [];
        }
    }
    updateCartUI();
}

// Save cart to storage
function saveCart() {
    localStorage.setItem('canteen_cart', JSON.stringify(cart));
    updateCartUI();
}

// Fetch categories from API
async function fetchCategories() {
    try {
        const res = await apiRequest('/api/categories.php');
        if (res.status === 'success') {
            categoriesList = res.categories;
            renderCategories();
        }
    } catch(e) {}
}

// Fetch foods from API
async function fetchFoods() {
    try {
        const res = await apiRequest('/api/get-foods.php');
        if (res.status === 'success') {
            foodsList = res.foods;
            renderFoods();
        }
    } catch(e) {}
}

// Fetch user defaults
async function fetchProfileDefaults() {
    try {
        const res = await apiRequest('/api/profile.php');
        if (res.status === 'success' && res.profile) {
            document.getElementById('chk-floor').value = res.profile.floor || 1;
            document.getElementById('chk-cabin').value = res.profile.cabin || '';
            document.getElementById('chk-desk').value = res.profile.desk_number || '';
        }
    } catch (e) {}
}

// Render horizontal category chips
function renderCategories() {
    const container = document.getElementById('category-container');
    container.innerHTML = `
        <button onclick="filterCategory(0)" class="px-5 py-2 text-sm font-bold rounded-full border transition-all flex-shrink-0 ${selectedCategoryId === 0 ? 'bg-brand-600 border-brand-600 text-white shadow-md' : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50'}">
            <i class="fas fa-border-all mr-1.5"></i>All Categories
        </button>
    ` + categoriesList.map(cat => `
        <button onclick="filterCategory(${cat.id})" class="px-5 py-2 text-sm font-bold rounded-full border transition-all flex-shrink-0 ${selectedCategoryId === cat.id ? 'bg-brand-600 border-brand-600 text-white shadow-md' : 'bg-white border-slate-200 dark:bg-slate-800 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50'}">
            <i class="fas ${cat.icon} mr-1.5"></i>${cat.name}
        </button>
    `).join('');
}

// Filter foods by Category ID
function filterCategory(catId) {
    selectedCategoryId = catId;
    renderCategories();
    renderFoods();
}

// Filter foods by type (All, Veg, Nonveg)
function filterType(type) {
    selectedType = type;
    ['all', 'veg', 'nonveg'].forEach(t => {
        const btn = document.getElementById('type-' + t);
        if (t === type) {
            btn.className = "px-4 py-2 text-xs font-bold rounded-xl bg-brand-600 text-white transition-colors";
        } else {
            btn.className = "px-4 py-2 text-xs font-bold rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors";
        }
    });
    renderFoods();
}

// Render food items list
function renderFoods() {
    const grid = document.getElementById('food-grid');
    
    // Perform local filtering based on active states
    const filtered = foodsList.filter(food => {
        if (selectedCategoryId > 0 && food.category_id != selectedCategoryId) return false;
        if (selectedType !== 'all' && food.veg_nonveg !== selectedType) return false;
        if (searchQuery.trim() !== '') {
            const term = searchQuery.toLowerCase();
            return food.name.toLowerCase().includes(term) || 
                   food.description.toLowerCase().includes(term) ||
                   (food.ingredients && food.ingredients.toLowerCase().includes(term));
        }
        return true;
    });

    if (filtered.length === 0) {
        grid.className = "col-span-full py-12 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500";
        grid.innerHTML = `
            <i class="fas fa-face-frown text-4xl mb-3"></i>
            <p class="text-sm font-bold">No food items found matching your filters</p>
            <p class="text-xs mt-1">Try resetting the category filter or searching for another keyword</p>
        `;
        return;
    }

    grid.className = "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6";
    grid.innerHTML = filtered.map(food => {
        // Find quantity in cart
        const cartItem = cart.find(item => item.food_id === food.id);
        const qty = cartItem ? cartItem.quantity : 0;
        
        // Stock flags
        const isOutOfStock = food.stock_status === 'out_of_stock' || food.stock_status === 'unavailable';

        let actionBtn = `
            <button onclick="addToCart(${food.id})" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-all transform active:scale-95">
                <i class="fas fa-plus mr-1"></i>Add
            </button>
        `;
        if (isOutOfStock) {
            actionBtn = `
                <button disabled class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 font-bold text-xs rounded-xl cursor-not-allowed">
                    Sold Out
                </button>
            `;
        } else if (qty > 0) {
            actionBtn = `
                <div class="flex items-center space-x-2 bg-brand-50 dark:bg-brand-500/10 px-2.5 py-1.5 rounded-xl border border-brand-100 dark:border-brand-900/30">
                    <button onclick="updateQty(${food.id}, ${qty - 1})" class="text-brand-600 dark:text-brand-500 hover:text-brand-800 font-extrabold text-sm px-1.5 focus:outline-none">-</button>
                    <span class="text-xs font-bold text-brand-700 dark:text-brand-400 px-1">${qty}</span>
                    <button onclick="updateQty(${food.id}, ${qty + 1})" class="text-brand-600 dark:text-brand-500 hover:text-brand-800 font-extrabold text-sm px-1.5 focus:outline-none">+</button>
                </div>
            `;
        }

        const tagColor = food.veg_nonveg === 'veg' ? 'border-green-400 text-green-600' : 'border-red-400 text-red-500';
        const tagIcon = food.veg_nonveg === 'veg' ? 'fa-circle text-green-600' : 'fa-triangle-exclamation text-red-500';

        return `
            <div class="premium-card p-4 flex flex-col justify-between h-full bg-white dark:bg-slate-800">
                <div>
                    <!-- Card Top Image & Badges -->
                    <div class="relative rounded-2xl overflow-hidden mb-4 group/img h-40 bg-slate-100">
                        <img src="${food.image_url}" alt="${food.name}" 
                             class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300" 
                             onerror="this.src='https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600'">
                        
                        <!-- Veg/Nonveg Badge -->
                        <span class="absolute top-3 left-3 bg-white/95 px-2 py-1 rounded-lg shadow-sm border ${tagColor} flex items-center space-x-1 text-[10px] font-bold">
                            <i class="fas ${tagIcon} text-[8px]"></i>
                            <span>${food.veg_nonveg.toUpperCase()}</span>
                        </span>

                        <!-- Prep time badge -->
                        <span class="absolute bottom-3 right-3 bg-slate-900/70 backdrop-blur-sm text-white px-2.5 py-1 rounded-lg text-[10px] font-bold">
                            <i class="fas fa-clock mr-1"></i>${food.prep_time}m
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">${food.category_name || 'MENU'}</span>
                        <div class="flex items-center text-xs text-amber-500 font-bold">
                            <i class="fas fa-star mr-1"></i>4.5
                        </div>
                    </div>
                    
                    <h3 onclick="viewFoodDetails(${food.id})" class="text-sm font-extrabold text-slate-800 dark:text-slate-200 cursor-pointer hover:text-brand-600 dark:hover:text-brand-500 transition-colors line-clamp-1">${food.name}</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 line-clamp-2 mt-1 mb-4 h-8">${food.description}</p>
                </div>

                <!-- Price and CTA -->
                <div class="flex justify-between items-center pt-2 border-t border-slate-50 dark:border-slate-700/50">
                    <span class="text-sm font-black text-slate-900 dark:text-white">₹${parseFloat(food.price).toFixed(2)}</span>
                    ${actionBtn}
                </div>
            </div>
        `;
    }).join('');
}

// Add item to cart with quantity 1
function addToCart(foodId) {
    const food = foodsList.find(f => f.id === foodId);
    if (!food) return;

    cart.push({
        food_id: food.id,
        name: food.name,
        price: parseFloat(food.price),
        quantity: 1,
        special_notes: '',
        veg_nonveg: food.veg_nonveg,
        image_url: food.image_url
    });
    saveCart();
    showToast(`Added ${food.name} to cart`, 'success');
}

// Update item quantity in cart
function updateQty(foodId, newQty) {
    const index = cart.findIndex(item => item.food_id === foodId);
    if (index === -1) return;

    if (newQty <= 0) {
        const item = cart[index];
        cart.splice(index, 1);
        showToast(`Removed ${item.name} from cart`, 'info');
    } else {
        // Validate stock
        const food = foodsList.find(f => f.id === foodId);
        if (food && food.current_stock < newQty) {
            showToast(`Sorry, only ${food.current_stock} of ${food.name} available in stock.`, 'warning');
            return;
        }
        cart[index].quantity = newQty;
    }
    saveCart();
}

// Sync cart array changes with HTML UI Drawer and Floating buttons
function updateCartUI() {
    const countBadge = document.getElementById('cart-floating-badge');
    const totalVal = document.getElementById('cart-floating-total');
    const container = document.getElementById('cart-items-container');
    const checkoutBtn = document.getElementById('checkout-btn');

    let subtotal = 0.00;
    let totalItems = 0;

    cart.forEach(item => {
        subtotal += item.price * item.quantity;
        totalItems += item.quantity;
    });

    const gst = subtotal * 0.05;
    const grand = subtotal + gst;

    // Update Float Button
    if (totalItems > 0) {
        countBadge.textContent = totalItems;
        countBadge.classList.remove('hidden');
    } else {
        countBadge.classList.add('hidden');
    }
    totalVal.textContent = `₹${grand.toFixed(2)}`;

    // Update Drawer Prices
    document.getElementById('cart-subtotal').textContent = `₹${subtotal.toFixed(2)}`;
    document.getElementById('cart-gst').textContent = `₹${gst.toFixed(2)}`;
    document.getElementById('cart-grandtotal').textContent = `₹${grand.toFixed(2)}`;

    // Toggle checkout button
    if (cart.length > 0) {
        checkoutBtn.disabled = false;
    } else {
        checkoutBtn.disabled = true;
    }

    // Render Drawer Items List
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                <i class="fas fa-basket-shopping text-4xl mb-3"></i>
                <p class="text-sm font-bold">Your cart is currently empty</p>
                <p class="text-xs mt-1">Add items from the menu to build your meal</p>
            </div>
        `;
        renderFoods(); // Redraw menu quantity buttons
        return;
    }

    container.innerHTML = cart.map((item, idx) => `
        <div class="flex items-start space-x-3 p-3 bg-slate-50 dark:bg-slate-700/30 rounded-2xl border border-slate-100 dark:border-slate-700/50">
            <img src="${item.image_url}" class="w-12 h-12 object-cover rounded-xl bg-slate-100 flex-shrink-0" onerror="this.src='https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600'">
            <div class="flex-grow min-w-0">
                <h4 class="text-xs font-extrabold text-slate-800 dark:text-slate-200 truncate">${item.name}</h4>
                <p class="text-[10px] font-black text-slate-400 mt-0.5">₹${item.price.toFixed(2)}</p>
                
                <!-- Special notes field -->
                <input type="text" placeholder="Add special note..." value="${item.special_notes}" 
                       onchange="updateCartItemNote(${item.food_id}, this.value)"
                       class="mt-1 w-full text-[10px] border-b border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-brand-600 focus:outline-none bg-transparent py-0.5">
            </div>
            
            <div class="flex flex-col items-end justify-between h-12">
                <button onclick="updateQty(${item.food_id}, 0)" class="text-slate-400 hover:text-red-500"><i class="fas fa-trash-can text-xs"></i></button>
                <div class="flex items-center space-x-1.5 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button onclick="updateQty(${item.food_id}, ${item.quantity - 1})" class="text-slate-500 hover:text-slate-700 text-xs font-bold px-1">-</button>
                    <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200">${item.quantity}</span>
                    <button onclick="updateQty(${item.food_id}, ${item.quantity + 1})" class="text-slate-500 hover:text-slate-700 text-xs font-bold px-1">+</button>
                </div>
            </div>
        </div>
    `).join('');

    renderFoods(); // Redraw menu buttons
}

// Update specific note on item
function updateCartItemNote(foodId, note) {
    const item = cart.find(i => i.food_id === foodId);
    if (item) {
        item.special_notes = note;
        saveCart();
    }
}

// Toggle Slide-over Drawer
function toggleCartDrawer() {
    const drawer = document.getElementById('cart-drawer');
    if (drawer.classList.contains('translate-x-full')) {
        drawer.classList.remove('translate-x-full');
    } else {
        drawer.classList.add('translate-x-full');
    }
}

// Open Food Modal Detail
async function viewFoodDetails(foodId) {
    try {
        const res = await apiRequest('/api/get-food.php?id=' + foodId, { method: 'GET' }, true);
        if (res.status === 'success') {
            const food = res.food;
            const content = document.getElementById('modal-content');
            
            const isOutOfStock = food.stock_status === 'out_of_stock' || food.stock_status === 'unavailable';

            // Find current cart qty
            const cartItem = cart.find(item => item.food_id === food.id);
            const qty = cartItem ? cartItem.quantity : 1;

            content.innerHTML = `
                <!-- Main Header Image -->
                <div class="relative h-60 bg-slate-100">
                    <img src="${food.image_url}" alt="${food.name}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600'">
                    <span class="absolute bottom-4 left-4 bg-white/95 px-3 py-1 rounded-xl shadow border border-slate-100 flex items-center space-x-1.5 text-xs font-bold ${food.veg_nonveg === 'veg' ? 'text-green-600' : 'text-red-500'}">
                        <i class="fas ${food.veg_nonveg === 'veg' ? 'fa-circle' : 'fa-triangle-exclamation'} text-[10px]"></i>
                        <span>${food.veg_nonveg.toUpperCase()}</span>
                    </span>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Title and Info -->
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">${food.category_name || 'MENU'}</span>
                        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">${food.name}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">${food.description}</p>
                    </div>

                    <!-- Nutrition / Info Badges -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 p-3 rounded-2xl text-center">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Prep Time</span>
                            <span class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mt-0.5 block"><i class="fas fa-clock mr-1.5 text-brand-600"></i>${food.prep_time} Mins</span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 p-3 rounded-2xl text-center">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Calories</span>
                            <span class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mt-0.5 block"><i class="fas fa-fire mr-1.5 text-red-500"></i>${food.calories} kCal</span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 p-3 rounded-2xl text-center">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Spice Level</span>
                            <span class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mt-0.5 block">
                                ${Array.from({length: 3}, (_, i) => `<i class="fas fa-pepper-hot text-xs ${i < food.spice_level ? 'text-red-500' : 'text-slate-300 dark:text-slate-600'}"></i>`).join('')}
                            </span>
                        </div>
                    </div>

                    <!-- Ingredients -->
                    <?php if (isset($food['ingredients'])): ?>
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Ingredients</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/20 p-3 rounded-2xl border border-slate-100 dark:border-slate-700/50 leading-relaxed">${food.ingredients || 'Standard Indian herbs & spices.'}</p>
                    </div>
                    <?php endif; ?>

                    <!-- Quantity and Add button -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-400 block uppercase">Price</span>
                            <span class="text-xl font-black text-slate-900 dark:text-white">₹${parseFloat(food.price).toFixed(2)}</span>
                        </div>

                        <div class="flex items-center space-x-4">
                            ${isOutOfStock ? `
                                <span class="text-xs font-bold text-red-500 bg-red-50 dark:bg-red-950/20 px-4 py-2.5 rounded-2xl border border-red-100 dark:border-red-900/30">Currently Sold Out</span>
                            ` : `
                                <div class="flex items-center space-x-3 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-2xl">
                                    <button onclick="adjustModalQty(-1)" class="font-bold text-slate-500 hover:text-slate-700 text-lg px-2">-</button>
                                    <span id="modal-qty" class="text-sm font-extrabold text-slate-800 dark:text-slate-100 px-1 w-6 text-center">${qty}</span>
                                    <button onclick="adjustModalQty(1, ${food.current_stock})" class="font-bold text-slate-500 hover:text-slate-700 text-lg px-2">+</button>
                                </div>
                                <button onclick="modalAddToCart(${food.id})" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-2xl shadow-lg transition-colors">
                                    Add to Cart
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;

            // Open Modal container
            const modal = document.getElementById('food-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
            }, 10);
        }
    } catch(err) {}
}

function adjustModalQty(delta, limit = 99) {
    const qtySpan = document.getElementById('modal-qty');
    let qty = parseInt(qtySpan.textContent) + delta;
    if (qty < 1) qty = 1;
    if (qty > limit) {
        showToast(`Only ${limit} items left in stock.`, 'warning');
        qty = limit;
    }
    qtySpan.textContent = qty;
}

function modalAddToCart(foodId) {
    const qty = parseInt(document.getElementById('modal-qty').textContent);
    const food = foodsList.find(f => f.id === foodId);
    if (!food) return;

    // Check if item is already in cart
    const existingIndex = cart.findIndex(item => item.food_id === foodId);
    if (existingIndex !== -1) {
        cart[existingIndex].quantity = qty;
    } else {
        cart.push({
            food_id: food.id,
            name: food.name,
            price: parseFloat(food.price),
            quantity: qty,
            special_notes: '',
            veg_nonveg: food.veg_nonveg,
            image_url: food.image_url
        });
    }

    saveCart();
    closeFoodModal();
    showToast(`Added ${qty} of ${food.name} to cart.`, 'success');
}

function closeFoodModal() {
    const modal = document.getElementById('food-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Checkout Dialog Controls
function openCheckoutModal() {
    toggleCartDrawer();
    const checkout = document.getElementById('checkout-modal');
    checkout.classList.remove('hidden');
    setTimeout(() => {
        checkout.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeCheckoutModal() {
    const checkout = document.getElementById('checkout-modal');
    checkout.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        checkout.classList.add('hidden');
    }, 200);
}

// Form Checkout Submission
document.getElementById('checkout-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = document.getElementById('submit-order-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-2"></i>Placing Order...`;

    const orderData = {
        floor: parseInt(document.getElementById('chk-floor').value),
        cabin: document.getElementById('chk-cabin').value,
        desk_number: document.getElementById('chk-desk').value,
        delivery_date: document.getElementById('chk-date').value,
        delivery_time: document.getElementById('chk-time').value,
        special_instructions: document.getElementById('chk-instructions').value,
        is_agreed: document.getElementById('chk-agree').checked ? 1 : 0,
        csrf_token: '<?php echo get_csrf_token(); ?>',
        items: cart.map(item => ({
            food_id: item.food_id,
            quantity: item.quantity,
            special_notes: item.special_notes
        }))
    };

    try {
        const res = await apiRequest('/api/place-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        }, true);

        if (res.status === 'success') {
            // Clear Local Cart
            cart = [];
            localStorage.removeItem('canteen_cart');
            updateCartUI();

            closeCheckoutModal();
            showToast('Order Placed Successfully!', 'success');
            
            // Redirect to timeline tracking view
            setTimeout(() => {
                window.location.href = '/employee/track.php?id=' + res.order_id;
            }, 1000);
        }
    } catch(err) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Confirm Order';
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
