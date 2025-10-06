/**
 * Property Rental System - Main JavaScript File
 * Modern, responsive homepage functionality
 */

class PropertyRentalApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializePlugins();
        this.setupPreloader();
        this.setupNavigation();
        this.setupAnimations();
        this.setupInteractions();
    }

    setupEventListeners() {
        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Property Rental System loaded successfully');
        });

        // Window Load
        window.addEventListener('load', () => {
            this.handleWindowLoad();
        });

        // Window Scroll
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });

        // Window Resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    initializePlugins() {
        // Initialize AOS (Animate On Scroll)
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100,
                disable: 'mobile'
            });
        }
    }

    setupPreloader() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            setTimeout(() => {
                preloader.classList.add('hidden');
                // Remove from DOM after animation
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }, 1000);
        }
    }

    setupNavigation() {
        const navbar = document.getElementById('navbar');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileNav = document.getElementById('mobileNav');

        // Mobile menu toggle
        if (mobileMenu && mobileNav) {
            mobileMenu.addEventListener('click', (e) => {
                e.preventDefault();
                mobileMenu.classList.toggle('active');
                mobileNav.classList.toggle('active');
                document.body.classList.toggle('nav-open');
            });

            // Close mobile nav when clicking links
            document.querySelectorAll('.mobile-nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.classList.remove('nav-open');
                });
            });

            // Close mobile nav when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileNav.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.classList.remove('nav-open');
                }
            });
        }

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Active navigation link highlighting
        this.setupActiveNavigation();
    }

    setupActiveNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

        if (sections.length && navLinks.length) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const currentId = entry.target.getAttribute('id');
                        
                        navLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === `#${currentId}`) {
                                link.classList.add('active');
                            }
                        });
                    }
                });
            }, {
                threshold: 0.3,
                rootMargin: '-100px 0px -100px 0px'
            });

            sections.forEach(section => {
                observer.observe(section);
            });
        }
    }

    setupAnimations() {
        // Stats counter animation
        this.setupStatsCounter();
        
        // Parallax effects
        this.setupParallax();
        
        // Hover animations
        this.setupHoverAnimations();
    }

    setupStatsCounter() {
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.getAttribute('data-target'));
                    this.animateCounter(entry.target, target);
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-number[data-target]').forEach(stat => {
            statsObserver.observe(stat);
        });
    }

    animateCounter(element, target) {
        let current = 0;
        const increment = target / 100;
        const duration = 2000; // 2 seconds
        const stepTime = duration / 100;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, stepTime);
    }

    setupParallax() {
        const parallaxElements = document.querySelectorAll('.hero-background, .cta-background');
        
        if (parallaxElements.length) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                
                parallaxElements.forEach(element => {
                    const rate = scrolled * -0.3;
                    element.style.transform = `translateY(${rate}px)`;
                });
            });
        }
    }

    setupHoverAnimations() {
        // Enhanced card hover effects
        const cards = document.querySelectorAll(
            '.property-card, .feature-card, .testimonial-card, .step-card, .stat-card'
        );

        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
                card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-2px)';
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0)';
            });
        });
    }

    setupInteractions() {
        // Property card interactions
        this.setupPropertyCards();
        
        // Search form enhancements
        this.setupSearchForm();
        
        // Back to top button
        this.setupBackToTop();
        
        // Loading states
        this.setupLoadingStates();
    }

    setupPropertyCards() {
        // Favorite button functionality
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const icon = btn.querySelector('i');
                const isFavorite = icon.classList.contains('fas');
                
                if (isFavorite) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    btn.style.color = '';
                    btn.title = 'Add to favorites';
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    btn.style.color = '#ef4444';
                    btn.title = 'Remove from favorites';
                }

                // Add visual feedback
                btn.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    btn.style.transform = 'scale(1)';
                }, 150);
            });
        });

        // Share button functionality
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Check out this property',
                        url: window.location.href
                    });
                } else {
                    // Fallback: Copy to clipboard
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        this.showToast('Link copied to clipboard!');
                    });
                }
            });
        });
    }

    setupSearchForm() {
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            // Form validation and enhancement
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const formData = new FormData(searchForm);
                const params = new URLSearchParams();
                
                for (let [key, value] of formData) {
                    if (value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                
                // Show loading state
                const submitBtn = searchForm.querySelector('.search-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Searching...</span>';
                submitBtn.disabled = true;
                
                // Simulate API call delay
                setTimeout(() => {
                    window.location.href = 'properties.php?' + params.toString();
                }, 800);
            });

            // Input enhancements
            const inputs = searchForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    input.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', () => {
                    input.parentElement.classList.remove('focused');
                });
            });
        }
    }

    setupBackToTop() {
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }

    setupLoadingStates() {
        // Add loading states to all buttons with href
        document.querySelectorAll('a.btn[href]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Only add loading state for internal links
                const href = btn.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('http')) {
                    const icon = btn.querySelector('i');
                    const text = btn.querySelector('span');
                    
                    if (icon && text) {
                        icon.className = 'fas fa-spinner fa-spin';
                        text.textContent = 'Loading...';
                    }
                }
            });
        });
    }

    handleWindowLoad() {
        // Optimize images loading
        const images = document.querySelectorAll('img[loading="lazy"]');
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.src; // Trigger loading
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    }

    handleScroll() {
        const navbar = document.getElementById('navbar');
        const backToTop = document.getElementById('backToTop');
        
        // Navbar scroll effect
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        // Back to top visibility
        if (backToTop) {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }
    }

    handleResize() {
        // Handle window resize events
        if (window.innerWidth > 768) {
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileNav = document.getElementById('mobileNav');
            
            if (mobileMenu && mobileNav) {
                mobileMenu.classList.remove('active');
                mobileNav.classList.remove('active');
                document.body.classList.remove('nav-open');
            }
        }
    }

    // Utility methods
    showToast(message, type = 'success') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Hide toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }
}

// Initialize the application
const app = new PropertyRentalApp();

// Additional utility functions
const Utils = {
    // Format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-LK', {
            style: 'currency',
            currency: 'LKR',
            minimumFractionDigits: 0
        }).format(amount);
    },

    // Format date
    formatDate(date) {
        return new Intl.DateTimeFormat('en-LK', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
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

// Make Utils available globally
window.PropertyRentalUtils = Utils;