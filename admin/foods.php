<?php
/**
 * Admin Console: Food Catalog CRUD Management
 */
require_once dirname(__DIR__) . '/includes/admin_header.php';

// Access guard
if ($admin_role !== 'admin') {
    header("Location: /admin/chef.php");
    exit;
}

$error = '';
$success = '';

// Handle POST submissions (Add, Edit, Delete actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = sanitize($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $prep_time = (int)($_POST['prep_time'] ?? 0);
        $veg_nonveg = sanitize($_POST['veg_nonveg'] ?? 'veg');
        
        $description = sanitize($_POST['description'] ?? '');
        $ingredients = sanitize($_POST['ingredients'] ?? '');
        $calories = (int)($_POST['calories'] ?? 0);
        $spice_level = (int)($_POST['spice_level'] ?? 0);
        $image_url = sanitize($_POST['image_url'] ?? '');
        
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($name) || $category_id <= 0 || $price <= 0 || $prep_time <= 0) {
            $error = 'Please fill in all required fields (Name, Category, Price, Prep Time).';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE foods SET name = ?, category_id = ?, price = ?, prep_time = ?, veg_nonveg = ?, description = ?, ingredients = ?, calories = ?, spice_level = ?, image_url = ?, is_popular = ?, is_featured = ? WHERE id = ?");
                    $stmt->execute([$name, $category_id, $price, $prep_time, $veg_nonveg, $description, $ingredients, $calories, $spice_level, $image_url, $is_popular, $is_featured, $id]);
                    log_activity($pdo, 'Edit Food', "Updated food item: {$name} (ID: {$id})");
                    $success = 'Food catalog item updated successfully.';
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO foods (name, category_id, price, prep_time, veg_nonveg, description, ingredients, calories, spice_level, image_url, is_popular, is_featured, stock_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
                    $stmt->execute([$name, $category_id, $price, $prep_time, $veg_nonveg, $description, $ingredients, $calories, $spice_level, $image_url, $is_popular, $is_featured]);
                    $new_id = $pdo->lastInsertId();
                    
                    // Create default inventory record for new item
                    $inv_stmt = $pdo->prepare("INSERT INTO inventory (food_id, current_stock, status) VALUES (?, 50, 'available')");
                    $inv_stmt->execute([$new_id]);

                    log_activity($pdo, 'Add Food', "Created food item: {$name}");
                    $success = 'New food catalog item created successfully.';
                }
            } catch (\Exception $e) {
                $error = 'Operation failed: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                // Fetch name for logging
                $name_stmt = $pdo->prepare("SELECT name FROM foods WHERE id = ?");
                $name_stmt->execute([$id]);
                $name = $name_stmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Delete Food', "Deleted food item: {$name} (ID: {$id})");
                $success = 'Food item deleted successfully.';
            } catch (\Exception $e) {
                $error = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all foods
$foods = [];
$categories = [];
try {
    $foods_stmt = $pdo->query("SELECT f.*, c.name as category_name 
                               FROM foods f 
                               LEFT JOIN categories c ON f.category_id = c.id 
                               ORDER BY f.name ASC");
    $foods = $foods_stmt->fetchAll();

    $cat_stmt = $pdo->query("SELECT id, name FROM categories WHERE visibility = 1 ORDER BY sort_order ASC");
    $categories = $cat_stmt->fetchAll();
} catch (\Exception $e) {
    $error = 'Data fetch error: ' . $e->getMessage();
}
?>

<div class="space-y-8">
    
    <!-- Title Area -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Foods Catalog</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Manage corporate canteen dishes menu items, metadata parameters, and tags</p>
        </div>
        
        <button onclick="openFoodCrudModal()" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow transition-colors">
            <i class="fas fa-plus mr-1.5"></i>Add New Food
        </button>
    </div>

    <!-- Feedback messages -->
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

    <!-- Foods List table -->
    <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-400 border-b border-slate-100 dark:border-slate-700/50 font-bold uppercase">
                        <th class="p-4">Dish</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Price</th>
                        <th class="p-4">Prep Time</th>
                        <th class="p-4">Cal / Spice</th>
                        <th class="p-4">Badges</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php if (empty($foods)): ?>
                        <tr><td colspan="7" class="p-12 text-center text-slate-400">No dishes inside canteen menu catalog.</td></tr>
                    <?php else: ?>
                        <?php foreach ($foods as $f): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 text-slate-700 dark:text-slate-300 transition-colors">
                                <td class="p-4 font-bold text-slate-800 dark:text-slate-100">
                                    <div class="flex items-center space-x-3">
                                        <img src="<?php echo $f['image_url']; ?>" class="w-10 h-10 object-cover rounded-xl bg-slate-100" onerror="this.src='https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600'">
                                        <div>
                                            <span class="block"><?php echo htmlspecialchars($f['name']); ?></span>
                                            <span class="text-[9px] font-bold uppercase <?php echo $f['veg_nonveg'] === 'veg' ? 'text-green-600' : 'text-red-500'; ?>">
                                                <?php echo $f['veg_nonveg']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4"><?php echo htmlspecialchars($f['category_name'] ?: 'N/A'); ?></td>
                                <td class="p-4 font-black">₹<?php echo number_format($f['price'], 2); ?></td>
                                <td class="p-4 font-bold text-slate-500"><?php echo $f['prep_time']; ?> Mins</td>
                                <td class="p-4 text-slate-500">
                                    <?php echo $f['calories']; ?> kCal / Lvl <?php echo $f['spice_level']; ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-wrap gap-1">
                                        <?php if ($f['is_featured']): ?>
                                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 font-extrabold text-[8px] uppercase tracking-wider">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($f['is_popular']): ?>
                                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-600 font-extrabold text-[8px] uppercase tracking-wider">Popular</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <button onclick='editFood(<?php echo json_encode($f); ?>)' class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-lg"><i class="fas fa-pen text-xs"></i></button>
                                        
                                        <form method="POST" onsubmit="return confirm('Delete this food item?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
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

<!-- Add / Edit Modal -->
<div id="food-crud-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 dark:border-slate-700 max-h-[90vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-200">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 id="modal-title" class="text-lg font-bold text-slate-800 dark:text-white">Add New Food Item</h3>
            <button onclick="closeFoodCrudModal()" class="w-8 h-8 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" id="food-id" name="id" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Food Name *</label>
                    <input type="text" id="food-name" name="name" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Category *</label>
                    <select id="food-category" name="category_id" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Price (INR) *</label>
                    <input type="number" step="0.01" id="food-price" name="price" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Prep Time (mins) *</label>
                    <input type="number" id="food-preptime" name="prep_time" required class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Calories (kCal)</label>
                    <input type="number" id="food-calories" name="calories" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Spice Level (0 to 3)</label>
                    <select id="food-spice" name="spice_level" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
                        <option value="0">Mild / None</option>
                        <option value="1">Medium</option>
                        <option value="2">Spicy</option>
                        <option value="3">Extra Hot</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Type *</label>
                    <div class="mt-2 flex space-x-4">
                        <label class="flex items-center text-xs font-bold"><input type="radio" name="veg_nonveg" id="food-veg" value="veg" checked class="mr-1.5"> Veg</label>
                        <label class="flex items-center text-xs font-bold"><input type="radio" name="veg_nonveg" id="food-nonveg" value="nonveg" class="mr-1.5"> Non-Veg</label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Badges</label>
                    <div class="mt-2 flex space-x-4">
                        <label class="flex items-center text-xs font-bold"><input type="checkbox" id="food-popular" name="is_popular" value="1" class="mr-1.5 rounded"> Popular</label>
                        <label class="flex items-center text-xs font-bold"><input type="checkbox" id="food-featured" name="is_featured" value="1" class="mr-1.5 rounded"> Featured</label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Image URL (Unsplash Link)</label>
                <input type="url" id="food-image" name="image_url" placeholder="https://..." class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Short Description</label>
                <textarea id="food-desc" name="description" rows="2" class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Ingredients</label>
                <textarea id="food-ingredients" name="ingredients" rows="2" placeholder="Sauteed paneer, capsicum, bell peppers..." class="mt-1 block w-full border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-brand-600 focus:outline-none"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 flex space-x-3">
                <button type="button" onclick="closeFoodCrudModal()" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-2xl transition-colors">Cancel</button>
                <button type="submit" class="flex-1 py-3 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-2xl shadow-lg transition-colors">Save Food</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFoodCrudModal() {
    document.getElementById('modal-title').textContent = 'Add New Food Item';
    document.getElementById('food-id').value = '0';
    document.getElementById('food-name').value = '';
    document.getElementById('food-category').value = '';
    document.getElementById('food-price').value = '';
    document.getElementById('food-preptime').value = '';
    document.getElementById('food-calories').value = '';
    document.getElementById('food-spice').value = '0';
    document.getElementById('food-veg').checked = true;
    document.getElementById('food-popular').checked = false;
    document.getElementById('food-featured').checked = false;
    document.getElementById('food-image').value = '';
    document.getElementById('food-desc').value = '';
    document.getElementById('food-ingredients').value = '';

    const modal = document.getElementById('food-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeFoodCrudModal() {
    const modal = document.getElementById('food-crud-modal');
    modal.querySelector('.transform').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function editFood(food) {
    document.getElementById('modal-title').textContent = 'Edit Food Item';
    document.getElementById('food-id').value = food.id;
    document.getElementById('food-name').value = food.name;
    document.getElementById('food-category').value = food.category_id;
    document.getElementById('food-price').value = food.price;
    document.getElementById('food-preptime').value = food.prep_time;
    document.getElementById('food-calories').value = food.calories;
    document.getElementById('food-spice').value = food.spice_level;
    
    if (food.veg_nonveg === 'veg') {
        document.getElementById('food-veg').checked = true;
    } else {
        document.getElementById('food-nonveg').checked = true;
    }

    document.getElementById('food-popular').checked = food.is_popular == 1;
    document.getElementById('food-featured').checked = food.is_featured == 1;
    document.getElementById('food-image').value = food.image_url || '';
    document.getElementById('food-desc').value = food.description || '';
    document.getElementById('food-ingredients').value = food.ingredients || '';

    const modal = document.getElementById('food-crud-modal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('.transform').classList.remove('scale-95', 'opacity-0');
    }, 10);
}
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
