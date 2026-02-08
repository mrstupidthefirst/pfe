<?php
/**
 * Admin - Manage Categories
 * Add, edit, and manage product categories
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')");
            if ($stmt->execute([$name, $description])) {
                $message = 'Category added successfully.';
            } else {
                $error = 'Failed to add category.';
            }
        }
    } elseif (isset($_POST['update_category'])) {
        $id = intval($_POST['category_id']);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $status, $id])) {
                $message = 'Category updated successfully.';
            } else {
                $error = 'Failed to update category.';
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        
        // Check if category is used by products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = 'Cannot delete category. It is used by ' . $count . ' product(s).';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Category deleted successfully.';
            } else {
                $error = 'Failed to delete category.';
            }
        }
    }
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$page_title = 'Manage Categories';
include '../includes/header.php';
?>

<div class="container">
    <h1>Manage Categories</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo escape($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo escape($error); ?></div>
    <?php endif; ?>
    
    <!-- Add Category Form -->
    <div class="form-section">
        <h2>Add New Category</h2>
        <form method="POST" class="category-form">
            <div class="form-group">
                <label for="name">Category Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
        </form>
    </div>
    
    <!-- Categories List -->
    <div class="table-container">
        <h2>Existing Categories</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5">No categories found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <input type="text" name="name" value="<?php echo escape($category['name']); ?>" required>
                            </td>
                            <td>
                                    <textarea name="description" rows="2"><?php echo escape($category['description']); ?></textarea>
                            </td>
                            <td>
                                    <select name="status">
                                        <option value="active" <?php echo ($category['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($category['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" name="update_category" class="btn btn-success btn-sm">Update</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <p><a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
</div>

<?php include '../includes/footer.php'; ?>
