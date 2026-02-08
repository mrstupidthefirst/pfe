<?php
/**
 * Header Template
 * Common header for all pages
 */
if (!isset($page_title)) {
    $page_title = 'JemAll Marketplace';
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" dir="<?php echo getCurrentLanguage() === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - JemAll</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-black py-3">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" 
                         class="navbar-logo me-2 shadow-sm rounded-circle" 
                         style="width: 40px; height: 40px; object-fit: cover;"
                         alt="JemAll">
                    <span class="fs-4 tracking-tight">JemAll</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon">
                        <span class="navbar-toggler-icon-bar"></span>
                    </span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Search Bar (Hidden on Auth, Admin and Seller pages) -->
                    <?php 
                    $current_path = $_SERVER['PHP_SELF'];
                    $is_auth = in_array(basename($current_path), ['login.php', 'register.php']);
                    $is_admin = strpos($current_path, '/admin/') !== false;
                    $is_seller = strpos($current_path, '/seller/') !== false;
                    $show_search = !$is_auth && !$is_admin && !$is_seller;
                    ?>
                    
                    <?php if ($show_search): ?>
                    <form class="d-flex mx-auto my-2 my-lg-0 w-50 position-relative" action="<?php echo BASE_URL; ?>index.php" method="GET" id="searchForm">
                        <div class="input-group">
                            <input class="form-control border-0 rounded-start-pill py-2 ps-4" type="search" name="search" placeholder="<?php echo __('search'); ?>..." aria-label="Search">
                            
                            <!-- AI Camera JS Handler will be added to main.js or inline -->
                            <button class="btn btn-light border-0" type="button" id="visualSearchBtn" title="Recherche Visuelle AI">
                                <i class="fas fa-camera text-primary"></i>
                            </button>
                            
                            <button class="btn btn-primary border-0 rounded-end-pill px-4" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Visual Search Modal -->
                    <div class="modal fade" id="visualSearchModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-magic me-2 text-primary"></i>Recherche Visuelle AI</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center py-5">
                                    <div id="camera-upload-area" class="p-4 border-2 border-dashed rounded-3 cursor-pointer bg-light hover-bg-light-dark transition-all">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h6 class="fw-bold mb-2">Prendre une photo ou importer</h6>
                                        <p class="text-muted small mb-3">L'IA analysera votre image pour trouver des produits similaires.</p>
                                        <input type="file" id="visualSearchInput" accept="image/*" class="d-none">
                                        <button class="btn btn-black rounded-pill px-4" onclick="document.getElementById('visualSearchInput').click()">
                                            <i class="fas fa-camera me-2"></i>Scanner
                                        </button>
                                    </div>
                                    <div id="visual-search-loading" class="d-none mt-4">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <p class="fw-bold animate-pulse">Analyse de l'image par IA...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>index.php"><?php echo __('home'); ?></a>
                        </li>
                        
                        <!-- Language Switcher -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-globe me-1"></i> <?php echo strtoupper(getCurrentLanguage()); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="?lang=fr">Français</a></li>
                                <li><a class="dropdown-item" href="?lang=en">English</a></li>
                                <li><a class="dropdown-item" href="?lang=ar">العربية</a></li>
                            </ul>
                        </li>

                        <?php if (isLoggedIn()): ?>
                            <!-- Notification Bell -->
                            <?php
                            $notif_pdo = getDB();
                            $notif_user_id = $_SESSION['user_id'];
                            
                            // Get unread count
                            $unread_stmt = $notif_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
                            $unread_stmt->execute([$notif_user_id]);
                            $unread_count = $unread_stmt->fetchColumn();
                            
                            // Get latest notifications (Only unread ones if we want them to disappear once read)
                            $latest_notifs_stmt = $notif_pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 5");
                            $latest_notifs_stmt->execute([$notif_user_id]);
                            $latest_notifs = $latest_notifs_stmt->fetchAll();
                            ?>
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link position-relative dropdown-toggle no-caret" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                            <?php echo $unread_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark p-0 shadow-lg border-0" aria-labelledby="notifDropdown" style="width: 300px; max-height: 400px; overflow-y: auto; border-radius: 12px;">
                                    <li class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold">Notifications</h6>
                                        <?php if ($unread_count > 0): ?>
                                            <span class="badge bg-danger rounded-pill"><?php echo $unread_count; ?> Neuf</span>
                                        <?php endif; ?>
                                    </li>
                                    <?php if (empty($latest_notifs)): ?>
                                        <li class="p-4 text-center text-muted">
                                            <i class="fas fa-bell-slash mb-2 d-block opacity-25" style="font-size: 2rem;"></i>
                                            <small>Aucune nouvelle notification</small>
                                        </li>
                                    <?php else: ?>
                                        <?php foreach ($latest_notifs as $notif): ?>
                                            <li>
                                                <a class="dropdown-item p-3 border-bottom border-dark position-relative" href="<?php echo BASE_URL; ?>read_notification.php?id=<?php echo $notif['id']; ?>">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <div class="mt-1">
                                                            <?php 
                                                            $icon = 'fa-info-circle text-info';
                                                            if (strpos($notif['type'], 'order') !== false) $icon = 'fa-shopping-bag text-warning';
                                                            if (strpos($notif['type'], 'account') !== false) $icon = 'fa-user-check text-success';
                                                            if (strpos($notif['type'], 'product') !== false) $icon = 'fa-box text-primary';
                                                            ?>
                                                            <i class="fas <?php echo $icon; ?>"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="small text-white text-wrap"><?php echo escape($notif['message']); ?></div>
                                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                                <i class="far fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        <li class="p-2 text-center">
                                            <a href="<?php echo BASE_URL; ?>notifications.php" class="text-white small text-decoration-none opacity-75 hover-opacity-100">Voir tout</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <?php if (hasRole('buyer')): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>buyer/cart.php">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item dropdown ms-lg-3">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <?php
                                    $user_profile_pic = '';
                                    $user_initials = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
                                    if (isLoggedIn()) {
                                        try {
                                            $pdo = getDB();
                                            $user_id = $_SESSION['user_id'];
                                            $stmt = $pdo->prepare("SELECT profile_picture, username FROM users WHERE id = ?");
                                            $stmt->execute([$user_id]);
                                            $user_data = $stmt->fetch();
                                            if ($user_data) {
                                                $user_profile_pic = $user_data['profile_picture'] ?? '';
                                                $user_initials = strtoupper(substr($user_data['username'], 0, 1));
                                            }
                                        } catch (Exception $e) {
                                            error_log('Error fetching user for dropdown: ' . $e->getMessage());
                                        }
                                    }
                                    ?>
                                    <?php if ($user_profile_pic && file_exists(__DIR__ . '/../uploads/profiles/' . $user_profile_pic)): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo escape($user_profile_pic); ?>" 
                                             class="profile-picture-nav me-2" 
                                             alt="Profile">
                                    <?php else: ?>
                                        <div class="profile-picture-placeholder me-2" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                            <?php echo $user_initials; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo escape($_SESSION['username']); ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                    <?php if (hasRole('buyer')): ?>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>buyer/orders.php">Mes Commandes</a></li>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>buyer/favorites.php"><i class="fas fa-heart me-2"></i>Mes Favoris</a></li>
                                    <?php elseif (hasRole('seller')): ?>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>seller/dashboard.php">Tableau de bord</a></li>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>seller/products.php">Mes Produits</a></li>
                                    <?php elseif (hasRole('admin')): ?>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Déconnexion</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                            
                            <?php if ($current_page !== 'login.php'): ?>
                                <li class="nav-item ms-lg-3">
                                    <a class="btn btn-outline-light btn-sm px-3 me-2" href="<?php echo BASE_URL; ?>login.php">Connexion</a>
                                </li>
                            <?php endif; ?>

                            <?php if ($current_page !== 'register.php'): ?>
                                <li class="nav-item <?php echo ($current_page === 'login.php') ? 'ms-lg-3' : ''; ?>">
                                    <a class="btn btn-light btn-sm px-3" href="<?php echo BASE_URL; ?>register.php">Inscription</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="<?php echo (isset($show_hero) && $show_hero) ? 'p-0' : 'py-5'; ?>">
