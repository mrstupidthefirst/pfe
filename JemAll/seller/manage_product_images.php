<?php
/**
 * Seller - Manage Product Images Order
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];
$product_id = intval($_GET['id'] ?? 0);
$message = '';
$message_type = 'success';

// Verify product ownership
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$product_id, $seller_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

// Handle image order update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $image_orders = $_POST['image_order'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($image_orders as $image_id => $order) {
            $image_id = intval($image_id);
            $order = intval($order);
            
            // Verify image belongs to this product
            $stmt = $pdo->prepare("SELECT id FROM product_images WHERE id = ? AND product_id = ?");
            $stmt->execute([$image_id, $product_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE product_images SET display_order = ? WHERE id = ?");
                $stmt->execute([$order, $image_id]);
            }
        }
        
        $pdo->commit();
        $message = 'Ordre des images mis à jour avec succès.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Erreur lors de la mise à jour.';
        $message_type = 'danger';
        error_log('Error updating image order: ' . $e->getMessage());
    }
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $image_id = intval($_POST['image_id'] ?? 0);
    
    try {
        // Get image path
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->execute([$image_id, $product_id]);
        $image = $stmt->fetch();
        
        if ($image) {
            // Delete file
            if (file_exists(UPLOAD_DIR . $image['image_path'])) {
                @unlink(UPLOAD_DIR . $image['image_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
            if ($stmt->execute([$image_id, $product_id])) {
                $message = 'Image supprimée avec succès.';
            } else {
                $message = 'Erreur lors de la suppression.';
                $message_type = 'danger';
            }
        }
    } catch (Exception $e) {
        $message = 'Erreur lors de la suppression.';
        $message_type = 'danger';
        error_log('Error deleting image: ' . $e->getMessage());
    }
}

// Get all product images
try {
    $stmt = $pdo->prepare("SELECT id, image_path, display_order FROM product_images WHERE product_id = ? ORDER BY display_order ASC, id ASC");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching images: ' . $e->getMessage());
    $product_images = [];
}

// Also get main image
$main_image = null;
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product_data = $stmt->fetch();
if ($product_data && $product_data['image']) {
    $main_image = $product_data['image'];
}

$page_title = 'Gérer les Images';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex align-items-center mb-4">
        <a href="products.php" class="btn btn-outline-black btn-sm me-3"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="fw-bold mb-0">Gérer les Images</h1>
            <p class="text-muted mb-0">Produit: <?php echo escape($product['name']); ?></p>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Réorganiser les Images</h5>
                    <small class="text-muted">Glissez-déposez ou utilisez les flèches pour réorganiser. La première image sera l'image principale.</small>
                </div>
                <div class="card-body">
                    <form method="POST" id="imageOrderForm">
                        <div id="imageList" class="list-group">
                            <?php 
                            $all_images = [];
                            if ($main_image) {
                                $all_images[] = ['type' => 'main', 'path' => $main_image, 'id' => 0];
                            }
                            foreach ($product_images as $img) {
                                $all_images[] = ['type' => 'extra', 'path' => $img['image_path'], 'id' => $img['id'], 'order' => $img['display_order']];
                            }
                            
                            foreach ($all_images as $index => $img): 
                                $img_path = BASE_URL . 'uploads/products/' . escape($img['path']);
                                $img_exists = file_exists(UPLOAD_DIR . $img['path']);
                            ?>
                                <div class="list-group-item d-flex align-items-center mb-2 border rounded" data-image-id="<?php echo $img['id']; ?>">
                                    <div class="me-3">
                                        <i class="fas fa-grip-vertical text-muted" style="cursor: move;"></i>
                                    </div>
                                    <div class="me-3">
                                        <?php if ($img_exists): ?>
                                            <img src="<?php echo $img_path; ?>" class="rounded" style="width: 80px; height: 80px; object-fit: cover;" alt="Image">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?php echo $img['type'] === 'main' ? 'primary' : 'secondary'; ?> me-2">
                                                <?php echo $img['type'] === 'main' ? 'Image Principale' : 'Image ' . ($index + 1); ?>
                                            </span>
                                            <small class="text-muted">Position: <?php echo $index + 1; ?></small>
                                        </div>
                                    </div>
                                    <div class="btn-group me-2">
                                        <?php if ($index > 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary move-up" title="Déplacer vers le haut">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($index < count($all_images) - 1): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary move-down" title="Déplacer vers le bas">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($img['type'] === 'extra'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette image ?');">
                                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                            <button type="submit" name="delete_image" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <input type="hidden" name="image_order[<?php echo $img['id']; ?>]" value="<?php echo $index; ?>" class="image-order-input">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="update_order" class="btn btn-black">
                                <i class="fas fa-save me-2"></i>Enregistrer l'ordre
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary ms-2">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Instructions</h6>
                    <ul class="small text-muted">
                        <li>La première image sera utilisée comme image principale</li>
                        <li>Utilisez les flèches pour réorganiser</li>
                        <li>Maximum 5 images au total</li>
                        <li>L'ordre sera visible sur la page produit</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple move up/down functionality
document.querySelectorAll('.move-up').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = this.closest('.list-group-item');
        const prev = item.previousElementSibling;
        if (prev) {
            prev.before(item);
            updateOrderInputs();
        }
    });
});

document.querySelectorAll('.move-down').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = this.closest('.list-group-item');
        const next = item.nextElementSibling;
        if (next) {
            next.after(item);
            updateOrderInputs();
        }
    });
});

function updateOrderInputs() {
    document.querySelectorAll('#imageList .list-group-item').forEach((item, index) => {
        const input = item.querySelector('.image-order-input');
        if (input) {
            input.value = index;
        }
        // Update position badge
        const badge = item.querySelector('.badge');
        if (badge && !badge.textContent.includes('Principale')) {
            badge.textContent = 'Image ' + (index + 1);
        }
        const positionText = item.querySelector('.text-muted');
        if (positionText) {
            positionText.textContent = 'Position: ' + (index + 1);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
