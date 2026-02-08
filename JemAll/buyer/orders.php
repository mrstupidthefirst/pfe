<?php
/**
 * Buyer - My Orders
 * View order history
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    redirect('index.php');
}

$pdo = getDB();
$buyer_id = $_SESSION['user_id'];

// Get all orders
$orders = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY created_at DESC");
$orders->execute([$buyer_id]);
$all_orders = $orders->fetchAll();

$page_title = 'My Orders';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-0">Mes Commandes</h1>
            <p class="text-muted mb-0">Suivez l'état de vos achats et gérez vos commandes.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-black">
            <i class="fas fa-shopping-bag me-2"></i> Continuer les achats
        </a>
    </div>
    
    <?php if (empty($all_orders)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <div class="mb-4">
                <i class="fas fa-box-open text-light" style="font-size: 6rem;"></i>
            </div>
            <h3 class="fw-bold">Vous n'avez pas encore de commandes</h3>
            <p class="text-muted mb-4">C'est le moment idéal pour faire vos premiers achats !</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-black btn-lg px-5">Découvrir nos produits</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($all_orders as $order): ?>
                <div class="order-card p-4">
                    <div class="order-header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="order-id">Commande #<?php echo $order['id']; ?></span>
                            <span class="status-pill status-<?php echo $order['status']; ?>">
                                <?php 
                                $status_fr = [
                                    'pending' => 'En attente',
                                    'processing' => 'En cours',
                                    'shipped' => 'Expédiée',
                                    'delivered' => 'Livrée',
                                    'cancelled' => 'Annulée'
                                ];
                                echo $status_fr[$order['status']] ?? ucfirst($order['status']); 
                                ?>
                            </span>
                        </div>
                        <span class="order-date">
                            <i class="far fa-calendar-alt me-1"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="order-body mt-4">
                        <div class="order-stat">
                            <span class="order-stat-label">Total de la commande</span>
                            <span class="order-stat-value fs-5"><?php echo number_format($order['total_amount'], 2); ?> DH</span>
                        </div>
                        
                        <div class="order-stat">
                            <span class="order-stat-label">Adresse de livraison</span>
                            <span class="order-stat-value text-truncate" style="max-width: 300px;">
                                <?php echo escape($order['shipping_address']); ?>
                            </span>
                        </div>
                        
                        <div class="text-end ms-auto">
                            <a href="print_invoice.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-outline-secondary px-3 py-2 me-2" title="Facture">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-dark px-4 py-2">
                                <i class="fas fa-eye me-2"></i> Voir les détails
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
