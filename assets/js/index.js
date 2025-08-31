/**
 * Index Page JavaScript - Real-time Version
 * 
 * Handles all functionality for the main index page including:
 * - Real-time raffle data loading
 * - Countdown timer with real dates
 * - Ticket selection
 * - Contact form
 * - Package purchasing
 */

class IndexController {
    constructor() {
        this.selectedTickets = [];
        this.takenTickets = [];
        this.currentRaffle = null;
        this.ticketPrice = 5.00; // Default price, will be updated when a raffle is selected
        this.countdownInterval = null;
        this.rafflesData = [];
        this.refreshInterval = null;
        
        this.init();
    }

    /**
     * Initialize all index page functionality
     */
    init() {
        this.initializeNavbar();
        this.initCountdown();
        this.loadRaffles();
        this.initTicketSelection();
        this.startAutoRefresh();
    }

    /**
     * Initialize navbar if available
     */
    initializeNavbar() {
        if (typeof NavbarController !== 'undefined') {
            new NavbarController();
        }
    }

    /**
     * Load raffles from the API
     */
    async loadRaffles() {
        try {
            const loadingElement = document.getElementById('raffles-loading');
            const sliderWrapper = document.querySelector('.rifas-slider-wrapper');
            const noRafflesMessage = document.getElementById('no-raffles-message');
            
            if (loadingElement) loadingElement.style.display = 'block';
            if (sliderWrapper) sliderWrapper.style.display = 'none';
            if (noRafflesMessage) noRafflesMessage.style.display = 'none';

            const response = await fetch(window.APP_DATA?.apiUrl || './api/get_raffles.php');
            const data = await response.json();

            if (data.success && data.data) {
                this.rafflesData = data.data;
                if (this.rafflesData.length > 0) {
                    this.initRifasSlider();
                    this.populateRaffleSelector(); // Populate raffle selector
                    if (loadingElement) loadingElement.style.display = 'none';
                    if (sliderWrapper) sliderWrapper.style.display = 'block';
                } else {
                    if (loadingElement) loadingElement.style.display = 'none';
                    if (noRafflesMessage) noRafflesMessage.style.display = 'block';
                }
            } else {
                console.error('Error loading raffles:', data.error || 'Unknown error');
                this.showFallbackRaffles();
            }
        } catch (error) {
            console.error('Error fetching raffles:', error);
            this.showFallbackRaffles();
        }
    }

    /**
     * Show fallback message when raffles can't be loaded
     */
    showFallbackRaffles() {
        const loadingElement = document.getElementById('raffles-loading');
        const noRafflesMessage = document.getElementById('no-raffles-message');
        
        if (loadingElement) loadingElement.style.display = 'none';
        if (noRafflesMessage) {
            noRafflesMessage.style.display = 'block';
            noRafflesMessage.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: #ef4444;">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 1rem; opacity: 0.5;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="m15 9-6 6"/>
                        <path d="m9 9 6 6"/>
                    </svg>
                    <h3 style="margin-bottom: 0.5rem;">Error al cargar rifas</h3>
                    <p>No se pudieron cargar las rifas. Intenta recargar la página.</p>
                    <button onclick="indexController.loadRaffles()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }

    /**
     * Start auto-refresh for raffles
     */
    startAutoRefresh() {
        // Refresh raffles every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadRaffles();
        }, 30000);
    }

