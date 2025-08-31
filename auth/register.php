<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/partials/navbar.css">
    <link rel="stylesheet" href="../assets/css/auth/auth.css">
    <link rel="stylesheet" href="../assets/css/partials/footer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../partials/navbar.php'; ?>

    <!-- Register Container -->
    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- Left Side - Image/Info -->
            <div class="auth-visual">
                <div class="visual-content">
                    <div class="visual-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6"/>
                            <path d="M23 11h-6"/>
                        </svg>
                    </div>
                    <h2 class="visual-title">¡Únete a Nosotros!</h2>
                    <p class="visual-text">Crea tu cuenta y comienza a participar en nuestros increíbles sorteos. Es rápido, fácil y completamente seguro.</p>
                    <div class="visual-features">
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Registro gratuito</span>
                        </div>
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Acceso inmediato</span>
                        </div>
                        <div class="feature-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Datos protegidos</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="auth-form-container">
                <div class="auth-form-content">
                    <div class="auth-header">
                        <h1 class="auth-title">Crear cuenta</h1>
                        <p class="auth-subtitle">Completa el formulario para crear tu cuenta</p>
                    </div>

                    <form class="auth-form" id="registerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName" class="form-label">Nombre</label>
                                <div class="input-container">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Tu nombre" required>
                                </div>
                                <span class="error-message" id="firstNameError"></span>
                            </div>

                            <div class="form-group">
                                <label for="lastName" class="form-label">Apellido</label>
                                <div class="input-container">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Tu apellido" required>
                                </div>
                                <span class="error-message" id="lastNameError"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-container">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <input type="email" id="email" name="email" class="form-input" placeholder="tu@email.com" required>
                            </div>
                            <span class="error-message" id="emailError"></span>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Teléfono (opcional)</label>
                            <div class="input-container">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder="+52 123 456 7890">
                            </div>
                            <span class="error-message" id="phoneError"></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-container">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <circle cx="12" cy="16" r="1"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                <span class="error-message" id="passwordError"></span>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                                <div class="input-container">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <circle cx="12" cy="16" r="1"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="••••••••" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                <span class="error-message" id="confirmPasswordError"></span>
                            </div>
                        </div>

                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-label">Fuerza de la contraseña:</div>
                            <div class="strength-bars">
                                <div class="strength-bar"></div>
                                <div class="strength-bar"></div>
                                <div class="strength-bar"></div>
                                <div class="strength-bar"></div>
                            </div>
                            <div class="strength-text">Muy débil</div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="acceptTerms" name="acceptTerms" required>
                                <span class="checkmark"></span>
                                Acepto los <a href="#terms" class="auth-link" onclick="showTerms()">Términos y Condiciones</a> y la <a href="#privacy" class="auth-link" onclick="showPrivacy()">Política de Privacidad</a>
                            </label>
                            <span class="error-message" id="termsError"></span>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="newsletter" name="newsletter">
                                <span class="checkmark"></span>
                                Quiero recibir noticias y promociones por email
                            </label>
                        </div>

                        <button type="submit" class="auth-btn primary" id="registerBtn">
                            <span class="btn-text">Crear Cuenta</span>
                            <span class="btn-loading">
                                <svg class="loading-spinner" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                        <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                        <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                                Creando cuenta...
                            </span>
                        </button>

                        <div class="divider">
                            <span>o regístrate con</span>
                        </div>

                        <div class="social-login">
                            <button type="button" class="social-btn google" onclick="registerWithGoogle()">
                                <svg viewBox="0 0 24 24" class="social-icon">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                Google
                            </button>
                            <button type="button" class="social-btn facebook" onclick="registerWithFacebook()">
                                <svg viewBox="0 0 24 24" class="social-icon">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/>
                                </svg>
                                Facebook
                            </button>
                        </div>

                        <div class="auth-footer">
                            <p>¿Ya tienes una cuenta? <a href="login.php" class="auth-link">Inicia sesión aquí</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal-overlay" id="termsModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Términos y Condiciones</h3>
                <button class="modal-close" onclick="closeTerms()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <h4>1. Aceptación de Términos</h4>
                <p>Al utilizar nuestros servicios, usted acepta estos términos y condiciones.</p>
                
                <h4>2. Participación en Rifas</h4>
                <p>La participación en rifas está sujeta a las reglas específicas de cada sorteo.</p>
                
                <h4>3. Pagos y Reembolsos</h4>
                <p>Todos los pagos son finales. Los reembolsos solo se otorgarán en casos específicos.</p>
                
                <h4>4. Responsabilidades del Usuario</h4>
                <p>Los usuarios son responsables de proporcionar información veraz y mantener la seguridad de su cuenta.</p>
                
                <h4>5. Modificaciones</h4>
                <p>Nos reservamos el derecho de modificar estos términos en cualquier momento.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="auth-btn primary" onclick="closeTerms()">Entendido</button>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal-overlay" id="privacyModal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Política de Privacidad</h3>
                <button class="modal-close" onclick="closePrivacy()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <h4>Recopilación de Información</h4>
                <p>Recopilamos información que usted nos proporciona directamente y automáticamente a través de su uso del servicio.</p>
                
                <h4>Uso de la Información</h4>
                <p>Utilizamos su información para proporcionar, mantener y mejorar nuestros servicios.</p>
                
                <h4>Compartir Información</h4>
                <p>No vendemos, comercializamos ni transferimos su información personal a terceros sin su consentimiento.</p>
                
                <h4>Seguridad</h4>
                <p>Implementamos medidas de seguridad apropiadas para proteger su información personal.</p>
                
                <h4>Contacto</h4>
                <p>Si tiene preguntas sobre esta política, puede contactarnos en privacidad@rifas.com</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="auth-btn primary" onclick="closePrivacy()">Entendido</button>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../partials/footer.php'; ?>

    <!-- Load Scripts -->
    <script src="../assets/js/partials/navbar.js"></script>
    <script src="../assets/js/auth/auth.js"></script>
</body>
</html>