<?php
/**
 * Seller Dashboard
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];

// Get seller statistics
$stats = [];

// Total Products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$stats['total_products'] = $stmt->fetchColumn();

// Pending Products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'pending'");
$stmt->execute([$seller_id]);
$stats['pending_products'] = $stmt->fetchColumn();

// Total Orders
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) 
                       FROM orders o 
                       JOIN order_items oi ON o.id = oi.order_id 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE p.seller_id = ?");
$stmt->execute([$seller_id]);
$stats['total_orders'] = $stmt->fetchColumn();

// Total Sales (Revenue)
$stmt = $pdo->prepare("SELECT SUM(oi.quantity * oi.price) 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       JOIN orders o ON oi.order_id = o.id
                       WHERE p.seller_id = ? AND o.status != 'cancelled'");
$stmt->execute([$seller_id]);
$stats['total_sales'] = $stmt->fetchColumn() ?? 0;

// Get recent orders
$stmt = $pdo->prepare("SELECT o.*, SUM(oi.quantity * oi.price) as order_total 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE p.seller_id = ? 
                         GROUP BY o.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5");
$stmt->execute([$seller_id]);
$recent_orders = $stmt->fetchAll();

$page_title = 'Tableau de bord Vendeur';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Tableau de bord</h1>
            <p class="text-muted">Bienvenue, <?php echo escape($_SESSION['full_name']); ?></p>
        </div>
        <div>
            <a href="add_product.php" class="btn btn-black">
                <i class="fas fa-plus me-2"></i>Ajouter un produit
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Total Produits</h6>
                    <h2 class="fw-bold mb-0"><?php echo $stats['total_products']; ?></h2>
                    <i class="fas fa-box stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left-color: #ffc107;">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">En attente</h6>
                    <h2 class="fw-bold mb-0"><?php echo $stats['pending_products']; ?></h2>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Total Commandes</h6>
                    <h2 class="fw-bold mb-0"><?php echo $stats['total_orders']; ?></h2>
                    <i class="fas fa-shopping-bag stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left-color: #198754;">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Chiffre d'affaires</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_sales'], 2); ?> MAD</h2>
                    <i class="fas fa-wallet stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Commandes Récentes</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-black">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Aucune commande récente.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo number_format($order['order_total'], 2); ?> MAD</td>
                                            <td>
                                                <?php 
                                                $status_class = 'bg-secondary';
                                                if($order['status'] == 'pending') $status_class = 'bg-warning text-dark';
                                                elseif($order['status'] == 'delivered') $status_class = 'bg-success';
                                                elseif($order['status'] == 'cancelled') $status_class = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-black">Détails</a>
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
        
        <!-- Quick Links / Info -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="products.php" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                            <div class="bg-light rounded p-2 me-3">
                                <i class="fas fa-boxes text-black"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Gérer mes produits</h6>
                                <small class="text-muted">Modifier ou supprimer vos articles</small>
                            </div>
                        </a>
                        <a href="add_product.php" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                            <div class="bg-light rounded p-2 me-3">
                                <i class="fas fa-plus-circle text-black"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Nouvel article</h6>
                                <small class="text-muted">Mettre en vente un nouveau produit</small>
                            </div>
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                            <div class="bg-light rounded p-2 me-3">
                                <i class="fas fa-truck text-black"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Suivi des ventes</h6>
                                <small class="text-muted">Gérer vos commandes clients</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
