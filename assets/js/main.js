document.addEventListener('DOMContentLoaded', () => {
    // 1. Smooth Scrolling for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
                // Close navbar on mobile after click
                const navbarCollapse = document.getElementById('navbarNav');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bootstrapCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bootstrapCollapse) {
                        bootstrapCollapse.hide();
                    } else {
                        // Fallback if instance not found
                        navbarCollapse.classList.remove('show');
                    }
                }
            }
        });
    });

    // 2. Element Reveal Animations (Intersection Observer)
    const revealCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    };

    const revealTargets = document.querySelectorAll('.feature-card, .product-card, section h2, .img-fluid, .col-lg-6');

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(revealCallback, { threshold: 0.1 });
        revealTargets.forEach(el => {
            el.classList.add('reveal-on-scroll');
            revealObserver.observe(el);
        });
    } else {
        revealTargets.forEach(el => el.classList.add('active'));
    }

    // 3. Navbar background change on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('bg-ingoude', 'shadow');
                navbar.classList.remove('bg-transparent');
            } else {
                if (navbar.classList.contains('bg-transparent-alt')) return; // skip if forced
                navbar.classList.add('bg-transparent');
                navbar.classList.remove('bg-ingoude', 'shadow');
            }
        });
    }

    // 4. Professional Toast System
    const showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast-professional ${type} animate-slide-up`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i>
            ${message}
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };

    window.notify = showToast;

    // 5. Intercept Cart/Wishlist for feedback
    document.querySelectorAll('.btn-add-cart, .btn-wishlist').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Check if it's an AJAX call or just feedback before reload
            if (btn.classList.contains('ajax-action')) {
                // Future AJAX logic
            } else {
                showToast("Item added successfully!");
            }
        });
    });

    // 6. Cart Quantity AJAX (Simulated for UX)
    window.updateCartQty = (productId, qty) => {
        // ... (existing logic)
    };

    // 7. Dashboard Interactivity (Sidebar Toggle)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        };

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // 8. Auto-Toast from URL Parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const successType = urlParams.get('success');
        let message = "Action completed successfully!";

        if (successType === 'cart_added') message = "Item added to your shopping cart!";
        if (successType === 'wishlist_added') message = "Item saved to your wishlist!";
        if (successType === 'checkout_complete') message = "Order placed successfully! Thank you.";
        if (successType === 'updated') message = "Record updated successfully.";
        if (successType === 'deleted') message = "Item removed successfully.";

        setTimeout(() => showToast(message, 'success'), 500);
    }

    // 9. Theme Switcher Logic
    window.setTheme = (themeName) => {
        if (themeName === 'green') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.removeItem('selected-theme');
        } else {
            document.documentElement.setAttribute('data-theme', themeName);
            localStorage.setItem('selected-theme', themeName);
        }
        showToast(`Theme switched to ${themeName.charAt(0).toUpperCase() + themeName.slice(1)}!`);
    };

    // Initialize Theme on Load
    const savedTheme = localStorage.getItem('selected-theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
});
