// assets/js/login.js

/**
 * Login Page Manager
 * Handles all login page functionality for both admin and staff
 */
class LoginManager {
    constructor() {
        this.initializePasswordToggles();
        this.initializeFormSubmission();
        this.initializeAlerts();
        this.initializeBackgroundEffects();
        this.initializeFloatingLabels();
        this.initializeRememberMe();
    }

    /**
     * Initialize password toggle buttons
     */
    initializePasswordToggles() {
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const wrapper = button.closest('.password-wrapper');
                if (!wrapper) return;

                const input = wrapper.querySelector('input');
                const icon = button.querySelector('i');

                if (input && icon) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    
                    // Update icon
                    icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
                    
                    // Add visual feedback
                    button.style.transform = 'translateY(-50%) scale(1.2)';
                    setTimeout(() => {
                        button.style.transform = 'translateY(-50%) scale(1)';
                    }, 200);
                    
                    // Focus input after toggle
                    input.focus();
                }
            });
        });
    }

    /**
     * Initialize form submission handling
     */
    initializeFormSubmission() {
        const loginForm = document.getElementById('loginForm') || document.getElementById('staffLoginForm');
        if (!loginForm) return;

        const submitBtn = loginForm.querySelector('button[type="submit"]');
        
        loginForm.addEventListener('submit', (e) => {
            if (!this.validateForm(loginForm)) {
                e.preventDefault();
                return;
            }

            // Add loading state
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Store original content
                const originalHtml = submitBtn.innerHTML;
                submitBtn.dataset.originalHtml = originalHtml;
                
                // Set loading content
                const role = document.body.classList.contains('admin-bg') ? 'admin' : 'staff';
                submitBtn.innerHTML = role === 'admin' 
                    ? '<i class="bi bi-arrow-repeat me-2 spinning"></i> Signing in...'
                    : '<i class="bi bi-arrow-repeat me-2 spinning"></i> Signing in...';
            }

            // Prevent double submission
            if (loginForm.dataset.submitted) {
                e.preventDefault();
                return;
            }
            loginForm.dataset.submitted = 'true';

            // Timeout to reset button if request takes too long
            setTimeout(() => {
                if (submitBtn && submitBtn.disabled) {
                    this.resetButton(submitBtn);
                    loginForm.dataset.submitted = '';
                }
            }, 10000);
        });

        // Add input validation on the fly
        loginForm.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                this.validateInput(input);
            });
            
            input.addEventListener('blur', () => {
                this.validateInput(input);
            });
        });
    }

    /**
     * Validate individual input
     */
    validateInput(input) {
        const value = input.value.trim();
        const type = input.type;
        const errorElement = input.nextElementSibling?.classList.contains('invalid-feedback') 
            ? input.nextElementSibling 
            : input.parentElement.nextElementSibling;

        if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (value && !emailRegex.test(value)) {
                this.showInputError(input, 'Please enter a valid email address');
                return false;
            }
        }

        if (type === 'password' && value && value.length < 6) {
            this.showInputError(input, 'Password must be at least 6 characters');
            return false;
        }

        this.clearInputError(input);
        return true;
    }

    /**
     * Show input error
     */
    showInputError(input, message) {
        input.classList.add('is-invalid');
        
        let errorDiv = input.nextElementSibling;
        if (!errorDiv?.classList.contains('invalid-feedback')) {
            errorDiv = input.parentElement.nextElementSibling;
        }
        
        if (errorDiv?.classList.contains('invalid-feedback')) {
            errorDiv.textContent = message;
        }
    }

    /**
     * Clear input error
     */
    clearInputError(input) {
        input.classList.remove('is-invalid');
        
        let errorDiv = input.nextElementSibling;
        if (!errorDiv?.classList.contains('invalid-feedback')) {
            errorDiv = input.parentElement.nextElementSibling;
        }
        
        if (errorDiv?.classList.contains('invalid-feedback')) {
            errorDiv.textContent = '';
        }
    }

    /**
     * Validate entire form
     */
    validateForm(form) {
        let isValid = true;
        form.querySelectorAll('input[required]').forEach(input => {
            if (!input.value.trim()) {
                this.showInputError(input, 'This field is required');
                isValid = false;
            } else if (!this.validateInput(input)) {
                isValid = false;
            }
        });
        return isValid;
    }

    /**
     * Reset button to original state
     */
    resetButton(button) {
        button.classList.remove('loading');
        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }

    /**
     * Initialize alert auto-dismiss and animations
     */
    initializeAlerts() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(alert => {
            // Add close button functionality
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeAlert(alert);
                });
            }

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                if (alert.parentElement) {
                    this.closeAlert(alert);
                }
            }, 5000);
        });
    }

    /**
     * Close alert with animation
     */
    closeAlert(alert) {
        alert.style.transition = 'all 0.3s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 300);
    }

    /**
     * Initialize floating labels effect
     */
    initializeFloatingLabels() {
        document.querySelectorAll('.form-control').forEach(input => {
            const label = input.previousElementSibling;
            if (!label?.classList.contains('form-label')) return;

            if (input.value) {
                label.style.transform = 'translateY(-5px) scale(0.9)';
                label.style.color = 'var(--primary-blue)';
            }

            input.addEventListener('focus', () => {
                label.style.transform = 'translateY(-5px) scale(0.9)';
                label.style.color = 'var(--primary-blue)';
            });

            input.addEventListener('blur', () => {
                if (!input.value) {
                    label.style.transform = '';
                    label.style.color = '';
                }
            });
        });
    }

    /**
     * Initialize remember me checkbox
     */
    initializeRememberMe() {
        const rememberCheck = document.getElementById('remember');
        if (rememberCheck && localStorage.getItem('rememberEmail')) {
            rememberCheck.checked = true;
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) {
                emailInput.value = localStorage.getItem('rememberEmail');
            }
        }

        rememberCheck?.addEventListener('change', (e) => {
            const emailInput = document.querySelector('input[name="email"]');
            if (e.target.checked && emailInput?.value) {
                localStorage.setItem('rememberEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberEmail');
            }
        });
    }

    /**
     * Initialize background effects for admin
     */
    initializeBackgroundEffects() {
        if (!document.querySelector('.bubble-container')) return;

        this.createBubbles();
        this.createParticles();
        
        // Resize handler to adjust particles on window resize
        window.addEventListener('resize', () => {
            this.recreateBackgroundEffects();
        });
    }

    /**
     * Create floating bubbles
     */
    createBubbles() {
        const container = document.querySelector('.bubble-container');
        if (!container) return;

        // Clear existing bubbles
        container.innerHTML = '';
        
        const bubbleCount = 25;
        const fragment = document.createDocumentFragment();

        for (let i = 0; i < bubbleCount; i++) {
            const bubble = document.createElement('div');
            bubble.className = 'bubble';

            const size = Math.random() * 70 + 20;
            const left = Math.random() * 100;
            const duration = Math.random() * 12 + 8;
            const delay = Math.random() * 3;

            bubble.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${left}%;
                animation-duration: ${duration}s;
                animation-delay: ${delay}s;
                opacity: ${Math.random() * 0.4 + 0.2};
            `;

            fragment.appendChild(bubble);
        }

        container.appendChild(fragment);
    }

    /**
     * Create floating particles
     */
    createParticles() {
        const container = document.querySelector('.floating-particles');
        if (!container) return;

        // Clear existing particles
        container.innerHTML = '';
        
        const particleCount = 50;
        const types = ['particle-drop', 'particle-shimmer', 'particle-soap'];
        const fragment = document.createDocumentFragment();

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            const type = types[Math.floor(Math.random() * types.length)];
            particle.className = `particle ${type}`;

            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            const duration = Math.random() * 25 + 15;
            const delay = Math.random() * 5;

            particle.style.cssText = `
                left: ${x}px;
                top: ${y}px;
                animation: ${type === 'particle-shimmer' ? 'shimmer' : 'float'} ${duration}s linear ${delay}s infinite;
            `;

            fragment.appendChild(particle);
        }

        container.appendChild(fragment);
    }

    /**
     * Recreate background effects
     */
    recreateBackgroundEffects() {
        if (this._debounceTimer) clearTimeout(this._debounceTimer);
        
        this._debounceTimer = setTimeout(() => {
            if (document.querySelector('.bubble-container')) {
                this.createBubbles();
                this.createParticles();
            }
        }, 250);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.loginManager = new LoginManager();
});

// Export for module use if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoginManager;
}