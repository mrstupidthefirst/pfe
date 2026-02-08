<?php
/**
 * Seller - Edit Product
 * Edit existing products
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get product ID
$product_id = intval($_GET['id'] ?? 0);

// Get product and verify ownership
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$product_id, $seller_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

// Get categories
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || $category_id <= 0) {
        $error = 'Veuillez remplir correctement tous les champs obligatoires.';
    } else {
        $image_name = $product['image'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Type d\'image non valide. Seuls JPEG, PNG, GIF et WebP sont autorisés.';
            } elseif ($file['size'] > $max_size) {
                $error = 'La taille de l\'image dépasse la limite de 5 Mo.';
            } else {
                // Delete old image
                if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
                    @unlink(UPLOAD_DIR . $product['image']);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('product_') . '.' . $extension;
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $image_name)) {
                    $error = 'Échec du téléchargement de l\'image.';
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ?, status = 'pending' WHERE id = ? AND seller_id = ?");
            
            if ($stmt->execute([$category_id, $name, $description, $price, $stock, $image_name, $product_id, $seller_id])) {
                $success = 'Produit mis à jour avec succès ! Il est en attente d\'approbation.';
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
                $stmt->execute([$product_id, $seller_id]);
                $product = $stmt->fetch();
            } else {
                $error = 'Erreur lors de la mise à jour du produit.';
            }
        }
    }
}

$page_title = 'Modifier le produit';
include '../includes/header.php';
?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fw-bold mb-0">Modifier le Produit</h1>
                    <p class="text-muted">Mettez à jour les informations de votre article</p>
                </div>
                <a href="products.php" class="btn btn-outline-black">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                    <i class="fas fa-check-circle me-2"></i> <?php echo escape($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Left Column: Basic Info -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-4">Informations Générales</h5>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Nom du produit <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg bg-light border-0" 
                                       placeholder="Ex: Souris Gamer RGB" required value="<?php echo escape($product['name']); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Description détaillée <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control bg-light border-0" rows="8" 
                                          placeholder="Décrivez les caractéristiques de votre produit..." required><?php echo escape($product['description']); ?></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Prix (DH) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">DH</span>
                                        <input type="number" name="price" step="0.01" min="0.01" class="form-control form-control-lg bg-light border-0" 
                                               required value="<?php echo $product['price']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Stock disponible <span class="text-danger">*</span></label>
                                    <input type="number" name="stock" min="0" class="form-control form-control-lg bg-light border-0" 
                                           required value="<?php echo $product['stock']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings & Image -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                            <h5 class="fw-bold mb-4">Catégorie</h5>
                            <select name="category_id" class="form-select form-select-lg bg-light border-0" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo escape($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold mb-4">Image du Produit</h5>
                            
                            <div class="mb-4 text-center">
                                <p class="text-muted small mb-2 text-start">Image actuelle</p>
                                <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo escape($product['image']); ?>" 
                                         class="rounded-3 border shadow-sm w-100 mb-3" style="max-height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center mb-3" style="height: 150px;">
                                        <i class="fas fa-image text-muted fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Changer l'image</label>
                                <input type="file" name="image" class="form-control bg-light border-0" accept="image/*">
                                <small class="text-muted mt-2 d-block">Laissez vide pour conserver l'image actuelle.</small>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-black btn-lg w-100 py-3 rounded-4 mb-2">
                                <i class="fas fa-save me-2"></i>Mettre à jour
                            </button>
                            <a href="products.php" class="btn btn-outline-black btn-lg w-100 py-3 rounded-4">
                                Annuler
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
