    </main>
    <footer class="bg-black text-white py-5 mt-auto border-top border-dark">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="JemAll" height="30" class="me-2 filter-white">
                        <h4 class="fw-bold mb-0 tracking-tight">JemAll</h4>
                    </div>
                    <p class="text-muted small mb-4 line-height-lg"><?php echo __('footer_description'); ?></p>
                    <div class="d-flex gap-3 social-links">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small letter-spacing-1"><?php echo __('quick_links'); ?></h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>index.php" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('home'); ?></a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('about_us'); ?></a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('contact'); ?></a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-text-white transition-all">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small letter-spacing-1"><?php echo __('account'); ?></h6>
                    <ul class="list-unstyled">
                        <?php if (!isLoggedIn()): ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>login.php" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('login'); ?></a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>register.php" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('register'); ?></a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>logout.php" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('logout'); ?></a></li>
                        <?php endif; ?>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('privacy_policy'); ?></a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none hover-text-white transition-all"><?php echo __('terms_of_service'); ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h6 class="fw-bold text-white mb-3 text-uppercase small letter-spacing-1"><?php echo __('newsletter'); ?></h6>
                    <p class="text-muted small mb-3"><?php echo __('newsletter_desc'); ?></p>
                    <form class="input-group">
                        <input type="email" class="form-control bg-dark border-secondary text-white placeholder-muted focus-none" placeholder="Email...">
                        <button class="btn btn-light fw-bold px-4" type="submit"><?php echo __('subscribe'); ?></button>
                    </form>
                </div>
            </div>
            <hr class="my-4 border-secondary opacity-25">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted small">&copy; <?php echo date('Y'); ?> JemAll. <?php echo __('all_rights_reserved'); ?></p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <i class="fab fa-cc-visa text-muted fs-4 me-2"></i>
                    <i class="fab fa-cc-mastercard text-muted fs-4 me-2"></i>
                    <i class="fab fa-cc-paypal text-muted fs-4"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
    // Visual Search UX Flow
    document.addEventListener('DOMContentLoaded', function() {
        const visualBtn = document.getElementById('visualSearchBtn');
        const visualModal = new bootstrap.Modal(document.getElementById('visualSearchModal'));
        const fileInput = document.getElementById('visualSearchInput');
        const uploadArea = document.getElementById('camera-upload-area');
        const loadingState = document.getElementById('visual-search-loading');
        
        if (visualBtn) {
            visualBtn.addEventListener('click', function() {
                visualModal.show();
            });
        }
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Start simulation
                    uploadArea.classList.add('d-none');
                    loadingState.classList.remove('d-none');
                    
                    // Simulate AI Analysis (1.5s delay)
                    setTimeout(function() {
                        // In a real app, we would upload via AJAX here.
                        // For demo, redirect to search results simulating a match.
                        // We'll search for 'iphone' or generic term to show results.
                        window.location.href = '<?php echo BASE_URL; ?>index.php?search=iphone&from_camera=1';
                    }, 1500);
                }
            });
        }
    });
    </script>
</body>
</html>
