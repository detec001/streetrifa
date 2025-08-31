<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación
requireAuth();

// Obtener información del administrador actual
$current_admin = getCurrentAdmin();

// Procesar logout si se solicita
if (isset($_GET['logout'])) {
    logAdminActivity('logout', 'Cierre de sesión');
    logoutAdmin();
    header('Location: admin_login.php');
    exit();
}

// Obtener rifas reales de la base de datos con datos actualizados
$rifas_data = [];
try {
    if ($current_admin['user_type'] === 'committee') {
        // Para comité, obtener precios y comisiones específicos de raffle_committee
        $sql = "SELECT 
                    r.*,
                    COALESCE(r.sold_tickets, 0) as sold_tickets,
                    a.username as created_by_name,
                    rc.ticket_price as committee_ticket_price,
                    rc.commission_rate as committee_commission_rate,
                    rc.original_price,
                    rc.updated_at as committee_updated_at,
                    CASE 
                        WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price 
                        ELSE r.ticket_price 
                    END as display_ticket_price,
                    CASE 
                        WHEN rc.commission_rate IS NOT NULL THEN rc.commission_rate 
                        ELSE r.commission_rate 
                    END as display_commission_rate
                FROM raffles r 
                LEFT JOIN admins a ON r.created_by = a.id 
                LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
                ORDER BY r.updated_at DESC, r.created_at DESC";
        
        $rifas_data = fetchAll($sql, [$current_admin['id']]);
    } else {
        // Para admin y seller, usar precios originales
        $sql = "SELECT 
                    r.*,
                    COALESCE(r.sold_tickets, 0) as sold_tickets,
                    a.username as created_by_name,
                    r.ticket_price as display_ticket_price,
                    r.commission_rate as display_commission_rate
                FROM raffles r 
                LEFT JOIN admins a ON r.created_by = a.id 
                ORDER BY r.updated_at DESC, r.created_at DESC";
        
        $rifas_data = fetchAll($sql);
    }
    
    // Procesar datos para la vista
    foreach ($rifas_data as &$rifa) {
        // Decodificar imágenes JSON
        if ($rifa['images']) {
            $rifa['images_array'] = json_decode($rifa['images'], true) ?: [];
        } else {
            $rifa['images_array'] = [];
        }
        
        // Formatear fecha para mostrar
        $rifa['formatted_date'] = date('d/m/Y H:i', strtotime($rifa['draw_date']));
        $rifa['days_remaining'] = ceil((strtotime($rifa['draw_date']) - time()) / 86400);
        
        // Calcular potencial de ingresos basado en precio del comité o admin
        $rifa['potential_revenue'] = $rifa['total_tickets'] * $rifa['display_ticket_price'];
        $rifa['current_revenue'] = $rifa['sold_tickets'] * $rifa['display_ticket_price'];
        
        // Calcular comisiones basadas en la tasa del comité o admin
        $rifa['total_commission'] = $rifa['current_revenue'] * ($rifa['display_commission_rate'] / 100);
        
        // Marcar si tiene cambios del comité
        if ($current_admin['user_type'] === 'committee') {
            $rifa['has_committee_changes'] = isset($rifa['committee_ticket_price']) || isset($rifa['committee_commission_rate']);
            $rifa['recently_updated_by_committee'] = isset($rifa['committee_updated_at']) && 
                strtotime($rifa['committee_updated_at']) > strtotime('-1 hour');
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener rifas: " . $e->getMessage());
    $rifas_data = [];
}

// Estadísticas generales actualizadas
$stats = [
    'active_raffles' => 0,
    'total_users' => 0,
    'monthly_sales' => 0,
    'total_tickets_sold' => 0,
    'total_revenue' => 0,
    'total_commissions' => 0
];

try {
    // Rifas activas
    $stats['active_raffles'] = fetchOne("SELECT COUNT(*) as count FROM raffles WHERE status = 'active'")['count'] ?? 0;
    
    // Total de usuarios registrados
    $stats['total_users'] = fetchOne("SELECT COUNT(*) as count FROM admins")['count'] ?? 0;
    
    if ($current_admin['user_type'] === 'committee') {
        // Para comité, usar precios específicos del comité en cálculos
        $monthly_sales_query = "
            SELECT COALESCE(SUM(
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price * r.sold_tickets
                    ELSE r.ticket_price * r.sold_tickets 
                END
            ), 0) as total 
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
            WHERE MONTH(r.created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
        ";
        $stats['monthly_sales'] = fetchOne($monthly_sales_query, [$current_admin['id']])['total'] ?? 0;
        
        $revenue_query = "
            SELECT COALESCE(SUM(
                CASE 
                    WHEN rc.ticket_price IS NOT NULL THEN rc.ticket_price * r.sold_tickets
                    ELSE r.ticket_price * r.sold_tickets 
                END
            ), 0) as total 
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
        ";
        $stats['total_revenue'] = fetchOne($revenue_query, [$current_admin['id']])['total'] ?? 0;
        
        $commissions_query = "
            SELECT COALESCE(SUM(
                CASE 
                    WHEN rc.ticket_price IS NOT NULL AND rc.commission_rate IS NOT NULL THEN 
                        rc.ticket_price * r.sold_tickets * (rc.commission_rate / 100)
                    ELSE 
                        r.ticket_price * r.sold_tickets * (r.commission_rate / 100)
                END
            ), 0) as total 
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
        ";
        $stats['total_commissions'] = fetchOne($commissions_query, [$current_admin['id']])['total'] ?? 0;
    } else {
        // Para admin y seller, usar precios originales
        $monthly_sales_query = "
            SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets), 0) as total 
            FROM raffles r 
            WHERE MONTH(r.created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
        ";
        $stats['monthly_sales'] = fetchOne($monthly_sales_query)['total'] ?? 0;
        
        $revenue_query = "SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets), 0) as total FROM raffles r";
        $stats['total_revenue'] = fetchOne($revenue_query)['total'] ?? 0;
        
        $commissions_query = "SELECT COALESCE(SUM(r.ticket_price * r.sold_tickets * (r.commission_rate / 100)), 0) as total FROM raffles r";
        $stats['total_commissions'] = fetchOne($commissions_query)['total'] ?? 0;
    }
    
    // Total de boletos vendidos (igual para todos)
    $stats['total_tickets_sold'] = fetchOne("SELECT COALESCE(SUM(sold_tickets), 0) as total FROM raffles")['total'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de <?php echo ucfirst($current_admin['user_type']); ?> - Rifas Online</title>
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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .header-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .user-role {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        .role-committee {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
        }

        .role-seller {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notifications-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #6b7280;
            position: relative;
        }

        .notifications-btn:hover {
            background: #f9fafb;
            color: #374151;
            border-color: #d1d5db;
        }

        .notifications-btn .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* Contenido Principal */
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

        .stat-card.stat-rifas { --card-color: #3b82f6; }
        .stat-card.stat-revenue { --card-color: #10b981; }
        .stat-card.stat-sales { --card-color: #f59e0b; }
        .stat-card.stat-commissions { --card-color: #8b5cf6; }
        .stat-card.stat-tickets { --card-color: #ef4444; }

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

        /* Main Content Section */
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

        .primary-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            color: white;
            text-decoration: none;
        }

        .success-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .success-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            color: white;
            text-decoration: none;
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

        .raffle-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .raffle-image {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .raffle-info h4 {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .raffle-meta {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .price-display {
            font-weight: 700;
            color: #059669;
            font-size: 1.1rem;
        }

        .commission-info {
            color: #7c3aed;
            font-weight: 600;
        }

        .progress-container {
            min-width: 120px;
        }

        .progress-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 10px;
            transition: width 0.3s ease;
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
            background: #d1fae5;
            color: #065f46;
        }

        .status-paused {
            background: #fef3c7;
            color: #92400e;
        }

        .status-finished {
            background: #fee2e2;
            color: #991b1b;
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

        .action-btn.primary:hover {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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

            .raffle-cell {
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

        /* Committee & Seller specific styles */
        .committee-specific .user-avatar {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
        }

        .seller-specific .user-avatar {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .feature-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: #fef3c7;
            color: #92400e;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .updated-indicator {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .committee-pricing {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body class="<?php echo $current_admin['user_type']; ?>-specific">
    <!-- Header Unificado -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                </div>
                <div class="header-info">
                    <h1>Panel de Control</h1>
                    <div class="user-role role-<?php echo $current_admin['user_type']; ?>">
                        <i class="fas fa-<?php echo $current_admin['user_type'] === 'admin' ? 'crown' : ($current_admin['user_type'] === 'committee' ? 'users' : 'user-tie'); ?>"></i>
                        <?php echo ucfirst($current_admin['user_type']); ?>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <button class="notifications-btn">
                    <i class="fas fa-bell"></i>
                    <span class="badge"></span>
                </button>
                <a href="?logout=1" class="logout-btn" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="main-container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card stat-rifas">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="stat-title">Rifas Activas</div>
                </div>
                <div class="stat-number"><?php echo number_format($stats['active_raffles']); ?></div>
            </div>

            <div class="stat-card stat-revenue">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-title">Revenue Total</div>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
            </div>

            <div class="stat-card stat-sales">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-title">Ventas del Mes</div>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['monthly_sales'], 0); ?></div>
            </div>

            <div class="stat-card stat-commissions">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-title"><?php echo $current_admin['user_type'] === 'seller' ? 'Mis Comisiones' : 'Comisiones Totales'; ?></div>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_commissions'], 0); ?></div>
            </div>
        </div>

        <!-- Gestión de Rifas -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Gestión de Rifas
                </h2>
                <?php if ($current_admin['user_type'] === 'admin'): ?>
                    <a href="admin_create_raffle.php" class="primary-btn">
                        <i class="fas fa-plus"></i>
                        Nueva Rifa
                    </a>
                <?php elseif ($current_admin['user_type'] === 'committee'): ?>
                    <a href="add_seller.php" class="primary-btn">
                        <i class="fas fa-user-plus"></i>
                        Agregar Vendedor
                    </a>
                <?php elseif ($current_admin['user_type'] === 'seller'): ?>
                    <a href="realizar_venta.php" class="success-btn">
                        <i class="fas fa-shopping-cart"></i>
                        Realizar Venta
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($rifas_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-gift"></i>
                    <h3>No hay rifas disponibles</h3>
                    <p>Comienza creando tu primera rifa</p>
                    <?php if ($current_admin['user_type'] === 'admin'): ?>
                        <a href="admin_create_raffle.php" class="primary-btn" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i>
                            Crear Nueva Rifa
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Rifa</th>
                            <th>Precio & Comisión</th>
                            <th>Progreso</th>
                            <th>Revenue</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rifas_data as $rifa): ?>
                            <?php 
                                $progress_percentage = $rifa['total_tickets'] > 0 ? ($rifa['sold_tickets'] / $rifa['total_tickets']) * 100 : 0;
                                $is_expired = strtotime($rifa['draw_date']) < time();
                                
                                // Determinar imagen
                                $image_url = 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=100&h=100&fit=crop&crop=center';
                                if (!empty($rifa['images_array'])) {
                                    $image_url = '../uploads/raffles/' . $rifa['images_array'][0];
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="raffle-cell">
                                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($rifa['name']); ?>" class="raffle-image">
                                        <div class="raffle-info">
                                            <h4><?php echo htmlspecialchars($rifa['name']); ?></h4>
                                            <div class="raffle-meta">
                                                ID: #<?php echo $rifa['id']; ?> • 
                                                <?php echo $rifa['formatted_date']; ?>
                                                <?php if ($rifa['days_remaining'] >= 0 && !$is_expired): ?>
                                                    • <?php echo $rifa['days_remaining']; ?> días
                                                <?php elseif ($is_expired && $rifa['status'] !== 'finished'): ?>
                                                    • <span style="color: #ef4444;">Vencida</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="price-display">$<?php echo number_format($rifa['display_ticket_price'], 2); ?></div>
                                    <div class="commission-info"><?php echo number_format($rifa['display_commission_rate'], 1); ?>% comisión</div>
                                    <?php if ($current_admin['user_type'] === 'committee' && isset($rifa['has_committee_changes']) && $rifa['has_committee_changes']): ?>
                                        <div class="feature-badge committee-pricing">
                                            <i class="fas fa-users"></i>
                                            Personalizado
                                        </div>
                                    <?php endif; ?>
                                    <?php if (isset($rifa['recently_updated_by_committee']) && $rifa['recently_updated_by_committee']): ?>
                                        <div class="feature-badge updated-indicator">
                                            <i class="fas fa-clock"></i>
                                            Actualizado
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-text">
                                            <?php echo number_format($rifa['sold_tickets']); ?>/<?php echo number_format($rifa['total_tickets']); ?> 
                                            (<?php echo number_format($progress_percentage, 1); ?>%)
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min($progress_percentage, 100); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="price-display">$<?php echo number_format($rifa['current_revenue'], 0); ?></div>
                                    <div style="font-size: 0.85rem; color: #6b7280;">
                                        de $<?php echo number_format($rifa['potential_revenue'], 0); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-badge status-<?php echo $rifa['status']; ?>">
                                        <i class="fas fa-<?php echo $rifa['status'] === 'active' ? 'check-circle' : ($rifa['status'] === 'paused' ? 'pause-circle' : 'times-circle'); ?>"></i>
                                        <?php 
                                            $status_labels = [
                                                'active' => 'Activa',
                                                'paused' => 'Pausada',
                                                'finished' => 'Finalizada',
                                                'cancelled' => 'Cancelada'
                                            ];
                                            echo $status_labels[$rifa['status']] ?? ucfirst($rifa['status']);
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($current_admin['user_type'] === 'admin'): ?>
                                            <button class="action-btn primary" title="Gestionar Usuarios" onclick="manageUsers(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button class="action-btn warning" title="Lanzar Sorteo" onclick="launchDraw(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-trophy"></i>
                                            </button>
                                            <button class="action-btn success" title="Ver Reportes" onclick="viewReports(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                        <?php elseif ($current_admin['user_type'] === 'committee'): ?>
                                            <button class="action-btn primary" title="Vendedores" onclick="window.location.href='sellers.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                                <i class="fas fa-user-tie"></i>
                                            </button>
                                            <button class="action-btn success" title="Contabilidad" onclick="window.location.href='accounting.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                                <i class="fas fa-calculator"></i>
                                            </button>
                                            <button class="action-btn warning" title="Configuración" onclick="window.location.href='settings_committee.php?rifa_id=<?php echo $rifa['id']; ?>'">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        <?php else: // seller ?>
                                            <button class="action-btn primary" title="Vender Boletos" onclick="sellTickets(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                            <button class="action-btn success" title="Mis Ventas" onclick="viewMySales(<?php echo $rifa['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
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

    <script>
        // Funciones para el Admin
        function createNewRifa() {
            window.location.href = 'admin_create_raffle.php';
        }
        
        function manageUsers(rifaId) {
            window.location.href = 'admin_create_user.php?rifa_id=' + rifaId;
        }
        
        function launchDraw(rifaId) {
            if (confirm(`¿Estás seguro de que deseas lanzar el sorteo para la rifa ID: ${rifaId}?`)) {
                window.location.href = 'admin_run_raffle.php?rifa_id=' + rifaId;
            }
        }
        
        function viewReports(rifaId) {
            window.location.href = 'admin_reports.php?rifa_id=' + rifaId;
        }
        
        // Funciones para vendedores
        function sellTickets(rifaId) {
            window.location.href = 'sell_tickets.php?rifa_id=' + rifaId;
        }
        
        function viewMySales(rifaId) {
            window.location.href = 'my_sales.php?rifa_id=' + rifaId;
        }

        // Notificaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Simular notificaciones
            const notificationBtn = document.querySelector('.notifications-btn');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', function() {
                    alert('Sistema de notificaciones - Por implementar');
                });
            }

            // Animaciones de entrada
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