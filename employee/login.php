<?php
/**
 * Employee Login Page
 */
$skip_auth = true; // Skip route guards
require_once dirname(__DIR__) . '/includes/header.php';

// Redirect to dashboard if already logged in
if (is_employee_logged_in()) {
    header("Location: /employee/dashboard.php");
    exit;
}
?>

<div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Icon -->
        <div class="mx-auto h-16 w-16 rounded-3xl bg-brand-600 flex items-center justify-center text-white shadow-lg transform hover:scale-105 transition-transform duration-300">
            <i class="fas fa-bowl-food text-3xl"></i>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Welcome back!
        </h2>
        <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">
            Sign in to order from the corporate cafeteria
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white dark:bg-slate-800 py-8 px-6 shadow-2xl rounded-3xl border border-slate-100 dark:border-slate-700/50 transition-colors">
            
            <!-- Error Alert Container -->
            <div id="login-error" class="hidden mb-4 p-4 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 text-sm font-semibold text-red-600 dark:text-red-400 flex items-center space-x-2">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span id="error-message">Invalid credentials</span>
            </div>

            <form id="login-form" class="space-y-6">
                <input type="hidden" name="login_type" value="employee">
                
                <div>
                    <label for="employee_id" class="block text-sm font-bold text-slate-700 dark:text-slate-300">
                        Employee ID
                    </label>
                    <div class="mt-1 relative rounded-2xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <input id="employee_id" name="employee_id" type="text" required placeholder="e.g. EMP001" 
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

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" 
                               class="h-4.5 w-4.5 text-brand-600 focus:ring-brand-600 border-slate-300 rounded-md">
                        <label for="remember_me" class="ml-2 block text-sm text-slate-500 dark:text-slate-400 font-medium">
                            Remember Me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" onclick="showToast('Password reset demo: Contact IT Admin or check seed credentials (password123)', 'info')" 
                           class="font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" id="submit-btn" 
                            class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 transition-all transform active:scale-[0.98]">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-slate-100 dark:border-slate-700/50 pt-4 text-center">
                <a href="/admin/login.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <i class="fas fa-shield-halved mr-1.5"></i>Are you Kitchen Staff/Admin? Log in here
                </a>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    const errorContainer = document.getElementById('login-error');
    const errorMessage = document.getElementById('error-message');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Setup loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-2"></i>Verifying...`;
        errorContainer.classList.add('hidden');

        const formData = new FormData(form);

        try {
            const data = await apiRequest('/api/login.php', {
                method: 'POST',
                body: formData
            });

            if (data.status === 'success') {
                showToast('Welcome back, ' + data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '/employee/dashboard.php';
                }, 800);
            } else {
                throw new Error(data.message || 'Login failed.');
            }
        } catch (err) {
            errorMessage.textContent = err.message || 'Network error, please try again.';
            errorContainer.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Sign In';
        }
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
