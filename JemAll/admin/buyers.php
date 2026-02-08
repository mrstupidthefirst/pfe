<?php
/**
 * Admin - Manage Buyers
 */
require_once '../config/config.php';
requireRole('admin');

$pdo = getDB();
$message = '';
$message_type = 'success';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($user_id > 0 && in_array($action, ['activate', 'deactivate', 'delete'])) {
        try {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'buyer'");
                if ($stmt->execute([$user_id])) {
                    $message = 'Acheteur supprimé avec succès.';
                } else {
                    $message = 'Erreur lors de la suppression.';
                    $message_type = 'danger';
                }
            } else {
                $new_status = $action === 'activate' ? 'active' : 'inactive';
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'buyer'");
                if ($stmt->execute([$new_status, $user_id])) {
                    $message = 'Statut de l\'acheteur mis à jour.';
                } else {
                    $message = 'Erreur lors de la mise à jour.';
                    $message_type = 'danger';
                }
            }
        } catch (PDOException $e) {
            error_log('Database error in buyers.php: ' . $e->getMessage());
            $message = 'Erreur de base de données.';
            $message_type = 'danger';
        }
    }
}

// Get all buyers
try {
    $buyers = $pdo->query("SELECT u.*, 
                                  (SELECT COUNT(*) FROM orders WHERE buyer_id = u.id) as total_orders,
                                  (SELECT SUM(total_amount) FROM orders WHERE buyer_id = u.id AND status != 'cancelled') as total_spent
                           FROM users u 
                           WHERE u.role = 'buyer' 
                           ORDER BY u.created_at DESC")->fetchAll();
} catch (PDOException $e) {
    error_log('Database error fetching buyers: ' . $e->getMessage());
    $buyers = [];
}

$page_title = 'Gestion des Acheteurs';
include '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0">Gestion des Acheteurs</h1>
            <p class="text-muted">Gérer tous les comptes acheteurs</p>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo escape($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Acheteur</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Téléphone</th>
                            <th class="py-3">Commandes</th>
                            <th class="py-3">Total Dépensé</th>
                            <th class="py-3">Statut</th>
                            <th class="py-3">Date Inscription</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($buyers)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <p class="text-muted mb-0">Aucun acheteur trouvé.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($buyers as $buyer): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if (!empty($buyer['profile_picture']) && file_exists(__DIR__ . '/../uploads/profiles/' . $buyer['profile_picture'])): ?>
                                                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo escape($buyer['profile_picture']); ?>" 
                                                         class="rounded-circle" 
                                                         width="40" height="40" 
                                                         style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($buyer['username'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo escape($buyer['full_name'] ?: $buyer['username']); ?></h6>
                                                <small class="text-muted">@<?php echo escape($buyer['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo escape($buyer['email']); ?></td>
                                    <td><?php echo escape($buyer['phone'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo intval($buyer['total_orders']); ?></span>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($buyer['total_spent'] ?? 0, 2); ?> MAD</td>
                                    <td>
                                        <?php if ($buyer['status'] === 'active'): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php elseif ($buyer['status'] === 'inactive'): ?>
                                            <span class="badge bg-secondary">Inactif</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($buyer['created_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group" role="group">
                                            <?php if ($buyer['status'] === 'active'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $buyer['id']; ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Désactiver">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $buyer['id']; ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Activer">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet acheteur ?');">
                                                <input type="hidden" name="user_id" value="<?php echo $buyer['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
</div>

<?php include '../includes/footer.php'; ?>
