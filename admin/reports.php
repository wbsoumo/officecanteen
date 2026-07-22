<?php
/**
 * Admin Console: Canteen reports, analytics, and CSV export
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Access guard
if ($admin_role !== 'admin') {
    header("Location: /admin/chef.php");
    exit;
}

$error = '';

try {
    // 1. Department wise orders
    $dept_stmt = $pdo->query("SELECT department, COUNT(*) as order_count, SUM(grand_total) as total_sales, AVG(grand_total) as avg_order_value
                              FROM orders 
                              GROUP BY department 
                              ORDER BY total_sales DESC");
    $dept_stats = $dept_stmt->fetchAll();

    // 2. Popular food items all-time
    $popular_stmt = $pdo->query("SELECT f.name as food_name, c.name as category_name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price) as total_sales
                                 FROM order_items oi
                                 JOIN foods f ON oi.food_id = f.id
                                 LEFT JOIN categories c ON f.category_id = c.id
                                 GROUP BY oi.food_id, f.name, c.name
                                 ORDER BY total_qty DESC
                                 LIMIT 10");
    $popular_foods = $popular_stmt->fetchAll();

    // 3. Peak hours analysis (hourly counts)
    $peak_stmt = $pdo->query("SELECT HOUR(created_at) as order_hour, COUNT(*) as order_count, SUM(grand_total) as hourly_sales
                              FROM orders 
                              GROUP BY HOUR(created_at) 
                              ORDER BY order_hour ASC");
    $peak_stats = $peak_stmt->fetchAll();

} catch (\Exception $e) {
    $error = 'Failed to load report analytics: ' . $e->getMessage();
}
?>

<div class="space-y-8">
    
    <!-- Header title -->
    <div class="flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Reports & Analytics</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Canteen sales performance analysis, hourly peaks, and department-wise summaries</p>
        </div>
        <button onclick="exportAllAnalyticsCSV()" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
            <i class="fas fa-file-export mr-1.5"></i>Export Analytics CSV
        </button>
    </div>

    <!-- Error message if any -->
    <?php if (!empty($error)): ?>
        <div class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 text-xs font-bold flex items-center space-x-2">
            <i class="fas fa-exclamation-circle text-base"></i><span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Two-column layouts split -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Department-wise orders summary -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Department sales performance</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700 font-bold uppercase"><th class="pb-2">Department</th><th class="pb-2 text-center">Orders</th><th class="pb-2 text-right">Avg Val</th><th class="pb-2 text-right">Total Sales</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($dept_stats as $d): ?>
                            <tr class="text-slate-700 dark:text-slate-300">
                                <td class="py-2.5 font-bold"><?php echo htmlspecialchars($d['department']); ?></td>
                                <td class="py-2.5 text-center font-semibold"><?php echo $d['order_count']; ?></td>
                                <td class="py-2.5 text-right">₹<?php echo number_format($d['avg_order_value'], 2); ?></td>
                                <td class="py-2.5 text-right font-black">₹<?php echo number_format($d['total_sales'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Peak ordering hours -->
        <div class="premium-card p-5 bg-white dark:bg-slate-800">
            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Peak Canteen hours analysis</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700 font-bold uppercase"><th class="pb-2">Hour</th><th class="pb-2 text-center">Orders Count</th><th class="pb-2 text-right">Sales volume</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($peak_stats as $p): ?>
                            <?php
                            $hour_formatted = date("h:00 A", strtotime("1970-01-01 " . $p['order_hour'] . ":00:00"));
                            ?>
                            <tr class="text-slate-700 dark:text-slate-300">
                                <td class="py-2.5 font-bold"><?php echo $hour_formatted; ?></td>
                                <td class="py-2.5 text-center font-semibold"><?php echo $p['order_count']; ?></td>
                                <td class="py-2.5 text-right font-black">₹<?php echo number_format($p['hourly_sales'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Popular dishes catalog list -->
    <div class="premium-card p-5 bg-white dark:bg-slate-800">
        <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">All-time Popular Canteen food items</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700 font-bold uppercase"><th class="pb-2">Dish Name</th><th class="pb-2">Category</th><th class="pb-2 text-center">Total Quantity Sold</th><th class="pb-2 text-right">Sales Value</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach($popular_foods as $f): ?>
                        <tr class="text-slate-700 dark:text-slate-300">
                            <td class="py-2.5 font-bold"><?php echo htmlspecialchars($f['food_name']); ?></td>
                            <td class="py-2.5"><?php echo htmlspecialchars($f['category_name'] ?: 'N/A'); ?></td>
                            <td class="py-2.5 text-center font-extrabold text-brand-600 dark:text-brand-400"><?php echo $f['total_qty']; ?></td>
                            <td class="py-2.5 text-right font-black">₹<?php echo number_format($f['total_sales'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
/**
 * Export analytics summary data into CSV formatted string download
 */
function exportAllAnalyticsCSV() {
    let csv = "data:text/csv;charset=utf-8,";
    csv += "--- CANTEEN REPORTS ANALYTICS ---\r\n\r\n";

    // 1. Department Breakdown
    csv += "DEPARTMENT WISE SUMMARY\r\n";
    csv += "Department,Orders Count,Avg Order Value,Total Sales\r\n";
    <?php foreach($dept_stats as $d): ?>
        csv += `"${'<?php echo htmlspecialchars($d['department']); ?>'}",${'<?php echo $d['order_count']; ?>'},${'<?php echo $d['avg_order_value']; ?>'},${'<?php echo $d['total_sales']; ?>'}\r\n`;
    <?php endforeach; ?>
    csv += "\r\n";

    // 2. Popular items
    csv += "POPULAR FOODS LIST\r\n";
    csv += "Dish,Category,Total Qty Sold,Total Revenue\r\n";
    <?php foreach($popular_foods as $f): ?>
        csv += `"${'<?php echo htmlspecialchars($f['food_name']); ?>'}","${'<?php echo htmlspecialchars($f['category_name'] ?: 'N/A'); ?>'}",${'<?php echo $f['total_qty']; ?>'},${'<?php echo $f['total_sales']; ?>'}\r\n`;
    <?php endforeach; ?>
    csv += "\r\n";

    // 3. Peak hours
    csv += "PEAK HOURS SALES\r\n";
    csv += "Hour,Orders Count,Sales Volume\r\n";
    <?php foreach($peak_stats as $p): ?>
        csv += `${'<?php echo $p['order_hour']; ?>:00'},${'<?php echo $p['order_count']; ?>'},${'<?php echo $p['hourly_sales']; ?>'}\r\n`;
    <?php endforeach; ?>

    const encoded = encodeURI(csv);
    const link = document.createElement("a");
    link.setAttribute("href", encoded);
    link.setAttribute("download", `Canteen_Analytics_Report_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('Analytics CSV exported!', 'success');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
