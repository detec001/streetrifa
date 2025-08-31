/**
 * Admin Login Controller
 * Maneja la funcionalidad del formulario de login del panel de administración
 */
class AdminLoginController {
    constructor() {
        // Elementos del DOM
        this.form = document.getElementById('adminLoginForm');
        this.usernameInput = document.getElementById('username');
        this.passwordInput = document.getElementById('password');
        this.togglePasswordBtn = document.getElementById('togglePassword');
        this.rememberMeCheckbox = document.getElementById('rememberMe');
        this.loginBtn = document.getElementById('loginBtn');
        this.errorMessage = document.getElementById('errorMessage');

        // Estados de validación
        this.validation = {
            username: false,
            password: false
        };

        // Configuración
        this.config = {
            minUsernameLength: 3,
            minPasswordLength: 6,
            maxAttempts: 5,
            lockoutDuration: 300000, // 5 minutos
            animationDuration: 300
        };

        // Inicializar
        this.init();
    }

    /**
     * Inicializa el controlador
     */
    init() {
        this.initializeEventListeners();
        this.initializeValidation();
        this.checkLockoutStatus();
        this.loadRememberedCredentials();
    }

    /**
     * Configura todos los event listeners
     */
    initializeEventListeners() {
        // Envío del formulario
        this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Toggle de contraseña
        this.togglePasswordBtn.addEventListener('click', () => this.togglePasswordVisibility());

        // Validación en tiempo real
        this.usernameInput.addEventListener('input', () => {
            this.clearValidationState(this.usernameInput);
            this.validateUsernameRealTime();
        });

        this.passwordInput.addEventListener('input', () => {
            this.clearValidationState(this.passwordInput);
            this.validatePasswordRealTime();
        });

        // Validación al perder foco
        this.usernameInput.addEventListener('blur', () => this.validateUsername());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());

        // Navegación con teclado
        this.form.addEventListener('keydown', (e) => this.handleKeyNavigation(e));

