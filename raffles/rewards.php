<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premios - Rifas Online</title>
    <link rel="stylesheet" href="../assets/css/partials/navbar.css">
    <link rel="stylesheet" href="../assets/css/raffles/rewards.css">
    <link rel="stylesheet" href="../assets/css/partials/footer.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include '../partials/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="rewards-hero">
        <div class="hero-content">
            <h1 class="hero-title">Increíbles Premios</h1>
            <p class="hero-subtitle">Descubre todos los premios disponibles y participa por el que más te guste</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-container">
        <div class="filters-content">
            <h2 class="filters-title">Filtrar Premios</h2>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="electronics">Electrónicos</button>
                <button class="filter-btn" data-filter="vehicles">Vehículos</button>
                <button class="filter-btn" data-filter="cash">Dinero</button>
                <button class="filter-btn" data-filter="other">Otros</button>
            </div>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Buscar premio..." class="search-input">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Rewards Grid Section -->
    <div class="rewards-container">
        <div class="rewards-grid" id="rewardsGrid">
            <div class="reward-card" data-category="electronics">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=400&h=250&fit=crop&crop=center" alt="MacBook Pro" class="reward-image">
                    <div class="reward-badge">Electrónicos</div>
                    <div class="reward-value">$2,500</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">MacBook Pro 16"</h3>
                    <p class="reward-description">MacBook Pro con chip M3 Pro, 16GB RAM, 512GB SSD. La herramienta perfecta para profesionales creativos.</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 15 Febrero 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>850 / 1000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$25</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(1, 'MacBook Pro 16', 25)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>

            <div class="reward-card" data-category="vehicles">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=400&h=250&fit=crop&crop=center" alt="Motocicleta Deportiva" class="reward-image">
                    <div class="reward-badge">Vehículos</div>
                    <div class="reward-value">$15,000</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">Motocicleta Deportiva</h3>
                    <p class="reward-description">Motocicleta deportiva de alta cilindrada, perfecta para los amantes de la velocidad y la adrenalina.</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 28 Febrero 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>1,250 / 2000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 62.5%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$50</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(2, 'Motocicleta Deportiva', 50)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>

            <div class="reward-card" data-category="cash">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&h=250&fit=crop&crop=center" alt="Premio en Efectivo" class="reward-image">
                    <div class="reward-badge">Efectivo</div>
                    <div class="reward-value">$10,000</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">$10,000 en Efectivo</h3>
                    <p class="reward-description">Premio en efectivo para que puedas usarlo en lo que más necesites o desees. ¡La libertad de elegir!</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 10 Marzo 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>890 / 1500</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 59.3%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$35</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(3, '$10,000 en Efectivo', 35)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>

            <div class="reward-card" data-category="electronics">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400&h=250&fit=crop&crop=center" alt="iPhone 15 Pro Max" class="reward-image">
                    <div class="reward-badge">Electrónicos</div>
                    <div class="reward-value">$1,200</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">iPhone 15 Pro Max</h3>
                    <p class="reward-description">El último iPhone con cámara profesional, chip A17 Pro y pantalla Super Retina XDR de 6.7 pulgadas.</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 20 Marzo 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>430 / 800</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 53.7%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$20</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(4, 'iPhone 15 Pro Max', 20)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>

            <div class="reward-card" data-category="other">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1546026423-cc4642628d2b?w=400&h=250&fit=crop&crop=center" alt="Viaje a Europa" class="reward-image">
                    <div class="reward-badge">Viajes</div>
                    <div class="reward-value">$5,000</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">Viaje a Europa</h3>
                    <p class="reward-description">Viaje todo incluido para 2 personas a Europa por 10 días. Incluye vuelos, hoteles y tours guiados.</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 5 Abril 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>320 / 1000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 32%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$30</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(5, 'Viaje a Europa', 30)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>

            <div class="reward-card" data-category="vehicles">
                <div class="reward-image-container">
                    <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400&h=250&fit=crop&crop=center" alt="Automóvil Sedán" class="reward-image">
                    <div class="reward-badge">Vehículos</div>
                    <div class="reward-value">$25,000</div>
                </div>
                <div class="reward-content">
                    <h3 class="reward-name">Automóvil Sedán 2025</h3>
                    <p class="reward-description">Automóvil sedán modelo 2025, completamente equipado con tecnología de última generación y garantía completa.</p>
                    <div class="reward-details">
                        <div class="reward-date">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Sorteo: 15 Abril 2025
                        </div>
                        <div class="reward-progress">
                            <div class="progress-text">
                                <span>Boletos vendidos</span>
                                <span>1,890 / 3000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 63%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="reward-footer">
                        <div class="reward-price">
                            <div class="price-label">Precio por boleto</div>
                            <div class="price">$75</div>
                        </div>
                        <button class="buy-btn" onclick="selectRaffle(6, 'Automóvil Sedán 2025', 75)">
                            Comprar Boletos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../partials/footer.php'; ?>

    <!-- Load Scripts -->
    <script src="../assets/js/partials/navbar.js"></script>
    <script src="../assets/js/raffles/rewards.js"></script>
    <script>
        // Funciones de filtrado
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.reward-card');
            const searchInput = document.getElementById('searchInput');

            // Filtrado por categorías
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const filter = btn.dataset.filter;
                    
                    // Actualizar botón activo
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // Filtrar tarjetas
                    cards.forEach(card => {
                        if (filter === 'all' || card.dataset.category === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Búsqueda por texto
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                cards.forEach(card => {
                    const title = card.querySelector('.reward-name').textContent.toLowerCase();
                    const description = card.querySelector('.reward-description').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Función para seleccionar rifa (compatible con index.php)
        function selectRaffle(raffleId, raffleName, ticketPrice) {
            // Guardar datos en localStorage para usar en index.php
            localStorage.setItem('selectedRaffle', JSON.stringify({
                id: raffleId,
                name: raffleName,
                price: ticketPrice
            }));
            
            // Redirigir a la página principal con hash
            window.location.href = window.BASE_URL + 'index.php#seleccion';
        }
    </script>
</body>
</html>