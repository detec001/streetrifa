<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Solo vendedores pueden acceder
if ($current_admin['user_type'] !== 'seller') {
    header('Location: panel.php');
    exit();
}

// Procesar logout si se solicita
if (isset($_GET['logout'])) {
    logAdminActivity('logout', 'Cierre de sesión');
    logoutAdmin();
    header('Location: admin_login.php');
    exit();
}

$success_message = '';
$error_message = '';
$selected_raffle = null;

// Obtener ID de rifa específica si se proporciona
$selected_raffle_id = $_GET['rifa_id'] ?? null;

// Obtener rifas disponibles con precios y comisiones del comité
$available_raffles = [];
try {
    $sql = "SELECT 
                r.id, r.name, r.total_tickets, 
                COALESCE(r.sold_tickets, 0) as sold_tickets, 
                r.status, r.draw_date, r.updated_at,
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price 
                    ELSE r.ticket_price 
                END as ticket_price,
                CASE 
                    WHEN rc.commission_rate IS NOT NULL THEN rc.commission_rate 
                    ELSE r.commission_rate 
                END as commission_rate,
                rc.original_price,
                rc.updated_at as committee_updated_at,
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN 1
                    ELSE 0
                END as has_committee_pricing
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.is_active = 1
            WHERE r.status = 'active' 
            AND r.draw_date > NOW() 
            ORDER BY r.draw_date ASC";
    $available_raffles = fetchAll($sql);
    
    // Si hay una rifa específica seleccionada, buscarla
    if ($selected_raffle_id) {
        foreach ($available_raffles as $raffle) {
            if ($raffle['id'] == $selected_raffle_id) {
                $selected_raffle = $raffle;
                break;
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error al obtener rifas disponibles: " . $e->getMessage());
}

// Procesar la venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        $raffle_id = intval($_POST['raffle_id'] ?? 0);
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $cash_received = floatval($_POST['cash_received'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validaciones básicas
        if ($raffle_id <= 0) {
            throw new Exception('Debe seleccionar una rifa válida');
        }
        if (empty($customer_name)) {
            throw new Exception('El nombre del cliente es obligatorio');
        }
        if (empty($customer_phone)) {
            throw new Exception('El teléfono del cliente es obligatorio');
        }
        if ($quantity <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }
        if (!in_array($payment_method, ['cash', 'transfer', 'card'])) {
            throw new Exception('Método de pago inválido');
        }

        // Verificar disponibilidad de boletos y obtener precios del comité
        $raffle_query = "SELECT 
                            r.*,
                            CASE 
                                WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price 
                                ELSE r.ticket_price 
                            END as final_ticket_price,
                            CASE 
                                WHEN rc.commission_rate IS NOT NULL THEN rc.commission_rate 
                                ELSE r.commission_rate 
                            END as final_commission_rate,
                            rc.committee_id,
                            rc.original_price
                        FROM raffles r 
                        LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.is_active = 1
                        WHERE r.id = ? AND r.status = 'active'";
        
        $raffle = fetchOne($raffle_query, [$raffle_id]);
        if (!$raffle) {
            throw new Exception('La rifa seleccionada no está disponible');
        }

        $available_tickets = $raffle['total_tickets'] - $raffle['sold_tickets'];
        if ($quantity > $available_tickets) {
            throw new Exception("Solo quedan {$available_tickets} boletos disponibles");
        }

        // Calcular totales con precios del comité
        $unit_price = $raffle['final_ticket_price'];
        $total_amount = $unit_price * $quantity;
        $commission_rate = $raffle['final_commission_rate'];
        $commission_amount = $total_amount * ($commission_rate / 100);

        // Validar efectivo si es necesario
        $change_amount = 0;
        if ($payment_method === 'cash') {
            if ($cash_received < $total_amount) {
                throw new Exception('El efectivo recibido es insuficiente');
            }
            $change_amount = $cash_received - $total_amount;
        }

        // Generar números de boletos consecutivos
        $start_ticket = $raffle['sold_tickets'] + 1;
        $end_ticket = $start_ticket + $quantity - 1;
        $ticket_numbers = [];
        for ($i = $start_ticket; $i <= $end_ticket; $i++) {
            $ticket_numbers[] = str_pad($i, 6, '0', STR_PAD_LEFT);
        }

        // Iniciar transacción
        $pdo = getDB();
        $pdo->beginTransaction();

        try {
            // Insertar la venta en la tabla sells con precios del comité
            $sql_sale = "INSERT INTO sells (
                raffle_id, seller_id, customer_name, customer_phone, customer_email,
                quantity, unit_price, total_amount, commission_rate, commission_amount, 
                payment_method, cash_received, change_amount, ticket_numbers, notes, 
                committee_id, original_unit_price,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())";
            
            // Preparar y ejecutar la consulta de inserción
            $stmt = $pdo->prepare($sql_sale);
            $stmt->execute([
                $raffle_id,
                $current_admin['id'], 
                $customer_name,
                $customer_phone,
                $customer_email,
                $quantity,
                $unit_price, // Precio del comité
                $total_amount,
                $commission_rate, // Comisión del comité
                $commission_amount,
                $payment_method,
                $payment_method === 'cash' ? $cash_received : null,
                $change_amount,
                json_encode($ticket_numbers),
                $notes,
                $raffle['committee_id'], // ID del comité que estableció los precios
                $raffle['ticket_price'] // Precio original de la rifa
            ]);
            
            // Obtener el ID de la venta recién insertada
            $sale_id = $pdo->lastInsertId();

            // Actualizar contador de boletos vendidos
            $sql_update = "UPDATE raffles SET 
                          sold_tickets = sold_tickets + ?,
                          updated_at = NOW() 
                          WHERE id = ?";
            executeQuery($sql_update, [$quantity, $raffle_id]);

            // Confirmar transacción
            $pdo->commit();

            // Log de actividad con información de precios del comité
            $price_info = "";
            if ($raffle['committee_id']) {
                $price_info = " - Precio comité: $" . number_format($unit_price, 2) . 
                             " (Original: $" . number_format($raffle['ticket_price'], 2) . ")";
            }
            
            logAdminActivity('sell_tickets', "Venta #{$sale_id}: {$quantity} boletos de rifa '{$raffle['name']}' - Cliente: {$customer_name} - Total: $" . number_format($total_amount, 2) . " - Comisión: $" . number_format($commission_amount, 2) . $price_info);
            
            $success_message = "¡Venta registrada exitosamente!\n";
            $success_message .= "ID de Venta: #{$sale_id}\n";
            $success_message .= "Boletos: " . implode(', ', $ticket_numbers) . "\n";
            $success_message .= "Total: $" . number_format($total_amount, 2) . "\n";
            $success_message .= "Tu comisión: $" . number_format($commission_amount, 2);
            
            if ($raffle['committee_id']) {
                $success_message .= "\n(Precios establecidos por el comité)";
            }
            
            if ($change_amount > 0) {
                $success_message .= "\nCambio: $" . number_format($change_amount, 2);
            }

            // Actualizar la información de la rifa seleccionada
            if ($selected_raffle && $selected_raffle['id'] == $raffle_id) {
                $selected_raffle['sold_tickets'] += $quantity;
            }
            
            // Actualizar la lista de rifas disponibles
            foreach ($available_raffles as &$r) {
                if ($r['id'] == $raffle_id) {
                    $r['sold_tickets'] += $quantity;
                    break;
                }
            }

        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error en venta de boletos: " . $e->getMessage());
    }
}

// Obtener estadísticas del vendedor actual usando precios del comité
$seller_stats = [
    'total_sales' => 0,
    'total_tickets' => 0,
    'total_commission' => 0,
    'sales_this_month' => 0
];

try {
    // Total de ventas del vendedor (usando precios reales de venta)
    $stats_query = "SELECT 
                        COUNT(*) as total_sales,
                        COALESCE(SUM(quantity), 0) as total_tickets,
                        COALESCE(SUM(commission_amount), 0) as total_commission
                    FROM sells 
                    WHERE seller_id = ? AND status = 'completed'";
    
    $stats = fetchOne($stats_query, [$current_admin['id']]);
    if ($stats) {
        $seller_stats['total_sales'] = $stats['total_sales'];
        $seller_stats['total_tickets'] = $stats['total_tickets'];
        $seller_stats['total_commission'] = $stats['total_commission'];
    }

    // Ventas del mes actual
    $month_query = "SELECT COUNT(*) as sales_this_month 
                   FROM sells 
                   WHERE seller_id = ? 
                   AND status = 'completed'
                   AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    
    $month_stats = fetchOne($month_query, [$current_admin['id']]);
    if ($month_stats) {
        $seller_stats['sales_this_month'] = $month_stats['sales_this_month'];
    }

} catch (Exception $e) {
    error_log("Error al obtener estadísticas del vendedor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender Boletos - Panel de Vendedor</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #ffffff;
            color: #1f2937;
            line-height: 1.6;
        }

        /* Header */
        .main-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #059669, #047857);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .header-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .breadcrumb a {
            color: #059669;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: 1px solid #ef4444;
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-color);
            opacity: 0.8;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--card-color);
        }

        .stat-card.stat-sales { --card-color: #3b82f6; }
        .stat-card.stat-tickets { --card-color: #10b981; }
        .stat-card.stat-commission { --card-color: #f59e0b; }
        .stat-card.stat-month { --card-color: #8b5cf6; }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: var(--card-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.85rem;
            color: #059669;
            font-weight: 500;
        }

        /* Content Sections */
        .content-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafbfc;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .section-content {
            padding: 2rem;
        }

        /* Raffle Selection */
        .raffle-selector {
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-select, .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-select:focus, .form-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        /* Selected Raffle Info */
        .selected-raffle-info {
            display: none;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 2px solid #10b981;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .selected-raffle-info.show {
            display: block;
        }

        .raffle-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 0.8rem;
            color: #065f46;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #064e3b;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-grid.single-column {
            grid-template-columns: 1fr;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .payment-option:hover {
            border-color: #059669;
            background: #f0fdf4;
        }

        .payment-option.selected {
            border-color: #059669;
            background: #ecfdf5;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .payment-icon {
            font-size: 1.8rem;
            color: #059669;
            margin-bottom: 0.5rem;
        }

        .payment-option h4 {
            font-weight: 600;
            color: #111827;
        }

        /* Cash Section */
        .cash-section {
            display: none;
            background: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .cash-section.active {
            display: block;
        }

        .cash-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .change-display {
            background: #dcfdf7;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }

        .change-display h4 {
            color: #065f46;
            margin-bottom: 0.5rem;
        }

        .change-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #047857;
        }

        /* Summary Panel */
        .summary-panel {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid rgba(59, 130, 246, 0.2);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .summary-label {
            color: #1e40af;
            font-weight: 500;
        }

        .summary-value {
            color: #1e3a8a;
            font-weight: 600;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            border: 1px solid #059669;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            border-color: #047857;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            white-space: pre-line;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .cash-grid {
                grid-template-columns: 1fr;
            }

            .form-buttons {
                flex-direction: column;
            }

            .raffle-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                </div>
                <div class="header-info">
                    <div class="breadcrumb">
                        <a href="panel.php">Panel</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Vender Boletos</span>
                    </div>
                    <h1>Vender Boletos</h1>
                </div>
            </div>
            <div class="header-right">
                <a href="panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-sales">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-title">Ventas Totales</div>
                </div>
                <div class="stat-number"><?php echo number_format($seller_stats['total_sales']); ?></div>
                <div class="stat-change">Transacciones completadas</div>
            </div>

            <div class="stat-card stat-tickets">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-title">Boletos Vendidos</div>
                </div>
                <div class="stat-number"><?php echo number_format($seller_stats['total_tickets']); ?></div>
                <div class="stat-change">Total de boletos</div>
            </div>

            <div class="stat-card stat-commission">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-title">Comisiones Ganadas</div>
                </div>
                <div class="stat-number">$<?php echo number_format($seller_stats['total_commission'], 2); ?></div>
                <div class="stat-change">Total acumulado</div>
            </div>

            <div class="stat-card stat-month">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-title">Ventas Este Mes</div>
                </div>
                <div class="stat-number"><?php echo number_format($seller_stats['sales_this_month']); ?></div>
                <div class="stat-change">Ventas realizadas</div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div class="alert-content"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <div class="alert-content"><?php echo htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>

        <!-- Selected Raffle Info -->
        <?php if ($selected_raffle): ?>
        <div class="selected-raffle-info show" id="selectedRaffleInfo">
            <h3 style="color: #065f46; margin-bottom: 0.5rem;">
                <i class="fas fa-info-circle"></i>
                Rifa Seleccionada: <?php echo htmlspecialchars($selected_raffle['name']); ?>
            </h3>
            <div class="raffle-info-grid">
                <div class="info-item">
                    <div class="info-label">Precio por Boleto</div>
                    <div class="info-value">$<?php echo number_format($selected_raffle['ticket_price'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tu Comisión</div>
                    <div class="info-value"><?php echo number_format($selected_raffle['commission_rate'], 1); ?>%</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Boletos Disponibles</div>
                    <div class="info-value"><?php echo number_format($selected_raffle['total_tickets'] - $selected_raffle['sold_tickets']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Sorteo</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($selected_raffle['draw_date'])); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sell Form -->
        <div class="content-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        <i class="fas fa-shopping-cart"></i>
                        Nueva Venta
                    </h2>
                    <p class="section-subtitle">Complete la información para registrar una nueva venta de boletos</p>
                </div>
            </div>

            <div class="section-content">
                <?php if (empty($available_raffles)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle alert-icon"></i>
                        <div class="alert-content">No hay rifas disponibles para vender en este momento.</div>
                    </div>
                <?php else: ?>

                <form method="POST" id="sellForm">
                    <!-- Raffle Selection -->
                    <div class="form-group">
                        <label class="form-label" for="raffle_id">
                            <i class="fas fa-gift"></i> Seleccionar Rifa *
                        </label>
                        <select id="raffle_id" name="raffle_id" class="form-select" required onchange="updateRaffleInfo()">
                            <option value="">Seleccione una rifa...</option>
                            <?php foreach ($available_raffles as $raffle): ?>
                                <?php 
                                    $available = $raffle['total_tickets'] - $raffle['sold_tickets']; 
                                    $isSelected = $selected_raffle && $selected_raffle['id'] == $raffle['id'];
                                ?>
                                <option value="<?php echo $raffle['id']; ?>" 
                                        data-price="<?php echo $raffle['ticket_price']; ?>"
                                        data-commission="<?php echo $raffle['commission_rate']; ?>"
                                        data-available="<?php echo $available; ?>"
                                        data-name="<?php echo htmlspecialchars($raffle['name']); ?>"
                                        <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($raffle['name']); ?> - 
                                    $<?php echo number_format($raffle['ticket_price'], 2); ?> 
                                    (<?php echo $available; ?> disponibles)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Customer Information -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="customer_name">
                                <i class="fas fa-user"></i> Nombre del Cliente *
                            </label>
                            <input type="text" 
                                   id="customer_name" 
                                   name="customer_name" 
                                   class="form-input" 
                                   placeholder="Nombre completo del cliente"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_phone">
                                <i class="fas fa-phone"></i> Teléfono *
                            </label>
                            <input type="tel" 
                                   id="customer_phone" 
                                   name="customer_phone" 
                                   class="form-input" 
                                   placeholder="+52 662 123 4567"
                                   required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="customer_email">
                                <i class="fas fa-envelope"></i> Email (Opcional)
                            </label>
                            <input type="email" 
                                   id="customer_email" 
                                   name="customer_email" 
                                   class="form-input" 
                                   placeholder="cliente@email.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="quantity">
                                <i class="fas fa-hashtag"></i> Cantidad de Boletos *
                            </label>
                            <input type="number" 
                                   id="quantity" 
                                   name="quantity" 
                                   class="form-input" 
                                   placeholder="1"
                                   min="1"
                                   required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-credit-card"></i> Método de Pago *
                        </label>
                        <div class="payment-methods">
                            <div class="payment-option" onclick="selectPaymentMethod('cash')">
                                <div class="payment-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <h4>Efectivo</h4>
                                <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">Pago en efectivo</p>
                            </div>
                            <div class="payment-option" onclick="selectPaymentMethod('transfer')">
                                <div class="payment-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <h4>Transferencia</h4>
                                <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">Transferencia bancaria</p>
                            </div>
                            <div class="payment-option" onclick="selectPaymentMethod('card')">
                                <div class="payment-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <h4>Tarjeta</h4>
                                <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem;">Tarjeta de crédito/débito</p>
                            </div>
                        </div>
                        <input type="hidden" id="payment_method" name="payment_method" required>
                    </div>

                    <!-- Cash Section -->
                    <div id="cash-section" class="cash-section">
                        <h4 style="color: #d97706; margin-bottom: 1rem;">
                            <i class="fas fa-calculator"></i> Cálculo de Cambio
                        </h4>
                        <div class="cash-grid">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="cash_received">
                                    Efectivo Recibido
                                </label>
                                <input type="number" 
                                       id="cash_received" 
                                       name="cash_received" 
                                       class="form-input" 
                                       step="0.01"
                                       placeholder="0.00"
                                       oninput="calculateChange()">
                            </div>
                            <div id="change-display" class="change-display" style="display: none;">
                                <h4>Cambio a Entregar</h4>
                                <div class="change-amount" id="change-amount">$0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label class="form-label" for="notes">
                            <i class="fas fa-sticky-note"></i> Notas (Opcional)
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="form-input" 
                                  rows="3"
                                  style="resize: vertical;"
                                  placeholder="Notas adicionales sobre la venta..."></textarea>
                    </div>

                    <!-- Summary Panel -->
                    <div class="summary-panel">
                        <div class="summary-title">
                            <i class="fas fa-receipt"></i>
                            Resumen de la Venta
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Rifa Seleccionada:</span>
                            <span class="summary-value" id="selected-raffle-name">Ninguna</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Precio por Boleto:</span>
                            <span class="summary-value" id="unit-price">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Cantidad:</span>
                            <span class="summary-value" id="quantity-display">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value" id="subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tu Comisión:</span>
                            <span class="summary-value" id="commission">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total a Cobrar:</span>
                            <span class="summary-value" id="total-amount">$0.00</span>
                        </div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="form-buttons">
                        <a href="panel.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                            <i class="fas fa-check"></i>
                            Registrar Venta
                        </button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        let selectedRaffle = null;
        let paymentMethod = null;

        // Auto-seleccionar rifa si viene pre-seleccionada
        <?php if ($selected_raffle): ?>
        window.addEventListener('load', function() {
            updateRaffleInfo();
        });
        <?php endif; ?>

        // Actualizar información cuando cambia la rifa
        function updateRaffleInfo() {
            const select = document.getElementById('raffle_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                selectedRaffle = {
                    id: option.value,
                    name: option.dataset.name,
                    price: parseFloat(option.dataset.price),
                    commission: parseFloat(option.dataset.commission),
                    available: parseInt(option.dataset.available)
                };
                
                // Actualizar límite de cantidad
                const quantityInput = document.getElementById('quantity');
                quantityInput.max = selectedRaffle.available;
                quantityInput.value = 1;
                
                // Mostrar info de rifa seleccionada
                showSelectedRaffleInfo();
                
            } else {
                selectedRaffle = null;
                hideSelectedRaffleInfo();
            }
            
            updateSummary();
        }

        function showSelectedRaffleInfo() {
            let infoDiv = document.getElementById('selectedRaffleInfo');
            if (!infoDiv) {
                // Crear el div si no existe
                infoDiv = document.createElement('div');
                infoDiv.id = 'selectedRaffleInfo';
                infoDiv.className = 'selected-raffle-info';
                
                // Insertarlo después de las stats
                const statsGrid = document.querySelector('.stats-grid');
                statsGrid.insertAdjacentElement('afterend', infoDiv);
            }
            
            if (selectedRaffle) {
                const available = selectedRaffle.available;
                const commissionAmount = selectedRaffle.price * (selectedRaffle.commission / 100);
                
                infoDiv.innerHTML = `
                    <h3 style="color: #065f46; margin-bottom: 0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        Rifa Seleccionada: ${selectedRaffle.name}
                    </h3>
                    <div class="raffle-info-grid">
                        <div class="info-item">
                            <div class="info-label">Precio por Boleto</div>
                            <div class="info-value">$${selectedRaffle.price.toFixed(2)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tu Comisión</div>
                            <div class="info-value">${selectedRaffle.commission.toFixed(1)}% ($${commissionAmount.toFixed(2)})</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Boletos Disponibles</div>
                            <div class="info-value">${available.toLocaleString()}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Estado</div>
                            <div class="info-value" style="color: #059669;">Disponible</div>
                        </div>
                    </div>
                `;
                infoDiv.classList.add('show');
            }
        }

        function hideSelectedRaffleInfo() {
            const infoDiv = document.getElementById('selectedRaffleInfo');
            if (infoDiv) {
                infoDiv.classList.remove('show');
            }
        }

        // Selección de cantidad
        document.getElementById('quantity').addEventListener('input', function() {
            if (selectedRaffle && this.value > selectedRaffle.available) {
                this.value = selectedRaffle.available;
                alert(`Solo hay ${selectedRaffle.available} boletos disponibles`);
            }
            updateSummary();
        });

        // Selección de método de pago
        function selectPaymentMethod(method) {
            paymentMethod = method;
            document.getElementById('payment_method').value = method;
            
            // Actualizar UI
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.payment-option').classList.add('selected');
            
            // Mostrar/ocultar sección de efectivo
            const cashSection = document.getElementById('cash-section');
            if (method === 'cash') {
                cashSection.classList.add('active');
            } else {
                cashSection.classList.remove('active');
            }
            
            updateSummary();
        }

        // Cálculo de cambio
        function calculateChange() {
            if (!selectedRaffle || paymentMethod !== 'cash') return;
            
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const total = selectedRaffle.price * quantity;
            const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
            const change = cashReceived - total;
            
            const changeDisplay = document.getElementById('change-display');
            const changeAmount = document.getElementById('change-amount');
            
            if (cashReceived > 0) {
                changeDisplay.style.display = 'block';
                
                if (change < 0) {
                    changeAmount.style.color = '#dc2626';
                    changeAmount.textContent = 'Insuficiente: -$' + Math.abs(change).toFixed(2);
                    changeDisplay.style.borderColor = '#dc2626';
                    changeDisplay.style.background = '#fef2f2';
                } else {
                    changeAmount.style.color = '#047857';
                    changeAmount.textContent = '$' + change.toFixed(2);
                    changeDisplay.style.borderColor = '#10b981';
                    changeDisplay.style.background = '#dcfdf7';
                }
            } else {
                changeDisplay.style.display = 'none';
            }
        }

        // Actualizar resumen
        function updateSummary() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            
            if (selectedRaffle && quantity > 0) {
                const unitPrice = selectedRaffle.price;
                const commissionRate = selectedRaffle.commission;
                const subtotal = unitPrice * quantity;
                const commission = subtotal * (commissionRate / 100);
                
                document.getElementById('selected-raffle-name').textContent = selectedRaffle.name;
                document.getElementById('unit-price').textContent = '$' + unitPrice.toFixed(2);
                document.getElementById('quantity-display').textContent = quantity;
                document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('commission').textContent = '$' + commission.toFixed(2);
                document.getElementById('total-amount').textContent = '$' + subtotal.toFixed(2);
                
                // Calcular cambio si es efectivo
                calculateChange();
                
                // Habilitar botón si todo está completo
                const submitBtn = document.getElementById('submit-btn');
                const customerName = document.getElementById('customer_name').value.trim();
                const customerPhone = document.getElementById('customer_phone').value.trim();
                
                if (customerName && customerPhone && paymentMethod) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            } else {
                document.getElementById('selected-raffle-name').textContent = 'Ninguna';
                document.getElementById('unit-price').textContent = '$0.00';
                document.getElementById('quantity-display').textContent = '0';
                document.getElementById('subtotal').textContent = '$0.00';
                document.getElementById('commission').textContent = '$0.00';
                document.getElementById('total-amount').textContent = '$0.00';
                document.getElementById('submit-btn').disabled = true;
            }
        }

        // Validar formulario en tiempo real
        document.getElementById('customer_name').addEventListener('input', updateSummary);
        document.getElementById('customer_phone').addEventListener('input', updateSummary);

        // Validación antes de envío
        document.getElementById('sellForm').addEventListener('submit', function(e) {
            if (paymentMethod === 'cash') {
                const quantity = parseInt(document.getElementById('quantity').value) || 0;
                const total = selectedRaffle.price * quantity;
                const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
                
                if (cashReceived < total) {
                    e.preventDefault();
                    alert('El efectivo recibido es insuficiente para completar la venta.');
                    return;
                }
            }
        });

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>