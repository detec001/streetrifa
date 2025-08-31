<?php
/**
 * Navbar con sistema de rutas dinámico
 * Detecta automáticamente desde qué directorio se incluye
 */

// Detectar el nivel de directorio actual
$currentPath = $_SERVER['REQUEST_URI'];
$currentDir = dirname($_SERVER['SCRIPT_NAME']);

// Determinar el prefijo de ruta basado en la ubicación actual
$baseUrl = '';

// Si estamos en un subdirectorio, necesitamos volver a la raíz
if (strpos($currentDir, '/auth/') !== false || 
    strpos($currentDir, '/raffles/') !== false || 
    strpos($currentDir, '/admin/') !== false) {
    $baseUrl = '../';
} else {
    // Estamos en la raíz o en un subdirectorio de primer nivel
    $baseUrl = './';
}

// Alternativa más robusta: usar rutas absolutas desde la raíz del sitio
$siteRoot = '/';
if (strpos($_SERVER['REQUEST_URI'], '/raffles/') !== false) {
    $siteRoot = '../';
} elseif (strpos($_SERVER['REQUEST_URI'], '/auth/') !== false) {
    $siteRoot = '../';
} elseif (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $siteRoot = '../';
}
?>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-content">
        <a href="<?php echo $baseUrl; ?>index.php" class="logo">RIFAS</a>
        <div class="nav-container">
            <ul class="nav-links" id="navLinks">
                <li><a href="<?php echo $baseUrl; ?>index.php">Inicio</a></li>
                <li><a href="<?php echo $baseUrl; ?>raffles/rewards.php">Premios</a></li>
                <li><a href="<?php echo $baseUrl; ?>raffles/results.php">Resultados</a></li>
                <li><a href="<?php echo $baseUrl; ?>index.php#contacto">Contacto</a></li>
            </ul>
            <div class="auth-buttons" id="authButtons">
                <a href="<?php echo $baseUrl; ?>auth/login.php" class="btn-login">Iniciar Sesión</a>
                <a href="<?php echo $baseUrl; ?>auth/register.php" class="btn-register">Registrarse</a>
            </div>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</nav>

<script>
// Configurar la ruta base para JavaScript
window.BASE_URL = '<?php echo $baseUrl; ?>';
</script>