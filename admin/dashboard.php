<?php
/**
 * Admin Console Dashboard (Charts and Stats Overview)
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Access control: only admin role is allowed here
if ($admin_role !== 'admin') {
    header("Location: /admin/chef.php");
    exit;
}

// Fetch stats summary for today
$today_stats = [
    'total_orders' => 0,
    'revenue' => 0.00,
    'pending' => 0,
    'preparing' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

try {
    // 1. Counters
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt, SUM(grand_total) as revenue 
                         FROM orders 
                         WHERE DATE(created_at) = CURDATE() 
                         GROUP BY status");
    while ($row = $stmt->fetch()) {
        $cnt = (int)$row['cnt'];
        $today_stats['total_orders'] += $cnt;
        
        if ($row['status'] === 'delivered') {
            $today_stats['revenue'] += (float)$row['revenue'];
            $today_stats['delivered'] += $cnt;
        } elseif ($row['status'] === 'cancelled') {
            $today_stats['cancelled'] += $cnt;
        } elseif (in_array($row['status'], ['received', 'confirmed'])) {
            $today_stats['pending'] += $cnt;
        } elseif (in_array($row['status'], ['preparing', 'ready', 'out_of_delivery'])) {
            $today_stats['preparing'] += $cnt;
        }
    }

    // 2. Recent orders
    $recent_stmt = $pdo->query("SELECT o.*, e.name as employee_name 
                                FROM orders o 
                                JOIN employees e ON o.employee_id = e.id 
                                ORDER BY o.created_at DESC 
                                LIMIT 5");
    $recent_orders = $recent_stmt->fetchAll();

    // 3. Most ordered items today
    $popular_stmt = $pdo->query("SELECT food_name, SUM(quantity) as qty, SUM(quantity * price) as val 
                                 FROM order_items 
                                 WHERE DATE(created_at) = CURDATE() 
                                 GROUP BY food_id, food_name 
                                 ORDER BY qty DESC 
                                 LIMIT 5");
    $popular_items = $popular_stmt->fetchAll();

    // 4. Activity Logs
    $log_stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5");
    $activity_logs = $log_stmt->fetchAll();

} catch (\Exception $e) {
    die("Database aggregates fetch failed: " . $e->getMessage());
}
?>

<!-- Include Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    <!-- Canteen console header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Console Overview</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Real-time canteen logistics, sales indicators, and kitchen operations</p>
        </div>
        <div class="text-right text-xs text-slate-400 font-bold">
            Console Status: <span class="text-green-600 bg-green-50 px-2 py-1 rounded-full"><i class="fas fa-signal mr-1"></i>Live Connected</span>
        </div>
    </div>

    <!-- Counters stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-5">
        <!-- Stat Card: Revenue -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800 col-span-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Revenue Today</span>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white mt-1">₹<?php echo number_format($today_stats['revenue'], 2); ?></h3>
            <span class="text-[10px] text-green-600 font-semibold flex items-center mt-1"><i class="fas fa-arrow-up-long mr-1"></i>+12.5% from yesterday</span>
        </div>
        
        <!-- Total orders -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Total Orders</span>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1"><?php echo $today_stats['total_orders']; ?></h3>
        </div>

        <!-- Pending orders -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Pending</span>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-500 mt-1"><?php echo $today_stats['pending']; ?></h3>
        </div>

        <!-- Preparing orders -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Preparing</span>
            <h3 class="text-2xl font-black text-orange-500 mt-1"><?php echo $today_stats['preparing']; ?></h3>
        </div>

        <!-- Delivered orders -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Delivered</span>
            <h3 class="text-2xl font-black text-green-600 dark:text-green-500 mt-1"><?php echo $today_stats['delivered']; ?></h3>
        </div>
    </div>

    <!-- Charts graphs visualizer -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales line graph -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wider">Order Count Hourly Today</h3>
            <div class="h-64">
                <canvas id="hourlySalesChart"></canvas>
            </div>
        </div>

        <!-- Department wise bar graph -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wider">Department Wise Ordering Volume</h3>
            <div class="h-64">
                <canvas id="deptOrdersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Table details lists split column -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Live Incoming Orders Queue -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800 lg:col-span-2">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Recent incoming orders</h3>
                <a href="/admin/orders.php" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Manage Queue <i class="fas fa-angle-right ml-1"></i></a>
            </div>

            <?php if (empty($recent_orders)): ?>
                <p class="text-center text-xs text-slate-400 py-12">No orders placed recently.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700 font-bold uppercase"><th class="py-2">Order Ref</th><th class="py-2">Employee</th><th class="py-2">Total Price</th><th class="py-2">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php
                                $status_badge = 'bg-brand-50 text-brand-600 dark:bg-brand-500/10';
                                if ($order['status'] === 'cancelled') $status_badge = 'bg-red-50 text-red-600 dark:bg-red-500/10';
                                if (in_array($order['status'], ['received', 'confirmed'])) $status_badge = 'bg-amber-50 text-amber-600 dark:bg-amber-500/10';
                                ?>
                                <tr class="border-b border-slate-50 dark:border-slate-700/30 text-slate-700 dark:text-slate-300">
                                    <td class="py-3 font-bold"><?php echo $order['order_number']; ?></td>
                                    <td class="py-3"><?php echo htmlspecialchars($order['employee_name']); ?> (<?php echo htmlspecialchars($order['department']); ?>)</td>
                                    <td class="py-3 font-black">₹<?php echo number_format($order['grand_total'], 2); ?></td>
                                    <td class="py-3"><span class="px-2.5 py-0.5 rounded-full uppercase tracking-wider text-[9px] font-extrabold <?php echo $status_badge; ?>"><?php echo $order['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Most Ordered Foods & Activity log -->
        <div class="space-y-6">
            <!-- Popular foods card -->
            <div class="premium-card p-5 bg-white dark:bg-slate-800">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 pb-2 border-b border-slate-100 dark:border-slate-700/50 uppercase tracking-wider">Top Selling Today</h3>
                <?php if (empty($popular_items)): ?>
                    <p class="text-center text-xs text-slate-400 py-12">No orders placed today yet.</p>
                <?php else: ?>
                    <div class="space-y-3.5">
                        <?php foreach ($popular_items as $item): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate max-w-[150px]"><?php echo htmlspecialchars($item['food_name']); ?></span>
                                    <span class="text-[10px] text-slate-400"><?php echo $item['qty']; ?> units sold today</span>
                                </div>
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200">₹<?php echo number_format($item['val'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity logger card -->
            <div class="premium-card p-5 bg-white dark:bg-slate-800">
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-200 mb-4 pb-2 border-b border-slate-100 dark:border-slate-700/50 uppercase tracking-wider">Activity Logger</h3>
                <div class="space-y-3.5">
                    <?php foreach ($activity_logs as $log): ?>
                        <div class="text-[10px] leading-relaxed">
                            <span class="font-extrabold text-slate-700 dark:text-slate-300 block"><?php echo htmlspecialchars($log['action']); ?> &bull; <span class="text-slate-400 font-semibold"><?php echo date('h:i A', strtotime($log['created_at'])); ?></span></span>
                            <span class="text-slate-400 dark:text-slate-500 block truncate"><?php echo htmlspecialchars($log['details']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Mock data queries details (Department statistics and hourly distribution maps)
const hourlyLabels = ['08 AM', '10 AM', '12 PM', '02 PM', '04 PM', '06 PM', '08 PM'];
const hourlyOrdersData = [12, 28, 65, 48, 18, 30, 22]; // seeded stats

const deptLabels = ['IT Dev', 'HR Office', 'Finance', 'Marketing', 'Legal', 'Sales', 'Design'];
const deptOrdersData = [45, 20, 25, 38, 12, 19, 32]; // department orders count

// Initialize Line Chart for Hourly Orders
const ctxHourly = document.getElementById('hourlySalesChart').getContext('2d');
new Chart(ctxHourly, {
    type: 'line',
    data: {
        labels: hourlyLabels,
        datasets: [{
            label: 'Orders Count',
            data: hourlyOrdersData,
            borderColor: '#16A34A',
            backgroundColor: 'rgba(22, 163, 74, 0.05)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#16A34A',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { grid: { color: 'rgba(226, 232, 240, 0.1)' }, ticks: { color: '#94A3B8', font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { color: '#94A3B8', font: { size: 10 } } }
        }
    }
});

// Initialize Bar Chart for Department Orders
const ctxDept = document.getElementById('deptOrdersChart').getContext('2d');
new Chart(ctxDept, {
    type: 'bar',
    data: {
        labels: deptLabels,
        datasets: [{
            data: deptOrdersData,
            backgroundColor: 'rgba(22, 163, 74, 0.85)',
            borderRadius: 8,
            barThickness: 18
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { grid: { color: 'rgba(226, 232, 240, 0.1)' }, ticks: { color: '#94A3B8', font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { color: '#94A3B8', font: { size: 10 } } }
        }
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
