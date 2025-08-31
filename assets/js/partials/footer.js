/**
 * Footer JavaScript
 * 
 * Handles footer functionality including back to top button
 */

class FooterController {
    constructor() {
        this.backToTopBtn = null;
        this.init();
    }

    /**
     * Initialize footer functionality
     */
    init() {
        this.setupBackToTopButton();
        this.setupScrollHandler();
    }

    /**
     * Setup back to top button
     */
    setupBackToTopButton() {
        this.backToTopBtn = document.getElementById('backToTopBtn');
        
        if (!this.backToTopBtn) {
            console.warn('Back to top button not found');
            return;
        }

        // Add click event listener
        this.backToTopBtn.addEventListener('click', () => {
            this.scrollToTop();
        });
    }

    /**
     * Setup scroll handler to show/hide back to top button
     */
    setupScrollHandler() {
        let ticking = false;

        const updateBackToTopVisibility = () => {
            if (!this.backToTopBtn) return;

            const scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
            
            if (scrollTop > 300) {
                this.backToTopBtn.style.display = "flex";
                this.backToTopBtn.style.opacity = "1";
            } else {
                this.backToTopBtn.style.opacity = "0";
                setTimeout(() => {
                    if (scrollTop <= 300) {
                        this.backToTopBtn.style.display = "none";
                    }
                }, 300);
            }

            ticking = false;
        };

        const requestTick = () => {
            if (!ticking) {
                requestAnimationFrame(updateBackToTopVisibility);
                ticking = true;
            }
        };

        // Use scroll event with requestAnimationFrame for better performance
        window.addEventListener('scroll', requestTick);
    }

    /**
     * Scroll to top of page with smooth animation
     */
    scrollToTop() {
        const scrollToTopSmooth = () => {
            const currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
            
            if (currentScroll > 0) {
                window.requestAnimationFrame(scrollToTopSmooth);
                window.scrollTo(0, currentScroll - (currentScroll / 8));
            }
        };

        // Check if smooth scrolling is supported
        if ('scrollBehavior' in document.documentElement.style) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            // Fallback for browsers that don't support smooth scrolling
            scrollToTopSmooth();
        }
    }

    /**
     * Get current scroll position
     */
    getScrollPosition() {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    /**
     * Check if user has scrolled past threshold
     */
    hasScrolledPastThreshold(threshold = 300) {
        return this.getScrollPosition() > threshold;
    }
}

/**
 * Footer utility functions
 */
const FooterUtils = {
    /**
     * Animate element with fade in effect
     */
    fadeIn(element, duration = 300) {
        element.style.opacity = 0;
        element.style.display = 'flex';
        
        let start = null;
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            element.style.opacity = Math.min(progress / duration, 1);
            
            if (progress < duration) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    },

    /**
     * Animate element with fade out effect
     */
    fadeOut(element, duration = 300) {
        let start = null;
        const initialOpacity = parseFloat(getComputedStyle(element).opacity);
        
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            
            element.style.opacity = Math.max(initialOpacity - (progress / duration), 0);
            
            if (progress < duration) {
                requestAnimationFrame(animate);
            } else {
                element.style.display = 'none';
            }
        };
        
        requestAnimationFrame(animate);
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
    },

    /**
     * Throttle function for scroll events
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Initialize footer when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.footerController = new FooterController();
});

// Global function for backward compatibility
function scrollToTop() {
    if (window.footerController) {
        window.footerController.scrollToTop();
    } else {
        // Fallback if controller is not available
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// Export for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FooterController, FooterUtils };
}