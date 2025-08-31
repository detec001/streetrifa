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

$success_message = '';
$error_message = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        $name = trim($_POST['raffle_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $draw_date = $_POST['draw_date'] ?? '';
        $draw_time = $_POST['draw_time'] ?? '';
        $ticket_price = floatval($_POST['ticket_price'] ?? 0);
        $total_tickets = intval($_POST['total_tickets'] ?? 0);
        $commission_rate = floatval($_POST['commission_rate'] ?? 10);

        // Validaciones básicas
        if (empty($name)) {
            throw new Exception('El nombre de la rifa es obligatorio');
        }
        if (empty($draw_date) || empty($draw_time)) {
            throw new Exception('La fecha y hora del sorteo son obligatorias');
        }
        if ($ticket_price <= 0) {
            throw new Exception('El precio del boleto debe ser mayor a 0');
        }
        if ($total_tickets <= 0) {
            throw new Exception('El número de boletos debe ser mayor a 0');
        }

        // Validar que la fecha sea futura
        $draw_datetime = $draw_date . ' ' . $draw_time;
        if (strtotime($draw_datetime) <= time()) {
            throw new Exception('La fecha y hora del sorteo debe ser futura');
        }

        // Procesar imágenes subidas
        $uploaded_images = [];
        if (isset($_FILES['images'])) {
            $upload_dir = '../uploads/raffles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = uniqid('raffle_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        $uploaded_images[] = $new_filename;
                    }
                }
            }
        }

        // Insertar en la base de datos
        $sql = "INSERT INTO raffles (name, description, draw_date, ticket_price, total_tickets, commission_rate, images, created_by, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $images_json = json_encode($uploaded_images);
        
        executeQuery($sql, [
            $name,
            $description,
            $draw_datetime,
            $ticket_price,
            $total_tickets,
            $commission_rate,
            $images_json,
            $current_admin['id']
        ]);

        logAdminActivity('create_raffle', "Rifa creada: {$name}");
        $success_message = 'Rifa creada exitosamente';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Error al crear rifa: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Rifa - Panel de <?php echo ucfirst($current_admin['user_type']); ?></title>
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

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
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

        /* Content Section */
        .content-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

        .form-grid.single-column {
            grid-template-columns: 1fr;
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

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Image Upload */
        .image-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fafbfc;
        }

        .image-upload-container:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .upload-text {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .upload-subtext {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .hidden-input {
            display: none;
        }

        .image-preview {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }

        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .remove-image {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        /* Calculations Panel */
        .calculations-panel {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .calculations-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .calc-row:last-child {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid rgba(59, 130, 246, 0.2);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .calc-label {
            color: #1e40af;
            font-weight: 500;
        }

        .calc-value {
            color: #1e3a8a;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: 1px solid #3b82f6;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            border-color: #1d4ed8;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-right {
                flex-direction: column;
                gap: 0.5rem;
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
                        <span>Crear Rifa</span>
                    </div>
                    <h1>Crear Nueva Rifa</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-role role-<?php echo $current_admin['user_type']; ?>">
                    <i class="fas fa-<?php echo $current_admin['user_type'] === 'admin' ? 'crown' : 'users'; ?>"></i>
                    <?php echo ucfirst($current_admin['user_type']); ?>
                </div>
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
        <!-- Formulario -->
        <div class="content-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">
                        <i class="fas fa-gift"></i>
                        Información de la Rifa
                    </h2>
                    <p class="section-description">Complete toda la información para crear una nueva rifa</p>
                </div>
            </div>

            <div class="section-content">
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

                <form method="POST" enctype="multipart/form-data" id="raffleForm">
                    <!-- Información Básica -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="raffle_name">
                                <i class="fas fa-tag"></i> Nombre de la Rifa *
                            </label>
                            <input type="text" 
                                   id="raffle_name" 
                                   name="raffle_name" 
                                   class="form-input" 
                                   placeholder="Ej: iPhone 15 Pro Max"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ticket_price">
                                <i class="fas fa-dollar-sign"></i> Precio del Boleto *
                            </label>
                            <input type="number" 
                                   id="ticket_price" 
                                   name="ticket_price" 
                                   class="form-input" 
                                   placeholder="50.00"
                                   step="0.01"
                                   min="0.01"
                                   required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="total_tickets">
                                <i class="fas fa-ticket-alt"></i> Número Total de Boletos *
                            </label>
                            <input type="number" 
                                   id="total_tickets" 
                                   name="total_tickets" 
                                   class="form-input" 
                                   placeholder="1000"
                                   min="1"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="commission_rate">
                                <i class="fas fa-percentage"></i> Tasa de Comisión (%)
                            </label>
                            <input type="number" 
                                   id="commission_rate" 
                                   name="commission_rate" 
                                   class="form-input" 
                                   placeholder="10"
                                   step="0.1"
                                   min="0"
                                   max="100"
                                   value="10">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="draw_date">
                                <i class="fas fa-calendar"></i> Fecha del Sorteo *
                            </label>
                            <input type="date" 
                                   id="draw_date" 
                                   name="draw_date" 
                                   class="form-input" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="draw_time">
                                <i class="fas fa-clock"></i> Hora del Sorteo *
                            </label>
                            <input type="time" 
                                   id="draw_time" 
                                   name="draw_time" 
                                   class="form-input" 
                                   required>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group">
                        <label class="form-label" for="description">
                            <i class="fas fa-align-left"></i> Descripción (Opcional)
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-textarea" 
                                  placeholder="Descripción detallada de la rifa, términos y condiciones, etc."></textarea>
                    </div>

                    <!-- Subida de imágenes -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-images"></i> Imágenes de la Rifa (Máximo 5)
                        </label>
                        <div class="image-upload-container" onclick="document.getElementById('images').click()">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">
                                Haz clic aquí o arrastra las imágenes
                            </div>
                            <div class="upload-subtext">
                                Formatos soportados: JPG, PNG, GIF (Máximo 5MB cada una)
                            </div>
                        </div>
                        <input type="file" 
                               id="images" 
                               name="images[]" 
                               class="hidden-input" 
                               multiple 
                               accept="image/*">
                        <div id="imagePreview" class="image-preview"></div>
                    </div>

                    <!-- Panel de cálculos -->
                    <div class="calculations-panel">
                        <div class="calculations-title">
                            <i class="fas fa-calculator"></i>
                            Cálculos Automáticos
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Ingresos Brutos Potenciales:</span>
                            <span class="calc-value" id="grossRevenue">$0.00</span>
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Total en Comisiones:</span>
                            <span class="calc-value" id="totalCommissions">$0.00</span>
                        </div>
                        <div class="calc-row">
                            <span class="calc-label">Ingresos Netos Estimados:</span>
                            <span class="calc-value" id="netRevenue">$0.00</span>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="form-buttons">
                        <a href="panel.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Crear Rifa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Configurar fecha mínima (hoy)
        document.getElementById('draw_date').min = new Date().toISOString().split('T')[0];

        // Variables para imágenes
        const imageInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        const uploadContainer = document.querySelector('.image-upload-container');
        let selectedFiles = [];

        // Manejar selección de archivos
        imageInput.addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        // Drag and drop
        uploadContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadContainer.style.borderColor = '#3b82f6';
            uploadContainer.style.background = '#eff6ff';
        });

        uploadContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadContainer.style.borderColor = '#d1d5db';
            uploadContainer.style.background = '#fafbfc';
        });

        uploadContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadContainer.style.borderColor = '#d1d5db';
            uploadContainer.style.background = '#fafbfc';
            handleFiles(e.dataTransfer.files);
        });

        function handleFiles(files) {
            const maxFiles = 5;
            const remainingSlots = maxFiles - selectedFiles.length;
            const filesToAdd = Math.min(files.length, remainingSlots);

            for (let i = 0; i < filesToAdd; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    displayPreview(file, selectedFiles.length - 1);
                }
            }

            updateFileInput();
        }

        function displayPreview(file, index) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                    <button type="button" class="remove-image" onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                imagePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            refreshPreviews();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imageInput.files = dt.files;
        }

        function refreshPreviews() {
            imagePreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                displayPreview(file, index);
            });
        }

        // Cálculos automáticos
        function calculateRevenues() {
            const ticketPrice = parseFloat(document.getElementById('ticket_price').value) || 0;
            const totalTickets = parseInt(document.getElementById('total_tickets').value) || 0;
            const commissionRate = parseFloat(document.getElementById('commission_rate').value) || 0;

            const grossRevenue = ticketPrice * totalTickets;
            const totalCommissions = grossRevenue * (commissionRate / 100);
            const netRevenue = grossRevenue - totalCommissions;

            document.getElementById('grossRevenue').textContent = '$' + grossRevenue.toLocaleString('es-MX', {minimumFractionDigits: 2});
            document.getElementById('totalCommissions').textContent = '$' + totalCommissions.toLocaleString('es-MX', {minimumFractionDigits: 2});
            document.getElementById('netRevenue').textContent = '$' + netRevenue.toLocaleString('es-MX', {minimumFractionDigits: 2});
        }

        // Event listeners para cálculos
        document.getElementById('ticket_price').addEventListener('input', calculateRevenues);
        document.getElementById('total_tickets').addEventListener('input', calculateRevenues);
        document.getElementById('commission_rate').addEventListener('input', calculateRevenues);

        // Calcular al cargar la página
        calculateRevenues();
    </script>
</body>
</html>