    /**
     * Initialize countdown timer using real raffle date
     */
    initCountdown() {
        let targetDate = null;

        // Try to use the next raffle date from PHP
        if (window.APP_DATA?.nextRaffleDate) {
            targetDate = new Date(window.APP_DATA.nextRaffleDate);
        } else {
            // Fallback: Set target date 7 days from now
            targetDate = new Date();
            targetDate.setDate(targetDate.getDate() + 7);
            targetDate.setHours(targetDate.getHours() + 15);
            targetDate.setMinutes(targetDate.getMinutes() + 49);
            targetDate.setSeconds(targetDate.getSeconds() + 28);
        }

        const updateCountdown = () => {
            const now = new Date().getTime();
            const timeLeft = targetDate.getTime() - now;

            if (timeLeft <= 0) {
                this.handleCountdownExpired();
                return;
            }

            const timeUnits = this.calculateTimeUnits(timeLeft);
            this.updateCountdownDisplay(timeUnits);
            this.updateProgressCircles(timeUnits);
        };

        // Update countdown every second
        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    /**
     * Calculate time units from milliseconds
     */
    calculateTimeUnits(timeLeft) {
        return {
            days: Math.floor(timeLeft / (1000 * 60 * 60 * 24)),
            hours: Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            minutes: Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60)),
            seconds: Math.floor((timeLeft % (1000 * 60)) / 1000)
        };
    }

    /**
     * Update countdown display elements
     */
    updateCountdownDisplay(timeUnits) {
        const elements = {
            days: document.getElementById('days'),
            hours: document.getElementById('hours'),
            minutes: document.getElementById('minutes'),
            seconds: document.getElementById('seconds')
        };

        Object.keys(elements).forEach(key => {
            if (elements[key]) {
                elements[key].textContent = timeUnits[key].toString().padStart(2, '0');
            }
        });
    }

    /**
     * Update progress circles
     */
    updateProgressCircles(timeUnits) {
        this.updateProgress('days-progress', timeUnits.days / 30); // Assuming max 30 days
        this.updateProgress('hours-progress', timeUnits.hours / 24);
        this.updateProgress('minutes-progress', timeUnits.minutes / 60);
        this.updateProgress('seconds-progress', timeUnits.seconds / 60);
    }

    /**
     * Update individual progress circle
     */
    updateProgress(elementId, progress) {
        const circle = document.getElementById(elementId);
        if (!circle) return;

        const circumference = 2 * Math.PI * 54; // radius = 54
        const offset = circumference - (progress * circumference);
        
        circle.style.strokeDasharray = circumference;
        circle.style.strokeDashoffset = offset;
    }

    /**
     * Handle countdown expiration
     */
    handleCountdownExpired() {
        const elements = ['days', 'hours', 'minutes', 'seconds'];
        const progressElements = ['days-progress', 'hours-progress', 'minutes-progress', 'seconds-progress'];
        
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.textContent = '00';
        });

        progressElements.forEach(id => {
            this.updateProgress(id, 0);
        });

        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        // Reload raffles to get the next one
        this.loadRaffles();
    }

    /**
     * Initialize rifas slider with real data
     */
    initRifasSlider() {
        const slider = document.getElementById('rifasSlider');
        if (!slider || !this.rafflesData.length) return;

        let cardsData = [...this.rafflesData];
        
        // Only duplicate if we have multiple raffles for smooth scrolling
        // If we only have 1-2 raffles, just repeat them to fill the space
        if (this.rafflesData.length === 1) {
            cardsData = Array(8).fill(this.rafflesData[0]); // Show same raffle 8 times for visual effect
        } else if (this.rafflesData.length === 2) {
            cardsData = [...this.rafflesData, ...this.rafflesData, ...this.rafflesData, ...this.rafflesData];
        } else if (this.rafflesData.length < 6) {
            cardsData = [...this.rafflesData, ...this.rafflesData]; // Duplicate for seamless scroll
        }
        
        slider.innerHTML = cardsData.map((rifa, index) => this.createRifaCard(rifa, index)).join('');
    }

    /**
     * Create HTML for a rifa card with real data
     */
    createRifaCard(rifa, index = 0) {
        // Determine status
        let statusClass = 'available';
        let statusText = 'Disponible';
        
        if (rifa.available_tickets === 0) {
            statusClass = 'sold-out';
            statusText = 'Agotada';
        } else if (rifa.progress_percentage > 80) {
            statusClass = 'limited';
            statusText = 'Últimos boletos';
        }

        // Use the image from the API, with a fallback
        let imageUrl = rifa.image;
        
        // If it's a default placeholder, try to use a better fallback based on the raffle name
        if (imageUrl.includes('unsplash.com') && rifa.name.toLowerCase().includes('moto')) {
            imageUrl = 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=150&h=150&fit=crop&crop=center';
        }

        return `
            <div class="rifa-card" data-id="${rifa.id}" data-price="${rifa.price_value}" data-index="${index}">
                <img src="${imageUrl}" 
                     alt="${rifa.name}" 
                     class="rifa-image" 
                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=150&h=150&fit=crop&crop=center';">
                <div class="rifa-content">
                    <h3 class="rifa-name">${rifa.name}</h3>
                    <div class="rifa-date">
                        <svg class="date-icon" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                        ${rifa.date}
                    </div>
                    <p class="rifa-price">${rifa.price}</p>
                    <div class="raffle-status ${statusClass}">${statusText}</div>
                    ${rifa.progress_percentage > 0 ? `
                        <div class="raffle-progress">
                            <div class="progress-text">${rifa.sold_tickets.toLocaleString()} / ${rifa.total_tickets.toLocaleString()} vendidos (${rifa.progress_percentage}%)</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${rifa.progress_percentage}%"></div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                <div class="rifa-actions">
                    <button class="buy-button" 
                            onclick="selectRaffle(${rifa.id}, '${rifa.name.replace(/'/g, "\\'")}', ${rifa.price_value})"
                            ${rifa.available_tickets === 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>
                        ${rifa.available_tickets === 0 ? 'Agotada' : 'Seleccionar'}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Populate the raffle selector dropdown
     */
    populateRaffleSelector() {
        const selector = document.getElementById('raffleSelector');
        if (!selector || !this.rafflesData.length) return;

        // Clear existing options
        selector.innerHTML = '<option value="">Selecciona una rifa...</option>';

        // Add raffle options
        this.rafflesData.forEach(raffle => {
            const option = document.createElement('option');
            option.value = raffle.id;
            option.textContent = `${raffle.name} - ${raffle.price} (${raffle.available_tickets.toLocaleString()} disponibles)`;
            selector.appendChild(option);
        });
    }

    /**
     * Handle raffle selection change
     */
    onRaffleChange(raffleId) {
        if (!raffleId) {
            // No raffle selected - show message
            this.currentRaffle = null;
            this.showTicketSelectionMessage();
            this.hideSelectedRaffleInfo();
            this.clearAllSelectedTickets();
            return;
        }

        // Find the selected raffle
        const raffle = this.rafflesData.find(r => r.id == raffleId);
        if (!raffle) return;

        // Update current raffle
        this.currentRaffle = raffle;
        this.ticketPrice = raffle.price_value;

        // Update raffle info display
        this.updateSelectedRaffleInfo(raffle);
        this.showSelectedRaffleInfo();

        // Clear previous selections
        this.clearAllSelectedTickets();

        // Generate tickets for this raffle
        this.generateTakenTicketsFromRaffle(raffle);
        this.generateTicketsGrid();
        
        // Show tickets grid
        this.showTicketsGrid();

        // Update price display in sidebar
        const ticketPriceElement = document.getElementById('ticketPrice');
        if (ticketPriceElement) {
            ticketPriceElement.textContent = raffle.price;
        }

        // Update summary
        this.updateSummary();
    }

    /**
     * Update selected raffle info display
     */
    updateSelectedRaffleInfo(raffle) {
        const elements = {
            selectedRafflePrice: document.getElementById('selectedRafflePrice'),
            selectedRaffleTotal: document.getElementById('selectedRaffleTotal'),
            selectedRaffleSold: document.getElementById('selectedRaffleSold'),
            selectedRaffleAvailable: document.getElementById('selectedRaffleAvailable')
        };

        if (elements.selectedRafflePrice) {
            elements.selectedRafflePrice.textContent = raffle.price;
        }
        if (elements.selectedRaffleTotal) {
            elements.selectedRaffleTotal.textContent = raffle.total_tickets.toLocaleString();
        }
        if (elements.selectedRaffleSold) {
            elements.selectedRaffleSold.textContent = raffle.sold_tickets.toLocaleString();
        }
        if (elements.selectedRaffleAvailable) {
            elements.selectedRaffleAvailable.textContent = raffle.available_tickets.toLocaleString();
        }
    }

    /**
     * Show selected raffle info
     */
    showSelectedRaffleInfo() {
        const info = document.getElementById('selectedRaffleInfo');
        if (info) info.style.display = 'block';
    }

    /**
     * Hide selected raffle info
     */
    hideSelectedRaffleInfo() {
        const info = document.getElementById('selectedRaffleInfo');
        if (info) info.style.display = 'none';
    }

    /**
     * Show ticket selection message
     */
    showTicketSelectionMessage() {
        const message = document.getElementById('ticketSelectionMessage');
        const grid = document.getElementById('ticketsGrid');
        
        if (message) message.style.display = 'block';
        if (grid) grid.style.display = 'none';
    }

    /**
     * Show tickets grid
     */
    showTicketsGrid() {
        const message = document.getElementById('ticketSelectionMessage');
        const grid = document.getElementById('ticketsGrid');
        
        if (message) message.style.display = 'none';
        if (grid) grid.style.display = 'grid';
    }

    /**
     * Clear all selected tickets without confirmation
     */
    clearAllSelectedTickets() {
        // Reset all selected tickets to available
        this.selectedTickets.forEach(ticket => {
            const ticketElement = document.querySelector(`[data-ticket="${ticket}"]`);
            if (ticketElement) {
                ticketElement.classList.remove('selected');
                ticketElement.classList.add('available');
            }
        });

        this.selectedTickets = [];
        
        const manualQuantityInput = document.getElementById('manualQuantity');
        if (manualQuantityInput) {
            manualQuantityInput.value = 0;
        }
        
        this.updateSummary();
        this.updateSelectedTicketsList();
    }

    /**
     * Generate taken tickets based on raffle data
     */
    generateTakenTicketsFromRaffle(raffle) {
        this.takenTickets = [];
        
        // Simulate taken tickets based on sold tickets count
        const soldCount = raffle.sold_tickets || 0;
        const totalTickets = raffle.total_tickets || 1000;
        
        // Generate random taken tickets
        for (let i = 0; i < soldCount; i++) {
            let randomTicket;
            do {
                randomTicket = Math.floor(Math.random() * totalTickets) + 1;
            } while (this.takenTickets.includes(randomTicket));
            
            this.takenTickets.push(randomTicket);
        }
    }

    /**
     * Initialize ticket selection functionality
     */
    initTicketSelection() {
        // Show initial message to select a raffle
        this.showTicketSelectionMessage();
        this.updateSummary();
    }

    /**
     * Generate random taken tickets for demonstration
     */
    generateTakenTickets() {
        this.takenTickets = [];
        for (let i = 0; i < 150; i++) {
            const randomTicket = Math.floor(Math.random() * 1000) + 1;
            if (!this.takenTickets.includes(randomTicket)) {
                this.takenTickets.push(randomTicket);
            }
        }
    }

    /**
     * Generate the tickets grid using the selected raffle's ticket count
     */
    generateTicketsGrid() {
        const grid = document.getElementById('ticketsGrid');
        if (!grid || !this.currentRaffle) return;

        const totalTickets = this.currentRaffle.total_tickets;
        let ticketsHTML = '';

        for (let i = 1; i <= totalTickets; i++) {
            let ticketClass = 'available';
            if (this.takenTickets.includes(i)) {
                ticketClass = 'taken';
            } else if (this.selectedTickets.includes(i)) {
                ticketClass = 'selected';
            }

            ticketsHTML += `
                <div class="ticket-item ${ticketClass}" 
                     data-ticket="${i}" 
                     onclick="toggleTicket(${i})">
                    ${i.toString().padStart(String(totalTickets).length, '0')}
                </div>
            `;
        }

        grid.innerHTML = ticketsHTML;
    }

    /**
     * Toggle ticket selection
     */
    toggleTicket(ticketNumber) {
        if (!this.currentRaffle) {
            alert('Por favor, selecciona una rifa primero');
            return;
        }

        // Don't allow selecting taken tickets
        if (this.takenTickets.includes(ticketNumber)) {
            return;
        }

        const ticketElement = document.querySelector(`[data-ticket="${ticketNumber}"]`);
        if (!ticketElement) return;
        
        if (this.selectedTickets.includes(ticketNumber)) {
            // Remove from selection
            this.selectedTickets = this.selectedTickets.filter(t => t !== ticketNumber);
            ticketElement.classList.remove('selected');
            ticketElement.classList.add('available');
        } else {
            // Add to selection
            this.selectedTickets.push(ticketNumber);
            ticketElement.classList.remove('available');
            ticketElement.classList.add('selected');
        }

        this.updateSummary();
        this.updateSelectedTicketsList();
    }

    /**
     * Update payment summary
     */
    updateSummary() {
        const totalTickets = this.selectedTickets.length;
        const totalAmount = totalTickets * this.ticketPrice;

        const totalTicketsElement = document.getElementById('totalTickets');
        const totalAmountElement = document.getElementById('totalAmount');
        
        if (totalTicketsElement) {
            totalTicketsElement.textContent = totalTickets;
        }
        if (totalAmountElement) {
            totalAmountElement.textContent = `$${totalAmount.toFixed(2)}`;
        }

        // Update checkout button state
        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = totalTickets === 0 || !this.currentRaffle;
        }
    }

    /**
     * Update selected tickets list display
     */
    updateSelectedTicketsList() {
        const listContainer = document.getElementById('selectedTicketsList');
        if (!listContainer) return;
        
        if (this.selectedTickets.length === 0) {
            listContainer.innerHTML = '<p class="no-tickets">No has seleccionado ningún boleto</p>';
            return;
        }

        const sortedTickets = [...this.selectedTickets].sort((a, b) => a - b);
        let listHTML = '';

        sortedTickets.forEach(ticket => {
            listHTML += `
                <div class="selected-ticket-item">
                    <span>Boleto #${ticket.toString().padStart(4, '0')}</span>
                    <span class="remove-ticket-btn" onclick="removeTicket(${ticket})">×</span>
                </div>
            `;
        });

        listContainer.innerHTML = listHTML;
    }

    /**
     * Remove ticket from selection
     */
    removeTicket(ticketNumber) {
        const ticketElement = document.querySelector(`[data-ticket="${ticketNumber}"]`);
        if (!ticketElement) return;
        
        this.selectedTickets = this.selectedTickets.filter(t => t !== ticketNumber);
        ticketElement.classList.remove('selected');
        ticketElement.classList.add('available');

        this.updateSummary();
        this.updateSelectedTicketsList();
    }

    /**
     * Add random tickets to selection
     */
    addRandomTickets(quantity) {
        if (!this.currentRaffle) {
            alert('Por favor, selecciona una rifa primero');
            return;
        }

        if (quantity === 0) {
            alert('Por favor, selecciona una cantidad mayor a 0');
            return;
        }

        // Get available tickets (not taken and not selected)
        const totalTickets = this.currentRaffle.total_tickets;
        const availableTickets = [];
        
        for (let i = 1; i <= totalTickets; i++) {
            if (!this.takenTickets.includes(i) && !this.selectedTickets.includes(i)) {
                availableTickets.push(i);
            }
        }

        if (availableTickets.length < quantity) {
            alert(`Solo hay ${availableTickets.length} boletos disponibles`);
            return;
        }

        // Select random tickets
        const newTickets = [];
        for (let i = 0; i < quantity; i++) {
            const randomIndex = Math.floor(Math.random() * availableTickets.length);
            const selectedTicket = availableTickets[randomIndex];
            
            newTickets.push(selectedTicket);
            this.selectedTickets.push(selectedTicket);
            
            // Remove from available tickets to avoid duplicates
            availableTickets.splice(randomIndex, 1);
            
            // Update ticket visual
            const ticketElement = document.querySelector(`[data-ticket="${selectedTicket}"]`);
            if (ticketElement) {
                ticketElement.classList.remove('available');
                ticketElement.classList.add('selected');
            }
        }

        this.updateSummary();
        this.updateSelectedTicketsList();

        alert(`Se agregaron ${quantity} boletos aleatorios: ${newTickets.sort((a, b) => a - b).map(t => t.toString().padStart(4, '0')).join(', ')}`);
    }

    /**
     * Clear all selected tickets
     */
    clearAllTickets() {
        if (this.selectedTickets.length === 0) {
            alert('No hay boletos seleccionados para limpiar');
            return;
        }

        if (confirm('¿Estás seguro de que quieres limpiar toda tu selección?')) {
            // Reset all selected tickets to available
            this.selectedTickets.forEach(ticket => {
                const ticketElement = document.querySelector(`[data-ticket="${ticket}"]`);
                if (ticketElement) {
                    ticketElement.classList.remove('selected');
                    ticketElement.classList.add('available');
                }
            });

            this.selectedTickets = [];
            
            const manualQuantityInput = document.getElementById('manualQuantity');
            if (manualQuantityInput) {
                manualQuantityInput.value = 0;
            }
            
            this.updateSummary();
            this.updateSelectedTicketsList();
        }
    }

    /**
     * Proceed to payment
     */
    proceedToPayment() {
        if (!this.currentRaffle) {
            alert('Por favor, selecciona una rifa primero');
            return;
        }

        if (this.selectedTickets.length === 0) {
            alert('No has seleccionado ningún boleto');
            return;
        }

        const totalAmount = this.selectedTickets.length * this.ticketPrice;
        const ticketList = this.selectedTickets.sort((a, b) => a - b).map(t => t.toString().padStart(4, '0')).join(', ');
        
        if (confirm(`¿Proceder al pago?\n\nRifa: ${this.currentRaffle.name}\nBoletos seleccionados: ${this.selectedTickets.length}\nNúmeros: ${ticketList}\nTotal: $${totalAmount.toFixed(2)}`)) {
            // Here you would send the data to a payment processing endpoint
            const paymentData = {
                raffle_id: this.currentRaffle.id,
                raffle_name: this.currentRaffle.name,
                tickets: this.selectedTickets,
                total_amount: totalAmount,
                ticket_price: this.ticketPrice
            };
            
            console.log('Payment data:', paymentData);
            alert('Redirigiendo al procesador de pagos...\n\n¡Gracias por tu compra!');
            // window.location.href = '/payment';
        }
    }

    /**
     * Submit contact form
     */
    submitContactForm(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            subject: formData.get('subject'),
            message: formData.get('message')
        };

        // Simulate form submission
        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem;"><div style="width: 20px; height: 20px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>Enviando...</div>';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            alert(`¡Gracias ${data.firstName}!\n\nTu mensaje ha sido enviado correctamente. Nos pondremos en contacto contigo pronto.`);
            event.target.reset();
        }, 2000);
    }

    /**
     * Cleanup method
     */
    destroy() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Global functions for backward compatibility and HTML onclick handlers
