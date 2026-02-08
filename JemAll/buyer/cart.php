<?php
/**
 * Buyer - Shopping Cart
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

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_cart'])) {
            $cart_id = intval($_POST['cart_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            
            if ($cart_id <= 0) {
                throw new Exception('ID de panier invalide.');
            }
            
            if ($quantity <= 0) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND buyer_id = ?");
                if (!$stmt->execute([$cart_id, $buyer_id])) {
                    throw new Exception('Erreur lors de la suppression du produit.');
                }
                $message = 'Produit retiré du panier.';
                $message_type = 'success';
            } else {
                // Verify cart item belongs to buyer and check stock, min_qty, max_qty
                $stmt = $pdo->prepare("SELECT c.id, p.stock, p.name, p.status as product_status, 
                                              COALESCE(p.min_qty, 1) as min_qty, 
                                              COALESCE(p.max_qty, p.stock) as max_qty
                                       FROM cart c 
                                       JOIN products p ON c.product_id = p.id 
                                       WHERE c.id = ? AND c.buyer_id = ?");
                if (!$stmt->execute([$cart_id, $buyer_id])) {
                    throw new Exception('Erreur lors de la vérification du panier.');
                }
                $result = $stmt->fetch();
                
                if (!$result) {
                    throw new Exception('Article introuvable dans votre panier.');
                }
                
                $min_qty = max(1, intval($result['min_qty']));
                $max_qty = min(intval($result['max_qty']), intval($result['stock']));
                
                if ($result['product_status'] !== 'approved') {
                    $message = 'Le produit "' . escape($result['name']) . '" n\'est plus disponible.';
                    $message_type = 'danger';
                } elseif ($quantity < $min_qty) {
                    $message = 'Quantité minimale requise : ' . $min_qty . ' unité(s) pour le produit "' . escape($result['name']) . '". Vous avez sélectionné ' . $quantity . ' unité(s). Veuillez sélectionner au moins ' . $min_qty . ' unité(s).';
                    $message_type = 'danger';
                } elseif ($quantity > $max_qty) {
                    $message = 'Quantité maximale autorisée : ' . $max_qty . ' unité(s) pour le produit "' . escape($result['name']) . '".';
                    $message_type = 'danger';
                } elseif ($quantity > $result['stock']) {
                    $message = 'Stock insuffisant pour "' . escape($result['name']) . '". Disponible : ' . $result['stock'] . ' unité(s).';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND buyer_id = ?");
                    if (!$stmt->execute([$quantity, $cart_id, $buyer_id])) {
                        throw new Exception('Erreur lors de la mise à jour du panier.');
                    }
                    $message = 'Panier mis à jour.';
                    $message_type = 'success';
                }
            }
        } elseif (isset($_POST['remove_item'])) {
            $cart_id = intval($_POST['cart_id'] ?? 0);
            
            if ($cart_id <= 0) {
                throw new Exception('ID de panier invalide.');
            }
            
            // Verify cart item belongs to buyer
            $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND buyer_id = ?");
            if (!$stmt->execute([$cart_id, $buyer_id])) {
                throw new Exception('Erreur lors de la vérification du panier.');
            }
            
            if (!$stmt->fetch()) {
                throw new Exception('Article introuvable dans votre panier.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND buyer_id = ?");
            if (!$stmt->execute([$cart_id, $buyer_id])) {
                throw new Exception('Erreur lors de la suppression du produit.');
            }
            $message = 'Produit retiré du panier.';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        error_log('Database error in cart.php: ' . $e->getMessage());
        $message = 'Erreur de base de données. Veuillez réessayer.';
        $message_type = 'danger';
    } catch (Exception $e) {
        error_log('Error in cart.php: ' . $e->getMessage());
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get cart items
try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.stock, p.image, p.status as product_status,
                                  COALESCE(p.min_qty, 1) as min_qty, 
                                  COALESCE(p.max_qty, p.stock) as max_qty
                                 FROM cart c 
                                 JOIN products p ON c.product_id = p.id 
                                 WHERE c.buyer_id = ? 
                                 ORDER BY c.created_at DESC");
    
    if (!$stmt->execute([$buyer_id])) {
        throw new Exception('Erreur lors de la récupération des articles du panier.');
    }
    
    $items = $stmt->fetchAll();
    
    $subtotal = 0;
    foreach ($items as $item) {
        if ($item['product_status'] === 'approved') {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
    }
} catch (PDOException $e) {
    error_log('Database error fetching cart items: ' . $e->getMessage());
    $items = [];
    $subtotal = 0;
    if (empty($message)) {
        $message = 'Erreur lors du chargement du panier. Veuillez réessayer.';
        $message_type = 'danger';
    }
} catch (Exception $e) {
    error_log('Error fetching cart items: ' . $e->getMessage());
    $items = [];
    $subtotal = 0;
    if (empty($message)) {
        $message = 'Erreur: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

$page_title = 'Mon Panier';
include '../includes/header.php';
?>

<div class="container">
    <h1 class="fw-bold mb-4">Mon Panier</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <div class="mb-4">
                <i class="fas fa-shopping-cart fa-4x text-light"></i>
            </div>
            <h3 class="fw-bold">Votre panier est vide</h3>
            <p class="text-muted">Il semble que vous n'ayez pas encore ajouté de produits.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-black px-4 mt-3">Continuer mes achats</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="cart-items">
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['product_status'] !== 'approved'): ?>
                                    <div class="cart-item-unavailable p-3 mb-3 bg-light rounded-3 border border-warning">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">
                                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                                <strong><?php echo escape($item['name']); ?></strong> n'est plus disponible.
                                            </span>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-times me-1"></i>Retirer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="cart-item p-3 mb-3 bg-white rounded-3 border border-light shadow-sm">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="cart-item-image me-3">
                                                        <?php 
                                                        $img_path = '';
                                                        $img_exists = false;
                                                        if (!empty($item['image'])) {
                                                            $img_path = BASE_URL . 'uploads/products/' . escape($item['image']);
                                                            $img_exists = file_exists(UPLOAD_DIR . $item['image']);
                                                        }
                                                        ?>
                                                        <?php if ($img_exists): ?>
                                                            <img src="<?php echo $img_path; ?>" 
                                                                 class="rounded-3" 
                                                                 style="width: 100px; height: 100px; object-fit: cover;"
                                                                 alt="<?php echo escape($item['name']); ?>"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; display: none;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="cart-item-info">
                                                        <h6 class="mb-1 fw-bold"><?php echo escape($item['name']); ?></h6>
                                                        <small class="text-muted d-block">Stock: <?php echo $item['stock']; ?></small>
                                                        <div class="mt-2">
                                                            <span class="fw-bold text-black"><?php echo number_format($item['price'], 2); ?> MAD</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold mb-1">Quantité</label>
                                                <?php 
                                                $min_qty = max(1, intval($item['min_qty'] ?? 1));
                                                $max_qty = min(intval($item['max_qty'] ?? $item['stock']), intval($item['stock']));
                                                ?>
                                                <form method="POST" class="d-flex align-items-center">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <input type="number" 
                                                           name="quantity" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="<?php echo $min_qty; ?>" 
                                                           max="<?php echo $max_qty; ?>" 
                                                           class="form-control form-control-sm" 
                                                           onchange="this.form.submit()"
                                                           style="max-width: 80px;"
                                                           title="Min: <?php echo $min_qty; ?>, Max: <?php echo $max_qty; ?>">
                                                    <input type="hidden" name="update_cart" value="1">
                                                </form>
                                                <?php if ($min_qty > 1): ?>
                                                    <small class="text-muted d-block mt-1">Min: <?php echo $min_qty; ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="cart-item-subtotal">
                                                    <small class="text-muted d-block mb-1">Sous-total</small>
                                                    <span class="h6 fw-bold mb-0"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> MAD</span>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <form method="POST" onsubmit="return confirm('Retirer cet article du panier ?');">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline-black">
                        <i class="fas fa-arrow-left me-2"></i>Continuer mes achats
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4 pb-3 border-bottom">Résumé de la commande</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Sous-total</span>
                            <span class="fw-bold"><?php echo number_format($subtotal, 2); ?> MAD</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Livraison</span>
                            <span class="text-success fw-bold">Gratuite</span>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5 fw-bold">Total</span>
                            <span class="h5 fw-bold text-black"><?php echo number_format($subtotal, 2); ?> MAD</span>
                        </div>
                        
                        <?php if ($subtotal > 0): ?>
                            <a href="checkout.php" class="btn btn-black w-100 py-3 fw-bold rounded-3">
                                Passer à la caisse <i class="fas fa-chevron-right ms-2"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-black w-100 py-3 fw-bold rounded-3" disabled>Panier vide</button>
                        <?php endif; ?>
                        
                        <div class="mt-4 pt-4 border-top text-center">
                            <div class="mb-3">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" height="30" class="me-2 opacity-75">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349230.png" height="30" class="me-2 opacity-75">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349226.png" height="30" class="opacity-75">
                            </div>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-lock me-1"></i> Paiement 100% sécurisé
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
