<?php
/**
 * Buyer - Order Details
 * View detailed order information
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    redirect('index.php');
}

$pdo = getDB();
$buyer_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
$stmt->execute([$order_id, $buyer_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}

// Get order items
$items_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$page_title = 'Order Details';
include '../includes/header.php';
?>

<div class="order-detail-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-6 fw-bold mb-0">Détails de la commande #<?php echo $order['id']; ?></h1>
            <p class="mb-0 opacity-75">Passée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
        </div>
        <div class="status-pill status-<?php echo $order['status']; ?> shadow-sm">
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
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="mb-4 d-flex justify-content-between">
        <a href="orders.php" class="btn btn-outline-dark">
            <i class="fas fa-arrow-left me-2"></i> Retour à mes commandes
        </a>
        <a href="print_invoice.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-file-invoice me-2"></i> Télécharger Facture
        </a>
    </div>

    <div class="order-detail-grid">
        <!-- Main: Order Items -->
        <div class="order-items-section">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Articles commandés</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Produit</th>
                                <th>Prix</th>
                                <th>Quantité</th>
                                <th class="text-end pe-4">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3 py-2">
                                            <?php if ($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
                                                <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($item['image']); ?>" alt="Product" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo escape($item['product_name']); ?></h6>
                                                <small class="text-muted">Réf: PROD-<?php echo $item['product_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price'], 2); ?> DH</td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end pe-4 fw-bold">
                                        <?php echo number_format($item['quantity'] * $item['price'], 2); ?> DH
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top-0">
                            <tr>
                                <td colspan="3" class="text-end py-4">
                                    <span class="fs-5 text-muted">Total de la commande:</span>
                                </td>
                                <td class="text-end pe-4 py-4">
                                    <span class="fs-4 fw-bold text-dark"><?php echo number_format($order['total_amount'], 2); ?> DH</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar: Order Context -->
        <div class="order-sidebar">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Livraison</h5>
                    <div class="d-flex gap-3 mb-4">
                        <div class="bg-light rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fas fa-map-marker-alt text-dark"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1 uppercase fw-bold ls-1">Adresse de livraison</p>
                            <p class="mb-0 fw-semibold h6"><?php echo nl2br(escape($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Paiement</h5>
                    <div class="d-flex gap-3 mb-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1 uppercase fw-bold ls-1">Mode de paiement</p>
                            <p class="mb-0 fw-semibold h6">Paiement à la livraison</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-dark text-white rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Besoin d'aide ?</h5>
                    <p class="small opacity-75 mb-4">Si vous avez des questions concernant votre commande, n'hésitez pas à nous contacter.</p>
                    <a href="mailto:support@jemall.com" class="btn btn-light w-100 py-2">
                        <i class="fas fa-headset me-2"></i> Contacter le support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
