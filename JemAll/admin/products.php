<?php
/**
 * Admin - Approve Products
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();
$message = '';
$message_type = 'success';

// Handle product approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'approved' WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            // Get product name and seller_id
            $p_stmt = $pdo->prepare("SELECT name, seller_id FROM products WHERE id = ?");
            $p_stmt->execute([$product_id]);
            $product_data = $p_stmt->fetch();
            if ($product_data) {
                $msg = "Votre produit \"" . $product_data['name'] . "\" a été approuvé par l'administrateur.";
                createNotification($product_data['seller_id'], 'product_status', $msg, 'seller/products.php');
            }
            $message = 'Produit approuvé avec succès.';
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'rejected' WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            // Get product name and seller_id
            $p_stmt = $pdo->prepare("SELECT name, seller_id FROM products WHERE id = ?");
            $p_stmt->execute([$product_id]);
            $product_data = $p_stmt->fetch();
            if ($product_data) {
                $msg = "Votre produit \"" . $product_data['name'] . "\" a été rejeté par l'administrateur.";
                createNotification($product_data['seller_id'], 'product_status', $msg, 'seller/products.php');
            }
            $message = 'Produit rejeté.';
            $message_type = 'warning';
        }
    }
}

// Get all products with seller and category info
$products = $pdo->query("SELECT p.*, c.name as category_name, u.username as seller_username, u.full_name as seller_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         JOIN users u ON p.seller_id = u.id 
                         ORDER BY p.created_at DESC")->fetchAll();

$page_title = 'Gestion des Produits';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Gestion des Produits</h1>
            <p class="text-muted">Approuvez ou rejetez les soumissions des vendeurs</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-black">
            <i class="fas fa-chevron-left me-2"></i>Retour
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
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
                            <th class="py-3">Vendeur</th>
                            <th class="py-3">Prix</th>
                            <th class="py-3">Statut</th>
                            <th class="py-3">Date</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Aucun produit trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($product['image']); ?>" class="rounded" width="45" height="45" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                        <i class="fas fa-image text-muted small"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold small"><?php echo escape($product['name']); ?></h6>
                                                <small class="text-muted"><?php echo escape($product['category_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold"><?php echo escape($product['seller_name']); ?></div>
                                        <div class="text-muted small">@<?php echo escape($product['seller_username']); ?></div>
                                    </td>
                                    <td class="small fw-bold"><?php echo number_format($product['price'], 2); ?> MAD</td>
                                    <td>
                                        <?php 
                                        $status_class = 'bg-warning text-dark';
                                        if($product['status'] == 'approved') $status_class = 'bg-success';
                                        elseif($product['status'] == 'rejected') $status_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> small">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <?php if ($product['status'] === 'pending'): ?>
                                            <div class="btn-group">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approuver">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Rejeter" onclick="return confirm('Êtes-vous sûr de vouloir rejeter ce produit ?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Traité</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
