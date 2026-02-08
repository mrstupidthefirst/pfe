<?php
/**
 * Seller - Manage Products
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    $stmt = $pdo->prepare("SELECT id, image FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $seller_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
            unlink(UPLOAD_DIR . $product['image']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        if ($stmt->execute([$product_id, $seller_id])) {
            $message = 'Produit supprimé avec succès.';
        } else {
            $message = 'Échec de la suppression du produit.';
            $message_type = 'danger';
        }
    }
}

// Get all products for this seller
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.seller_id = ? 
                       ORDER BY p.created_at DESC");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();

$page_title = 'Mes Produits';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Mes Produits</h1>
            <p class="text-muted">Gérez votre catalogue de produits</p>
        </div>
        <a href="add_product.php" class="btn btn-black">
            <i class="fas fa-plus me-2"></i>Ajouter un produit
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Produit</th>
                            <th class="py-3">Catégorie</th>
                            <th class="py-3">Prix</th>
                            <th class="py-3">Stock</th>
                            <th class="py-3">Statut</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3">
                                        <i class="fas fa-box-open fa-3x"></i>
                                    </div>
                                    <p class="mb-0">Vous n'avez pas encore de produits.</p>
                                    <a href="add_product.php" class="btn btn-link text-black fw-bold">Ajouter votre premier produit</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($product['image']); ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo escape($product['name']); ?></h6>
                                                <small class="text-muted">ID: #<?php echo $product['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo escape($product['category_name']); ?></td>
                                    <td class="fw-bold"><?php echo number_format($product['price'], 2); ?> MAD</td>
                                    <td>
                                        <?php if ($product['stock'] <= 5): ?>
                                            <span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i><?php echo $product['stock']; ?></span>
                                        <?php else: ?>
                                            <?php echo $product['stock']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = 'bg-warning text-dark';
                                        if($product['status'] == 'approved') $status_class = 'bg-success';
                                        elseif($product['status'] == 'rejected') $status_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-black" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_product_images.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-info" title="Gérer les images">
                                                <i class="fas fa-images"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    
    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-outline-black">
            <i class="fas fa-chevron-left me-2"></i>Retour au tableau de bord
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
