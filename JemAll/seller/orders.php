<?php
/**
 * Seller - View Orders
 * View orders for seller's products
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];

// Get statistics for seller
$stats_stmt = $pdo->prepare("SELECT 
    COUNT(DISTINCT o.id) as total_orders,
    SUM(oi.quantity * oi.price) as total_revenue,
    COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ? AND o.status != 'cancelled'");
$stats_stmt->execute([$seller_id]);
$stats = $stats_stmt->fetch();

// Get all orders containing seller's products with buyer info
$orders = $pdo->prepare("SELECT DISTINCT o.*, u.full_name as buyer_name, u.username as buyer_username,
                        (SELECT SUM(oi2.quantity * oi2.price) 
                         FROM order_items oi2 
                         JOIN products p2 ON oi2.product_id = p2.id 
                         WHERE oi2.order_id = o.id AND p2.seller_id = ?) as seller_total
                        FROM orders o
                        JOIN order_items oi ON o.id = oi.order_id
                        JOIN products p ON oi.product_id = p.id
                        JOIN users u ON o.buyer_id = u.id
                        WHERE p.seller_id = ?
                        ORDER BY o.created_at DESC");
$orders->execute([$seller_id, $seller_id]);
$all_orders = $orders->fetchAll();

$page_title = 'Mes Ventes';
include '../includes/header.php';
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Mes Ventes</h1>
            <p class="text-muted">Gérez les commandes de vos produits</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-black">
            <i class="fas fa-chevron-left me-2"></i>Tableau de bord
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle p-3 me-3">
                        <i class="fas fa-shopping-bag text-dark fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 uppercase fw-bold ls-1">Total Commandes</p>
                        <h3 class="fw-bold mb-0"><?php echo $stats['total_orders']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fas fa-money-bill-wave text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 uppercase fw-bold ls-1">Chiffre d'Affaires</p>
                        <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> DH</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fas fa-clock text-warning fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 uppercase fw-bold ls-1">En Attente</p>
                        <h3 class="fw-bold mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">Commande</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Client</th>
                        <th class="py-3">Ma Part</th>
                        <th class="py-3">Statut</th>
                        <th class="text-end pe-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Aucune commande trouvée.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $order): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">#<?php echo $order['id']; ?></span>
                                </td>
                                <td>
                                    <div class="small"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold small"><?php echo escape($order['buyer_name']); ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">@<?php echo escape($order['buyer_username']); ?></div>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?php echo number_format($order['seller_total'], 2); ?> DH</span>
                                </td>
                                <td>
                                    <?php 
                                    $status_fr = [
                                        'pending' => 'En attente',
                                        'processing' => 'En cours',
                                        'shipped' => 'Expédiée',
                                        'delivered' => 'Livrée',
                                        'cancelled' => 'Annulée'
                                    ];
                                    ?>
                                    <span class="status-pill status-<?php echo $order['status']; ?> shadow-none" style="font-size: 0.7rem;">
                                        <?php echo $status_fr[$order['status']] ?? ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-black btn-sm px-3 rounded-pill">
                                        Détails <i class="fas fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
