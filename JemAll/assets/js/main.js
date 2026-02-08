/**
 * JemAll Marketplace - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Bootstrap Tooltips & Popovers initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // 2. Auto-hide alerts after 5 seconds with Bootstrap fade
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // 3. Image preview for file inputs (Enhanced)
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const files = e.target.files;
            let previewContainer = input.parentElement.querySelector('.image-preview-container');
            
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'image-preview-container d-flex flex-wrap gap-2 mt-3';
                input.parentElement.appendChild(previewContainer);
            }
            
            previewContainer.innerHTML = ''; // Clear previous previews
            
            Array.from(files).forEach(file => {
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'position-relative';
                        wrapper.innerHTML = `
                            <img src="${e.target.result}" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="cursor:pointer; font-size: 0.6rem;">&times;</span>
                        `;
                        previewContainer.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    });
    
    // 4. Quantity input validation (Enhanced)
    const quantityInputs = document.querySelectorAll('input[type="number"][name="quantity"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseInt(this.getAttribute('min')) || 1;
            const max = parseInt(this.getAttribute('max')) || 999;
            let value = parseInt(this.value) || min;
            
            if (value < min) value = min;
            if (value > max) value = max;
            
            this.value = value;
        });
    });
    
    // 5. Dynamic Search Suggestions (Mockup logic)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (this.value.length > 2) {
                // Here you would normally call an API
                console.log('Searching for: ' + this.value);
            }
        });
    }

    // 6. Cart AJAX Simulation (Update totals without full reload if possible)
    // For now, we keep the auto-submit on change for reliability
    const cartQtyInputs = document.querySelectorAll('.cart-update-form input[name="quantity"]');
    cartQtyInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // 7. Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // 8. Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('shadow-sm');
                navbar.style.padding = '0.5rem 0';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.padding = '1rem 0';
            }
        });
    }
    
    console.log('JemAll - Interactions JavaScript initialisées');
});
