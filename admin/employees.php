<?php
/**
 * Admin Console: Employee Members Directory CRUD
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Access guard
if ($admin_role !== 'admin') {
    header("Location: /admin/chef.php");
    exit;
}

$error = '';
$success = '';

// Handle POST submissions (Save / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $employee_id = sanitize($_POST['employee_id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $floor = (int)($_POST['floor'] ?? 1);
        $cabin = sanitize($_POST['cabin'] ?? '');
        $desk_number = sanitize($_POST['desk_number'] ?? '');
        $wallet_balance = (float)($_POST['wallet_balance'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        $password = $_POST['password'] ?? '';

        if (empty($employee_id) || empty($name) || empty($department) || empty($email)) {
            $error = 'Employee Code, Name, Department, and Email are required.';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $pass_sql = "";
                    $pass_params = [];
                    if (!empty($password)) {
                        $pass_sql = ", password_hash = ?";
                        $pass_params[] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $sql = "UPDATE employees SET employee_id = ?, name = ?, department = ?, phone = ?, email = ?, floor = ?, cabin = ?, desk_number = ?, wallet_balance = ?, status = ? {$pass_sql} WHERE id = ?";
                    $params = array_merge([$employee_id, $name, $department, $phone, $email, $floor, $cabin, $desk_number, $wallet_balance, $status], $pass_params, [$id]);

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    log_activity($pdo, 'Edit Employee', "Updated employee details: {$name} ({$employee_id})");
                    $success = 'Employee details updated successfully.';
                } else {
                    // Insert
                    $p_hash = password_hash(!empty($password) ? $password : 'password123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO employees (employee_id, name, department, phone, email, password_hash, floor, cabin, desk_number, wallet_balance, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$employee_id, $name, $department, $phone, $email, $p_hash, $floor, $cabin, $desk_number, $wallet_balance, $status]);

                    log_activity($pdo, 'Add Employee', "Created employee member: {$name} ({$employee_id})");
                    $success = 'New employee member registered successfully.';
                }
            } catch (\Exception $e) {
                $error = 'Operation failed: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $name_stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
                $name_stmt->execute([$id]);
                $name = $name_stmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Delete Employee', "Deleted employee member: {$name} (ID: {$id})");
                $success = 'Employee member deleted successfully.';
            } catch (\Exception $e) {
                $error = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch employees list
$employees = [];
try {
    $emp_stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_id ASC");
    $employees = $emp_stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Data fetch error: ' . $e->getMessage();
}
?>

<div class="space-y-8">
    <!-- Header title -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Employees Directory</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Manage office employee details, allocate delivery floors, and check wallet balance statistics</p>
        </div>
        
        <button onclick="openEmployeeModal()" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
            <i class="fas fa-plus mr-1.5"></i>Register Employee
        </button>
    </div>

    <!-- Feedbacks -->
    <?php if (!empty($success)): ?>
        <div class="p-4 bg-green-50 text-green-600 rounded-2xl border border-green-100 text-xs font-bold flex items-center space-x-2">
            <i class="fas fa-check-circle text-base"></i><span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 text-xs font-bold flex items-center space-x-2">
            <i class="fas fa-exclamation-circle text-base"></i><span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Employees Directory Table -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-400 border-b border-slate-100 dark:border-slate-700/50 font-bold uppercase">
                        <th class="p-4">Employee ID</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Floor / Desk</th>
                        <th class="p-4">Wallet</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="8" class="p-12 text-center text-slate-400">No employees registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employees as $e): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 text-slate-700 dark:text-slate-300 transition-colors">
                                <td class="p-4 font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($e['employee_id']); ?></td>
                                <td class="p-4 font-semibold"><?php echo htmlspecialchars($e['name']); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($e['department']); ?></td>
                                <td class="p-4">
                                    <span class="block"><?php echo htmlspecialchars($e['email']); ?></span>
                                    <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($e['phone']); ?></span>
                                </td>
                                <td class="p-4">
                                    Floor <?php echo $e['floor']; ?>, Desk <?php echo htmlspecialchars($e['desk_number'] ?: 'N/A'); ?>
                                </td>
                                <td class="p-4 font-bold text-amber-600">₹<?php echo number_format($e['wallet_balance'], 2); ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider <?php echo $e['status'] === 'active' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?>">
                                        <?php echo $e['status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button onclick='editEmployee(<?php echo json_encode($e); ?>)' class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                        
                                        <form method="POST" onsubmit="return confirm('Delete employee member?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                            <button type="submit" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 font-bold rounded-lg"><i class="fas fa-trash-can text-xs"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Register / Edit Modal -->
<div id="employee-crud-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 dark:border-slate-700 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-200">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 id="modal-title" class="text-lg font-bold text-slate-800 dark:text-white">Register Employee</h3>
            <button onclick="closeEmployeeModal()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" id="emp-id" name="id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Employee ID *</label>
                    <input type="text" id="emp-code" name="employee_id" required placeholder="e.g. EMP031" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Full Name *</label>
                    <input type="text" id="emp-name" name="name" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Department *</label>
                    <input type="text" id="emp-dept" name="department" required placeholder="e.g. IT Dev" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Phone Number</label>
                    <input type="text" id="emp-phone" name="phone" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase">Email Address *</label>
                    <input type="email" id="emp-email" name="email" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Floor Preference</label>
                    <input type="number" id="emp-floor" name="floor" min="1" max="10" value="1" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Cabin / Room</label>
                    <input type="text" id="emp-cabin" name="cabin" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Desk Number</label>
                    <input type="text" id="emp-desk" name="desk_number" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Wallet Starting Balance</label>
                    <input type="number" step="0.01" id="emp-wallet" name="wallet_balance" value="500.00" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Status</label>
                    <select id="emp-status" name="status" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Password <span id="pwd-note" class="text-[9px] font-semibold text-slate-400 lowercase">(Default: password123)</span></label>
                    <input type="password" id="emp-pwd" name="password" placeholder="••••••••" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 flex space-x-3">
                <button type="button" onclick="closeEmployeeModal()" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-2xl transition-colors">Cancel</button>
                <button type="submit" class="flex-1 py-3 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-lg transition-colors">Register</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEmployeeModal() {
    document.getElementById('modal-title').textContent = 'Register Employee';
    document.getElementById('emp-id').value = '0';
    document.getElementById('emp-code').value = '';
    document.getElementById('emp-code').disabled = false;
    document.getElementById('emp-name').value = '';
    document.getElementById('emp-dept').value = '';
    document.getElementById('emp-phone').value = '';
    document.getElementById('emp-email').value = '';
    document.getElementById('emp-floor').value = '1';
    document.getElementById('emp-cabin').value = '';
    document.getElementById('emp-desk').value = '';
    document.getElementById('emp-wallet').value = '500.00';
    document.getElementById('emp-status').value = 'active';
    document.getElementById('emp-pwd').value = '';
    document.getElementById('pwd-note').textContent = '(Default: password123)';

    const modal = document.getElementById('employee-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeEmployeeModal() {
    const modal = document.getElementById('employee-crud-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function editEmployee(emp) {
    document.getElementById('modal-title').textContent = 'Edit Employee details';
    document.getElementById('emp-id').value = emp.id;
    document.getElementById('emp-code').value = emp.employee_id;
    document.getElementById('emp-code').disabled = true; // Lock employee code
    document.getElementById('emp-name').value = emp.name;
    document.getElementById('emp-dept').value = emp.department;
    document.getElementById('emp-phone').value = emp.phone;
    document.getElementById('emp-email').value = emp.email;
    document.getElementById('emp-floor').value = emp.floor;
    document.getElementById('emp-cabin').value = emp.cabin || '';
    document.getElementById('emp-desk').value = emp.desk_number || '';
    document.getElementById('emp-wallet').value = emp.wallet_balance;
    document.getElementById('emp-status').value = emp.status;
    document.getElementById('emp-pwd').value = '';
    document.getElementById('pwd-note').textContent = '(Leave blank to keep current)';

    const modal = document.getElementById('employee-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
