<?php
/**
 * Employee Dashboard
 */
require_once dirname(__DIR__) . '/includes/header.php';

$employee_db_id = $_SESSION['employee_db_id'];
$employee_name = $_SESSION['employee_name'];
$employee_dept = $_SESSION['employee_dept'];

// Query Today's Stats
$stats = [
    'pending' => 0,
    'completed' => 0,
    'total' => 0
];
try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt 
                           FROM orders 
                           WHERE employee_id = ? AND DATE(created_at) = CURDATE() 
                           GROUP BY status");
    $stmt->execute([$employee_db_id]);
    while ($row = $stmt->fetch()) {
        if (in_array($row['status'], ['received', 'confirmed', 'preparing', 'ready', 'out_of_delivery'])) {
            $stats['pending'] += $row['cnt'];
        } elseif ($row['status'] === 'delivered') {
            $stats['completed'] += $row['cnt'];
        }
        $stats['total'] += $row['cnt'];
    }
} catch (\Exception $e) {
    // Silence error
}

// Fetch Active Orders for Live Tracking
$active_orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders 
                           WHERE employee_id = ? AND status NOT IN ('delivered', 'cancelled') 
                           ORDER BY created_at DESC");
    $stmt->execute([$employee_db_id]);
    $active_orders = $stmt->fetchAll();
} catch (\Exception $e) {
    // Silence error
}

// Fetch Recent Orders (History)
$recent_orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders 
                           WHERE employee_id = ? AND status IN ('delivered', 'cancelled') 
                           ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$employee_db_id]);
    $recent_orders = $stmt->fetchAll();
} catch (\Exception $e) {
    // Silence error
}

