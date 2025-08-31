<?php
require_once '../admin/process_admin_login.php';

// Requerir autenticación y verificar que sea committee
requireAuth();
$current_admin = getCurrentAdmin();

// Solo committees pueden acceder
if ($current_admin['user_type'] !== 'committee') {
    header('Location: panel.php');
    exit();
}

$message = '';
$error = '';
$success = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // Validaciones
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            throw new Exception('Todos los campos obligatorios deben ser completados.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El email no tiene un formato válido.');
        }

        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres.');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Las contraseñas no coinciden.');
        }

        if (strlen($username) < 3) {
            throw new Exception('El nombre de usuario debe tener al menos 3 caracteres.');
        }

        // Verificar que no exista el usuario o email
        $existing_user = fetchOne("SELECT id FROM admins WHERE username = ? OR email = ?", [$username, $email]);
        if ($existing_user) {
            throw new Exception('El nombre de usuario o email ya existe.');
        }

        // Crear el vendedor
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO admins (username, user_type, email, password, committee_id, full_name, phone, status, created_at) 
                VALUES (?, 'seller', ?, ?, ?, ?, ?, 'active', NOW())";
        
        // Corregir: usar executeQuery en lugar de execute
        $result = executeQuery($sql, [
            $username,
            $email, 
            $hashed_password,
            $current_admin['id'], // committee_id es el ID del committee actual
            $full_name,
            $phone ?: null
        ]);

        if ($result) {
            $success = true;
            $message = 'Vendedor creado exitosamente.';
            
            // Limpiar formulario
            $_POST = [];
        } else {
            throw new Exception('Error al crear el vendedor. Intenta nuevamente.');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Vendedor - Panel Committee</title>
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
            min-height: 100vh;
        }

        /* Header igual al panel */
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
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
        }

        .back-btn {
            display: inline-flex;
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

        .back-btn:hover {
            background: #e5e7eb;
            color: #374151;
            border-color: #9ca3af;
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* Container principal */
        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .form-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .form-header h2 i {
            color: #7c3aed;
        }

        .form-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .required {
            color: #ef4444;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: #7c3aed;
            background: white;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Input con iconos */
        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 1;
        }

        .input-group .form-input {
            padding-left: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #6b7280;
        }

        /* Password strength */
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .strength-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #059669; width: 100%; }

        .submit-btn {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .main-container {
                padding: 0 1rem;
            }

            .form-card {
                padding: 2rem 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Loading State */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
</head>
<body>
    <!-- Header igual al panel -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_admin['username'], 0, 1)); ?>
                </div>
                <div class="header-info">
                    <h1>Agregar Vendedor</h1>
                    <div class="user-role">
                        <i class="fas fa-users"></i>
                        Committee
                    </div>
                </div>
            </div>
            <div class="header-right">
                <a href="panel.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Volver al Panel
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido principal -->
    <main class="main-container">
        <div class="form-card">
            <div class="form-header">
                <h2>
                    <i class="fas fa-user-plus"></i>
                    Crear Nuevo Vendedor
                </h2>
                <p>Completa la información para agregar un vendedor a tu equipo</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="sellerForm">
                <!-- Información básica -->
                <div class="form-group">
                    <label for="full_name">Nombre Completo <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            class="form-input" 
                            placeholder="Juan Pérez García"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Nombre de Usuario <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-at input-icon"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                placeholder="juan_perez"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                required
                                minlength="3"
                                pattern="[a-zA-Z0-9_]+"
                                title="Solo letras, números y guiones bajos"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-input" 
                                placeholder="+52 123 456 7890"
                                value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="juan@ejemplo.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Contraseñas -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Mínimo 6 caracteres"
                                required
                                minlength="6"
                            >
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div id="strengthText">Ingresa una contraseña</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Contraseña <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                placeholder="Repite la contraseña"
                                required
                                minlength="6"
                            >
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    Crear Vendedor
                </button>
            </form>
        </div>
    </main>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('.password-toggle');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = '';

            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            switch (score) {
                case 0:
                case 1:
                    strengthFill.className = 'strength-fill strength-weak';
                    feedback = 'Contraseña débil';
                    break;
                case 2:
                    strengthFill.className = 'strength-fill strength-fair';
                    feedback = 'Contraseña regular';
                    break;
                case 3:
                case 4:
                    strengthFill.className = 'strength-fill strength-good';
                    feedback = 'Contraseña buena';
                    break;
                case 5:
                    strengthFill.className = 'strength-fill strength-strong';
                    feedback = 'Contraseña fuerte';
                    break;
            }

            strengthText.textContent = feedback;
        }

        // Form validation and enhancements
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('sellerForm');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');

            // Password strength checking
            passwordField.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Password match validation
            function validatePasswords() {
                if (confirmPasswordField.value && passwordField.value !== confirmPasswordField.value) {
                    confirmPasswordField.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }

            passwordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
            });

            // Auto-generate username from full name
            document.getElementById('full_name').addEventListener('input', function() {
                const fullName = this.value.trim();
                const usernameField = document.getElementById('username');
                
                if (fullName && !usernameField.value) {
                    const suggestion = fullName
                        .toLowerCase()
                        .replace(/\s+/g, '_')
                        .replace(/[áàäâ]/g, 'a')
                        .replace(/[éèëê]/g, 'e')
                        .replace(/[íìïî]/g, 'i')
                        .replace(/[óòöô]/g, 'o')
                        .replace(/[úùüû]/g, 'u')
                        .replace(/ñ/g, 'n')
                        .replace(/[^a-z0-9_]/g, '');
                    
                    usernameField.value = suggestion;
                }
            });

            // Real-time username validation
            document.getElementById('username').addEventListener('input', function() {
                const username = this.value;
                if (username.length >= 3) {
                    // Here you could add AJAX validation to check if username exists
                    console.log('Checking username availability:', username);
                }
            });
        });
    </script>
</body>
</html>