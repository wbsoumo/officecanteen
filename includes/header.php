<?php
/**
 * Shared Employee Portal Header
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Route guards
if (!isset($skip_auth) || !$skip_auth) {
    require_employee();
}

// Fetch current canteen settings
$company_name = "Office Canteen";
$primary_color = "#16A34A";
$accent_color = "#F59E0B";

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    if (isset($settings['company_name'])) $company_name = $settings['company_name'];
    if (isset($settings['theme_primary'])) $primary_color = $settings['theme_primary'];
    if (isset($settings['theme_accent'])) $accent_color = $settings['theme_accent'];
} catch (\Exception $e) {
    // Silence settings fetch error
}

// Fetch current employee data if logged in
$employee_data = null;
if (is_employee_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION['employee_db_id']]);
    $employee_data = $stmt->fetch();
    // Keep session wallet in sync
    if ($employee_data) {
        $_SESSION['wallet_balance'] = $employee_data['wallet_balance'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company_name); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#F0FDF4',
                            100: '#DCFCE7',
                            200: '#BBF7D0',
                            500: '#22C55E',
                            600: '<?php echo $primary_color; ?>',
                            700: '#15803D',
                        },
                        accent: {
                            500: '<?php echo $accent_color; ?>',
                        }
                    },
                    borderRadius: {
                        '3xl': '20px',
                    }
                }
            }
        }
    </script>
    <!-- Custom style variables & loader -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/app.js?v=1.0.1" defer></script>
</head>
<body class="h-full flex flex-col bg-slate-50 dark:bg-slate-900 transition-colors duration-200">

    <!-- Global API loading spinner -->
    <div id="global-loader" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
        <div class="flex flex-col items-center bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-700">
            <svg class="animate-spin h-10 w-10 text-brand-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Processing...</span>
        </div>
    </div>

    <!-- Navigation Header -->
    <?php if (is_employee_logged_in()): ?>
    <nav class="bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700 sticky top-0 z-40 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left Nav Brand & Desktop Menu -->
                <div class="flex">
                    <a href="/employee/dashboard.php" class="flex-shrink-0 flex items-center space-x-2">
                        <div class="p-2 rounded-2xl bg-brand-600 text-white flex items-center justify-center">
                            <i class="fas fa-bowl-food text-lg"></i>
                        </div>
                        <span class="font-extrabold text-lg text-slate-800 dark:text-white tracking-tight">
                            <?php echo htmlspecialchars($company_name); ?>
                        </span>
                    </a>
                    
                    <div class="hidden md:ml-8 md:flex md:space-x-4 md:items-center">
                        <a href="/employee/dashboard.php" class="px-3 py-2 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                            <i class="fas fa-chart-pie mr-1.5"></i>Dashboard
                        </a>
                        <a href="/employee/order.php" class="px-3 py-2 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'order.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                            <i class="fas fa-utensils mr-1.5"></i>Order Food
                        </a>
                        <a href="/employee/order-history.php" class="px-3 py-2 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'order-history.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                            <i class="fas fa-clock-rotate-left mr-1.5"></i>History
                        </a>
                    </div>
                </div>

                <!-- Right Nav Operations -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Wallet demo card -->
                    <div class="flex items-center space-x-2 px-4 py-1.5 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-2xl">
                        <i class="fas fa-wallet text-amber-500 text-sm"></i>
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Wallet:</span>
                        <span class="text-sm font-extrabold text-amber-800 dark:text-amber-300">₹<?php echo number_format($employee_data['wallet_balance'] ?? 0.00, 2); ?></span>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="p-2 rounded-2xl text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>

                    <!-- Profile Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 focus:outline-none py-2">
                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 font-bold border border-slate-300 dark:border-slate-600">
                                <?php echo strtoupper(substr($employee_data['name'] ?? 'E', 0, 1)); ?>
                            </div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($employee_data['name'] ?? 'Employee'); ?></span>
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 w-48 mt-0 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden py-1">
                            <a href="/employee/profile.php" class="block px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium">
                                <i class="fas fa-user-circle mr-2 text-slate-400"></i>My Profile
                            </a>
                            <hr class="border-slate-100 dark:border-slate-700">
                            <a href="/api/logout.php" class="block px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 font-medium">
                                <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Hamburger menu button (Mobile) -->
                <div class="flex items-center md:hidden space-x-2">
                    <button onclick="toggleDarkMode()" class="p-2 rounded-2xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                    
                    <button id="mobile-menu-btn" class="p-2 rounded-2xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-100 dark:border-slate-700 px-4 pt-2 pb-4 bg-white dark:bg-slate-800 space-y-2">
            <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-950/20 rounded-2xl mb-2">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400"><i class="fas fa-wallet mr-1.5"></i>Wallet:</span>
                <span class="text-sm font-extrabold text-amber-800 dark:text-amber-300">₹<?php echo number_format($employee_data['wallet_balance'] ?? 0.00, 2); ?></span>
            </div>
            
            <a href="/employee/dashboard.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold transition-all">
                <i class="fas fa-chart-pie w-5 text-slate-400"></i><span>Dashboard</span>
            </a>
            <a href="/employee/order.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold transition-all">
                <i class="fas fa-utensils w-5 text-slate-400"></i><span>Order Food</span>
            </a>
            <a href="/employee/order-history.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold transition-all">
                <i class="fas fa-clock-rotate-left w-5 text-slate-400"></i><span>History</span>
            </a>
            <a href="/employee/profile.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold transition-all">
                <i class="fas fa-user-circle w-5 text-slate-400"></i><span>Profile</span>
            </a>
            <hr class="border-slate-100 dark:border-slate-700">
            <a href="/api/logout.php" class="flex items-center space-x-3 p-3 rounded-2xl text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 font-semibold transition-all">
                <i class="fas fa-sign-out-alt w-5"></i><span>Sign Out</span>
            </a>
        </div>
    </nav>
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    <?php endif; ?>

    <main class="flex-grow">
