<?php
/**
 * Employee Profile settings Page
 */
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">Profile Configurations</h1>
        <p class="text-xs font-semibold text-slate-400 mt-1">Manage contact information, office delivery desk preferences, and password settings</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Avatar & Overview Card -->
        <div class="premium-card p-6 text-center flex flex-col items-center justify-center">
            <div class="relative w-24 h-24 rounded-full bg-brand-100 dark:bg-brand-900/30 border border-brand-200 dark:border-brand-800 flex items-center justify-center text-brand-600 dark:text-brand-400 font-black text-4xl mb-4 shadow">
                <?php echo strtoupper(substr($employee_data['name'], 0, 1)); ?>
            </div>
            
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($employee_data['name']); ?></h3>
            <span class="px-2.5 py-0.5 mt-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700/50 text-slate-500 uppercase tracking-widest">
                ID: <?php echo $employee_data['employee_id']; ?>
            </span>

            <!-- Small stats details -->
            <div class="w-full grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-700/50 text-center">
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Department</span>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-0.5 block truncate"><?php echo htmlspecialchars($employee_data['department']); ?></span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Wallet</span>
                    <span class="text-xs font-black text-amber-600 dark:text-amber-400 mt-0.5 block">₹<?php echo number_format($employee_data['wallet_balance'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings Edit Form -->
        <div class="premium-card p-6 lg:col-span-2">
            <form id="profile-form" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700/50">
                    Contact Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Full Name *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($employee_data['name']); ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Phone Number *</label>
                        <input type="text" name="phone" required value="<?php echo htmlspecialchars($employee_data['phone']); ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase">Email Address *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($employee_data['email']); ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                </div>

                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700/50 pt-4">
                    Office Delivery preferences
                </h3>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Floor preference *</label>
                        <input type="number" name="floor" required min="1" max="10" value="<?php echo $employee_data['floor']; ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Cabin</label>
                        <input type="text" name="cabin" value="<?php echo htmlspecialchars($employee_data['cabin'] ?? ''); ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Desk Number</label>
                        <input type="text" name="desk_number" value="<?php echo htmlspecialchars($employee_data['desk_number'] ?? ''); ?>" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                </div>

                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-700/50 pt-4">
                    Change Password <span class="text-[10px] text-slate-400 font-semibold normal-case">(Leave blank to keep current)</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Current Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">New Password</label>
                        <input type="password" name="new_password" placeholder="••••••••" 
                               class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none transition-all">
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" id="save-profile-btn" 
                            class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-2xl shadow-lg transition-all transform active:scale-95">
                        Save Configurations
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('save-profile-btn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-1.5"></i>Saving...`;

    const form = document.getElementById('profile-form');
    const formData = new FormData(form);

    try {
        const res = await apiRequest('/api/profile.php', {
            method: 'POST',
            body: formData
        });

        if (res.status === 'success') {
            showToast('Profile configurations updated successfully!', 'success');
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

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