        // Auto-focus al campo de usuario si está vacío
        if (!this.usernameInput.value.trim()) {
            setTimeout(() => this.usernameInput.focus(), 100);
        }
    }

    /**
     * Inicializa el sistema de validación
     */
    initializeValidation() {
        this.validation = {
            username: false,
            password: false
        };
        this.hideError();
    }

    /**
     * Maneja el envío del formulario
     */
    async handleFormSubmit(event) {
        event.preventDefault();
        
        // Verificar bloqueo
        if (this.isLockedOut()) {
            const remaining = this.getLockoutTimeRemaining();
            this.showError(`Demasiados intentos fallidos. Intenta en ${Math.ceil(remaining / 60000)} minutos.`);
            return;
        }

        // Validar formulario
        const isUsernameValid = this.validateUsername();
        const isPasswordValid = this.validatePassword();

        if (!isUsernameValid || !isPasswordValid) {
            this.showError('Por favor, completa todos los campos correctamente.');
            this.shakeForm();
            return;
        }

        // Si el formulario se envía normalmente (sin AJAX), no necesitamos hacer nada más
        // El PHP se encargará del procesamiento
        this.setLoadingState(true);
        
        // Pequeño delay para mostrar la animación de carga
        setTimeout(() => {
            // El formulario se enviará normalmente
        }, 500);
    }

    /**
     * Valida el campo de usuario
     */
    validateUsername() {
        const username = this.usernameInput.value.trim();
        const isValid = this.isValidUsername(username);
        
        this.setFieldValidationState(this.usernameInput, isValid);
        this.validation.username = isValid;
        
        return isValid;
    }

    /**
     * Valida el campo de contraseña
     */
    validatePassword() {
        const password = this.passwordInput.value;
        const isValid = this.isValidPassword(password);
        
        this.setFieldValidationState(this.passwordInput, isValid);
        this.validation.password = isValid;
        
        return isValid;
    }

    /**
     * Validación en tiempo real para usuario
     */
    validateUsernameRealTime() {
        const username = this.usernameInput.value.trim();
        
        if (username.length >= this.config.minUsernameLength) {
            this.setFieldValidationState(this.usernameInput, true);
            this.validation.username = true;
        }
    }

    /**
     * Validación en tiempo real para contraseña
     */
    validatePasswordRealTime() {
        const password = this.passwordInput.value;
        
        if (password.length >= this.config.minPasswordLength) {
            this.setFieldValidationState(this.passwordInput, true);
            this.validation.password = true;
        }
    }

    /**
     * Verifica si un usuario es válido
     */
    isValidUsername(username) {
        if (!username || username.length < this.config.minUsernameLength) {
            return false;
        }
        
        // Permitir letras, números y algunos caracteres especiales
        const usernameRegex = /^[a-zA-Z0-9._-]+$/;
        return usernameRegex.test(username);
    }

    /**
     * Verifica si una contraseña es válida
     */
    isValidPassword(password) {
        return password && password.length >= this.config.minPasswordLength;
    }

    /**
     * Establece el estado de validación de un campo
     */
    setFieldValidationState(input, isValid) {
        const formGroup = input.closest('.form-group');
        
        formGroup.classList.remove('error', 'success');
        
        if (input.value.trim()) {
            if (isValid) {
                formGroup.classList.add('success');
            } else {
                formGroup.classList.add('error');
            }
        }
    }

    /**
     * Limpia el estado de validación de un campo
     */
    clearValidationState(input) {
        const formGroup = input.closest('.form-group');
        formGroup.classList.remove('error', 'success');
    }

    /**
     * Alterna la visibilidad de la contraseña
     */
    togglePasswordVisibility() {
        const isPassword = this.passwordInput.type === 'password';
        const eyeIcon = this.togglePasswordBtn.querySelector('.eye-icon');
        
        if (isPassword) {
            this.passwordInput.type = 'text';
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            `;
        } else {
            this.passwordInput.type = 'password';
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }

        // Mantener el foco en el input
        this.passwordInput.focus();
        
        // Pequeña animación
        this.togglePasswordBtn.style.transform = 'translateY(-50%) scale(0.9)';
        setTimeout(() => {
            this.togglePasswordBtn.style.transform = 'translateY(-50%) scale(1)';
        }, 150);
    }

    /**
     * Maneja la navegación con teclado
     */
    handleKeyNavigation(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            
            if (document.activeElement === this.usernameInput && this.usernameInput.value.trim()) {
                this.passwordInput.focus();
            } else if (document.activeElement === this.passwordInput && this.passwordInput.value) {
                this.loginBtn.click();
            }
        }
        
        if (event.key === 'Tab' && event.shiftKey && document.activeElement === this.usernameInput) {
            // Permitir navegación normal
        }
    }

    /**
     * Carga credenciales recordadas del localStorage
     */
    loadRememberedCredentials() {
        try {
            const remembered = localStorage.getItem('admin_remember_username');
            if (remembered && !this.usernameInput.value) {
                this.usernameInput.value = remembered;
                this.passwordInput.focus();
            }
        } catch (error) {
            console.warn('No se pudieron cargar las credenciales recordadas:', error);
        }
    }

    /**
     * Guarda el usuario si está marcado "recordar"
     */
    saveRememberedCredentials() {
        try {
            if (this.rememberMeCheckbox.checked) {
                localStorage.setItem('admin_remember_username', this.usernameInput.value.trim());
            } else {
                localStorage.removeItem('admin_remember_username');
            }
        } catch (error) {
            console.warn('No se pudieron guardar las credenciales:', error);
        }
    }

    /**
     * Establece el estado de carga del botón
     */
    setLoadingState(isLoading) {
        if (isLoading) {
            this.loginBtn.classList.add('loading');
            this.loginBtn.disabled = true;
        } else {
            this.loginBtn.classList.remove('loading');
            this.loginBtn.disabled = false;
        }
    }

    /**
     * Muestra un mensaje de error
     */
    showError(message) {
        const errorText = this.errorMessage.querySelector('.error-text');
        errorText.textContent = message;
        this.errorMessage.style.display = 'flex';
        
        // Auto-ocultar después de 8 segundos
        setTimeout(() => this.hideError(), 8000);
        
        // Scroll hacia el error si no es visible
        this.errorMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Oculta el mensaje de error
     */
    hideError() {
        this.errorMessage.style.display = 'none';
    }

    /**
     * Anima el formulario con un "shake" en caso de error
     */
    shakeForm() {
        this.form.style.animation = 'none';
        setTimeout(() => {
            this.form.style.animation = 'shake 0.5s ease-in-out';
        }, 10);
        
        setTimeout(() => {
            this.form.style.animation = 'none';
        }, 500);
    }

    /**
     * Verifica el estado de bloqueo por intentos fallidos
     */
    checkLockoutStatus() {
        try {
            const lockoutData = localStorage.getItem('admin_lockout');
            if (lockoutData) {
                const { timestamp, attempts } = JSON.parse(lockoutData);
                const now = Date.now();
                
                if (now - timestamp < this.config.lockoutDuration) {
                    const remaining = Math.ceil((this.config.lockoutDuration - (now - timestamp)) / 60000);
                    this.showError(`Cuenta bloqueada por múltiples intentos fallidos. Intenta en ${remaining} minutos.`);
                    this.setLoadingState(false);
                } else {
                    // Limpiar bloqueo expirado
                    localStorage.removeItem('admin_lockout');
                }
            }
        } catch (error) {
            console.warn('Error al verificar estado de bloqueo:', error);
        }
    }

    /**
     * Verifica si está en estado de bloqueo
     */
    isLockedOut() {
        try {
            const lockoutData = localStorage.getItem('admin_lockout');
            if (!lockoutData) return false;
            
            const { timestamp } = JSON.parse(lockoutData);
            return Date.now() - timestamp < this.config.lockoutDuration;
        } catch (error) {
            return false;
        }
    }

    /**
     * Obtiene el tiempo restante de bloqueo
     */
    getLockoutTimeRemaining() {
        try {
            const lockoutData = localStorage.getItem('admin_lockout');
            if (!lockoutData) return 0;
            
            const { timestamp } = JSON.parse(lockoutData);
            const elapsed = Date.now() - timestamp;
            return Math.max(0, this.config.lockoutDuration - elapsed);
        } catch (error) {
            return 0;
        }
    }

    /**
     * Registra un intento fallido
     */
    recordFailedAttempt() {
        try {
            let attempts = 1;
            const existingData = localStorage.getItem('admin_failed_attempts');
            
            if (existingData) {
                const data = JSON.parse(existingData);
                attempts = data.count + 1;
            }
            
            if (attempts >= this.config.maxAttempts) {
                // Bloquear cuenta
                localStorage.setItem('admin_lockout', JSON.stringify({
                    timestamp: Date.now(),
                    attempts: attempts
                }));
                localStorage.removeItem('admin_failed_attempts');
                
                this.showError(`Demasiados intentos fallidos. Cuenta bloqueada por ${this.config.lockoutDuration / 60000} minutos.`);
            } else {
                localStorage.setItem('admin_failed_attempts', JSON.stringify({
                    count: attempts,
                    timestamp: Date.now()
                }));
                
                const remaining = this.config.maxAttempts - attempts;
                this.showError(`Credenciales incorrectas. Te quedan ${remaining} intentos.`);
            }
        } catch (error) {
            console.warn('Error al registrar intento fallido:', error);
        }
    }

    /**
     * Limpia los intentos fallidos (en caso de login exitoso)
     */
    clearFailedAttempts() {
        try {
            localStorage.removeItem('admin_failed_attempts');
            localStorage.removeItem('admin_lockout');
        } catch (error) {
            console.warn('Error al limpiar intentos fallidos:', error);
        }
    }
}

// Clase para manejar utilidades adicionales
class AdminLoginUtils {
    /**
     * Detecta si es un dispositivo móvil
     */
    static isMobile() {
        return window.innerWidth <= 768;
    }

    /**
     * Sanitiza strings para prevenir XSS
     */
    static sanitizeString(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Genera un hash simple para verificación
     */
    static simpleHash(str) {
        let hash = 0;
        if (str.length === 0) return hash;
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        return Math.abs(hash).toString(36);
    }

    /**
     * Valida formato de email (si se usa email como usuario)
     */
    static isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Detecta el navegador
     */
    static getBrowserInfo() {
        const userAgent = navigator.userAgent;
        
        if (userAgent.indexOf('Chrome') > -1) return 'Chrome';
        if (userAgent.indexOf('Firefox') > -1) return 'Firefox';
        if (userAgent.indexOf('Safari') > -1) return 'Safari';
        if (userAgent.indexOf('Edge') > -1) return 'Edge';
        
        return 'Unknown';
    }
}

// Agregar estilos para la animación shake
const shakeStyles = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
        20%, 40%, 60%, 80% { transform: translateX(10px); }
    }
`;

// Inyectar estilos
if (!document.querySelector('#shake-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'shake-styles';
    styleSheet.textContent = shakeStyles;
    document.head.appendChild(styleSheet);
}

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', () => {
    // Verificar que todos los elementos necesarios existan
    const requiredElements = [
        'adminLoginForm',
        'username',
        'password',
        'togglePassword',
        'loginBtn'
    ];
    
    const missingElements = requiredElements.filter(id => !document.getElementById(id));
    
    if (missingElements.length > 0) {
        console.error('Elementos faltantes:', missingElements);
        return;
    }
    
    // Inicializar el controlador
    window.adminLoginController = new AdminLoginController();
    
    // Hacer utils disponibles globalmente si es necesario
    window.AdminLoginUtils = AdminLoginUtils;
});

// Manejar errores globales relacionados con el login
window.addEventListener('error', (event) => {
    if (event.error && event.error.message.includes('admin')) {
        console.error('Error en admin login:', event.error);
        
        if (window.adminLoginController) {
            window.adminLoginController.setLoadingState(false);
            window.adminLoginController.showError('Ha ocurrido un error técnico. Intenta nuevamente.');
        }
    }
});

// Manejar cuando se pierde la conexión
window.addEventListener('online', () => {
    if (window.adminLoginController) {
        window.adminLoginController.hideError();
    }
});

window.addEventListener('offline', () => {
    if (window.adminLoginController) {
        window.adminLoginController.showError('Sin conexión a internet. Verifica tu conexión.');
    }
});

// Exportar para uso en módulos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AdminLoginController, AdminLoginUtils };
}