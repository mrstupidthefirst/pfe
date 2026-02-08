<?php
/**
 * All User Notifications
 */
require_once 'config/config.php';
requireLogin();

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$user_id]);
    redirect('notifications.php');
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$page_title = 'Mes Notifications';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-0">Mes Notifications</h1>
            <p class="text-muted mb-0">Restez informé des mises à jour de vos commandes et de votre compte.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <a href="notifications.php?mark_all_read=1" class="btn btn-black">
                <i class="fas fa-check-double me-2"></i> Tout marquer comme lu
            </a>
        <?php endif; ?>
    </div>

    <div class="notifications-list mx-auto" style="max-width: 800px;">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                <div class="mb-4">
                    <i class="fas fa-bell-slash text-light" style="font-size: 6rem;"></i>
                </div>
                <h3 class="fw-bold">Aucune notification</h3>
                <p class="text-muted">Vous n'avez pas encore de messages.</p>
                <a href="index.php" class="btn btn-black btn-lg px-5 mt-3">Retour à l'accueil</a>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 <?php echo !$notif['is_read'] ? 'border-start border-primary border-4' : ''; ?>">
                    <div class="card-body p-4">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="bg-light rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <?php 
                                $icon = 'fa-info-circle text-info';
                                if (strpos($notif['type'], 'order') !== false) $icon = 'fa-shopping-bag text-warning';
                                if (strpos($notif['type'], 'account') !== false) $icon = 'fa-user-check text-success';
                                if (strpos($notif['type'], 'product') !== false) $icon = 'fa-box text-primary';
                                ?>
                                <i class="fas <?php echo $icon; ?> fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small fw-bold text-uppercase ls-1">
                                        <?php echo str_replace('_', ' ', $notif['type']); ?>
                                    </span>
                                    <span class="text-muted small">
                                        <i class="far fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                                <h5 class="mb-2 <?php echo !$notif['is_read'] ? 'fw-bold' : ''; ?>">
                                    <?php echo escape($notif['message']); ?>
                                </h5>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo BASE_URL; ?>read_notification.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-dark mt-2">
                                        Voir les détails <i class="fas fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <a href="<?php echo BASE_URL; ?>read_notification.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-link text-decoration-none p-0" title="Marquer comme lu et supprimer">
                                    <div class="bg-primary rounded-circle" style="width: 12px; height: 12px;"></div>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>read_notification.php?id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-link text-muted p-0" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
