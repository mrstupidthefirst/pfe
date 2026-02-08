<?php
/**
 * Buyer - My Favorites
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    redirect('index.php');
}

$pdo = getDB();
$buyer_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Handle remove from favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE buyer_id = ? AND product_id = ?");
            if ($stmt->execute([$buyer_id, $product_id])) {
                $message = 'Produit retiré des favoris.';
                $message_type = 'success';
            } else {
                $message = 'Erreur lors de la suppression.';
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            error_log('Database error removing favorite: ' . $e->getMessage());
            $message = 'Erreur lors de la suppression.';
            $message_type = 'danger';
        }
    }
}

// Get favorites
try {
    $stmt = $pdo->prepare("SELECT f.*, p.*, c.name as category_name, u.full_name as seller_name
                           FROM favorites f
                           JOIN products p ON f.product_id = p.id
                           JOIN categories c ON p.category_id = c.id
                           JOIN users u ON p.seller_id = u.id
                           WHERE f.buyer_id = ? AND p.status = 'approved'
                           ORDER BY f.created_at DESC");
    $stmt->execute([$buyer_id]);
    $favorites = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Database error fetching favorites: ' . $e->getMessage());
    $favorites = [];
}

$page_title = 'Mes Favoris';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Mes Favoris</h1>
            <p class="text-muted">Vos produits favoris</p>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($favorites)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <div class="mb-4">
                <i class="far fa-heart fa-4x text-muted"></i>
            </div>
            <h3 class="fw-bold">Aucun favori</h3>
            <p class="text-muted">Vous n'avez pas encore ajouté de produits à vos favoris.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-black px-4 mt-3">Découvrir les produits</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favorites as $product): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 position-relative">
                        <div class="position-relative">
                            <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($product['image']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo escape($product['name']); ?>">
                            <?php else: ?>
                                <img src="<?php echo BASE_URL; ?>assets/images/placeholder.jpg" 
                                     class="card-img-top product-image" 
                                     alt="No image">
                            <?php endif; ?>
                            <span class="position-absolute top-0 start-0 m-3 badge bg-black"><?php echo escape($product['category_name']); ?></span>
                            <form method="POST" class="position-absolute top-0 end-0 m-3">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="remove_favorite" class="btn btn-sm btn-danger rounded-circle" title="Retirer des favoris">
                                    <i class="fas fa-heart-broken"></i>
                                </button>
                            </form>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold mb-1"><?php echo escape($product['name']); ?></h5>
                            <p class="text-muted small mb-2">Vendu par : <?php echo escape($product['seller_name']); ?></p>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo escape(substr($product['description'], 0, 80)); ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="h5 fw-bold mb-0"><?php echo number_format($product['price'], 2); ?> MAD</span>
                                <span class="text-muted small">Stock: <?php echo $product['stock']; ?></span>
                            </div>
                            <div class="mt-3">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-black w-100">Voir Détails</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
