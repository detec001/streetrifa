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

// Datos de ejemplo para la rifa (en producción vendría de la base de datos)
$rifa_info = [
    'id' => $rifa_id,
    'name' => 'iPhone 15 Pro Max',
    'draw_date' => '2025-02-15',
    'ticket_price' => 50.00,
    'total_tickets' => 1000,
    'sold_tickets' => 680
];

// Datos de ejemplo para contabilidad (en producción vendría de la base de datos)
$accounting_data = [
    'income' => [
        'ticket_sales' => 34000.00,
        'online_sales' => 25000.00,
        'seller_sales' => 9000.00,
        'other_income' => 500.00
    ],
    'expenses' => [
        'seller_commissions' => 2700.00,
        'platform_fees' => 850.00,
        'marketing' => 1200.00,
        'operational' => 450.00,
        'other_expenses' => 300.00
    ],
    'pending' => [
        'pending_sales' => 5500.00,
        'pending_commissions' => 550.00,
        'pending_withdrawals' => 2000.00
    ]
];

// Datos de ejemplo para transacciones recientes
$recent_transactions = [
    [
        'id' => 'TXN-001',
        'date' => '2025-01-15',
        'type' => 'income',
        'category' => 'Venta de boletos',
        'description' => 'Venta online - 5 boletos',
        'amount' => 250.00,
        'method' => 'Tarjeta de crédito',
        'status' => 'completed'
    ],
    [
        'id' => 'TXN-002', 
        'date' => '2025-01-15',
        'type' => 'expense',
        'category' => 'Comisión vendedor',
        'description' => 'Comisión María García',
        'amount' => -30.00,
        'method' => 'Transferencia',
        'status' => 'completed'
    ],
    [
        'id' => 'TXN-003',
        'date' => '2025-01-14',
        'type' => 'income',
        'category' => 'Venta de boletos',
        'description' => 'Venta por vendedor - Juan Pérez',
        'amount' => 150.00,
        'method' => 'Efectivo',
        'status' => 'pending'
    ],
    [
        'id' => 'TXN-004',
        'date' => '2025-01-14',
        'type' => 'expense',
        'category' => 'Gastos operacionales',
        'description' => 'Comisión plataforma de pago',
        'amount' => -12.50,
        'method' => 'Automático',
        'status' => 'completed'
    ]
];

