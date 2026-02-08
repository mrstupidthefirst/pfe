<?php
/**
 * Seller Profile
 */
require_once '../config/config.php';
requireRole('seller');

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    
    // Check if ID is already verified
    $check = $pdo->prepare("SELECT is_id_verified FROM users WHERE id = ?");
    $check->execute([$user_id]);
    $is_verified = $check->fetchColumn();
    
    if (empty($full_name) || empty($phone)) {
        $message = 'Nom et téléphone sont obligatoires.';
        $msg_type = 'danger';
    } else {
        // Build query
        $sql = "UPDATE users SET full_name = ?, phone = ?, address = ?";
        $params = [$full_name, $phone, $address];
        
        // Only update ID if not verified
        if (!$is_verified) {
            $sql .= ", national_id = ?";
            $params[] = $national_id;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = 'Profil mis à jour avec succès.';
            $msg_type = 'success';
            // Update session name if changed
            $_SESSION['full_name'] = $full_name;
        } else {
            $message = 'Erreur lors de la mise à jour.';
            $msg_type = 'danger';
        }
    }
}

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = 'Mon Profil Seller';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold">Mon Profil Vendeur</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                            <?php echo escape($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom Complet *</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo escape($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control bg-light" value="<?php echo escape($user['email']); ?>" readonly>
                                <small class="text-muted">L'email ne peut pas être modifié.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Téléphone *</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo escape($user['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Adresse</label>
                            <textarea class="form-control" name="address" rows="2"><?php echo escape($user['address']); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex align-items-center justify-content-between">
                                Numéro CIN / Passeport
                                <?php if ($user['is_id_verified']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Vérifié</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>Non Vérifié</span>
                                <?php endif; ?>
                            </label>
                            <input type="text" class="form-control" name="national_id" value="<?php echo escape($user['national_id']); ?>" 
                                   <?php echo $user['is_id_verified'] ? 'readonly class="form-control bg-light"' : ''; ?> 
                                   placeholder="Entrez votre numéro d'identité pour vérification">
                            <?php if (!$user['is_id_verified']): ?>
                                <small class="text-muted text-warning"><i class="fas fa-info-circle me-1"></i>Veuillez saisir votre ID exact pour que l'administrateur puisse valider votre compte vendeur.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-black px-4">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
