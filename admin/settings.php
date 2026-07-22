<?php
/**
 * Admin Console: Global settings configuration
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Access guard
if ($admin_role !== 'admin') {
    header("Location: /admin/chef.php");
    exit;
}

// Fetch current configurations
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (\Exception $e) {
    // defaults
}
?>

<div class="max-w-4xl space-y-8">
    <!-- Title Section -->
    <div>
        <h1 class="text-2xl font-black text-slate-800 dark:text-white">Canteen Settings</h1>
        <p class="text-xs font-semibold text-slate-400 mt-1">Configure tax details, office addresses, order boundaries, and interface themes</p>
    </div>

    <!-- settings Form card -->
    <div class="premium-card p-6 bg-white dark:bg-slate-800">
        <form id="settings-form" class="space-y-6">
            
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-700/50">
                General configurations
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Cafeteria / Company Name *</label>
                    <input type="text" name="company_name" required value="<?php echo htmlspecialchars($settings['company_name'] ?? 'PixelTech Corporate Canteen'); ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Canteen Operating status</label>
                    <select name="canteen_status" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                        <option value="open" <?php echo ($settings['canteen_status'] ?? 'open') === 'open' ? 'selected' : ''; ?>>Open for Orders</option>
                        <option value="closed" <?php echo ($settings['canteen_status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed / Maintenance</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase">Canteen Address *</label>
                    <input type="text" name="canteen_address" required value="<?php echo htmlspecialchars($settings['canteen_address'] ?? 'Sector 62, Noida'); ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
            </div>

            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-700/50 pt-4">
                Tax & Pricing parameters
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">GST Rate (%) *</label>
                    <input type="number" step="0.1" name="gst_rate" required value="<?php echo $settings['gst_rate'] ?? '5'; ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Delivery Charge (INR) *</label>
                    <input type="number" step="0.01" name="delivery_charge" required value="<?php echo $settings['delivery_charge'] ?? '15.00'; ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
            </div>

            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-700/50 pt-4">
                Operating Timings
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Daily Ordering window</label>
                    <input type="text" name="order_timings" placeholder="e.g. 08:00 AM - 09:00 PM" value="<?php echo htmlspecialchars($settings['order_timings'] ?? '08:00 AM - 09:00 PM'); ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Peak Lunch Delivery hours</label>
                    <input type="text" name="lunch_timings" placeholder="e.g. 12:30 PM - 03:00 PM" value="<?php echo htmlspecialchars($settings['lunch_timings'] ?? '12:30 PM - 03:00 PM'); ?>" 
                           class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent">
                </div>
            </div>

            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-700/50 pt-4">
                Theme Stylesheets <span class="text-[10px] text-slate-400 font-semibold normal-case">(Hex codes only)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Primary Theme Color</label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" name="theme_primary" value="<?php echo $settings['theme_primary'] ?? '#16A34A'; ?>" class="w-10 h-10 border border-slate-200 rounded-xl">
                        <span class="text-xs font-mono font-bold text-slate-500"><?php echo $settings['theme_primary'] ?? '#16A34A'; ?></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Accent Warning Color</label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" name="theme_accent" value="<?php echo $settings['theme_accent'] ?? '#F59E0B'; ?>" class="w-10 h-10 border border-slate-200 rounded-xl">
                        <span class="text-xs font-mono font-bold text-slate-500"><?php echo $settings['theme_accent'] ?? '#F59E0B'; ?></span>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 dark:border-slate-700/50 flex justify-end">
                <button type="submit" id="save-settings-btn" 
                        class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-2xl shadow-lg transition-all transform active:scale-95">
                    Save Configurations
                </button>
            </div>

        </form>
    </div>
</div>

<script>
document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('save-settings-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-1.5"></i>Saving Settings...`;

    const form = document.getElementById('settings-form');
    const formData = new FormData(form);

    try {
        const res = await apiRequest('/api/settings.php', {
            method: 'POST',
            body: formData
        });

        if (res.status === 'success') {
            showToast('Canteen settings updated successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            throw new Error(res.message);
        }
    } catch (err) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save Configurations';
    }
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
