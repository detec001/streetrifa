<?php
/**
 * Funciones de autenticación para administradores
 */

require_once __DIR__ . '/../config/database.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar credenciales de administrador
 */
function verifyAdminCredentials($username, $password) {
    try {
        $sql = "SELECT id, username, user_type, email, password FROM admins WHERE username = ? OR email = ?";
        $admin = fetchOne($sql, [$username, $username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error en verificación de credenciales: " . $e->getMessage());
        return false;
    }
}

/**
 * Iniciar sesión de administrador
 */
function loginAdmin($admin) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_type'] = $admin['user_type'];
    $_SESSION['login_time'] = time();
    
    // Actualizar último login
    try {
        $sql = "UPDATE admins SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        executeQuery($sql, [$admin['id']]);
    } catch (Exception $e) {
        error_log("Error al actualizar último login: " . $e->getMessage());
    }
    
    return true;
}

/**
 * Cerrar sesión de administrador
 */
function logoutAdmin() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    return true;
}

/**
 * Verificar si el administrador está logueado
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Obtener información del administrador actual
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'email' => $_SESSION['admin_email'],
        'user_type' => $_SESSION['admin_type'],
        'login_time' => $_SESSION['login_time']
    ];
}

/**
 * Requerir autenticación (redirigir si no está logueado)
 */
function requireAuth($redirect_url = '/admin/admin_login.php') {
    if (!isAdminLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Verificar si el usuario tiene un tipo específico
 */
function hasUserType($required_type) {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    
    return $admin['user_type'] === $required_type;
}

/**
 * Verificar si el usuario es admin
 */
function isAdmin() {
    return hasUserType('admin');
}

/**
 * Verificar si el usuario es committee
 */
function isCommittee() {
    return hasUserType('committee');
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Limpiar y validar input
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validar formato de email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Log de actividad de administrador
 */
function logAdminActivity($action, $details = '') {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    try {
        $admin = getCurrentAdmin();
        $log_data = [
            'admin_id' => $admin['id'],
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Aquí podrías insertar en una tabla de logs si la tienes
        error_log("Admin Activity - ID: {$log_data['admin_id']}, Action: {$action}, Details: {$details}");
        
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}
?>