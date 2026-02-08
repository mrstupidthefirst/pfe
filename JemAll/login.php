<?php
/**
 * User Login Page
 */
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin')) redirect('admin/dashboard.php');
    elseif (hasRole('seller')) redirect('seller/dashboard.php');
    else redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // Email or Phone or Username
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $pdo = getDB();
        // Check by email, phone or username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? OR username = ?");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Votre compte est désactivé.';
            } elseif ($user['status'] === 'pending') {
                $error = 'Votre compte est en attente d\'approbation.';
            } else {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } elseif ($user['role'] === 'seller') {
                    redirect('seller/dashboard.php');
                } else {
                    redirect('index.php');
                }
            }
        } else {
            $error = 'Identifiants invalides ou compte non actif.';
        }
    }
}

$page_title = 'Connexion';
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card auth-card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Connexion</h2>
                        <p class="text-muted">Bon retour parmi nous !</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo escape($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="identifier" class="form-label">Email, Téléphone ou Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="identifier" name="identifier" required value="<?php echo escape($_POST['identifier'] ?? ''); ?>" placeholder="Identifiant">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <label for="password" class="form-label">Mot de passe</label>
                                <a href="#" class="text-muted small text-decoration-none">Oublié ?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="••••••••">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-black w-100 py-2 mb-3">Se connecter</button>
                        
                        <div class="text-center">
                            <p class="text-muted small">Pas encore de compte ? <a href="register.php" class="text-black fw-bold text-decoration-none">S'inscrire</a></p>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-white rounded shadow-sm">
                <h6 class="fw-bold border-bottom pb-2 mb-2">Comptes de démo :</h6>
                <p class="small mb-1"><strong>Admin:</strong> admin / admin123</p>
                <p class="small mb-1"><strong>Vendeur:</strong> seller1 / seller123</p>
                <p class="small mb-0"><strong>Acheteur:</strong> buyer1 / buyer123</p>
            </div>
        </div>
    </div>
</div>

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
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php include 'includes/footer.php'; ?>
