<?php
/**
 * Admin Console: Food Categories CRUD Configuration
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
        $name = sanitize($_POST['name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $visibility = isset($_POST['visibility']) ? 1 : 0;

        if (empty($name) || empty($icon)) {
            $error = 'Category Name and FontAwesome Icon class are required fields.';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, sort_order = ?, visibility = ? WHERE id = ?");
                    $stmt->execute([$name, $icon, $sort_order, $visibility, $id]);
                    log_activity($pdo, 'Edit Category', "Updated food category: {$name} (ID: {$id})");
                    $success = 'Category updated successfully.';
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO categories (name, icon, sort_order, visibility) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $icon, $sort_order, $visibility]);
                    log_activity($pdo, 'Add Category', "Created food category: {$name}");
                    $success = 'New category added successfully.';
                }
            } catch (\Exception $e) {
                $error = 'Operation failed: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $name_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $name_stmt->execute([$id]);
                $name = $name_stmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Delete Category', "Deleted category: {$name} (ID: {$id})");
                $success = 'Category deleted successfully.';
            } catch (\Exception $e) {
                $error = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories list
$categories = [];
try {
    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Data fetch error: ' . $e->getMessage();
}
?>

<div class="space-y-8">
    <!-- Header Title -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Categories Configuration</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Configure horizontal sliding chips tags, sort indices, and page visibility</p>
        </div>
        
        <button onclick="openCategoryModal()" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
            <i class="fas fa-plus mr-1.5"></i>Add Category
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

    <!-- Categories List Table -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-400 border-b border-slate-100 dark:border-slate-700/50 font-bold uppercase">
                        <th class="p-4">Sort index</th>
                        <th class="p-4">Icon preview</th>
                        <th class="p-4">Category Name</th>
                        <th class="p-4">Icon class</th>
                        <th class="p-4">Visibility</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="6" class="p-12 text-center text-slate-400">No categories created.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $c): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 text-slate-700 dark:text-slate-300 transition-colors">
                                <td class="p-4 font-black text-slate-550"><?php echo $c['sort_order']; ?></td>
                                <td class="p-4">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-brand-600">
                                        <i class="fas <?php echo $c['icon']; ?> text-sm"></i>
                                    </div>
                                </td>
                                <td class="p-4 font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($c['name']); ?></td>
                                <td class="p-4 font-mono text-[10px] text-slate-400"><?php echo htmlspecialchars($c['icon']); ?></td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider <?php echo $c['visibility'] ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?>">
                                        <?php echo $c['visibility'] ? 'Visible' : 'Hidden'; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button onclick='editCategory(<?php echo json_encode($c); ?>)' class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                        
                                        <form method="POST" onsubmit="return confirm('Delete this category?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
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

<!-- CRUD Modal Form -->
<div id="category-crud-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-sm w-full shadow-2xl border border-slate-100 dark:border-slate-700 transform scale-95 opacity-0 transition-all duration-200">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 id="modal-title" class="text-base font-bold text-slate-800 dark:text-white">Add Category</h3>
            <button onclick="closeCategoryModal()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" id="cat-id" name="id" value="0">

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Category Name *</label>
                <input type="text" id="cat-name" name="name" required placeholder="e.g. Desserts" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">FontAwesome Icon Class *</label>
                <input type="text" id="cat-icon" name="icon" required placeholder="e.g. fa-ice-cream" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Sort Order Index</label>
                <input type="number" id="cat-sort" name="sort_order" value="0" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:outline-none">
            </div>

            <div class="flex items-center pt-2">
                <input type="checkbox" id="cat-visible" name="visibility" value="1" checked class="h-4.5 w-4.5 text-brand-600 rounded">
                <label for="cat-visible" class="ml-2 block text-xs text-slate-500 font-semibold">Category is active and visible on menu</label>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 flex space-x-3">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-lg transition-colors">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('modal-title').textContent = 'Add Category';
    document.getElementById('cat-id').value = '0';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-icon').value = '';
    document.getElementById('cat-sort').value = '0';
    document.getElementById('cat-visible').checked = true;

    const modal = document.getElementById('category-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeCategoryModal() {
    const modal = document.getElementById('category-crud-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function editCategory(c) {
    document.getElementById('modal-title').textContent = 'Edit Category';
    document.getElementById('cat-id').value = c.id;
    document.getElementById('cat-name').value = c.name;
    document.getElementById('cat-icon').value = c.icon;
    document.getElementById('cat-sort').value = c.sort_order;
    document.getElementById('cat-visible').checked = c.visibility == 1;

    const modal = document.getElementById('category-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
