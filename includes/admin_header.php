<?php
/**
 * Shared Admin / Chef / Kitchen Header
 */
require_once dirname(__DIR__) . '/includes/auth.php';

// Route guards
if (!isset($skip_auth) || !$skip_auth) {
    require_admin();
}

$company_name = "Office Canteen Admin";
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

$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_name = $_SESSION['admin_name'] ?? 'Staff Member';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console | <?php echo htmlspecialchars($company_name); ?></title>
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
<body class="h-full flex flex-col md:flex-row bg-slate-50 dark:bg-slate-900 transition-colors duration-200">

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

    <?php if (is_admin_logged_in()): ?>
    <!-- Sidebar Navigation for Desktop -->
    <aside class="hidden md:flex flex-col w-64 bg-white dark:bg-slate-800 border-r border-slate-100 dark:border-slate-700 flex-shrink-0 transition-colors">
        <!-- Brand Header -->
        <div class="h-16 flex items-center px-6 border-b border-slate-100 dark:border-slate-700">
            <a href="/admin/dashboard.php" class="flex items-center space-x-2">
                <div class="p-2 rounded-2xl bg-brand-600 text-white flex items-center justify-center">
                    <i class="fas fa-screwdriver-wrench text-lg"></i>
                </div>
                <span class="font-extrabold text-base text-slate-800 dark:text-white tracking-tight">Console Portal</span>
            </a>
        </div>

        <!-- Role card -->
        <div class="p-4 mx-4 my-3 bg-slate-50 dark:bg-slate-700/50 rounded-2xl flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 font-extrabold text-sm">
                <?php echo strtoupper(substr($admin_role, 0, 2)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Role: <?php echo $admin_role; ?></p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate"><?php echo htmlspecialchars($admin_name); ?></p>
            </div>
        </div>

        <!-- Menu Links -->
        <div class="flex-grow px-3 space-y-1 overflow-y-auto">
            <?php if ($admin_role === 'admin'): ?>
            <a href="/admin/dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-chart-line w-5 text-lg"></i><span>Console Overview</span>
            </a>
            <a href="/admin/orders.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'orders.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-list-check w-5 text-lg"></i><span>Orders Queue</span>
            </a>
            <?php endif; ?>

            <?php if (in_array($admin_role, ['admin', 'chef', 'kitchen'])): ?>
            <a href="/admin/chef.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'chef.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-kitchen-set w-5 text-lg"></i><span>Kitchen Hub</span>
            </a>
            <a href="/admin/inventory.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'inventory.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-warehouse w-5 text-lg"></i><span>Inventory Control</span>
            </a>
            <?php endif; ?>

            <?php if ($admin_role === 'admin'): ?>
            <hr class="border-slate-100 dark:border-slate-700 my-2">
            <p class="px-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Catalog & Members</p>
            <a href="/admin/foods.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'foods.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-bowl-food w-5 text-lg"></i><span>Foods Catalog</span>
            </a>
            <a href="/admin/employees.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'employees.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-users w-5 text-lg"></i><span>Employees List</span>
            </a>
            <a href="/admin/categories.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'categories.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-tags w-5 text-lg"></i><span>Categories Config</span>
            </a>
            <a href="/admin/reports.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'reports.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-chart-pie w-5 text-lg"></i><span>Reports & Stats</span>
            </a>
            <a href="/admin/settings.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all <?php echo (strpos($_SERVER['REQUEST_URI'], 'settings.php') !== false) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' ?>">
                <i class="fas fa-sliders w-5 text-lg"></i><span>Canteen Settings</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Logout Bottom -->
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            <a href="/api/logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all">
                <i class="fas fa-sign-out-alt w-5 text-lg"></i><span>Logout Console</span>
            </a>
        </div>
    </aside>

    <!-- Top Navbar for Mobile screens -->
    <header class="md:hidden bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700 sticky top-0 z-40 transition-colors w-full">
        <div class="px-4 py-3 flex justify-between items-center">
            <a href="/admin/dashboard.php" class="flex items-center space-x-2">
                <div class="p-2 rounded-2xl bg-brand-600 text-white">
                    <i class="fas fa-screwdriver-wrench"></i>
                </div>
                <span class="font-extrabold text-sm text-slate-800 dark:text-white">Console</span>
            </a>
            <div class="flex items-center space-x-2">
                <button onclick="toggleDarkMode()" class="p-2 rounded-2xl text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                <button id="admin-mobile-menu-btn" class="p-2 rounded-2xl text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        <!-- Mobile Drawer menu -->
        <div id="admin-mobile-menu" class="hidden border-t border-slate-100 dark:border-slate-700 p-4 bg-white dark:bg-slate-800 space-y-1">
            <?php if ($admin_role === 'admin'): ?>
            <a href="/admin/dashboard.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-chart-line w-5"></i><span>Overview</span>
            </a>
            <a href="/admin/orders.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-list-check w-5"></i><span>Orders Queue</span>
            </a>
            <?php endif; ?>

            <a href="/admin/chef.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-kitchen-set w-5"></i><span>Kitchen Hub</span>
            </a>
            <a href="/admin/inventory.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-warehouse w-5"></i><span>Inventory Control</span>
            </a>

            <?php if ($admin_role === 'admin'): ?>
            <hr class="border-slate-100 dark:border-slate-700">
            <a href="/admin/foods.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-bowl-food w-5"></i><span>Foods</span>
            </a>
            <a href="/admin/employees.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-users w-5"></i><span>Employees</span>
            </a>
            <a href="/admin/categories.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-tags w-5"></i><span>Categories</span>
            </a>
            <a href="/admin/reports.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-chart-pie w-5"></i><span>Reports & Stats</span>
            </a>
            <a href="/admin/settings.php" class="flex items-center space-x-3 p-3 rounded-2xl text-slate-700 dark:text-slate-300 font-semibold">
                <i class="fas fa-sliders w-5"></i><span>Settings</span>
            </a>
            <?php endif; ?>
            <hr class="border-slate-100 dark:border-slate-700">
            <a href="/api/logout.php" class="flex items-center space-x-3 p-3 rounded-2xl text-red-600 font-semibold">
                <i class="fas fa-sign-out-alt w-5"></i><span>Logout Console</span>
            </a>
        </div>
        <script>
            document.getElementById('admin-mobile-menu-btn').addEventListener('click', () => {
                const menu = document.getElementById('admin-mobile-menu');
                menu.classList.toggle('hidden');
            });
        </script>
    </header>
    <?php endif; ?>

    <!-- Main Workspace Container -->
    <main class="flex-grow p-4 md:p-8 overflow-y-auto w-full">
