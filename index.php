<?php
// Include database functions
require_once './admin/process_admin_login.php';
require_once './config/database.php';

// Get next raffle for countdown
$next_raffle = null;
try {
    $sql = "SELECT draw_date FROM raffles 
            WHERE status = 'active' 
            AND draw_date > NOW() 
            ORDER BY draw_date ASC 
            LIMIT 1";
    $result = fetchOne($sql);
    $next_raffle = $result ? $result['draw_date'] : null;
} catch (Exception $e) {
    error_log("Error getting next raffle: " . $e->getMessage());
}

// Get some statistics for the hero section
$total_active_raffles = 0;
$total_prizes_value = 0;

try {
    $stats_sql = "SELECT 
                    COUNT(*) as active_count,
                    SUM(ticket_price * total_tickets) as total_value
                  FROM raffles 
                  WHERE status = 'active' 
                  AND draw_date > NOW()";
    $stats = fetchOne($stats_sql);
    $total_active_raffles = $stats['active_count'] ?? 0;
    $total_prizes_value = $stats['total_value'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rifas Online - Página Principal</title>
    <link rel="stylesheet" href="./assets/css/partials/navbar.css">
    <link rel="stylesheet" href="./assets/css/index.css">
    <link rel="stylesheet" href="./assets/css/partials/footer.css">
    <script>
        // Pass PHP data to JavaScript
        window.APP_DATA = {
            nextRaffleDate: <?php echo $next_raffle ? "'" . $next_raffle . "'" : 'null'; ?>,
            totalActiveRaffles: <?php echo $total_active_raffles; ?>,
            totalPrizesValue: <?php echo $total_prizes_value; ?>,
            apiUrl: './api/get_raffles.php'
        };
    </script>
</head>
<body>
    <!-- Include Navbar -->
    <?php include './partials/navbar.php'; ?>

    <!-- Hero Section Container -->
    <div class="hero-container">
        <section class="hero-section">
            <div class="hero-video-container">
                <iframe 
                    src="https://www.youtube.com/embed/wgsPrKTzm4w?autoplay=1&mute=1&loop=1&playlist=wgsPrKTzm4w&controls=0&showinfo=0&rel=0&iv_load_policy=3&modestbranding=1&fs=0&disablekb=1&cc_load_policy=0&playsinline=1&widget_referrer=domain.com"
                    frameborder="0" 
                    allow="autoplay; encrypted-media" 
                    allowfullscreen
                    class="hero-video">
                </iframe>
                <div class="video-overlay"></div>
            </div>
            <div class="hero-content">
                <h1 class="hero-title">Gana Increíbles Premios</h1>
                <p class="hero-subtitle">
                    Participa en nuestras <?php echo $total_active_raffles; ?> rifas activas y ten la oportunidad de ganar productos increíbles.
                    Sorteos transparentes, seguros y con premios garantizados por valor de $<?php echo number_format($total_prizes_value, 2); ?>.
                </p>
                <button class="hero-button" onclick="handleHeroClick()">
                    Participar Ahora
                </button>
            </div>
        </section>
    </div>

    <!-- Cómo Participar Section -->
    <div class="how-to-participate-container">
        <h2 class="how-to-title">¿CÓMO PARTICIPAR?</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4m-8 0V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-6 0h6m-5-3v3m2-3v3"/>
                    </svg>
                </div>
                <h3 class="step-title">Regístrate</h3>
                <p class="step-description">Crea tu cuenta de forma gratuita y accede a todas las rifas disponibles</p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h3 class="step-title">Elige tu Rifa</h3>
                <p class="step-description">Selecciona entre los increíbles premios disponibles y elige tus números favoritos</p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <h3 class="step-title">Realiza el Pago</h3>
                <p class="step-description">Paga de forma segura con tarjeta, transferencia o métodos digitales</p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                </div>
                <h3 class="step-title">¡Espera el Sorteo!</h3>
                <p class="step-description">Recibe tu comprobante y espera la fecha del sorteo para conocer al ganador</p>
            </div>
        </div>
    </div>

    <!-- Countdown Timer Section -->
    <div class="countdown-container">
        <h2 class="countdown-title">PRÓXIMO SORTEO</h2>
        <div class="countdown-timer">
            <div class="countdown-item">
                <div class="countdown-circle">
                    <svg class="countdown-svg" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient-days" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00f5ff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#00ff88;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle class="countdown-bg" cx="60" cy="60" r="54"></circle>
                        <circle class="countdown-progress" cx="60" cy="60" r="54" id="days-progress" stroke="url(#gradient-days)"></circle>
                    </svg>
                    <div class="countdown-number" id="days">00</div>
                </div>
                <div class="countdown-label">DÍAS</div>
            </div>
            
            <div class="countdown-item">
                <div class="countdown-circle">
                    <svg class="countdown-svg" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient-hours" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00f5ff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#00ff88;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle class="countdown-bg" cx="60" cy="60" r="54"></circle>
                        <circle class="countdown-progress" cx="60" cy="60" r="54" id="hours-progress" stroke="url(#gradient-hours)"></circle>
                    </svg>
                    <div class="countdown-number" id="hours">00</div>
                </div>
                <div class="countdown-label">HORAS</div>
            </div>
            
            <div class="countdown-item">
                <div class="countdown-circle">
                    <svg class="countdown-svg" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient-minutes" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00f5ff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#00ff88;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle class="countdown-bg" cx="60" cy="60" r="54"></circle>
                        <circle class="countdown-progress" cx="60" cy="60" r="54" id="minutes-progress" stroke="url(#gradient-minutes)"></circle>
                    </svg>
                    <div class="countdown-number" id="minutes">00</div>
                </div>
                <div class="countdown-label">MINUTOS</div>
            </div>
            
            <div class="countdown-item">
                <div class="countdown-circle">
                    <svg class="countdown-svg" viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient-seconds" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#00f5ff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#00ff88;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle class="countdown-bg" cx="60" cy="60" r="54"></circle>
                        <circle class="countdown-progress" cx="60" cy="60" r="54" id="seconds-progress" stroke="url(#gradient-seconds)"></circle>
                    </svg>
                    <div class="countdown-number" id="seconds">00</div>
                </div>
                <div class="countdown-label">SEGUNDOS</div>
            </div>
        </div>
        
        <?php if (!$next_raffle): ?>
        <div style="text-align: center; margin-top: 2rem; color: rgba(255, 255, 255, 0.8);">
            <p>No hay sorteos programados en este momento</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cómo se Selecciona Section -->
    <div class="how-selection-works-container">
        <h2 class="selection-works-title">¿CÓMO SE SELECCIONA EL GANADOR?</h2>
        <div class="selection-process">
            <div class="process-content">
                <div class="process-text">
                    <h3>Proceso 100% Transparente</h3>
                    <p>Nuestros sorteos se realizan de forma completamente transparente y verificable:</p>
                    <ul class="process-list">
                        <li>
                            <svg class="list-icon" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><strong>Transmisión en vivo:</strong> Todos los sorteos son transmitidos en tiempo real</span>
                        </li>
                        <li>
                            <svg class="list-icon" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><strong>Sistema aleatorio:</strong> Utilizamos generadores certificados de números aleatorios</span>
                        </li>
                        <li>
                            <svg class="list-icon" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><strong>Verificación externa:</strong> Proceso supervisado por entidades independientes</span>
                        </li>
                        <li>
                            <svg class="list-icon" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><strong>Notificación inmediata:</strong> Los ganadores son contactados al instante</span>
                        </li>
                    </ul>
                </div>
                <div class="process-visual">
                    <div class="lottery-ball">
                        <div class="ball-inner">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27,6.96 12,12.01 20.73,6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rifas Section -->
    <div class="rifas-container">
        <h2 class="rifas-title">RIFAS DISPONIBLES</h2>
        <div id="raffles-loading" style="text-align: center; padding: 3rem; color: #667eea;">
            <div style="display: inline-block; width: 50px; height: 50px; border: 4px solid #e2e8f0; border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem;"></div>
            <p>Cargando rifas disponibles...</p>
        </div>
        <div class="rifas-slider-wrapper" style="display: none;">
            <div class="rifas-slider" id="rifasSlider">
                <!-- Las rifas se generarán dinámicamente con JavaScript -->
            </div>
        </div>
        <div id="no-raffles-message" style="display: none; text-align: center; padding: 3rem; color: #666;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <h3 style="margin-bottom: 0.5rem;">No hay rifas activas</h3>
            <p>Las rifas aparecerán aquí cuando estén disponibles</p>
        </div>
    </div>

    <!-- Paquetes de Boletos Section -->
    <div class="packages-container">
        <h2 class="packages-title">PAQUETES DE BOLETOS</h2>
        <p class="packages-subtitle">Compra boletos en paquetes y ahorra más</p>
        <div class="packages-grid">
            <div class="package-card" data-package="1000">
                <div class="package-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h3 class="package-title">Paquete 1</h3>
                <div class="package-price">
                    <span class="package-currency">$</span>
                    <span class="package-amount">299</span>
                    <span class="package-period">.99</span>
                </div>
                <p style="color: #666; margin-bottom: 2rem;">1,000 boletos incluidos</p>
                <button class="package-button" onclick="buyPackage(1000, 299.99)">
                    Comprar Paquete
                </button>
            </div>

            <div class="package-card popular" data-package="2000">
                <div class="package-badge popular-badge">Popular</div>
                <div class="package-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
                    </svg>
                </div>
                <h3 class="package-title">Paquete 2</h3>
                <div class="package-price">
                    <span class="package-currency">$</span>
                    <span class="package-amount">549</span>
                    <span class="package-period">.99</span>
                </div>
                <p style="color: #666; margin-bottom: 2rem;">2,000 boletos incluidos</p>
                <button class="package-button" onclick="buyPackage(2000, 549.99)">
                    Comprar Paquete
                </button>
            </div>

            <div class="package-card" data-package="3000">
                <div class="package-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                </div>
                <h3 class="package-title">Paquete 3</h3>
                <div class="package-price">
                    <span class="package-currency">$</span>
                    <span class="package-amount">799</span>
                    <span class="package-period">.99</span>
                </div>
                <p style="color: #666; margin-bottom: 2rem;">3,000 boletos incluidos</p>
                <button class="package-button" onclick="buyPackage(3000, 799.99)">
                    Comprar Paquete
                </button>
            </div>
        </div>
    </div>

    <!-- Seleccionar Boletos Section -->
    <div class="ticket-selection-container">
        <h2 class="selection-title">SELECCIONA TUS BOLETOS</h2>
        <p class="selection-subtitle">Elige los números de tu preferencia</p>
        
        <!-- Selector de Rifa -->
        <div class="raffle-selector-container">
            <div class="raffle-selector-card">
                <h3 class="selector-title">Selecciona una Rifa</h3>
                <div class="raffle-dropdown-container">
                    <select id="raffleSelector" class="raffle-dropdown" onchange="onRaffleChange()">
                        <option value="">Cargando rifas disponibles...</option>
                    </select>
                    <div class="selected-raffle-info" id="selectedRaffleInfo" style="display: none;">
                        <div class="raffle-info-item">
                            <span class="info-label">Precio por boleto:</span>
                            <span class="info-value" id="selectedRafflePrice">$0.00</span>
                        </div>
                        <div class="raffle-info-item">
                            <span class="info-label">Total de boletos:</span>
                            <span class="info-value" id="selectedRaffleTotal">0</span>
                        </div>
                        <div class="raffle-info-item">
                            <span class="info-label">Boletos vendidos:</span>
                            <span class="info-value" id="selectedRaffleSold">0</span>
                        </div>
                        <div class="raffle-info-item">
                            <span class="info-label">Disponibles:</span>
                            <span class="info-value" id="selectedRaffleAvailable">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="selection-layout">
            <!-- Grid de Boletos -->
            <div class="tickets-grid-container">
                <div class="tickets-legend">
                    <div class="legend-item">
                        <div class="legend-ticket available"></div>
                        <span>Disponible</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-ticket taken"></div>
                        <span>No disponible</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-ticket selected"></div>
                        <span>Seleccionado</span>
                    </div>
                </div>
                <div class="ticket-selection-message" id="ticketSelectionMessage">
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <h3 style="margin-bottom: 0.5rem;">Selecciona una rifa</h3>
                        <p>Elige una rifa del menú superior para ver los boletos disponibles</p>
                    </div>
                </div>
                <div class="tickets-grid" id="ticketsGrid" style="display: none;">
                    <!-- Los boletos se generarán dinámicamente -->
                </div>
            </div>

            <!-- Sidebar de Resumen -->
            <div class="selection-sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Tu Selección</h3>
                    
                    <!-- Compra Manual -->
                    <div class="manual-selection">
                        <label class="manual-label">Cantidad de boletos</label>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                            <input type="number" id="manualQuantity" class="quantity-input" value="0" min="0" max="100" oninput="updateManualQuantity()">
                            <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                        </div>
                        <button class="add-random-btn" onclick="addRandomTickets()">
                            Agregar Aleatorios
                        </button>
                    </div>

                    <!-- Lista de Boletos Seleccionados -->
                    <div class="selected-tickets-section">
                        <h4 class="selected-title">Boletos Seleccionados</h4>
                        <div class="selected-tickets-list" id="selectedTicketsList">
                            <p class="no-tickets">No has seleccionado ningún boleto</p>
                        </div>
                    </div>

                    <!-- Resumen de Pago -->
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Total de boletos:</span>
                            <span id="totalTickets">0</span>
                        </div>
                        <div class="summary-row">
                            <span>Precio por boleto:</span>
                            <span id="ticketPrice">$5.00</span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total a pagar:</span>
                            <span id="totalAmount">$0.00</span>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="action-buttons">
                        <button class="clear-btn" onclick="clearSelection()">
                            Limpiar Selección
                        </button>
                        <button class="checkout-btn" onclick="proceedToPayment()">
                            Proceder al Pago
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="contact-container" id="contacto">
        <h2 class="contact-title">CONTÁCTANOS</h2>
        <p class="contact-subtitle">¿Tienes dudas? Estamos aquí para ayudarte</p>
        
        <div class="contact-layout">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Dirección</h4>
                        <p>Av. Principal 123<br>Ciudad, Estado 12345</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Teléfono</h4>
                        <p>+52 (662) 123-4567<br>Lun - Vie: 9:00 - 18:00</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="contact-details">
                        <h4>Email</h4>
                        <p>info@rifas.com<br>soporte@rifas.com</p>
                    </div>
                </div>
            </div>

            <div class="contact-form-container">
                <form class="contact-form" onsubmit="submitContactForm(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">Nombre</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Apellido</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Asunto</label>
                        <select id="subject" name="subject" required>
                            <option value="">Selecciona un asunto</option>
                            <option value="info">Información general</option>
                            <option value="soporte">Soporte técnico</option>
                            <option value="pagos">Problemas con pagos</option>
                            <option value="sorteos">Consultas sobre sorteos</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Mensaje</label>
                        <textarea id="message" name="message" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22,2 15,22 11,13 2,9 22,2"/>
                        </svg>
                        Enviar Mensaje
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- Load Scripts -->
    <style>
        .raffle-selector-container {
            max-width: 1400px;
            margin: 0 auto 3rem;
            padding: 0 2rem;
        }
        
        .raffle-selector-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .selector-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .raffle-dropdown-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            align-items: start;
        }
        
        .raffle-dropdown {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 15px;
            font-size: 1rem;
            background: #f8fafc;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .raffle-dropdown:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .raffle-dropdown:hover {
            border-color: #667eea;
            background: #ffffff;
        }
        
        .selected-raffle-info {
            background: #f8fafc;
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .raffle-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .raffle-info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .raffle-dropdown-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .raffle-selector-container {
                padding: 0 1rem;
            }
            
            .raffle-selector-card {
                padding: 1.5rem;
            }
        }
        
        .raffle-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .raffle-status.available {
            background: linear-gradient(135deg, #dcfdf7, #a7f3d0);
            color: #059669;
        }
        
        .raffle-status.limited {
            background: linear-gradient(135deg, #fef3c7, #fbbf24);
            color: #d97706;
        }
        
        .raffle-status.sold-out {
            background: linear-gradient(135deg, #fecaca, #ef4444);
            color: #dc2626;
        }
        
        .raffle-progress {
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
    </style>
    <script src="./assets/js/partials/navbar.js"></script>
    <script src="./assets/js/index.js"></script>
</body>
</html>