$total_income = array_sum($accounting_data['income']);
$total_expenses = array_sum($accounting_data['expenses']);
$net_profit = $total_income - $total_expenses;
$profit_margin = $total_income > 0 ? ($net_profit / $total_income) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad - <?php echo htmlspecialchars($rifa_info['name']); ?> - Panel de Committee</title>
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

        .stat-card.stat-income { --card-color: #10b981; }
        .stat-card.stat-expenses { --card-color: #ef4444; }
        .stat-card.stat-profit { --card-color: #3b82f6; }
        .stat-card.stat-margin { --card-color: #8b5cf6; }

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

        /* Layout principal */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
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
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }

        .section-title i {
            color: #6b7280;
        }

        /* Breakdown de ingresos y gastos */
        .breakdown-list {
            padding: 0;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 2rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }

        .breakdown-item:last-child {
            border-bottom: none;
        }

        .breakdown-item:hover {
            background: #f9fafb;
        }

        .breakdown-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .breakdown-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .breakdown-text {
            flex: 1;
        }

        .breakdown-label {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.3rem;
        }

        .breakdown-description {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .breakdown-amount {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .amount-income {
            color: #059669;
        }

        .amount-expense {
            color: #dc2626;
        }

        /* Transacciones recientes */
        .transactions-section {
            grid-column: 1 / -1;
        }

        .transactions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .transactions-table thead th {
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

        .transactions-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .transactions-table tbody tr:hover {
            background: #f9fafb;
        }

        .transactions-table tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .transaction-id {
            font-family: 'JetBrains Mono', 'Fira Code', 'Monaco', monospace;
            font-size: 0.9rem;
            color: #6b7280;
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .transaction-description {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .transaction-category {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .transaction-amount {
            font-weight: 700;
            font-size: 1rem;
        }

        .transaction-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #dcfdf7;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
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

            .breakdown-item {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .breakdown-left {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .transactions-table {
                font-size: 0.85rem;
            }

            .transactions-table th,
            .transactions-table td {
                padding: 0.8rem 0.5rem;
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
                        <span>Contabilidad</span>
                    </div>
                    <h1>Contabilidad y Finanzas</h1>
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
        <!-- Resumen financiero -->
        <div class="stats-grid">
            <div class="stat-card stat-income">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-title">Ingresos Totales</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_income, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-trending-up"></i>
                    +12.5% vs mes anterior
                </div>
            </div>

            <div class="stat-card stat-expenses">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-title">Gastos Totales</div>
                </div>
                <div class="stat-number">$<?php echo number_format($total_expenses, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-trending-down"></i>
                    -3.2% vs mes anterior
                </div>
            </div>

            <div class="stat-card stat-profit">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-title">Ganancia Neta</div>
                </div>
                <div class="stat-number">$<?php echo number_format($net_profit, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-trending-up"></i>
                    +18.7% vs mes anterior
                </div>
            </div>

            <div class="stat-card stat-margin">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-title">Margen de Ganancia</div>
                </div>
                <div class="stat-number"><?php echo number_format($profit_margin, 1); ?>%</div>
                <div class="stat-change">
                    <i class="fas fa-trending-up"></i>
                    +5.3% vs mes anterior
                </div>
            </div>
        </div>

        <!-- Layout principal -->
        <div class="main-layout">
            <!-- Desglose de ingresos -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        Desglose de Ingresos
                    </h2>
                </div>
                <div class="breakdown-list">
                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Venta de Boletos</div>
                                <div class="breakdown-description">Ventas directas de boletos</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['ticket_sales'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Ventas Online</div>
                                <div class="breakdown-description">Plataforma web</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['online_sales'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Ventas por Vendedores</div>
                                <div class="breakdown-description">Comisiones de vendedores</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['seller_sales'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Otros Ingresos</div>
                                <div class="breakdown-description">Patrocinios, bonificaciones</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-income">$<?php echo number_format($accounting_data['income']['other_income'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Desglose de gastos -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-minus-circle"></i>
                        Desglose de Gastos
                    </h2>
                </div>
                <div class="breakdown-list">
                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Comisiones Vendedores</div>
                                <div class="breakdown-description">Pagos a vendedores</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['seller_commissions'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Comisiones Plataforma</div>
                                <div class="breakdown-description">Pagos, transferencias</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['platform_fees'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Marketing</div>
                                <div class="breakdown-description">Publicidad, promoción</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['marketing'], 2); ?></div>
                    </div>

                    <div class="breakdown-item">
                        <div class="breakdown-left">
                            <div class="breakdown-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="breakdown-text">
                                <div class="breakdown-label">Gastos Operacionales</div>
                                <div class="breakdown-description">Hosting, mantenimiento</div>
                            </div>
                        </div>
                        <div class="breakdown-amount amount-expense">-$<?php echo number_format($accounting_data['expenses']['operational'], 2); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transacciones recientes -->
        <div class="content-section transactions-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list-alt"></i>
                    Transacciones Recientes
                </h2>
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $transaction): ?>
                    <tr>
                        <td>
                            <div class="transaction-id"><?php echo $transaction['id']; ?></div>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                        <td>
                            <div class="transaction-description"><?php echo htmlspecialchars($transaction['description']); ?></div>
                            <div class="transaction-category"><?php echo htmlspecialchars($transaction['category']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['method']); ?></td>
                        <td>
                            <div class="transaction-amount <?php echo $transaction['type'] === 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                <?php echo $transaction['amount'] >= 0 ? '+' : ''; ?>$<?php echo number_format(abs($transaction['amount']), 2); ?>
                            </div>
                        </td>
                        <td>
                            <span class="transaction-status status-<?php echo $transaction['status']; ?>">
                                <i class="fas fa-<?php echo $transaction['status'] === 'completed' ? 'check-circle' : ($transaction['status'] === 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
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

            // Animar las tarjetas de contenido después de las estadísticas
            const contentSections = document.querySelectorAll('.content-section');
            contentSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    section.style.transition = 'all 0.6s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, 400 + index * 200);
            });
        });
    </script>
</body>
</html>