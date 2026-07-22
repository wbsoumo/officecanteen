<?php
/**
 * Admin Console Portal Login
 */
$skip_auth = true;
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header("Location: /admin/dashboard.php");
    exit;
}
?>

<div class="min-h-[85vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8 w-full">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Brand Logo Icon -->
        <div class="mx-auto h-16 w-16 rounded-3xl bg-brand-600 flex items-center justify-center text-white shadow-lg transform hover:rotate-12 transition-transform duration-300">
            <i class="fas fa-screwdriver-wrench text-3xl"></i>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Console Login
        </h2>
        <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">
            Canteen Administrators, Chefs, and Kitchen Staff Console access
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white dark:bg-slate-800 py-8 px-6 shadow-2xl rounded-3xl border border-slate-100 dark:border-slate-700/50 transition-colors">
            
            <!-- Error Alert -->
            <div id="login-error" class="hidden mb-4 p-4 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 text-sm font-semibold text-red-600 dark:text-red-400 flex items-center space-x-2">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span id="error-message">Invalid administrator credentials</span>
            </div>

            <form id="admin-login-form" class="space-y-6">
                <input type="hidden" name="login_type" value="admin">

                <div>
                    <label for="username" class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                        Username / Staff ID
                    </label>
                    <div class="mt-1 relative rounded-2xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <input id="username" name="username" type="text" required placeholder="e.g. admin or chef" 
                               class="block w-full pl-10 pr-4 py-3 border border-slate-200 dark:border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-sm transition-all bg-slate-50 dark:bg-slate-900">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                        Password
                    </label>
                    <div class="mt-1 relative rounded-2xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input id="password" name="password" type="password" required placeholder="••••••••" 
                               class="block w-full pl-10 pr-4 py-3 border border-slate-200 dark:border-slate-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent text-sm transition-all bg-slate-50 dark:bg-slate-900">
                    </div>
                </div>

                <div>
                    <button type="submit" id="submit-btn" 
                            class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 transition-all transform active:scale-[0.98]">
                        Access Console
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-slate-100 dark:border-slate-700/50 pt-4 text-center">
                <a href="/employee/login.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <i class="fas fa-arrow-left mr-1.5"></i>Are you an Employee? Return to order food
                </a>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('admin-login-form');
    const errorContainer = document.getElementById('login-error');
    const errorMessage = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-2"></i>Verifying Console...`;
        errorContainer.classList.add('hidden');

        const formData = new FormData(form);

        try {
            const data = await apiRequest('/api/login.php', {
                method: 'POST',
                body: formData
            });

            if (data.status === 'success') {
                showToast('Welcome to Canteen Admin Hub', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '/admin/dashboard.php';
                }, 800);
            } else {
                throw new Error(data.message || 'Login failed.');
            }
        } catch (err) {
            errorMessage.textContent = err.message || 'Verification failed, please check inputs.';
            errorContainer.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Access Console';
        }
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
