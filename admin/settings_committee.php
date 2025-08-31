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

$success_message = '';
$error_message = '';

// Obtener información real de la rifa desde la base de datos
try {
    $sql = "SELECT r.*, rc.ticket_price as committee_ticket_price, rc.commission_rate as committee_commission_rate
            FROM raffles r 
            LEFT JOIN raffle_committee rc ON r.id = rc.raffle_id AND rc.committee_id = ? AND rc.is_active = 1
            WHERE r.id = ?";
    $rifa_info = fetchOne($sql, [$current_admin['id'], $rifa_id]);
    
    if (!$rifa_info) {
        header('Location: panel.php');
        exit();
    }
    
    // Determinar precios actuales (del comité si existen, sino los originales)
    $current_ticket_price = $rifa_info['committee_ticket_price'] ?? $rifa_info['ticket_price'];
    $current_commission_rate = $rifa_info['committee_commission_rate'] ?? $rifa_info['commission_rate'];
    
} catch (Exception $e) {
    error_log("Error al obtener información de la rifa: " . $e->getMessage());
    header('Location: panel.php');
    exit();
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $ticket_price = floatval($_POST['ticket_price'] ?? 0);
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        
        // Validaciones
        if ($ticket_price <= 0) {
            throw new Exception('El precio del boleto debe ser mayor a 0');
        }
        
        if ($commission_rate < 0 || $commission_rate > 50) {
            throw new Exception('La comisión debe estar entre 0% y 50%');
        }

        // Iniciar transacción
        $pdo = getDB();
        $pdo->beginTransaction();

        try {
            // IMPORTANTE: NO modificamos la tabla raffles, solo raffle_committee
            // La tabla raffles mantiene los precios originales del admin
            
            // Verificar si ya existe un registro en raffle_committee para esta rifa y comité
            $committee_check = fetchOne(
                "SELECT id FROM raffle_committee WHERE raffle_id = ? AND committee_id = ? AND is_active = 1",
                [$rifa_id, $current_admin['id']]
            );

            if ($committee_check) {
                // Actualizar registro existente en raffle_committee
                $update_committee_sql = "UPDATE raffle_committee SET 
                                        ticket_price = ?, 
                                        commission_rate = ?, 
                                        original_price = ?, 
                                        updated_at = NOW() 
                                        WHERE id = ?";
                
                executeQuery($update_committee_sql, [
                    $ticket_price,
                    $commission_rate,
                    $rifa_info['ticket_price'], // Precio original del admin
                    $committee_check['id']
                ]);
            } else {
                // Crear nuevo registro en raffle_committee
                $insert_committee_sql = "INSERT INTO raffle_committee 
                                        (raffle_id, committee_id, ticket_price, commission_rate, original_price, is_active, created_at, updated_at) 
                                        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";
                
                executeQuery($insert_committee_sql, [
                    $rifa_id,
                    $current_admin['id'],
                    $ticket_price,
                    $commission_rate,
                    $rifa_info['ticket_price'] // Precio original del admin
                ]);
            }

            // Confirmar transacción
            $pdo->commit();

            // Actualizar variables locales para mostrar los cambios en la interfaz
            $current_ticket_price = $ticket_price;
            $current_commission_rate = $commission_rate;
            $rifa_info['committee_ticket_price'] = $ticket_price;
            $rifa_info['committee_commission_rate'] = $commission_rate;
            
            logAdminActivity('update_committee_pricing', "Actualizó precios del comité para rifa: {$rifa_info['name']} - Precio: $ticket_price, Comisión: {$commission_rate}% (Originales: {$rifa_info['ticket_price']}, {$rifa_info['commission_rate']}%)");
            
            $success_message = 'Configuración del comité actualizada correctamente. Los precios originales del admin se mantienen inalterados.';

        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error al actualizar configuración del comité: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Comité - <?php echo htmlspecialchars($rifa_info['name']); ?></title>
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

        .stat-card.stat-original { --card-color: #3b82f6; }
        .stat-card.stat-committee { --card-color: #10b981; }

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

        /* Content Section */
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

        .section-title i {
            color: #6b7280;
        }

        .section-description {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .section-content {
            padding: 2rem;
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

        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .alert-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-addon {
            font-weight: 600;
            color: #6b7280;
        }

        /* Comparison Cards */
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .comparison-card {
            background: #ffffff;
            border: 2px solid;
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
        }

        .comparison-card.original {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }

        .comparison-card.committee {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #d1fae5);
        }

        .comparison-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comparison-title.original {
            color: #1e40af;
        }

        .comparison-title.committee {
            color: #065f46;
        }

        .comparison-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .comparison-row:last-child {
            border-bottom: none;
        }

        .comparison-label {
            font-weight: 500;
            color: #374151;
        }

        .comparison-value {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .comparison-value.original {
            color: #1e40af;
        }

        .comparison-value.committee {
            color: #065f46;
        }

        /* Buttons */
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

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        /* Schema Info */
        .schema-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.8rem;
            color: #64748b;
        }

        .schema-info h5 {
            color: #334155;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .schema-info p {
            margin: 0.25rem 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .comparison-grid {
                grid-template-columns: 1fr;
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

            .section-content {
                padding: 1.5rem;
            }

            .form-buttons {
                flex-direction: column;
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
                        <span>Configuración</span>
                    </div>
                    <h1>Configuración del Comité</h1>
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
        <!-- Stats Cards - Comparación de Precios -->
        <div class="stats-grid">
            <div class="stat-card stat-original">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-title">Precio Original (Admin)</div>
                </div>
                <div class="stat-number">$<?php echo number_format($rifa_info['ticket_price'], 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-lock"></i>
                    Precio inmutable
                </div>
            </div>

            <div class="stat-card stat-committee">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Precio del Comité</div>
                </div>
                <div class="stat-number">$<?php echo number_format($current_ticket_price, 2); ?></div>
                <div class="stat-change">
                    <i class="fas fa-edit"></i>
                    Personalizable
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Info Alert -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle alert-icon"></i>
            <span>🔄 <strong>Sistema de Precios Independientes:</strong> Los cambios del comité NO afectan los precios originales del admin. Los precios originales se mantienen en la tabla "raffles" y tus personalizaciones se guardan en "raffle_committee".</span>
        </div>

        <!-- Comparación Visual -->
        <div class="comparison-grid">
            <div class="comparison-card original">
                <div class="comparison-title original">
                    <i class="fas fa-database"></i>
                    Tabla "raffles" (Admin)
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Precio del Boleto:</span>
                    <span class="comparison-value original">$<?php echo number_format($rifa_info['ticket_price'], 2); ?></span>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Comisión:</span>
                    <span class="comparison-value original"><?php echo number_format($rifa_info['commission_rate'], 1); ?>%</span>
                </div>
            </div>

            <div class="comparison-card committee">
                <div class="comparison-title committee">
                    <i class="fas fa-table"></i>
                    Tabla "raffle_committee" (Tu Config)
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Precio del Boleto:</span>
                    <span class="comparison-value committee">$<?php echo number_format($current_ticket_price, 2); ?></span>
                </div>
                <div class="comparison-row">
                    <span class="comparison-label">Comisión:</span>
                    <span class="comparison-value committee"><?php echo number_format($current_commission_rate, 1); ?>%</span>
                </div>
            </div>
        </div>

        <!-- Formulario de Configuración -->
        <div class="content-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        <i class="fas fa-dollar-sign"></i>
                        Configuración de Precios del Comité
                    </h2>
                    <p class="section-description">Personaliza los precios y comisiones sin afectar la configuración original del admin</p>
                </div>
            </div>

            <div class="section-content">
                <form method="POST" id="settingsForm">
                    <input type="hidden" name="update_settings" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="ticket_price">
                                <i class="fas fa-tag"></i> Precio del Boleto del Comité
                            </label>
                            <div class="input-group">
                                <span class="input-addon">$</span>
                                <input type="number" 
                                       id="ticket_price" 
                                       name="ticket_price" 
                                       class="form-input" 
                                       value="<?php echo $current_ticket_price; ?>" 
                                       min="0.01" 
                                       step="0.01" 
                                       required>
                            </div>
                            <small style="color: #64748b; font-size: 0.8rem;">
                                Precio original del admin: $<?php echo number_format($rifa_info['ticket_price'], 2); ?> (no se modificará)
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="commission_rate">
                                <i class="fas fa-percentage"></i> Comisión de Vendedores del Comité
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       id="commission_rate" 
                                       name="commission_rate" 
                                       class="form-input" 
                                       value="<?php echo $current_commission_rate; ?>" 
                                       min="0" 
                                       max="50" 
                                       step="0.1" 
                                       required>
                                <span class="input-addon">%</span>
                            </div>
                            <small style="color: #64748b; font-size: 0.8rem;">
                                Comisión original del admin: <?php echo number_format($rifa_info['commission_rate'], 1); ?>% (no se modificará)
                            </small>
                        </div>
                    </div>

                    <!-- Información técnica -->
                    <div class="schema-info">
                        <h5>🔧 Arquitectura de Datos</h5>
                        <p><strong>raffles.ticket_price:</strong> $<?php echo number_format($rifa_info['ticket_price'], 2); ?> (inmutable por comité)</p>
                        <p><strong>raffles.commission_rate:</strong> <?php echo number_format($rifa_info['commission_rate'], 1); ?>% (inmutable por comité)</p>
                        <p><strong>raffle_committee.ticket_price:</strong> $<?php echo number_format($current_ticket_price, 2); ?> (editable)</p>
                        <p><strong>raffle_committee.commission_rate:</strong> <?php echo number_format($current_commission_rate, 1); ?>% (editable)</p>
                    </div>

                    <!-- Botones -->
                    <div class="form-buttons">
                        <a href="panel.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Solo en Raffle_Committee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Preview de cambios sin afectar precios originales
        function previewPriceChange() {
            const newPrice = document.querySelector('input[name="ticket_price"]').value;
            const commission = document.querySelector('input[name="commission_rate"]').value;
            const originalPrice = <?php echo $rifa_info['ticket_price']; ?>;
            const originalCommission = <?php echo $rifa_info['commission_rate']; ?>;
            
            if (newPrice && commission) {
                const commissionAmount = (newPrice * commission / 100).toFixed(2);
                console.log(`Precios independientes:
                    Admin (original): $${originalPrice} (${originalCommission}%)
                    Comité (nuevo): $${newPrice} (${commission}%) - Comisión: $${commissionAmount}`);
            }
        }
        
        // Actualizar preview en tiempo real
        document.querySelector('input[name="ticket_price"]').addEventListener('input', previewPriceChange);
        document.querySelector('input[name="commission_rate"]').addEventListener('input', previewPriceChange);
        
        // Confirmar cambios importantes
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const originalPrice = <?php echo $rifa_info['ticket_price']; ?>;
            const originalCommission = <?php echo $rifa_info['commission_rate']; ?>;
            const newPrice = parseFloat(document.querySelector('input[name="ticket_price"]').value);
            const newCommission = parseFloat(document.querySelector('input[name="commission_rate"]').value);
            
            let changes = [];
            changes.push(`COMITÉ - Precio: $${newPrice} (Original admin: $${originalPrice})`);
            changes.push(`COMITÉ - Comisión: ${newCommission}% (Original admin: ${originalCommission}%)`);
            
            const confirm = window.confirm(
                `¿Confirmar cambios del comité?\n\n${changes.join('\n')}\n\n✅ Los precios originales del admin NO se modificarán\n✅ Cambios se guardan solo en raffle_committee`
            );
            
            if (!confirm) {
                e.preventDefault();
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