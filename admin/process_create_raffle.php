<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Solo admins pueden crear rifas
if (!isAdmin()) {
    $_SESSION['error_message'] = 'No tienes permisos para crear rifas.';
    header('Location: panel.php');
    exit();
}

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_create_raffle.php');
    exit();
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Token de seguridad inválido.';
    header('Location: admin_create_raffle.php');
    exit();
}

// Función para limpiar y validar datos
function validateAndClean($data, $maxLength = null) {
    $cleaned = cleanInput($data);
    if ($maxLength && strlen($cleaned) > $maxLength) {
        return false;
    }
    return $cleaned;
}

// Función para subir imágenes
function uploadImages($files) {
    $uploadDir = '../uploads/raffles/';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadedFiles = [];
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            // Validar tipo de archivo
            $fileType = $files['type'][$key];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido: ' . $files['name'][$key]);
            }
            
            // Validar tamaño
            if ($files['size'][$key] > $maxFileSize) {
                throw new Exception('Archivo muy grande: ' . $files['name'][$key]);
            }
            
            // Generar nombre único
            $extension = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . strtolower($extension);
            $filePath = $uploadDir . $fileName;
            
            // Mover archivo
            if (move_uploaded_file($tmpName, $filePath)) {
                $uploadedFiles[] = $fileName;
            } else {
                throw new Exception('Error al subir archivo: ' . $files['name'][$key]);
            }
        }
    }
    
    return $uploadedFiles;
}

try {
    // Validar y limpiar datos del formulario
    $raffleName = validateAndClean($_POST['raffle_name'], 100);
    $drawDate = $_POST['draw_date'];
    $ticketPrice = floatval($_POST['ticket_price']);
    $totalTickets = intval($_POST['total_tickets']);
    $description = validateAndClean($_POST['description'] ?? '', 500);
    $commissionRate = floatval($_POST['commission_rate'] ?? 10);
    $status = in_array($_POST['status'], ['active', 'paused']) ? $_POST['status'] : 'active';
    
    // Validaciones
    $errors = [];
    
    if (!$raffleName || strlen($raffleName) < 3) {
        $errors[] = 'El nombre de la rifa debe tener al menos 3 caracteres.';
    }
    
    if (!$drawDate) {
        $errors[] = 'La fecha del sorteo es obligatoria.';
    } else {
        $drawDateTime = new DateTime($drawDate);
        $now = new DateTime();
        if ($drawDateTime <= $now) {
            $errors[] = 'La fecha del sorteo debe ser futura.';
        }
    }
    
    if ($ticketPrice <= 0 || $ticketPrice > 10000) {
        $errors[] = 'El precio del boleto debe estar entre $0.01 y $10,000.00';
    }
    
    if ($totalTickets < 10 || $totalTickets > 1000000) {
        $errors[] = 'El número de boletos debe estar entre 10 y 1,000,000.';
    }
    
    if ($commissionRate < 0 || $commissionRate > 50) {
        $errors[] = 'La tasa de comisión debe estar entre 0% y 50%.';
    }
    
    if (!isset($_FILES['raffle_images']) || empty($_FILES['raffle_images']['name'][0])) {
        $errors[] = 'Debe subir al menos una imagen.';
    }
    
    // Si hay errores, regresar
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: admin_create_raffle.php');
        exit();
    }
    
    // Subir imágenes
    $uploadedImages = uploadImages($_FILES['raffle_images']);
    
    if (empty($uploadedImages)) {
        $_SESSION['error_message'] = 'Error al subir las imágenes.';
        header('Location: admin_create_raffle.php');
        exit();
    }
    
    // Obtener ID del admin actual
    $currentAdmin = getCurrentAdmin();
    $createdBy = $currentAdmin['id'];
    
    // Generar código único para la rifa
    $raffleCode = 'RFA-' . strtoupper(uniqid());
    
    // Insertar en la base de datos
    $sql = "INSERT INTO raffles (
        raffle_code,
        name, 
        description, 
        draw_date, 
        ticket_price, 
        total_tickets, 
        sold_tickets, 
        commission_rate, 
        status, 
        images, 
        created_by, 
        created_at, 
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, NOW(), NOW())";
    
    $params = [
        $raffleCode,
        $raffleName,
        $description,
        $drawDate,
        $ticketPrice,
        $totalTickets,
        $commissionRate,
        $status,
        json_encode($uploadedImages),
        $createdBy
    ];
    
    $raffleId = executeQuery($sql, $params, true); // true para obtener el ID insertado
    
    if ($raffleId) {
        // Registrar actividad
        logAdminActivity('create_raffle', "Creó la rifa: {$raffleName} (ID: {$raffleId})");
        
        $_SESSION['success_message'] = "¡Rifa '{$raffleName}' creada exitosamente!";
        header('Location: panel.php');
        exit();
    } else {
        throw new Exception('Error al guardar la rifa en la base de datos.');
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error al crear rifa: " . $e->getMessage());
    
    // Si se subieron imágenes, eliminarlas
    if (isset($uploadedImages)) {
        foreach ($uploadedImages as $image) {
            $imagePath = '../uploads/raffles/' . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    $_SESSION['error_message'] = 'Error al crear la rifa: ' . $e->getMessage();
    header('Location: admin_create_raffle.php');
    exit();
}
?>