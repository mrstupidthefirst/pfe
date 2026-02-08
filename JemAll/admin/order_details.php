<?php
/**
 * Admin - Order Details & Management
 * View order details and update order status
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();
$order_id = intval($_GET['id'] ?? 0);

// Handle status update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        // Create notification for the buyer
        $status_fr = [
            'pending' => 'En attente',
            'processing' => 'En cours de traitement',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée'
        ];
        $display_status = $status_fr[$new_status] ?? $new_status;
        
        // Get buyer_id
        $buyer_stmt = $pdo->prepare("SELECT buyer_id FROM orders WHERE id = ?");
        $buyer_stmt->execute([$order_id]);
        $buyer_id = $buyer_stmt->fetchColumn();
        
        if ($buyer_id) {
            $msg = "Le statut de votre commande #$order_id a été mis à jour : " . $display_status;
            createNotification($buyer_id, 'order_update', $msg, "buyer/order_details.php?id=$order_id");
        }

        $message = '<div class="alert alert-success border-0 shadow-sm mb-4">Statut de la commande mis à jour avec succès !</div>';
    } else {
        $message = '<div class="alert alert-danger border-0 shadow-sm mb-4">Erreur lors de la mise à jour du statut.</div>';
    }
}

// Get order details with buyer info
$stmt = $pdo->prepare("SELECT o.*, u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone 
                       FROM orders o 
                       JOIN users u ON o.buyer_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
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

$page_title = 'Détails de la commande #' . $order_id;
include '../includes/header.php';
?>

<div class="order-detail-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-6 fw-bold mb-0">Gestion Commande #<?php echo $order['id']; ?></h1>
            <p class="mb-0 opacity-75">Client: <?php echo escape($order['buyer_name']); ?> | Passée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
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
    <?php echo $message; ?>

    <div class="mb-4">
        <a href="orders.php" class="btn btn-outline-dark">
            <i class="fas fa-arrow-left me-2"></i> Retour à la liste
        </a>
    </div>

    <div class="order-detail-grid">
        <!-- Main: Order Items -->
        <div class="order-items-section">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Articles de la commande</h5>
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

            <!-- Customer Info -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Informations Client</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3 ls-1">Coordonnées</h6>
                            <p class="mb-2"><strong>Nom:</strong> <?php echo escape($order['buyer_name']); ?></p>
                            <p class="mb-2"><strong>Email:</strong> <?php echo escape($order['buyer_email']); ?></p>
                            <p class="mb-0"><strong>Tél:</strong> <?php echo escape($order['buyer_phone'] ?? 'Non fourni'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3 ls-1">Adresse de livraison</h6>
                            <p class="mb-0"><?php echo nl2br(escape($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Order Actions -->
        <div class="order-sidebar">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Mettre à jour le statut</h5>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nouveau statut</label>
                            <select name="status" class="form-select form-select-lg">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>En cours</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-black btn-lg w-100 py-3">
                            <i class="fas fa-save me-2"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Paiement</h5>
                    <div class="d-flex gap-3 mb-0">
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
            
            <!-- Quick Actions -->
            <div class="card border-0 bg-dark text-white rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Actions rapides</h5>
                    <div class="d-grid gap-2">
                    <div class="d-grid gap-2">
                        <a href="print_order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-outline-light text-start py-2">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </a>
                        <a href="mailto:<?php echo $order['buyer_email']; ?>" class="btn btn-outline-light text-start py-2">
                            <i class="fas fa-envelope me-2"></i> Envoyer un email au client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
