<?php
/**
 * Home Page
 * Displays approved products for browsing
 */
require_once 'config/config.php';

// Redirect sellers and admins to their dashboards
if (isLoggedIn()) {
    if (hasRole('seller')) {
        redirect('seller/dashboard.php');
    } elseif (hasRole('admin')) {
        redirect('admin/dashboard.php');
    }
}

loadLanguage();

$pdo = getDB();
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';

// Get all active categories
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Get all active categories
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Build product query
$sql = "SELECT p.*, c.name as category_name, u.full_name as seller_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.seller_id = u.id 
        WHERE p.status = 'approved'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name ILIKE ? OR p.description ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = 'Accueil';
$show_hero = empty($search) && empty($category_id);
include 'includes/header.php';
?>

<?php if ($show_hero): ?>
<!-- Hero Section - Full Width, Attached to Header -->
<div class="hero-section-full">
    <div class="hero-image-container">
        <img src="<?php echo BASE_URL; ?>assets/images/placeholder.png" class="hero-image-full" alt="Hero Image">
        <div class="hero-overlay">
            <div class="hero-buttons-container">
                <div class="d-flex gap-3 flex-wrap justify-content-center">
                    <a href="#products" class="btn btn-light btn-lg px-5 py-3 fw-bold">
                        <i class="fas fa-shopping-bag me-2"></i><?php echo __('shop_now'); ?>
                    </a>
                    <a href="register.php?role=seller" class="btn btn-outline-light btn-lg px-5 py-3 fw-bold">
                        <i class="fas fa-store me-2"></i><?php echo __('become_seller'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <!-- Categories Filter -->
    <div class="mb-5 <?php echo $show_hero ? 'mt-5' : 'mt-4'; ?>" id="products">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><?php echo __('our_products'); ?></h2>
            <div class="dropdown">
                <button class="btn btn-outline-black dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php 
                    $current_cat = __('all_categories');
                    foreach($categories as $cat) {
                        if($category_id == $cat['id']) $current_cat = $cat['name'];
                    }
                    echo escape($current_cat);
                    ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="index.php"><?php echo __('all_categories'); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($categories as $cat): ?>
                        <li><a class="dropdown-item" href="index.php?category=<?php echo $cat['id']; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo escape($cat['name']); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row g-4">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="lead text-muted"><?php echo __('no_products_found'); ?></p>
                    <a href="index.php" class="btn btn-black mt-2"><?php echo __('view_all_products'); ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-6 col-md-6 col-lg-4 col-xl-3">
                        <div class="card h-100">
                            <div class="position-relative">
                                <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($product['image']); ?>" class="card-img-top product-image" alt="<?php echo escape($product['name']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/placeholder.png" class="card-img-top product-image" alt="No image">
                                <?php endif; ?>
                                <span class="position-absolute top-0 start-0 m-3 badge bg-black"><?php echo escape($product['category_name']); ?></span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-1"><?php echo escape($product['name']); ?></h5>
                                <p class="text-muted small mb-2">Vendu par : <?php echo escape($product['seller_name']); ?></p>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?php echo escape(substr($product['description'], 0, 80)); ?>...
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="h5 fw-bold mb-0"><?php echo number_format($product['price'], 2); ?> MAD</span>
                                    <span class="text-muted small">Stock: <?php echo $product['stock']; ?></span>
                                </div>
                                <div class="mt-3">
                                    <?php if (isLoggedIn() && hasRole('buyer')): ?>
                                        <a href="buyer/product.php?id=<?php echo $product['id']; ?>" class="btn btn-black w-100">Voir Détails</a>
                                    <?php elseif (!isLoggedIn()): ?>
                                        <a href="login.php" class="btn btn-black w-100">Se connecter pour acheter</a>
                                    <?php else: ?>
                                        <a href="buyer/product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-black w-100">Aperçu</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
