<?php
/**
 * Buyer - Product Details
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    redirect('index.php');
}

$pdo = getDB();
$product_id = intval($_GET['id'] ?? 0);
$message = '';
$message_type = 'success';

// Validate product ID
if ($product_id <= 0) {
    redirect('index.php');
}

// Get product details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.full_name as seller_name 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           JOIN users u ON p.seller_id = u.id 
                           WHERE p.id = ? AND p.status = 'approved'");
    
    if (!$stmt->execute([$product_id])) {
        throw new Exception('Erreur lors de la récupération du produit.');
    }
    
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect('index.php');
    }
    
    // Get min_qty and max_qty (default to 1 and stock if columns don't exist)
    $min_qty = isset($product['min_qty']) && $product['min_qty'] > 0 ? intval($product['min_qty']) : 1;
    $max_qty = isset($product['max_qty']) && $product['max_qty'] > 0 ? intval($product['max_qty']) : intval($product['stock']);
    
    // Ensure max_qty doesn't exceed stock
    if ($max_qty > $product['stock']) {
        $max_qty = intval($product['stock']);
    }
    
    // Ensure min_qty doesn't exceed max_qty
    if ($min_qty > $max_qty) {
        $min_qty = $max_qty;
    }
    
// Check if product is favorited
            $is_favorited = false;
            try {
                $stmt = $pdo->prepare("SELECT id FROM favorites WHERE buyer_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);
                $is_favorited = $stmt->fetch() !== false;
            } catch (PDOException $e) {
                error_log('Could not check favorites: ' . $e->getMessage());
            }
            
            // Get product images (handle case where table doesn't exist)
            $product_images = [];
            try {
                // Check if product_images table exists
                $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY display_order ASC");
                if ($stmt->execute([$product_id])) {
                    $product_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } catch (PDOException $e) {
                // Table doesn't exist or query failed - that's okay, we'll just use main image
                error_log('Could not fetch product images: ' . $e->getMessage());
                $product_images = [];
            }
    
    // Build image array - If no additional images, use main image
    $all_images = [];
    if (!empty($product['image']) && file_exists(UPLOAD_DIR . $product['image'])) {
        $all_images[] = $product['image'];
    }
    
    foreach ($product_images as $img) {
        if (!empty($img) && file_exists(UPLOAD_DIR . $img) && !in_array($img, $all_images)) {
            $all_images[] = $img;
        }
    }
    
    // Limit to 5 images
    $all_images = array_slice($all_images, 0, 5);
    
} catch (PDOException $e) {
    error_log('Database error in product.php: ' . $e->getMessage());
    $message = 'Erreur lors du chargement du produit. Veuillez réessayer.';
    $message_type = 'danger';
    redirect('index.php');
} catch (Exception $e) {
    error_log('Error in product.php: ' . $e->getMessage());
    $message = 'Erreur: ' . $e->getMessage();
    $message_type = 'danger';
    redirect('index.php');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity'] ?? 1);
    $buyer_id = $_SESSION['user_id'];
    
    // Get min_qty and max_qty for validation
    $min_qty = isset($product['min_qty']) && $product['min_qty'] > 0 ? intval($product['min_qty']) : 1;
    $max_qty = isset($product['max_qty']) && $product['max_qty'] > 0 ? intval($product['max_qty']) : intval($product['stock']);
    
    // Ensure max_qty doesn't exceed stock
    if ($max_qty > $product['stock']) {
        $max_qty = intval($product['stock']);
    }
    
    // Validate quantity - First check basic validation
    if ($quantity <= 0) {
        $message = 'Quantité invalide. Veuillez entrer une quantité supérieure à 0.';
        $message_type = 'danger';
    } elseif ($quantity < $min_qty) {
        $message = 'Quantité minimale requise : ' . $min_qty . ' unité(s). Vous avez sélectionné ' . $quantity . ' unité(s). Veuillez sélectionner au moins ' . $min_qty . ' unité(s).';
        $message_type = 'danger';
    } elseif ($quantity > $max_qty) {
        $message = 'Quantité maximale autorisée : ' . $max_qty . ' unité(s).';
        $message_type = 'danger';
    } elseif ($quantity > $product['stock']) {
        $message = 'Stock insuffisant. Disponible : ' . $product['stock'] . ' unité(s).';
        $message_type = 'danger';
    } else {
        try {
            // Get current product with min_qty and max_qty from database for final validation
            $stmt = $pdo->prepare("SELECT stock, status, 
                                          COALESCE(min_qty, 1) as min_qty, 
                                          COALESCE(max_qty, stock) as max_qty
                                   FROM products WHERE id = ?");
            if (!$stmt->execute([$product_id])) {
                throw new Exception('Erreur lors de la vérification du produit.');
            }
            $current_product = $stmt->fetch();
            
            if (!$current_product) {
                $message = 'Produit introuvable.';
                $message_type = 'danger';
            } elseif ($current_product['status'] !== 'approved') {
                $message = 'Ce produit n\'est plus disponible.';
                $message_type = 'danger';
            } else {
                $current_min_qty = max(1, intval($current_product['min_qty']));
                $current_max_qty = min(intval($current_product['max_qty']), intval($current_product['stock']));
                
                // Final validation against database values
                if ($quantity < $current_min_qty) {
                    $message = 'Quantité minimale requise : ' . $current_min_qty . ' unité(s). Vous avez sélectionné ' . $quantity . ' unité(s). Veuillez sélectionner au moins ' . $current_min_qty . ' unité(s).';
                    $message_type = 'danger';
                } elseif ($quantity > $current_max_qty) {
                    $message = 'Quantité maximale autorisée : ' . $current_max_qty . ' unité(s).';
                    $message_type = 'danger';
                } elseif ($quantity > $current_product['stock']) {
                    $message = 'Stock insuffisant. Disponible : ' . $current_product['stock'] . ' unité(s).';
                    $message_type = 'danger';
                } else {
                    // Check if item already in cart
                    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE buyer_id = ? AND product_id = ?");
                    if (!$stmt->execute([$buyer_id, $product_id])) {
                        throw new Exception('Erreur lors de la vérification du panier.');
                    }
                    $cart_item = $stmt->fetch();
                    
                    if ($cart_item) {
                        $new_quantity = $cart_item['quantity'] + $quantity;
                        
                        // Validate total quantity against min_qty
                        if ($new_quantity < $current_min_qty) {
                            $needed = max(1, $current_min_qty - $cart_item['quantity']);
                            $message = 'Quantité totale minimale requise : ' . $current_min_qty . ' unité(s). Vous avez actuellement ' . $cart_item['quantity'] . ' unité(s) dans votre panier. Veuillez ajouter au moins ' . $needed . ' unité(s) supplémentaire(s).';
                            $message_type = 'danger';
                        } elseif ($new_quantity > $current_max_qty) {
                            $message = 'Quantité totale maximale autorisée : ' . $current_max_qty . ' unité(s). Vous avez déjà ' . $cart_item['quantity'] . ' unité(s) dans votre panier.';
                            $message_type = 'danger';
                        } elseif ($new_quantity > $current_product['stock']) {
                            $message = 'Impossible d\'ajouter plus. Stock disponible : ' . $current_product['stock'] . ' unité(s). Vous avez déjà ' . $cart_item['quantity'] . ' unité(s) dans votre panier.';
                            $message_type = 'danger';
                        } else {
                            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                            if (!$stmt->execute([$new_quantity, $cart_item['id']])) {
                                throw new Exception('Erreur lors de la mise à jour du panier.');
                            }
                            $message = 'Produit ajouté au panier !';
                            $message_type = 'success';
                        }
                    } else {
                        // First time adding to cart
                        $stmt = $pdo->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
                        if (!$stmt->execute([$buyer_id, $product_id, $quantity])) {
                            throw new Exception('Erreur lors de l\'ajout au panier.');
                        }
                        $message = 'Produit ajouté au panier !';
                        $message_type = 'success';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Database error adding to cart: ' . $e->getMessage());
            $message = 'Erreur lors de l\'ajout au panier. Veuillez réessayer.';
            $message_type = 'danger';
        } catch (Exception $e) {
            error_log('Error adding to cart: ' . $e->getMessage());
            $message = 'Erreur: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        // logic add to cart
    } else {
        if (!isset($product) || !$product) {
            redirect('index.php');
        }
    }
    
}

$page_title = $product['name'];
include '../includes/header.php';
?>

<div class="container">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php" class="text-black">Accueil</a></li>
            <li class="breadcrumb-item active"><?php echo escape($product['category_name']); ?></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo escape($product['name']); ?></li>
        </ol>
    </nav>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
            <?php echo escape($message); ?>
            <?php if($message_type == 'success'): ?>
                <a href="cart.php" class="alert-link ms-2">Voir le panier</a>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- Product Images Carousel -->
        <div class="col-lg-6">
            <?php if (!empty($all_images)): ?>
                <div id="productCarousel" class="carousel slide carousel-fade shadow-sm rounded-4 overflow-hidden mb-3" data-bs-ride="false" data-bs-interval="false" data-bs-pause="true" data-bs-wrap="true">
                    <div class="carousel-inner rounded-4">
                        <?php foreach ($all_images as $index => $img): ?>
                            <?php 
                            $img_path = BASE_URL . 'uploads/products/' . escape($img);
                            $img_exists = file_exists(UPLOAD_DIR . $img);
                            ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <?php if ($img_exists): ?>
                                    <img src="<?php echo $img_path; ?>" 
                                         class="d-block w-100 product-detail-image" 
                                         alt="<?php echo escape($product['name']); ?> - Image <?php echo $index + 1; ?>"
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.png'; this.onerror=null;">
                                <?php else: ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/placeholder.png" 
                                         class="d-block w-100 product-detail-image" 
                                         alt="Image non disponible">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($all_images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (count($all_images) > 1): ?>
                    <div class="product-thumbnails d-flex gap-2 justify-content-center">
                        <?php foreach ($all_images as $index => $img): ?>
                            <?php 
                            $img_path = BASE_URL . 'uploads/products/' . escape($img);
                            $img_exists = file_exists(UPLOAD_DIR . $img);
                            ?>
                            <button type="button" 
                                    data-bs-target="#productCarousel" 
                                    data-bs-slide-to="<?php echo $index; ?>" 
                                    class="thumbnail-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                                    aria-label="Slide <?php echo $index + 1; ?>">
                                <img src="<?php echo $img_exists ? $img_path : BASE_URL . 'assets/images/placeholder.png'; ?>" 
                                     class="rounded" 
                                     alt="Thumbnail <?php echo $index + 1; ?>"
                                     onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.png'; this.onerror=null;">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                    <img src="<?php echo BASE_URL; ?>assets/images/placeholder.png" class="img-fluid" style="width: 100%; height: 500px; object-fit: cover;" alt="Aucune image disponible">
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="ps-lg-4">
                <span class="badge bg-black mb-2"><?php echo escape($product['category_name']); ?></span>
                <h1 class="fw-bold display-5 mb-3"><?php echo escape($product['name']); ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="h2 fw-bold text-black mb-0 me-3"><?php echo number_format($product['price'], 2); ?> MAD</div>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">En Stock</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Rupture de stock</span>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <p class="text-muted mb-1">Vendu par : <span class="text-black fw-bold"><?php echo escape($product['seller_name']); ?></span></p>
                    <p class="text-muted small">Quantité disponible : <?php echo $product['stock']; ?> unités</p>
                    <?php if ($min_qty > 1): ?>
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Quantité minimale de commande : <strong><?php echo $min_qty; ?> unité(s)</strong>
                        </p>
                    <?php endif; ?>
                    <?php if ($max_qty < $product['stock']): ?>
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Quantité maximale par commande : <strong><?php echo $max_qty; ?> unité(s)</strong>
                        </p>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Description</h5>
                    <p class="text-muted lead" style="font-size: 1rem;">
                        <?php echo nl2br(escape($product['description'])); ?>
                    </p>
                </div>

                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" class="mt-5" id="addToCartForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label fw-bold">Quantité</label>
                                <input type="number" id="quantity" name="quantity" class="form-control py-2" min="<?php echo $min_qty; ?>" max="<?php echo min($max_qty, $product['stock']); ?>" value="<?php echo $min_qty; ?>" step="1" required>
                                <?php if ($min_qty > 1): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>Minimum : <?php echo $min_qty; ?> unité(s)
                                    </small>
                                <?php endif; ?>
                                <?php if ($max_qty < $product['stock']): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>Maximum : <?php echo $max_qty; ?> unité(s)
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" name="add_to_cart" class="btn btn-black w-100 py-2">
                                    <i class="fas fa-shopping-cart me-2"></i>Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </form>
                    <script>
                    // Client-side validation for quantity
                    (function() {
                        const form = document.getElementById('addToCartForm');
                        if (!form) return;
                        
                        form.addEventListener('submit', function(e) {
                            const quantity = parseInt(document.getElementById('quantity').value);
                            const minQty = <?php echo $min_qty; ?>;
                            const maxQty = <?php echo min($max_qty, $product['stock']); ?>;
                            const stock = <?php echo $product['stock']; ?>;
                            
                            if (quantity < minQty) {
                                e.preventDefault();
                                alert('Quantité minimale requise : ' + minQty + ' unité(s).\nVous avez sélectionné ' + quantity + ' unité(s).\nVeuillez sélectionner au moins ' + minQty + ' unité(s).');
                                document.getElementById('quantity').focus();
                                document.getElementById('quantity').select();
                                return false;
                            }
                            
                            if (quantity > maxQty) {
                                e.preventDefault();
                                alert('Quantité maximale autorisée : ' + maxQty + ' unité(s).');
                                document.getElementById('quantity').focus();
                                return false;
                            }
                            
                            if (quantity > stock) {
                                e.preventDefault();
                                alert('Stock insuffisant. Disponible : ' + stock + ' unité(s).');
                                document.getElementById('quantity').focus();
                                return false;
                            }
                        });
                    })();
                    </script>
                    <script>
                    // Client-side validation for quantity
                    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
                        const quantity = parseInt(document.getElementById('quantity').value);
                        const minQty = <?php echo $min_qty; ?>;
                        const maxQty = <?php echo min($max_qty, $product['stock']); ?>;
                        const stock = <?php echo $product['stock']; ?>;
                        
                        if (quantity < minQty) {
                            e.preventDefault();
                            alert('Quantité minimale requise : ' + minQty + ' unité(s).');
                            document.getElementById('quantity').focus();
                            return false;
                        }
                        
                        if (quantity > maxQty) {
                            e.preventDefault();
                            alert('Quantité maximale autorisée : ' + maxQty + ' unité(s).');
                            document.getElementById('quantity').focus();
                            return false;
                        }
                        
                        if (quantity > stock) {
                            e.preventDefault();
                            alert('Stock insuffisant. Disponible : ' + stock + ' unité(s).');
                            document.getElementById('quantity').focus();
                            return false;
                        }
                    });
                    </script>
                <?php else: ?>
                    <div class="alert alert-light border mt-5">
                        <i class="fas fa-info-circle me-2"></i>Ce produit n'est plus disponible pour le moment.
                    </div>
                <?php endif; ?>

                <div class="mt-4 d-flex gap-3">
                    <button class="btn btn-outline-secondary btn-sm" id="favoriteBtn" data-product-id="<?php echo $product_id; ?>">
                        <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-heart me-1"></i> 
                        <span id="favoriteText"><?php echo $is_favorited ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?></span>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="shareProduct()">
                        <i class="fas fa-share-alt me-1"></i> Partager
                    </button>
                </div>
                <script>
                // Favorite functionality
                document.getElementById('favoriteBtn').addEventListener('click', function() {
                    const btn = this;
                    const productId = btn.dataset.productId;
                    const icon = btn.querySelector('i');
                    const text = btn.querySelector('span');
                    
                    fetch('<?php echo BASE_URL; ?>buyer/toggle_favorite.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_id=' + productId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.favorited) {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                text.textContent = 'Retirer des favoris';
                            } else {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                text.textContent = 'Ajouter aux favoris';
                            }
                        } else {
                            alert('Erreur: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erreur lors de la mise à jour des favoris.');
                    });
                });
                
                // Share functionality
                function shareProduct() {
                    const productName = '<?php echo escape($product['name']); ?>';
                    const productUrl = window.location.href;
                    
                    if (navigator.share) {
                        navigator.share({
                            title: productName,
                            text: 'Découvrez ce produit sur JemAll: ' + productName,
                            url: productUrl
                        }).catch(err => console.log('Error sharing', err));
                    } else {
                        // Fallback: copy to clipboard
                        navigator.clipboard.writeText(productUrl).then(() => {
                            alert('Lien copié dans le presse-papiers !');
                        }).catch(() => {
                            // Fallback: show URL
                            prompt('Copiez ce lien:', productUrl);
                        });
                    }
                }
                </script>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
