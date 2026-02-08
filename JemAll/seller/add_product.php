<?php
/**
 * Seller - Add Product
 */
require_once '../config/config.php';
require_once '../config/image_helper.php';
requireRole('seller');

$pdo = getDB();
$seller_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get categories
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $min_qty = intval($_POST['min_qty'] ?? 1);
    $max_qty = intval($_POST['max_qty'] ?? 100);
    
    // New Fields
    $genre = trim($_POST['genre'] ?? '');
    $subcategory = trim($_POST['subcategory'] ?? '');
    $sizes = isset($_POST['sizes']) ? json_encode($_POST['sizes']) : null;
    
    // Validate
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || $category_id <= 0) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        // Find category name to validate specific fields
        $cat_name = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $cat_name = strtolower($cat['name']);
                break;
            }
        }
        
        // Validate specific fields
        if (strpos($cat_name, 'livre') !== false && empty($genre)) {
            $error = 'Le genre est obligatoire pour les livres.';
        } elseif (strpos($cat_name, 'vêtement') !== false && empty($sizes)) {
            $error = 'Veuillez sélectionner au moins une taille pour les vêtements.';
        } elseif (strpos($cat_name, 'électronique') !== false && empty($subcategory)) {
            $error = 'La sous-catégorie est obligatoire pour l\'électronique.';
        }
        
        if (empty($error)) {
            // Handle Images (1 to 5)
            $uploaded_images = []; // Format: ['path' => 'x.jpg', 'order' => 0]
            
            // Check Main Image (Image 1)
            if (empty($_FILES['image_1']['name'])) {
                $error = 'L\'image principale (Image 1) est obligatoire.';
            } else {
                // Process each image slot
                for ($i = 1; $i <= 5; $i++) {
                    $field_name = 'image_1';
                    if ($i > 1) $field_name = 'image_' . $i; // Handle image_2, image_3...
                    
                    if (isset($_FILES[$field_name]) && !empty($_FILES[$field_name]['name'])) {
                        $file = $_FILES[$field_name];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            if ($i == 1) $error = 'Format d\'image invalide pour l\'image 1.';
                            continue;
                        }
                        
                        $new_name = uniqid('prod_') . '.' . $ext;
                        $dest = UPLOAD_DIR . $new_name;
                        
                        // Move and Resize
                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            // Resize to 800x800
                            $resized_name = uniqid('prod_res_') . '.' . $ext;
                            $resized_path = UPLOAD_DIR . $resized_name;
                            $resize_result = resizeImageTo800x800($dest, $resized_path);
                            
                            if ($resize_result['success']) {
                                unlink($dest); // remove original
                                $uploaded_images[] = ['path' => $resized_name, 'order' => $i - 1]; // 0-based index
                            } else {
                                // Keep original if resize fails
                                rename($dest, $resized_path);
                                $uploaded_images[] = ['path' => $resized_name, 'order' => $i - 1];
                            }
                        }
                    }
                }
            }
            
            if (empty($error)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Main image is the first one
                    $main_image = $uploaded_images[0]['path'];
                    
                    // Insert Product
                    $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, name, description, price, stock, min_qty, max_qty, image, genre, sizes, subcategory, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$seller_id, $category_id, $name, $description, $price, $stock, $min_qty, $max_qty, $main_image, $genre, $sizes, $subcategory]);
                    $product_id = $pdo->lastInsertId();
                    
                    // Insert Images into product_images
                    $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, display_order) VALUES (?, ?, ?)");
                    foreach ($uploaded_images as $img) {
                        $stmt_img->execute([$product_id, $img['path'], $img['order']]);
                    }
                    
                    $pdo->commit();
                    $success = 'Produit ajouté avec succès !';
                    $_POST = []; // Clear form
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Erreur base de données: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Ajouter un produit';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex align-items-center mb-4">
        <a href="products.php" class="btn btn-outline-black btn-sm me-3"><i class="fas fa-arrow-left"></i></a>
        <h1 class="fw-bold mb-0">Ajouter un produit</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo escape($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo escape($success); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate id="productForm">
                <div class="row g-4">
                    <!-- General Info -->
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom *</label>
                            <input type="text" class="form-control" name="name" required value="<?php echo escape($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description *</label>
                            <textarea class="form-control" name="description" rows="5" required><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Dynamic Fields Section -->
                        <div id="dynamic-fields" class="p-3 bg-light rounded d-none mb-3">
                            <!-- Books -->
                            <div class="dynamic-field d-none" id="field-books">
                                <label class="form-label fw-bold">Genre Littéraire *</label>
                                <input type="text" class="form-control" name="genre" placeholder="Ex: Roman, Science-Fiction, Manga...">
                            </div>
                            
                            <!-- Clothing -->
                            <div class="dynamic-field d-none" id="field-clothes">
                                <label class="form-label fw-bold d-block">Tailles Disponibles *</label>
                                <div class="btn-group" role="group">
                                    <?php foreach(['XS','S','M','L','XL','XXL'] as $size): ?>
                                    <input type="checkbox" class="btn-check" name="sizes[]" value="<?php echo $size; ?>" id="size-<?php echo $size; ?>" autocomplete="off">
                                    <label class="btn btn-outline-dark" for="size-<?php echo $size; ?>"><?php echo $size; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Electronics -->
                            <div class="dynamic-field d-none" id="field-electronics">
                                <label class="form-label fw-bold">Sous-catégorie *</label>
                                <select class="form-select" name="subcategory">
                                    <option value="">Sélectionner...</option>
                                    <option value="smartphone">Smartphones</option>
                                    <option value="laptop">Ordinateurs Portables</option>
                                    <option value="accessories">Accessoires</option>
                                    <option value="audio">Audio / Casques</option>
                                    <option value="camera">Photo / Vidéo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Side Panel -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Catégorie *</label>
                            <select class="form-select" name="category_id" id="category_select" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-name="<?php echo strtolower($cat['name']); ?>" 
                                        <?php echo (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo escape($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prix (MAD) *</label>
                            <input type="number" class="form-control" name="price" step="0.01" required value="<?php echo escape($_POST['price'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" class="form-control" name="stock" required value="<?php echo escape($_POST['stock'] ?? ''); ?>">
                        </div>

                        <div class="row g-2 mb-3">
                             <div class="col-6">
                                <label class="form-label small fw-bold">Qté Min</label>
                                <input type="number" class="form-control form-control-sm" name="min_qty" value="1">
                             </div>
                             <div class="col-6">
                                <label class="form-label small fw-bold">Qté Max</label>
                                <input type="number" class="form-control form-control-sm" name="max_qty" value="100">
                             </div>
                        </div>
                    </div>
                    
                    <!-- Images -->
                    <div class="col-12">
                        <hr>
                        <h5 class="fw-bold mb-3">Images du produit</h5>
                        <p class="text-muted small">Contrôlez l'ordre d'affichage. L'image 1 est l'image principale.</p>
                        
                        <div class="row g-3">
                            <?php for($i=1; $i<=5; $i++): ?>
                            <div class="col-md-2 col-6">
                                <div class="p-2 border rounded bg-light text-center h-100">
                                    <label class="form-label fw-bold mb-1">Image <?php echo $i; ?> <?php echo $i==1?'*':''; ?></label>
                                    <input type="file" class="form-control form-control-sm mb-2" name="image_<?php echo $i; ?>" accept="image/*" <?php echo $i==1?'required':''; ?> onchange="previewImage(this, 'preview-<?php echo $i; ?>')">
                                    <div class="img-preview-box bg-white border d-flex align-items-center justify-content-center mx-auto" style="width:80px; height:80px; overflow:hidden;">
                                        <img id="preview-<?php echo $i; ?>" src="" class="d-none" style="width:100%; height:100%; object-fit:cover;">
                                        <i class="fas fa-image text-muted d-block" id="icon-<?php echo $i; ?>"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-black px-5 py-2">Publier le produit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Dynamic Fields Logic
document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('category_select');
    const dynamicContainer = document.getElementById('dynamic-fields');
    
    function updateFields() {
        // Hide all first
        document.querySelectorAll('.dynamic-field').forEach(el => el.classList.add('d-none'));
        dynamicContainer.classList.add('d-none');
        
        const selected = catSelect.options[catSelect.selectedIndex];
        if (!selected || !selected.value) return;
        
        const name = selected.getAttribute('data-name');
        
        if (name.includes('livre') || name.includes('book')) {
            dynamicContainer.classList.remove('d-none');
            document.getElementById('field-books').classList.remove('d-none');
        } else if (name.includes('vêtement') || name.includes('cloth')) {
            dynamicContainer.classList.remove('d-none');
            document.getElementById('field-clothes').classList.remove('d-none');
        } else if (name.includes('électronique') || name.includes('electro')) {
            dynamicContainer.classList.remove('d-none');
            document.getElementById('field-electronics').classList.remove('d-none');
        }
    }
    
    catSelect.addEventListener('change', updateFields);
    updateFields(); // Run on load
});

// Image Preview
function previewImage(input, imgId) {
    const img = document.getElementById(imgId);
    const icon = document.getElementById('icon-' + imgId.split('-')[1]);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.remove('d-none');
            if(icon) icon.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        img.src = '';
        img.classList.add('d-none');
        if(icon) icon.classList.remove('d-none');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
