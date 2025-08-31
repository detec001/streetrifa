<?php

// Debug temporalmente
error_log("=== DEBUG DATABASE CONNECTION ===");
error_log("MYSQL_HOST: " . ($_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'NOT SET'));
error_log("MYSQL_PORT: " . ($_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? 'NOT SET'));
error_log("MYSQL_DATABASE: " . ($_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'NOT SET'));
error_log("MYSQL_USER: " . ($_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'NOT SET'));
/**
 * Database Configuration
 * Configuración para Railway y desarrollo local
 */

// Configuración para Railway (variables de entorno)
$host = $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? 'localhost';
$port = $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306';
$dbname = $_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? 'raffles';
$username = $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? 'root';

// MAMP usa 'root' como contraseña por defecto
$password = $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? 'root';

// Configuración de DSN
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

// Opciones de PDO para mayor seguridad
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    // Conexión exitosa - mensaje removido para producción
} catch (PDOException $e) {
    // En producción, no mostrar detalles del error
    if ($_ENV['ENVIRONMENT'] === 'production' || $_SERVER['ENVIRONMENT'] === 'production') {
        die('Error de conexión a la base de datos');
    } else {
        die('Error de conexión: ' . $e->getMessage());
    }
}

/**
 * Función para obtener la conexión PDO
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Función para ejecutar consultas preparadas
 */
function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Función para obtener un registro
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Función para obtener múltiples registros
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}
?>
