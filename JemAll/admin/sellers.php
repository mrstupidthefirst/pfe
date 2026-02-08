<?php
/**
 * Admin - Manage Sellers
 * Approve/reject seller accounts
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'seller'");
        if ($stmt->execute([$user_id])) {
            createNotification($user_id, 'account_status', 'Félicitations ! Votre compte vendeur a été approuvé. Vous pouvez maintenant ajouter des produits.', 'seller/dashboard.php');
            $message = '<div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>Vendeur approuvé avec succès.</div>';
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'seller'");
        if ($stmt->execute([$user_id])) {
            createNotification($user_id, 'account_status', 'Votre compte vendeur a été désactivé par l\'administrateur. Veuillez nous contacter pour plus d\'informations.');
            $message = '<div class="alert alert-warning border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i>Vendeur désactivé.</div>';
        }
    } elseif ($action === 'verify_id') {
        $stmt = $pdo->prepare("UPDATE users SET is_id_verified = TRUE WHERE id = ? AND role = 'seller'");
        if ($stmt->execute([$user_id])) {
            createNotification($user_id, 'account_status', 'Votre identité a été vérifiée avec succès. Merci !', 'seller/profile.php');
            $message = '<div class="alert alert-info border-0 shadow-sm text-white bg-info"><i class="fas fa-id-card me-2"></i>Identité du vendeur vérifiée.</div>';
        }
    }
}

// Get all sellers
$sellers = $pdo->query("SELECT * FROM users WHERE role = 'seller' ORDER BY created_at DESC")->fetchAll();

// Calculate stats
$total_sellers = count($sellers);
$pending_sellers = 0;
$verified_ids = 0;
foreach ($sellers as $s) {
    if ($s['status'] === 'pending') $pending_sellers++;
    if ($s['is_id_verified']) $verified_ids++;
}

$page_title = 'Gestion des Vendeurs';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-0">Gestion des Vendeurs</h1>
            <p class="text-muted mb-0">Administrez les comptes vendeurs et vérifiez les identités.</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-dark text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted text-uppercase small fw-bold ls-1">Total Vendeurs</h6>
                        <h3 class="fw-bold mb-0"><?php echo $total_sellers; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-clock fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted text-uppercase small fw-bold ls-1">En Attente</h6>
                        <h3 class="fw-bold mb-0"><?php echo $pending_sellers; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-id-check fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted text-uppercase small fw-bold ls-1">Identités Vérifiées</h6>
                        <h3 class="fw-bold mb-0"><?php echo $verified_ids; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Liste des Vendeurs</h5>
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control bg-light border-0" placeholder="Rechercher un vendeur...">
            </div>
        </div>
        <div class="table-responsive mobile-table-cards">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-uppercase small fw-bold text-muted">Vendeur</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Contact</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Identité (CIN)</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Statut</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted">Inscription</th>
                        <th class="text-end pe-4 py-3 text-uppercase small fw-bold text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sellers)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Aucun vendeur trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sellers as $seller): ?>
                            <tr>
                                <td class="ps-4" data-label="Vendeur">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold shadow-sm" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($seller['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo escape($seller['username']); ?></h6>
                                            <small class="text-muted">ID: #<?php echo $seller['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Contact">
                                    <div class="d-flex flex-column">
                                        <span class="mb-1"><i class="far fa-envelope me-2 text-muted"></i><?php echo escape($seller['email']); ?></span>
                                        <small class="text-muted"><i class="fas fa-phone-alt me-2"></i><?php echo escape($seller['phone']); ?></small>
                                    </div>
                                </td>
                                <td data-label="Identité (CIN)">
                                    <?php if (!empty($seller['national_id'])): ?>
                                        <div class="mb-1 fw-semibold"><?php echo escape($seller['national_id']); ?></div>
                                        <?php if ($seller['is_id_verified']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">
                                                <i class="fas fa-check-circle me-1"></i>Vérifié
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2">
                                                <i class="fas fa-clock me-1"></i>En attente
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Non soumis</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Statut">
                                    <?php 
                                    $status_classes = [
                                        'active' => 'bg-success bg-opacity-10 text-success',
                                        'pending' => 'bg-warning bg-opacity-10 text-warning',
                                        'inactive' => 'bg-danger bg-opacity-10 text-danger'
                                    ];
                                    $status_labels = [
                                        'active' => 'Actif',
                                        'pending' => 'En attente',
                                        'inactive' => 'Inactif'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_classes[$seller['status']] ?? 'bg-secondary'; ?> rounded-pill px-3 py-2">
                                        <?php echo $status_labels[$seller['status']] ?? ucfirst($seller['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Inscription">
                                    <span class="text-muted small"><?php echo date('d/m/Y', strtotime($seller['created_at'])); ?></span>
                                </td>
                                <td class="text-end pe-4" data-label="Actions">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                            <li><h6 class="dropdown-header text-uppercase small fw-bold">Compte</h6></li>
                                            
                                            <?php if ($seller['status'] === 'pending' || $seller['status'] === 'inactive'): ?>
                                                <li>
                                                    <form method="POST">
                                                        <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fas fa-check me-2"></i>Activer le compte
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($seller['status'] === 'active' || $seller['status'] === 'pending'): ?>
                                                <li>
                                                    <form method="POST">
                                                        <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce vendeur ?')">
                                                            <i class="fas fa-ban me-2"></i>Désactiver / Rejeter
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($seller['national_id']) && !$seller['is_id_verified']): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header text-uppercase small fw-bold">Vérification</h6></li>
                                                <li>
                                                    <form method="POST">
                                                        <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                                        <input type="hidden" name="action" value="verify_id">
                                                        <button type="submit" class="dropdown-item text-info">
                                                            <i class="fas fa-id-card me-2"></i>Valider l'identité (CIN)
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="mailto:<?php echo $seller['email']; ?>"><i class="fas fa-envelope me-2"></i>Contacter</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