// Determine Greeting based on hour
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon";
} elseif ($hour >= 17) {
    $greeting = "Good Evening";
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Greeting Card -->
    <div class="bg-gradient-to-r from-brand-600 to-green-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
        <div>
            <span class="text-xs font-bold bg-white/20 px-3 py-1 rounded-full uppercase tracking-wider">Dashboard Overview</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mt-2"><?php echo $greeting; ?>, <?php echo htmlspecialchars($employee_name); ?>!</h1>
            <p class="text-sm font-semibold text-green-50/80 mt-1">
                <i class="fas fa-building mr-1.5"></i>Department: <?php echo htmlspecialchars($employee_dept); ?> &bull; Floor <?php echo $employee_data['floor']; ?>
            </p>
        </div>
        <div class="text-right">
            <p class="text-xs font-bold text-green-100 uppercase tracking-widest">Today's Date</p>
            <p class="text-lg font-extrabold mt-0.5"><i class="fas fa-calendar-day mr-1.5"></i><?php echo date('d M Y'); ?></p>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card: Wallet -->
        <div class="premium-card p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Wallet Balance</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">₹<?php echo number_format($employee_data['wallet_balance'], 2); ?></h3>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-2xl">
                <i class="fas fa-wallet text-xl"></i>
            </div>
        </div>

        <!-- Stat Card: Pending -->
        <div class="premium-card p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Pending Orders</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1"><?php echo $stats['pending']; ?></h3>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-950/20 text-orange-500 rounded-2xl">
                <i class="fas fa-spinner animate-spin text-xl"></i>
            </div>
        </div>

        <!-- Stat Card: Completed -->
        <div class="premium-card p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Orders Today</p>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1"><?php echo $stats['total']; ?></h3>
            </div>
            <div class="p-3 bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-500 rounded-2xl">
                <i class="fas fa-clipboard-list text-xl"></i>
            </div>
        </div>

        <!-- Action Card: Order Now -->
        <a href="/employee/order.php" class="bg-brand-600 hover:bg-brand-700 p-6 rounded-3xl shadow-lg flex items-center justify-between text-white group transition-all transform hover:-translate-y-1">
            <div>
                <p class="text-xs font-bold text-green-200 uppercase tracking-wider">Craving Food?</p>
                <h3 class="text-lg font-bold mt-1">Order Lunch Now</h3>
                <span class="text-xs font-medium text-green-100 flex items-center mt-1">Browse Menu <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i></span>
            </div>
            <div class="p-4 bg-white/10 rounded-2xl text-white">
                <i class="fas fa-burger text-2xl"></i>
            </div>
        </a>
    </div>

    <!-- Active Tracking Widget -->
    <?php if (!empty($active_orders)): ?>
    <div class="mb-8">
        <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-4 flex items-center">
            <span class="relative flex h-3 w-3 mr-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-600"></span>
            </span>
            Track Active Orders
        </h2>
        
        <div class="space-y-4">
            <?php foreach ($active_orders as $order): ?>
                <?php
                // Map status progress percentage
                $status_percents = [
                    'received' => 15,
                    'confirmed' => 35,
                    'preparing' => 55,
                    'ready' => 75,
                    'out_of_delivery' => 90
                ];
                $pct = $status_percents[$order['status']] ?? 10;
                ?>
                <div class="premium-card p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 pb-4 border-b border-slate-100 dark:border-slate-700/50">
                        <div>
                            <span class="text-xs font-bold text-slate-400">Order Ref:</span>
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200 ml-1"><?php echo $order['order_number']; ?></span>
                        </div>
                        <div class="mt-2 sm:mt-0 flex items-center space-x-3">
                            <span class="text-xs font-bold text-slate-500">Deliver By: <?php echo date('h:i A', strtotime($order['delivery_time'])); ?></span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500 uppercase tracking-wider">
                                <?php echo str_replace('_', ' ', $order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative w-full h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden mb-6">
                        <div class="absolute top-0 left-0 h-full bg-brand-600 rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400">Est. Prep: <?php echo date('h:i A', strtotime($order['created_at']) + (20 * 60)); ?></span>
                        <a href="/employee/track.php?id=<?php echo $order['id']; ?>" class="px-4 py-2 bg-brand-50 text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-500 dark:hover:bg-brand-500/20 text-xs font-bold rounded-2xl transition-colors">
                            <i class="fas fa-location-crosshairs mr-1.5"></i>Live Track Order
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- History list -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">Recent Orders History</h2>
            <a href="/employee/order-history.php" class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                View All <i class="fas fa-chevron-right ml-1"></i>
            </a>
        </div>

        <?php if (empty($recent_orders)): ?>
            <div class="premium-card p-12 text-center flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-600 text-2xl mb-4">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">No previous orders found</h3>
                <p class="text-sm text-slate-400 mt-1">Looks like you haven't placed any orders yet today!</p>
                <a href="/employee/order.php" class="mt-4 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-2xl transition-colors">
                    Place First Order
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($recent_orders as $order): ?>
                    <div class="premium-card p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-bold text-slate-400"><?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $order['status'] === 'delivered' ? 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-500' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-500' ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                            
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Ref: <?php echo $order['order_number']; ?></h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total Paid: ₹<?php echo number_format($order['grand_total'], 2); ?></p>
                            
                            <!-- Items preview -->
                            <div class="mt-4 text-xs text-slate-400">
                                <?php
                                $items_stmt = $pdo->prepare("SELECT food_name, quantity FROM order_items WHERE order_id = ? LIMIT 2");
                                $items_stmt->execute([$order['id']]);
                                $items = $items_stmt->fetchAll();
                                $names = [];
                                foreach ($items as $itm) {
                                    $names[] = "{$itm['food_name']} x{$itm['quantity']}";
                                }
                                echo implode(', ', $names);
                                if (count($names) < $order['subtotal']) echo '...';
                                ?>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/50 flex space-x-2">
                            <button onclick="reorder(<?php echo $order['id']; ?>)" class="flex-1 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-all transform active:scale-95">
                                <i class="fas fa-rotate-right mr-1.5"></i>Reorder
                            </button>
                            <a href="/employee/order-history.php?id=<?php echo $order['id']; ?>" class="px-3 py-2 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
/**
 * Quick Reorder handler: copies items from target order to cart and checks out
 */
async function reorder(orderId) {
    const confirm = await showConfirm('Reorder Meal', 'Do you want to copy these items to your cart and place the order?', 'Copy & Place');
    if (!confirm) return;

    try {
        const res = await apiRequest('/api/order-details.php?id=' + orderId, { method: 'GET' }, true);
        if (res.status === 'success') {
            // Setup details for new order
            const items = res.items.map(item => ({
                food_id: item.food_id,
                quantity: item.quantity,
                special_notes: item.special_notes
            }));

            // Fetch user profile delivery defaults
            const profileRes = await apiRequest('/api/profile.php', { method: 'GET' });
            const profile = profileRes.profile;

            // Submit order directly
            const newOrderData = {
                floor: profile.floor || 1,
                cabin: profile.cabin || '',
                desk_number: profile.desk_number || '',
                delivery_date: new Date().toISOString().split('T')[0],
                delivery_time: '13:00:00', // default lunchtime
                special_instructions: 'Quick Reordered from history',
                is_agreed: 1,
                csrf_token: '<?php echo get_csrf_token(); ?>',
                items: items
            };

            const orderRes = await apiRequest('/api/place-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newOrderData)
            }, true);

            if (orderRes.status === 'success') {
                showToast('Order Placed Successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/employee/track.php?id=' + orderRes.order_id;
                }, 1000);
            }
        }
    } catch (err) {
        showToast(err.message || 'Failed to reorder items', 'error');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
