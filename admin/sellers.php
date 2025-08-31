<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Verificar que sea committee
if ($current_admin['user_type'] !== 'committee') {
    header('Location: panel.php');
    exit();
}

// Obtener rifa_id de la URL
$rifa_id = $_GET['rifa_id'] ?? null;
if (!$rifa_id) {
    header('Location: panel.php');
    exit();
}

// Obtener información real de la rifa desde la base de datos
$rifa_info = null;
try {
    $sql = "SELECT * FROM raffles WHERE id = ?";
    $rifa_info = fetchOne($sql, [$rifa_id]);
    
    if (!$rifa_info) {
        header('Location: panel.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener información de la rifa: " . $e->getMessage());
    header('Location: panel.php');
    exit();
}

// Obtener vendedores reales de la base de datos
$sellers_data = [];
$total_sellers = 0;
$active_sellers = 0;
$total_commission = 0;
$total_sales_by_sellers = 0;

try {
    // Obtener vendedores que pertenecen a este comité
    $sql = "SELECT 
                a.id,
                a.username,
                a.full_name,
                a.email,
                a.phone,
                a.status,
                a.created_at,
                COALESCE(seller_stats.tickets_sold, 0) as tickets_sold,
                COALESCE(seller_stats.total_sales, 0) as total_sales,
                COALESCE(seller_stats.commission_earned, 0) as commission_earned,
                COALESCE(seller_stats.sales_count, 0) as sales_count
            FROM admins a
            LEFT JOIN (
                SELECT 
                    s.seller_id,
                    COUNT(*) as sales_count,
                    SUM(s.quantity) as tickets_sold,
                    SUM(s.total_amount) as total_sales,
                    SUM(s.commission_amount) as commission_earned
                FROM sells s
                WHERE s.raffle_id = ? AND s.status = 'completed'
                GROUP BY s.seller_id
            ) seller_stats ON a.id = seller_stats.seller_id
            WHERE a.user_type = 'seller' 
            AND a.committee_id = ?
            ORDER BY seller_stats.tickets_sold DESC, a.full_name ASC";
    
    $sellers_data = fetchAll($sql, [$rifa_id, $current_admin['id']]);
    
    // Calcular estadísticas
    $total_sellers = count($sellers_data);
    $active_sellers = count(array_filter($sellers_data, fn($s) => $s['status'] === 'active'));
    $total_commission = array_sum(array_column($sellers_data, 'commission_earned'));
    $total_sales_by_sellers = array_sum(array_column($sellers_data, 'total_sales'));
    
    // Formatear fechas y calcular datos adicionales
    foreach ($sellers_data as &$seller) {
        $seller['joined_date'] = $seller['created_at'];
        $seller['formatted_date'] = date('d/m/Y', strtotime($seller['created_at']));
        
        // Calcular tasa de comisión promedio si hay ventas
        if ($seller['total_sales'] > 0 && $seller['commission_earned'] > 0) {
            $seller['commission_rate'] = ($seller['commission_earned'] / $seller['total_sales']) * 100;
        } else {
            $seller['commission_rate'] = 0;
        }
        
        // Datos para mostrar
        $seller['name'] = $seller['full_name'] ?: $seller['username'];
        $seller['phone'] = $seller['phone'] ?: 'No especificado';
    }
    
} catch (Exception $e) {
    error_log("Error al obtener vendedores: " . $e->getMessage());
    $sellers_data = [];
}

// Procesar acciones (cambiar estado, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $seller_id = intval($_POST['seller_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'toggle_status':
                // Cambiar estado del vendedor
                $current_status_query = "SELECT status FROM admins WHERE id = ? AND user_type = 'seller' AND committee_id = ?";
                $current_status = fetchOne($current_status_query, [$seller_id, $current_admin['id']]);
                
                if ($current_status) {
                    $new_status = $current_status['status'] === 'active' ? 'inactive' : 'active';
                    $update_sql = "UPDATE admins SET status = ?, updated_at = NOW() WHERE id = ?";
                    executeQuery($update_sql, [$new_status, $seller_id]);
                    
                    logAdminActivity('toggle_seller_status', "Cambió estado de vendedor ID: {$seller_id} a {$new_status}");
                    
                    // Actualizar en memoria para reflejar el cambio
                    foreach ($sellers_data as &$seller) {
                        if ($seller['id'] == $seller_id) {
                            $seller['status'] = $new_status;
                            break;
                        }
                    }
                    
                    if ($new_status === 'active') {
                        $active_sellers++;
                    } else {
                        $active_sellers--;
                    }
                }
                break;
                
            case 'delete_seller':
                // Eliminar vendedor (solo si no tiene ventas)
                $sales_check = fetchOne("SELECT COUNT(*) as count FROM sells WHERE seller_id = ?", [$seller_id]);
                
                if ($sales_check['count'] == 0) {
                    $delete_sql = "DELETE FROM admins WHERE id = ? AND user_type = 'seller' AND committee_id = ?";
                    executeQuery($delete_sql, [$seller_id, $current_admin['id']]);
                    
                    logAdminActivity('delete_seller', "Eliminó vendedor ID: {$seller_id}");
                    
                    // Remover de la lista en memoria
                    $sellers_data = array_filter($sellers_data, fn($s) => $s['id'] != $seller_id);
                    $total_sellers--;
                } else {
                    throw new Exception("No se puede eliminar el vendedor porque tiene ventas registradas");
                }
                break;
        }
        
        $success_message = "Acción realizada correctamente";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error en acción de vendedor: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendedores - <?php echo htmlspecialchars($rifa_info['name']); ?> - Panel de Committee</title>
    <meta name="robots" content="noindex, nofollow">
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

        /* Header Unificado */
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
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
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
            color: #7c3aed;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .raffle-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #3b82f6;
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

        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            border: 1px solid #7c3aed;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6);
            border-color: #6d28d9;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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

        .stat-card.stat-sellers { --card-color: #3b82f6; }
        .stat-card.stat-active { --card-color: #10b981; }
        .stat-card.stat-commission { --card-color: #f59e0b; }
        .stat-card.stat-sales { --card-color: #8b5cf6; }

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

        .stat-icon i {
            font-size: 1.2rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #059669;
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
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
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

        /* Content Section */
        .content-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #6b7280;
        }

        /* Tabla Moderna */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead th {
            background: #f9fafb;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .modern-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f9fafb;
        }

        .modern-table tbody td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .seller-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .seller-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .seller-info h4 {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .seller-contact {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .seller-username {
            font-size: 0.8rem;
            color: #8b5cf6;
            font-weight: 500;
            background: #f3f4f6;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            display: inline-block;
            margin-top: 0.25rem;
        }

        .performance-cell {
            text-align: center;
        }

        .performance-number {
            font-weight: 700;
            color: #111827;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .performance-label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .commission-info {
            text-align: center;
        }

        .commission-rate {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .commission-earned {
            font-weight: 700;
            color: #059669;
            font-size: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfdf7;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-suspended {
            background: #fef3c7;
            color: #92400e;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #6b7280;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #374151;
            border-color: #d1d5db;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border-color: #3b82f6;
        }

        .action-btn.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-color: #10b981;
        }

        .action-btn.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-color: #f59e0b;
        }

        .action-btn.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-color: #ef4444;
        }

        .action-btn.primary:hover,
        .action-btn.success:hover,
        .action-btn.warning:hover,
        .action-btn.danger:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* No sales indicator */
        .no-sales {
            color: #9ca3af;
            font-style: italic;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-container {
                padding: 1rem;
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

            .modern-table {
                font-size: 0.9rem;
            }

            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.75rem;
            }

            .seller-cell {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Unificado -->
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
                        <span>Vendedores</span>
                    </div>
                    <h1>Gestión de Vendedores</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="raffle-badge">
                    <i class="fas fa-gift"></i>
                    <?php echo htmlspecialchars($rifa_info['name']); ?>
                </div>
                <a href="panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <!-- Alerts -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-sellers">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Total Vendedores</div>
                </div>
                <div class="stat-number"><?php echo number_format($total_sellers); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Registrados en tu equipo
                </div>
            </div>

            <div class="stat-card stat-active">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-title">Vendedores Activos</div>
                </div>
                <div class="stat-number"><?php echo number_format($active_sellers); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Actualmente vendiendo
                </div>
            </div>

            <div class="stat-card stat-commission">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-title">Comisiones Pagadas</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_commission, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Total para esta rifa
                </div>
            </div>

            <div class="stat-card stat-sales">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-title">Ventas por Vendedores</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_sales_by_sellers, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i>
                    Ingresos generados
                </div>
            </div>
        </div>

        <!-- Gestión de Vendedores -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Lista de Vendedores
                </h2>
                <a href="add_seller.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Agregar Vendedor
                </a>
            </div>

            <?php if (empty($sellers_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No hay vendedores registrados</h3>
                    <p>Comienza agregando tu primer vendedor para esta rifa</p>
                    <a href="add_seller.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Agregar Primer Vendedor
                    </a>
                </div>
            <?php else: ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Rendimiento</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                            <th>Fecha de Ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers_data as $seller): ?>
                            <tr>
                                <td>
                                    <div class="seller-cell">
                                        <div class="seller-avatar">
                                            <?php echo strtoupper(substr($seller['name'], 0, 1)); ?>
                                        </div>
                                        <div class="seller-info">
                                            <h4><?php echo htmlspecialchars($seller['name']); ?></h4>
                                            <div class="seller-contact">
                                                <?php echo htmlspecialchars($seller['email']); ?><br>
                                                <?php echo htmlspecialchars($seller['phone']); ?>
                                            </div>
                                            <div class="seller-username">@<?php echo htmlspecialchars($seller['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="performance-cell">
                                    <?php if ($seller['tickets_sold'] > 0): ?>
                                        <div class="performance-number"><?php echo number_format($seller['tickets_sold']); ?></div>
                                        <div class="performance-label">boletos vendidos</div>
                                        <div style="margin-top: 0.5rem;">
                                            <div class="performance-number">$<?php echo number_format($seller['total_sales'], 2); ?></div>
                                            <div class="performance-label">ventas totales</div>
                                        </div>
                                        <div style="margin-top: 0.5rem;">
                                            <div class="performance-number"><?php echo number_format($seller['sales_count']); ?></div>
                                            <div class="performance-label">transacciones</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-sales">
                                            <i class="fas fa-info-circle"></i><br>
                                            Sin ventas aún
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="commission-info">
                                    <?php if ($seller['commission_earned'] > 0): ?>
                                        <div class="commission-rate"><?php echo number_format($seller['commission_rate'], 1); ?>%</div>
                                        <div class="commission-earned">$<?php echo number_format($seller['commission_earned'], 2); ?></div>
                                    <?php else: ?>
                                        <div class="no-sales">Sin comisiones</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="status-badge status-<?php echo $seller['status']; ?>">
                                        <i class="fas fa-<?php echo $seller['status'] === 'active' ? 'check-circle' : ($seller['status'] === 'suspended' ? 'pause-circle' : 'times-circle'); ?>"></i>
                                        <?php 
                                            $status_labels = [
                                                'active' => 'Activo',
                                                'inactive' => 'Inactivo',
                                                'suspended' => 'Suspendido'
                                            ];
                                            echo $status_labels[$seller['status']] ?? ucfirst($seller['status']);
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $seller['formatted_date']; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn primary" 
                                                title="Ver Ventas" 
                                                onclick="viewSellerSales(<?php echo $seller['id']; ?>, '<?php echo htmlspecialchars($seller['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <button class="action-btn success" 
                                                title="Editar Información" 
                                                onclick="editSeller(<?php echo $seller['id']; ?>, '<?php echo htmlspecialchars($seller['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirmToggleStatus('<?php echo $seller['status']; ?>', '<?php echo htmlspecialchars($seller['name'], ENT_QUOTES); ?>')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                                            <button type="submit" 
                                                    class="action-btn warning" 
                                                    title="<?php echo $seller['status'] === 'active' ? 'Desactivar' : 'Activar'; ?>">
                                                <?php if ($seller['status'] === 'active'): ?>
                                                    <i class="fas fa-user-slash"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user-check"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <?php if ($seller['tickets_sold'] == 0): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($seller['name'], ENT_QUOTES); ?>')">
                                            <input type="hidden" name="action" value="delete_seller">
                                            <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                                            <button type="submit" 
                                                    class="action-btn danger" 
                                                    title="Eliminar Vendedor">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        function viewSellerSales(sellerId, sellerName) {
            // Redirigir a página de ventas del vendedor
            window.location.href = `seller_sales.php?seller_id=${sellerId}&rifa_id=<?php echo $rifa_id; ?>`;
        }
        
        function editSeller(sellerId, sellerName) {
            // Redirigir a página de edición de vendedor
            window.location.href = `edit_seller.php?seller_id=${sellerId}`;
        }
        
        function confirmToggleStatus(currentStatus, sellerName) {
            const newStatus = currentStatus === 'active' ? 'inactivo' : 'activo';
            const action = currentStatus === 'active' ? 'desactivar' : 'activar';
            
            return confirm(`¿Estás seguro de que quieres ${action} a ${sellerName}?\n\nEsto cambiará su estado a: ${newStatus}`);
        }

        function confirmDelete(sellerName) {
            return confirm(`¿Estás seguro de que quieres eliminar a ${sellerName}?\n\n⚠️ Esta acción NO se puede deshacer.\n\nSolo se pueden eliminar vendedores que no tengan ventas registradas.`);
        }

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

            // Animar las filas de la tabla
            const rows = document.querySelectorAll('.modern-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-10px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 400 + (index * 50));
            });
        });

        // Función para refrescar estadísticas (opcional)
        function refreshStats() {
            // Aquí podrías hacer una petición AJAX para actualizar las estadísticas
            // sin recargar toda la página
            location.reload();
        }

        // Auto-refresh cada 5 minutos (opcional)
        // setInterval(refreshStats, 300000);
    </script>
</body>
</html>