<?php
/**
 * Buyer - Print Invoice
 * Generates a printable invoice
 */
require_once '../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['id']);
$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Fetch order details (Ensure ownership)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Commande introuvable ou accès refusé.");
}

// Fetch Buyer details
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$buyer = $stmtUser->fetch();

// Fetch items
$stmtItems = $pdo->prepare("SELECT oi.*, p.name 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll();

// Notify Admin that user printed invoice
// "admin is notified when any user places or prints an order"
// Check if notification already sent explicitly for printing this time? No, just send it.
// To avoid spam if they reload, maybe allow it.
try {
    $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    $admins = $admin_stmt->fetchAll();
    $notif_msg = "Le client " . $buyer['full_name'] . " a imprimé/téléchargé la facture de la commande #$order_id";
    
    // Check duplication to avoid spamming on F5? 
    // Simplify: just insert.
    $notif_sql = "INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, 'invoice_printed', ?, ?, FALSE)";
    $notif_stmt = $pdo->prepare($notif_sql);
    
    foreach ($admins as $admin) {
        $notif_stmt->execute([$admin['id'], $notif_msg, "admin/order_details.php?id=$order_id"]);
    }
} catch (Exception $e) {
    // Ignore notification errors to not block user
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #<?php echo $order_id; ?> - JemAll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .invoice-header { border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-logo { width: 150px; }
        .invoice-title { font-size: 2.5rem; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .table thead th { border-bottom: 2px solid #000; background: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        .invoice-total { background: #f8f9fa; padding: 15px; border-radius: 5px; -webkit-print-color-adjust: exact; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .container { max-width: 100%; width: 100%; padding: 0; }
            a { text-decoration: none; color: #000; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-5">
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../assets/images/logo.png" alt="JemAll" class="invoice-logo me-3">
                <div>
                    <h1 class="m-0 fw-bold">JemAll</h1>
                    <small class="text-muted">Marketplace</small>
                </div>
            </div>
            <div class="text-end">
                <div class="invoice-title">Facture</div>
                <h5>#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h5>
                <p class="mb-0">Date: <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Émetteur</h5>
                <p class="mb-1"><strong>JemAll Inc.</strong></p>
                <p class="mb-1">123 Avenue Mohamed VI</p>
                <p class="mb-1">Casablanca, Maroc</p>
                <p class="mb-1">support@jemall.com</p>
            </div>
            <div class="col-6 text-end">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Facturé à</h5>
                <p class="mb-1"><strong><?php echo escape($buyer['full_name']); ?></strong></p>
                <p class="mb-1"><?php echo nl2br(escape($order['shipping_address'])); ?></p>
                <p class="mb-1"><?php echo escape($buyer['phone']); ?></p>
                <p class="mb-1"><?php echo escape($buyer['email']); ?></p>
            </div>
        </div>

        <table class="table table-striped mb-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-end">Prix Unitaire</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo escape($item['name']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['price'], 2); ?> MAD</td>
                    <td class="text-end"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> MAD</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row">
            <div class="col-6">
                <div class="border p-3 rounded">
                    <h6 class="fw-bold">Paiement:</h6>
                    <p class="text-muted small mb-0">Paiement à la livraison (COD)</p>
                </div>
            </div>
            <div class="col-6">
                <div class="invoice-total text-end">
                    <h4 class="mb-0 fw-bold">Total TTC: <?php echo number_format($order['total_amount'], 2); ?> MAD</h4>
                </div>
            </div>
        </div>
        
        <div class="invoice-footer text-center mt-5 pt-5 text-muted small">
            <p>JemAll Marketplace - RC: 12345 - IF: 67890 - ICE: 001122334455</p>
            <p>Merci pour votre confiance !</p>
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-black btn-lg"><i class="fas fa-print me-2"></i>Imprimer / Télécharger PDF</button>
            <a href="orders.php" class="btn btn-outline-secondary btn-lg ms-2">Retour</a>
        </div>
    </div>
</body>
</html>
