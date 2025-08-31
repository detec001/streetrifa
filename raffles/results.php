<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/partials/navbar.css">
    <link rel="stylesheet" href="../assets/css/raffles/results.css">
    <link rel="stylesheet" href="../assets/css/partials/footer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../partials/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="results-hero">
        <div class="hero-content">
            <h1 class="hero-title">Resultados de Sorteos</h1>
            <p class="hero-subtitle">Consulta los ganadores de nuestros sorteos anteriores y verifica la transparencia de nuestro proceso</p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">247</div>
                    <div class="stat-label">Sorteos Realizados</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <path d="M20 8v6"/>
                        <path d="M23 11h-6"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">15,432</div>
                    <div class="stat-label">Ganadores Felices</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">$2.4M</div>
                    <div class="stat-label">En Premios Entregados</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number">98.7%</div>
                    <div class="stat-label">Satisfacción del Cliente</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-container">
        <div class="filters-content">
            <h2 class="filters-title">Filtrar Resultados</h2>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="monthFilter">Mes:</label>
                    <select id="monthFilter" class="filter-select">
                        <option value="all">Todos los meses</option>
                        <option value="2025-01">Enero 2025</option>
                        <option value="2024-12">Diciembre 2024</option>
                        <option value="2024-11">Noviembre 2024</option>
                        <option value="2024-10">Octubre 2024</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="prizeFilter">Tipo de Premio:</label>
                    <select id="prizeFilter" class="filter-select">
                        <option value="all">Todos</option>
                        <option value="electronics">Electrónicos</option>
                        <option value="vehicles">Vehículos</option>
                        <option value="cash">Dinero</option>
                        <option value="other">Otros</option>
                    </select>
                </div>

                <div class="search-group">
                    <input type="text" id="searchInput" placeholder="Buscar por ganador o premio..." class="search-input">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Winner Section -->
    <div class="latest-winner-container">
        <div class="latest-winner-content">
            <h2 class="section-title">🎉 Último Ganador</h2>
            <div class="winner-spotlight" id="latestWinner">
                <!-- Se generará dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="results-container">
        <h2 class="section-title">Historial de Resultados</h2>
        <div class="results-timeline" id="resultsTimeline">
            <!-- Los resultados se generarán dinámicamente -->
        </div>
        
        <div class="load-more-container">
            <button class="load-more-btn" onclick="loadMoreResults()">
                Cargar Más Resultados
            </button>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../partials/footer.php'; ?>

    <!-- Load Scripts -->
    <script src="../assets/js/partials/navbar.js"></script>
    <script src="../assets/js/raffles/results.js"></script>
</body>
</html>