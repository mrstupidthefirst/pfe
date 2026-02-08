<?php
/**
 * User Registration Page
 */
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin')) redirect('admin/dashboard.php');
    elseif (hasRole('seller')) redirect('seller/dashboard.php');
    else redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $role = $_POST['role'] ?? 'buyer';
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($phone)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (!in_array($role, ['buyer', 'seller'])) {
        $error = 'Rôle invalide.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Le nom d\'utilisateur ou l\'email existe déjà.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = ($role === 'seller') ? 'pending' : 'active';
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name, phone, address, national_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $role, $full_name, $phone, $address, $national_id, $status])) {
                $success = 'Inscription réussie ! ' . ($role === 'seller' ? 'Votre compte est en attente d\'approbation par l\'administrateur.' : 'Vous pouvez maintenant vous connecter.');
            } else {
                $error = 'Échec de l\'inscription. Veuillez réessayer.';
            }
        }
    }
}

$page_title = 'Inscription';
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card auth-card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Créer un compte</h2>
                        <p class="text-muted">Rejoignez la communauté JemAll</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo escape($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo escape($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-black w-100">Aller à la connexion</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="registerForm" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Nom complet *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo escape($_POST['full_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                                    <input type="text" class="form-control" id="username" name="username" required value="<?php echo escape($_POST['username'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo escape($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="phone" class="form-label">Numéro de téléphone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-12 d-none" id="seller-id-group">
                                    <label for="national_id" class="form-label">Numéro CIN ou Passeport *</label>
                                    <input type="text" class="form-control" id="national_id" name="national_id" value="<?php echo escape($_POST['national_id'] ?? ''); ?>">
                                    <small class="text-muted">Requis pour la vérification du compte vendeur.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirmation *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                <div class="col-12">
                                    <label for="role" class="form-label">Type de compte *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="buyer" <?php echo (($_POST['role'] ?? 'buyer') === 'buyer') ? 'selected' : ''; ?>>Acheteur</option>
                                        <option value="seller" <?php echo (($_POST['role'] ?? '') === 'seller') ? 'selected' : ''; ?>>Vendeur</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo escape($_POST['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-black w-100 py-2">S'inscrire</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">Vous avez déjà un compte ? <a href="login.php" class="text-black fw-bold text-decoration-none">Se connecter</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/Hide ID field based on role
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const idGroup = document.getElementById('seller-id-group');
    const idInput = document.getElementById('national_id');
    
    function toggleIdField() {
        if (roleSelect.value === 'seller') {
            idGroup.classList.remove('d-none');
            idInput.setAttribute('required', 'required');
        } else {
            idGroup.classList.add('d-none');
            idInput.removeAttribute('required');
            idInput.value = '';
        }
    }
    
    roleSelect.addEventListener('change', toggleIdField);
    toggleIdField(); // Run on load
});
</script>

<script>
// Client-side validation
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        
        // Custom password match validation
        var password = document.getElementById('password');
        var confirm = document.getElementById('confirm_password');
        if (password.value !== confirm.value) {
            confirm.setCustomValidity('Les mots de passe ne correspondent pas');
            event.preventDefault();
            event.stopPropagation();
        } else {
            confirm.setCustomValidity('');
        }

        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php include 'includes/footer.php'; ?>
