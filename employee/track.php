<?php
/**
 * Employee Order Tracking Page (Real-Time Timeline UI)
 */
require_once dirname(__DIR__) . '/includes/header.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: /employee/dashboard.php");
    exit;
}

// Fetch initial order details to prevent page load flash
try {
    $stmt = $pdo->prepare("SELECT o.*, e.name as employee_name 
                           FROM orders o 
                           JOIN employees e ON o.employee_id = e.id 
                           WHERE o.id = ? AND o.employee_id = ?");
    $stmt->execute([$order_id, $_SESSION['employee_db_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order not found or access denied.");
    }
} catch (\Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Title Section -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Order Tracking</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Order Ref: <?php echo $order['order_number']; ?></p>
        </div>
        <a href="/employee/dashboard.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-2xl transition-colors">
            <i class="fas fa-home mr-1"></i>Dashboard
        </a>
    </div>

    <!-- Live Status Card -->
    <div class="premium-card p-6 mb-8 text-center sm:text-left flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Current Status</span>
            <h3 id="live-status-text" class="text-xl font-extrabold text-brand-600 dark:text-brand-500 uppercase mt-0.5">
                <?php echo str_replace('_', ' ', $order['status']); ?>
            </h3>
            <p class="text-xs text-slate-400 mt-1">Auto-refreshing every 10 seconds...</p>
        </div>
        <div class="bg-brand-50 dark:bg-brand-500/10 p-4 rounded-2xl border border-brand-100 dark:border-brand-900/30 text-center">
            <span class="block text-[10px] font-bold text-brand-700 dark:text-brand-400 uppercase">Estimated Delivery</span>
            <span id="live-eta" class="text-lg font-black text-brand-800 dark:text-brand-300 mt-0.5 block">
                <?php echo date('h:i A', strtotime($order['delivery_time'])); ?>
            </span>
        </div>
    </div>

    <!-- Interactive Status Timeline -->
    <div class="premium-card p-6 sm:p-8 mb-8">
        <div class="relative flex flex-col space-y-8">
            
            <!-- Timeline Connective Bar -->
            <div class="absolute left-6.5 top-2 bottom-2 w-0.5 bg-slate-200 dark:bg-slate-700 z-0">
                <div id="timeline-progress-bar" class="w-full bg-brand-600 rounded transition-all duration-500" style="height: 0%"></div>
            </div>

            <!-- Step 1: Received -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="received">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-receipt text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Order Received</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Cafeteria has received your request and is queuing it.</p>
                </div>
            </div>

            <!-- Step 2: Confirmed -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="confirmed">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-thumbs-up text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Order Confirmed</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Kitchen staff has acknowledged your order and allocated resources.</p>
                </div>
            </div>

            <!-- Step 3: Preparing -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="preparing">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-fire-burner text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Preparing</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Head chef is preparing your meal using fresh ingredients.</p>
                </div>
            </div>

            <!-- Step 4: Ready -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="ready">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-circle-check text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Ready for pickup</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Meal cooked and packed. Ready at the counter.</p>
                </div>
            </div>

            <!-- Step 5: Out for Delivery -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="out_of_delivery">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-person-walking-luggage text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Out For Delivery</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Delivery agent is bringing the order to Floor <?php echo $order['floor']; ?>.</p>
                </div>
            </div>

            <!-- Step 6: Delivered -->
            <div class="timeline-step flex items-start space-x-4 z-10" data-status="delivered">
                <div class="step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white dark:bg-slate-800 transition-colors">
                    <i class="fas fa-gift text-slate-400"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Delivered</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Order delivered successfully! Bon Appétit.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Items List Card -->
    <div class="premium-card p-6">
        <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700/50 pb-3 mb-4">
            Items Ordered
        </h3>
        <div id="live-items-list" class="space-y-3">
            <!-- Loaded dynamically -->
            <div class="skeleton w-full h-8 rounded"></div>
            <div class="skeleton w-full h-8 rounded"></div>
        </div>
        <div class="border-t border-slate-100 dark:border-slate-700/50 mt-4 pt-4 flex justify-between text-sm font-black text-slate-800 dark:text-white">
            <span>Total Paid (COD):</span>
            <span id="live-total">₹0.00</span>
        </div>
    </div>

</div>

<script>
const orderId = <?php echo $order_id; ?>;
const statusesOrder = ['received', 'confirmed', 'preparing', 'ready', 'out_of_delivery', 'delivered'];

document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch
    trackOrder();

    // Setup active polling intervals
    const poller = setInterval(trackOrder, 10000);

    // Clean interval on page destroy
    window.addEventListener('beforeunload', () => clearInterval(poller));
});

// Update the timeline GUI
async function trackOrder() {
    try {
        const res = await apiRequest('/api/order-details.php?id=' + orderId, { method: 'GET' });
        if (res.status === 'success') {
            const order = res.order;
            const items = res.items;

            // Update simple labels
            document.getElementById('live-status-text').textContent = order.status.replace('_', ' ').toUpperCase();
            
            // Format ETA time
            const etaTime = new Date('1970-01-01T' + order.delivery_time + 'Z').toLocaleTimeString([], {
                hour: '2-digit', minute:'2-digit', timeZone: 'UTC'
            });
            document.getElementById('live-eta').textContent = etaTime;

            // Render Items details
            document.getElementById('live-total').textContent = '₹' + parseFloat(order.grand_total).toFixed(2);
            document.getElementById('live-items-list').innerHTML = items.map(item => `
                <div class="flex justify-between items-center text-xs">
                    <span class="font-bold text-slate-700 dark:text-slate-300">
                        ${item.food_name} <span class="text-slate-400 font-semibold ml-1">x${item.quantity}</span>
                    </span>
                    <span class="font-black text-slate-800 dark:text-slate-200">₹${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `).join('');

            // Highlight progress steps
            let currentStatusIndex = statusesOrder.indexOf(order.status);
            if (order.status === 'cancelled') currentStatusIndex = -1; // handle cancellation

            const steps = document.querySelectorAll('.timeline-step');
            
            steps.forEach((step, idx) => {
                const status = step.getAttribute('data-status');
                const statusIdx = statusesOrder.indexOf(status);
                const iconContainer = step.querySelector('.step-icon');
                const icon = iconContainer.querySelector('i');
                const title = step.querySelector('h4');

                if (statusIdx <= currentStatusIndex && currentStatusIndex !== -1) {
                    // Mark as Active/Completed
                    iconContainer.className = "step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-brand-600 bg-brand-50 dark:bg-brand-950/20 text-brand-600 dark:text-brand-400 z-10";
                    title.className = "text-sm font-bold text-slate-800 dark:text-slate-200";
                    if (statusIdx === currentStatusIndex) {
                        // Current status gets pulsing glow
                        iconContainer.classList.add('ring-4', 'ring-brand-100', 'dark:ring-brand-950/50');
                    } else {
                        iconContainer.classList.remove('ring-4');
                    }
                } else {
                    // Uncompleted states
                    iconContainer.className = "step-icon w-10.5 h-10.5 rounded-full flex items-center justify-center border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-300 dark:text-slate-600 z-10";
                    title.className = "text-sm font-semibold text-slate-400";
                    iconContainer.classList.remove('ring-4');
                }
            });

            // Adjust connector progress bar height percentage
            const pctHeight = currentStatusIndex >= 0 ? (currentStatusIndex / (statusesOrder.length - 1)) * 100 : 0;
            document.getElementById('timeline-progress-bar').style.height = pctHeight + '%';

            // Show Success toast once delivered and stop poller
            if (order.status === 'delivered') {
                showToast('Order delivered! Hope you love your meal.', 'success');
            } else if (order.status === 'cancelled') {
                showToast('This order has been cancelled by cafeteria.', 'error');
                document.getElementById('live-status-text').className = "text-xl font-extrabold text-red-500 uppercase mt-0.5";
            }
        }
    } catch(err) {}
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
