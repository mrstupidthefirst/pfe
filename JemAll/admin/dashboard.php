<?php
/**
 * Admin Dashboard
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();

// Get statistics
$stats = [
    'total_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
    'pending_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller' AND status = 'pending'")->fetchColumn(),
    'pending_products' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0,
];

$page_title = 'Administration';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Tableau de bord Admin</h1>
            <p class="text-muted">Gestion globale de la plateforme JemAll</p>
        </div>
        <div class="text-end">
            <div class="text-muted small">Dernière mise à jour</div>
            <div class="fw-bold"><?php echo date('d/m/Y H:i'); ?></div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Vendeurs</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['total_sellers']; ?></h3>
                    <i class="fas fa-users stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100" style="border-left-color: #ffc107;">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Vendeurs Attente</h6>
                    <h3 class="fw-bold mb-0 text-warning"><?php echo $stats['pending_sellers']; ?></h3>
                    <i class="fas fa-user-clock stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100" style="border-left-color: #dc3545;">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Produits Attente</h6>
                    <h3 class="fw-bold mb-0 text-danger"><?php echo $stats['pending_products']; ?></h3>
                    <i class="fas fa-clock stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Total Produits</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['total_products']; ?></h3>
                    <i class="fas fa-boxes stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100" style="border-left-color: #0d6efd;">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Commandes</h6>
                    <h3 class="fw-bold mb-0"><?php echo $stats['total_orders']; ?></h3>
                    <i class="fas fa-shopping-cart stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card h-100" style="border-left-color: #198754;">
                <div class="card-body p-3 position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Revenu Total</h6>
                    <h3 class="fw-bold mb-0 text-success"><?php echo number_format($stats['total_revenue'], 0); ?></h3>
                    <i class="fas fa-money-bill-wave stat-icon" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Gestion Utilisateurs</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="sellers.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="fas fa-user-tie me-2"></i> Gérer les vendeurs
                            </div>
                            <?php if($stats['pending_sellers'] > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $stats['pending_sellers']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="buyers.php" class="list-group-item list-group-item-action py-3">
                            <i class="fas fa-user-tag me-2"></i> Gérer les acheteurs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Gestion Catalogue</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="products.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="fas fa-box me-2"></i> Approbation produits
                            </div>
                            <?php if($stats['pending_products'] > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $stats['pending_products']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="categories.php" class="list-group-item list-group-item-action py-3">
                            <i class="fas fa-tags me-2"></i> Gérer les catégories
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Commandes</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="orders.php" class="list-group-item list-group-item-action py-3">
                            <i class="fas fa-shopping-bag me-2"></i> Voir toutes les commandes
                        </a>
                        <a href="orders.php?status=pending" class="list-group-item list-group-item-action py-3">
                            <i class="fas fa-clock me-2"></i> Commandes en attente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
