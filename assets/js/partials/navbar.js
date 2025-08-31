class NavbarController {
    constructor() {
        this.hamburger = document.getElementById('hamburger');
        this.navLinks = document.getElementById('navLinks');
        this.navbar = document.querySelector('.navbar');
        this.lastScrollY = window.scrollY;
        this.ticking = false;
        
        this.init();
    }

    /**
     * Initialize all navbar functionality
     */
    init() {
        this.setupMobileMenu();
        this.setupSmoothScroll();
        this.setupScrollEffects();
        this.setupLoadAnimations();
        this.setupAuthButtons();
    }

    /**
     * Mobile hamburger menu functionality
     */
    setupMobileMenu() {
        // Toggle mobile menu
        this.hamburger.addEventListener('click', () => {
            this.toggleMobileMenu();
        });

        // Close menu when clicking on navigation links
        this.navLinks.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' && !e.target.classList.contains('btn-login') && !e.target.classList.contains('btn-register')) {
                this.closeMobileMenu();
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.hamburger.contains(e.target) && !this.navLinks.contains(e.target)) {
                this.closeMobileMenu();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.navLinks.classList.contains('active')) {
                this.closeMobileMenu();
            }
        });
    }

    /**
     * Toggle mobile menu with animations
     */
    toggleMobileMenu() {
        this.hamburger.classList.toggle('active');
        this.navLinks.classList.toggle('active');
        
        // Prevent body scroll when menu is open
        if (this.navLinks.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            this.animateMenuItems();
        } else {
            document.body.style.overflow = '';
        }
    }

    /**
     * Close mobile menu
     */
    closeMobileMenu() {
        this.hamburger.classList.remove('active');
        this.navLinks.classList.remove('active');
        document.body.style.overflow = '';
    }

    /**
     * Animate menu items with stagger effect
     */
    animateMenuItems() {
        const links = this.navLinks.querySelectorAll('li a, .auth-buttons a');
        links.forEach((link, index) => {
            link.style.opacity = '0';
            link.style.transform = 'translateX(-30px)';
            
            setTimeout(() => {
                link.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                link.style.opacity = '1';
                link.style.transform = 'translateX(0)';
            }, index * 80 + 200);
        });
    }

    /**
     * Smooth scroll for anchor links
     */
    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const href = anchor.getAttribute('href');
                
                // Skip if it's a hash-only link for auth buttons or other purposes
                if (href === '#login' || href === '#register' || href === '#forgot-password' || href === '#terms' || href === '#privacy') {
                    return; // Let these be handled by their specific handlers
                }

                const target = document.querySelector(href);
                
                if (target) {
                    e.preventDefault();
                    const offsetTop = target.offsetTop - this.navbar.offsetHeight;
                    
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    this.closeMobileMenu();
                }
            });
        });
    }

    /**
     * Handle authentication button clicks - FIXED TO REDIRECT
     */
    setupAuthButtons() {
        const loginBtn = document.querySelector('.btn-login');
        const registerBtn = document.querySelector('.btn-register');

        // Only prevent default and handle manually if the href is a hash
        // Otherwise let the browser handle the navigation naturally
        if (loginBtn) {
            const href = loginBtn.getAttribute('href');
            if (href && href.startsWith('#')) {
                loginBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleLogin();
                });
            } else {
                // If it's a real URL (like ./login.html), let it navigate normally
                // but still close the mobile menu
                loginBtn.addEventListener('click', () => {
                    this.closeMobileMenu();
                });
            }
        }

        if (registerBtn) {
            const href = registerBtn.getAttribute('href');
            if (href && href.startsWith('#')) {
                registerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleRegister();
                });
            } else {
                // If it's a real URL (like ./register.html), let it navigate normally
                // but still close the mobile menu
                registerBtn.addEventListener('click', () => {
                    this.closeMobileMenu();
                });
            }
        }
    }

    /**
     * Handle login button click - FIXED TO REDIRECT PROPERLY
     */
    handleLogin() {
        // Close mobile menu if open
        this.closeMobileMenu();
        
        // Redirect to login page
        window.location.href = './login.html';
    }

    /**
     * Handle register button click - FIXED TO REDIRECT PROPERLY
     */
    handleRegister() {
        // Close mobile menu if open
        this.closeMobileMenu();
        
        // Redirect to register page
        window.location.href = './register.html';
    }

    /**
     * Navbar scroll effects (hide/show, style changes)
     */
    setupScrollEffects() {
        window.addEventListener('scroll', () => {
            this.requestTick();
        });
    }

    /**
     * Throttle scroll events for better performance
     */
    requestTick() {
        if (!this.ticking) {
            requestAnimationFrame(() => this.updateNavbar());
            this.ticking = true;
        }
    }

    /**
     * Update navbar based on scroll position
     */
    updateNavbar() {
        const currentScrollY = window.scrollY;
        
        // Add scrolled class for styling changes
        if (currentScrollY > 50) {
            this.navbar.classList.add('scrolled');
        } else {
            this.navbar.classList.remove('scrolled');
        }
        
        // Hide/show navbar based on scroll direction
        if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
            this.navbar.classList.add('hidden');
        } else {
            this.navbar.classList.remove('hidden');
        }
        
        this.lastScrollY = currentScrollY;
        this.ticking = false;
    }

    /**
     * Load animations for navbar elements
     */
    setupLoadAnimations() {
        // Wait for DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.animateNavLinks();
            });
        } else {
            this.animateNavLinks();
        }
    }

    /**
     * Animate navigation links on page load
     */
    animateNavLinks() {
        const navLinks = document.querySelectorAll('.nav-links li a');
        const authButtons = document.querySelectorAll('.auth-buttons a');
        const allLinks = [...navLinks, ...authButtons];
        
        allLinks.forEach((link, index) => {
            link.style.opacity = '0';
            link.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                link.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                link.style.opacity = '1';
                link.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Animate logo
        const logo = document.querySelector('.logo');
        if (logo) {
            logo.style.opacity = '0';
            logo.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                logo.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                logo.style.opacity = '1';
                logo.style.transform = 'translateX(0)';
            }, 50);
        }
    }
}

/**
 * Utility functions for navbar
 */
const NavbarUtils = {
    /**
     * Check if device is mobile
     */
    isMobile() {
        return window.innerWidth <= 768;
    },

    /**
     * Get scroll position
     */
    getScrollPosition() {
        return window.pageYOffset || document.documentElement.scrollTop;
    },

    /**
     * Check if element is in viewport
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * Debounce function for better performance
     */
    debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }
};

// Initialize navbar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NavbarController();
});

// Handle window resize events
window.addEventListener('resize', NavbarUtils.debounce(() => {
    // Close mobile menu on resize to desktop
    if (!NavbarUtils.isMobile()) {
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('navLinks');
        
        if (hamburger && navLinks) {
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
}, 250));

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { NavbarController, NavbarUtils };
}