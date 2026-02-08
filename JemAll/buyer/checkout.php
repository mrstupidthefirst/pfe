<?php
/**
 * Buyer - Checkout
 * Process order checkout
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    redirect('index.php');
}

$pdo = getDB();
$buyer_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get buyer info for default address
$buyer_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$buyer_stmt->execute([$buyer_id]);
$buyer = $buyer_stmt->fetch();

// Get cart items
$cart_items = $pdo->prepare("SELECT c.*, p.name, p.price, p.stock, p.image, p.status as product_status 
                             FROM cart c 
                             JOIN products p ON c.product_id = p.id 
                             WHERE c.buyer_id = ?");
$cart_items->execute([$buyer_id]);
$items = $cart_items->fetchAll();

// Filter only approved products
$valid_items = array_filter($items, function($item) {
    return $item['product_status'] === 'approved' && $item['quantity'] <= $item['stock'];
});

if (empty($valid_items)) {
    redirect('cart.php');
}

// Calculate total
$total = 0;
foreach ($valid_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    
    if (empty($shipping_address)) {
        $error = 'Shipping address is required.';
    } else {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$buyer_id, $total, $shipping_address]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items and update stock
            foreach ($valid_items as $item) {
                // Insert order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Notify Admins
            $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
            $admins = $admin_stmt->fetchAll();
            $notif_msg = "Nouvelle commande #$order_id de " . ($buyer['full_name'] ?? 'un client');
            $notif_sql = "INSERT INTO notifications (user_id, type, message, link, is_read) VALUES (?, 'new_order', ?, ?, FALSE)";
            $notif_stmt = $pdo->prepare($notif_sql);
            
            foreach ($admins as $admin) {
                $notif_stmt->execute([$admin['id'], $notif_msg, "admin/order_details.php?id=$order_id"]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE buyer_id = ?");
            $stmt->execute([$buyer_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success = 'Order placed successfully! Order ID: #' . $order_id;
            // Redirect after 3 seconds
            header("Refresh: 3; url=orders.php");
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order failed: ' . $e->getMessage();
        }
    }
}

$page_title = 'Checkout';
include '../includes/header.php';
?>

<div class="container checkout-wrapper">
    <div class="mb-5">
        <h1 class="display-5 fw-bold">Finaliser votre commande</h1>
        <p class="text-muted">Veuillez vérifier vos articles et fournir vos informations de livraison.</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?php echo escape($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold mb-3">Merci pour votre commande !</h2>
            <div class="alert alert-success d-inline-block px-4"><?php echo escape($success); ?></div>
            <p class="mt-4 text-muted">Redirection vers vos commandes dans quelques secondes...</p>
            <script>setTimeout(() => { window.location.href = 'orders.php'; }, 3000);</script>
        </div>
    <?php else: ?>
        <div class="checkout-grid">
            <!-- Left Side: Shipping Form -->
            <div class="checkout-card">
                <h2 class="checkout-title">
                    <i class="fas fa-shipping-fast"></i> Informations de livraison
                </h2>
                <form method="POST" id="checkoutForm">
                    <div class="mb-4">
                        <label for="shipping_address" class="form-label fw-semibold">Adresse de livraison complète *</label>
                        <textarea id="shipping_address" name="shipping_address" rows="5" class="form-control form-control-lg" placeholder="N° de rue, Quartier, Ville, Code Postal..." required><?php echo escape($buyer['address'] ?? ''); ?></textarea>
                        <div class="form-text mt-2">Nous utiliserons cette adresse pour l'expédition de votre commande.</div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Méthode de Paiement</label>
                                <div class="p-3 border rounded bg-light d-flex align-items-center">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" checked>
                                        <label class="form-check-label fw-bold" for="cod">
                                            Paiement à la livraison
                                        </label>
                                    </div>
                                    <i class="fas fa-money-bill-wave ms-auto text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex gap-3">
                        <button type="submit" class="btn btn-black btn-lg flex-grow-1 py-3">Confirmer la commande</button>
                        <a href="cart.php" class="btn btn-outline-dark btn-lg px-4">Retour au panier</a>
                    </div>
                </form>
            </div>

            <!-- Right Side: Order Summary -->
            <div class="checkout-card bg-light border-0">
                <h2 class="checkout-title">
                    <i class="fas fa-shopping-basket"></i> Résumé de la commande
                </h2>
                
                <div class="order-items-list mb-4">
                    <?php foreach ($valid_items as $item): ?>
                        <div class="order-item-mini">
                            <div class="order-item-info">
                                <?php if ($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($item['image']); ?>" alt="Product" class="order-item-img border">
                                <?php else: ?>
                                    <div class="bg-white border rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="order-item-details">
                                    <h4><?php echo escape($item['name']); ?></h4>
                                    <p>Quantité: <?php echo $item['quantity']; ?></p>
                                </div>
                            </div>
                            <div class="fw-bold text-nowrap">
                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?> DH
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-details p-3 bg-white rounded-3 shadow-sm">
                    <div class="summary-row">
                        <span class="text-muted">Sous-total</span>
                        <span><?php echo number_format($total, 2); ?> DH</span>
                    </div>
                    <div class="summary-row">
                        <span class="text-muted">Livraison</span>
                        <span class="text-success fw-bold">Gratuit</span>
                    </div>
                    <div class="summary-total">
                        <span>Total à payer</span>
                        <span><?php echo number_format($total, 2); ?> DH</span>
                    </div>
                </div>

                <div class="mt-4 p-3 border border-warning rounded-3 bg-warning bg-opacity-10">
                    <small class="text-dark d-flex align-items-start gap-2">
                        <i class="fas fa-info-circle mt-1"></i>
                        En cliquant sur "Confirmer la commande", vous acceptez nos conditions générales de vente.
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