let indexController;

function handleHeroClick() {
    const ticketSection = document.querySelector('.ticket-selection-container');
    if (ticketSection) {
        ticketSection.scrollIntoView({ 
            behavior: 'smooth' 
        });
    }
}

function buyTicket(id, name) {
    alert(`¡Compraste un boleto para la rifa: ${name}!\nID de rifa: ${id}`);
}

function selectRaffle(raffleId, raffleName, ticketPrice) {
    if (indexController) {
        // Set the dropdown to the selected raffle
        const selector = document.getElementById('raffleSelector');
        if (selector) {
            selector.value = raffleId;
        }
        
        // Call the raffle change handler
        indexController.onRaffleChange(raffleId);
        
        // Scroll to ticket selection
        const ticketSection = document.querySelector('.ticket-selection-container');
        if (ticketSection) {
            ticketSection.scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        alert(`Has seleccionado la rifa: ${raffleName}\nPrecio por boleto: ${ticketPrice}\nAhora puedes elegir tus boletos abajo.`);
    }
}

function onRaffleChange() {
    const selector = document.getElementById('raffleSelector');
    const raffleId = selector ? selector.value : '';
    
    if (indexController) {
        indexController.onRaffleChange(raffleId);
    }
}

function buyPackage(tickets, price) {
    alert(`¡Compraste el paquete de ${tickets.toLocaleString()} boletos por $${price}!\n\nTus boletos estarán disponibles en tu cuenta en unos minutos.`);
}

function toggleTicket(ticketNumber) {
    if (indexController) {
        indexController.toggleTicket(ticketNumber);
    }
}

function removeTicket(ticketNumber) {
    if (indexController) {
        indexController.removeTicket(ticketNumber);
    }
}

function increaseQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value) || 0;
    const maxValue = parseInt(input.max) || 100;
    
    if (currentValue < maxValue) {
        input.value = currentValue + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value) || 0;
    const minValue = parseInt(input.min) || 0;
    
    if (currentValue > minValue) {
        input.value = currentValue - 1;
    }
}

function updateManualQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const value = parseInt(input.value) || 0;
    const minValue = parseInt(input.min) || 0;
    const maxValue = parseInt(input.max) || 100;
    
    if (value < minValue) {
        input.value = minValue;
    } else if (value > maxValue) {
        input.value = maxValue;
    }
}

function addRandomTickets() {
    const quantity = parseInt(document.getElementById('manualQuantity')?.value) || 0;
    
    if (indexController) {
        indexController.addRandomTickets(quantity);
        // Reset manual quantity
        const input = document.getElementById('manualQuantity');
        if (input) input.value = 0;
    }
}

function clearSelection() {
    if (indexController) {
        indexController.clearAllTickets();
    }
}

function proceedToPayment() {
    if (indexController) {
        indexController.proceedToPayment();
    }
}

function submitContactForm(event) {
    if (indexController) {
        indexController.submitContactForm(event);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    indexController = new IndexController();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (indexController) {
        indexController.destroy();
    }
});

// Export for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { IndexController };
}