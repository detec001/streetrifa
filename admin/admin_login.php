<?php
require_once '../admin/process_admin_login.php';

// Si ya está logueado, redirigir al panel
if (isAdminLoggedIn()) {
    header('Location: panel.php');
    exit();
}

// Variables para mensajes
$error_message = '';
$success_message = '';
$remembered_username = '';

// Verificar si hay usuario recordado en cookies
if (isset($_COOKIE['admin_remember']) && !empty($_COOKIE['admin_remember'])) {
    $remembered_username = htmlspecialchars($_COOKIE['admin_remember']);
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validaciones básicas
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, completa todos los campos';
    } else {
        // Verificar credenciales
        $admin = verifyAdminCredentials($username, $password);
        
        if ($admin) {
            // Login exitoso
            loginAdmin($admin);
            
            // Manejar "recordar usuario"
            if ($remember) {
                setcookie('admin_remember', $username, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 días
            } else {
                setcookie('admin_remember', '', time() - 3600, '/', '', true, true); // Eliminar cookie
            }
            
            // Log de actividad
            logAdminActivity('login', 'Inicio de sesión exitoso');
            
            // Redirigir al panel
            header('Location: panel.php');
            exit();
        } else {
            $error_message = 'Usuario o contraseña incorrectos';
            
            // Log de intento fallido
            error_log("Intento de login fallido - Usuario: $username - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Login - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/admin/admin_login.css">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Panel de administración - Rifas Online">
</head>
<body>
    <div class="floating-shapes">
        <div class="shape circle"></div>
        <div class="shape square"></div>
        <div class="shape triangle"></div>
        <div class="shape circle"></div>
    </div>

    <div class="admin-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <h1>RIFAS</h1>
                    <span class="admin-badge">ADMIN</span>
                </div>
                <h2>Panel de Administración</h2>
                <p>Ingresa tus credenciales para acceder al sistema</p>
            </div>
            
            <form class="login-form" id="adminLoginForm" method="POST">
                <?php if ($error_message): ?>
                <div class="error-message">
                    <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span class="error-text"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="success-message">
                    <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                    <span class="success-text"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="Ingresa tu usuario" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? $remembered_username ?? ''); ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <circle cx="12" cy="16" r="1"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" 
                               id="rememberMe" 
                               name="remember"
                               <?php echo (isset($_POST['remember']) || isset($_COOKIE['admin_remember'])) ? 'checked' : ''; ?>>
                        <span class="checkmark">
                            <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                        </span>
                        Recordar mi sesión
                    </label>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <div class="btn-loader">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" 
                                    stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                <animate attributeName="stroke-dasharray" dur="2s" 
                                        values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                <animate attributeName="stroke-dashoffset" dur="2s" 
                                        values="0;-15.708;-31.416" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                    </div>
                </button>
                
                <div class="error-message" id="errorMessage" style="display: none;">
                    <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <span class="error-text"></span>
                </div>
            </form>
            
            <div class="login-footer">
                <a href="../index.php" class="back-link">
                    <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5"/>
                        <path d="M12 19l-7-7 7-7"/>
                    </svg>
                    Volver al sitio principal
                </a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin/admin-login.js"></script>
</body>
</html>