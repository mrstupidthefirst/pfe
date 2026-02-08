<?php
/**
 * Admin - Orders Management
 * View and manage all orders in the system
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();

// Filter by status if provided
$status_filter = $_GET['status'] ?? '';
$where_clause = "";
$params = [];

if (!empty($status_filter)) {
    $where_clause = "WHERE o.status = ?";
    $params[] = $status_filter;
}

// Fetch orders with buyer information
$query = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email 
          FROM orders o 
          JOIN users u ON o.buyer_id = u.id 
          $where_clause 
          ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics for the header
$stats_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$stats_result = $pdo->query($stats_query)->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = 'Gestion des commandes';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-0">Gestion des Commandes</h1>
            <p class="text-muted mb-0">Surveillez et gérez toutes les transactions de la plateforme.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-filter me-2"></i> 
                    <?php 
                    $status_labels = [
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'shipped' => 'Expédiées',
                        'delivered' => 'Livrées',
                        'cancelled' => 'Annulées'
                    ];
                    echo !empty($status_filter) ? $status_labels[$status_filter] : 'Tous les statuts';
                    ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="orders.php">Tous les statuts</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="orders.php?status=pending">En attente</a></li>
                    <li><a class="dropdown-item" href="orders.php?status=processing">En cours</a></li>
                    <li><a class="dropdown-item" href="orders.php?status=shipped">Expédiées</a></li>
                    <li><a class="dropdown-item" href="orders.php?status=delivered">Livrées</a></li>
                    <li><a class="dropdown-item" href="orders.php?status=cancelled">Annulées</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">En attente</h6>
                        <h4 class="fw-bold mb-0"><?php echo $stats_result['pending'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="fas fa-sync fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">En cours</h6>
                        <h4 class="fw-bold mb-0"><?php echo $stats_result['processing'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Livrées</h6>
                        <h4 class="fw-bold mb-0"><?php echo $stats_result['delivered'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-dark bg-opacity-10 text-dark rounded-circle p-3">
                        <i class="fas fa-shopping-bag fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Total</h6>
                        <h4 class="fw-bold mb-0"><?php echo array_sum($stats_result); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-search text-light mb-4" style="font-size: 5rem;"></i>
            <h3 class="fw-bold">Aucune commande trouvée</h3>
            <p class="text-muted">Il n'y a actuellement aucune commande correspondant à vos critères.</p>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-black text-white">
                        <tr>
                            <th class="ps-4 py-3">ID Commande</th>
                            <th class="py-3">Client</th>
                            <th class="py-3">Date</th>
                            <th class="py-3">Montant</th>
                            <th class="py-3">Statut</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?php echo $order['id']; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold"><?php echo escape($order['buyer_name']); ?></span>
                                        <small class="text-muted"><?php echo escape($order['buyer_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td class="fw-bold"><?php echo number_format($order['total_amount'], 2); ?> DH</td>
                                <td>
                                    <span class="status-pill status-<?php echo $order['status']; ?>">
                                        <?php echo $status_labels[$order['status']] ?? ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="print_order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Imprimer">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-black px-3">
                                        Gérer <i class="fas fa-chevron-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
