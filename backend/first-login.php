<?php
// backend/first-login.php - INTEGRADO CON CONFIG DE SEGURIDAD
session_start();

// CARGAR CONFIGURACIÓN DE SEGURIDAD
$securityConfig = require_once '../config/security.php';
$security = $securityConfig['security'];

// Debug basado en configuración
$isDebug = $security['debug_mode'];

if (!isset($_SESSION['user_id'])) {
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px;'>❌ No hay sesión activa. Redirigiendo a login...</div>";
        sleep(1);
    }
    header('Location: ../login.php');
    exit;
}

require 'db.php';

$error = '';
$success = '';

// Verificar que realmente necesita cambiar contraseña
$stmt = $pdo->prepare("SELECT first_time_login, email, first_name, last_name, subscription_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px;'>❌ Usuario no encontrado. Destruyendo sesión...</div>";
        sleep(1);
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}

if (!$user['first_time_login']) {
    if ($isDebug) {
        echo "<div style='background: green; color: white; padding: 10px;'>Usuario ya cambio contraseña. Redirigiendo a dashboard...</div>";
        sleep(1);
    }
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($isDebug) {
        echo "<div style='background: lightblue; padding: 10px;'>🔄 Procesando cambio de contraseña...</div>";
    }
    
    // USAR CONFIGURACIÓN DE SEGURIDAD PARA VALIDACIONES
    $minLength = $security['password']['min_length'];
    $requireSpecial = $security['password']['require_special'];
    $requireNumbers = $security['password']['require_numbers'];
    $requireUppercase = $security['password']['require_uppercase'];
    $requireLowercase = $security['password']['require_lowercase'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Ambas contraseñas son requeridas';
    } elseif (strlen($newPassword) < $minLength) {
        $error = "La contraseña debe tener al menos {$minLength} caracteres";
    } elseif ($requireLowercase && !preg_match('/[a-z]/', $newPassword)) {
        $error = 'La contraseña debe contener al menos una letra minúscula';
    } elseif ($requireUppercase && !preg_match('/[A-Z]/', $newPassword)) {
        $error = 'La contraseña debe contener al menos una letra mayúscula';
    } elseif ($requireNumbers && !preg_match('/[0-9]/', $newPassword)) {
        $error = 'La contraseña debe contener al menos un número';
    } elseif ($requireSpecial && !preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
        $error = 'La contraseña debe contener al menos un carácter especial';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // IMPORTANTE: Marcar como NO primer login
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, first_time_login = 0, temp_password = NULL
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            if ($result) {
                $success = '¡Contraseña actualizada exitosamente! Redirigiendo al dashboard...';
                
                // LOG DE SEGURIDAD
                if ($security['logging']['enable']) {
                    $logMessage = "[" . date('Y-m-d H:i:s') . "] Password changed for user ID: " . $_SESSION['user_id'] . " (First login completed)\n";
                    file_put_contents($security['logging']['log_file'], $logMessage, FILE_APPEND | LOCK_EX);
                }
                
                if ($isDebug) {
                    echo "<div style='background: green; color: white; padding: 10px;'>Contraseña actualizada. Redirigiendo...</div>";
                    echo "<script>setTimeout(() => window.location.href = '../dashboard.php', 2000);</script>";
                } else {
                    // Redirección inmediata en producción
                    header('Location: ../dashboard.php');
                    exit;
                }
            } else {
                $error = 'Error al actualizar la contraseña';
            }
            
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            $error = 'Error actualizando contraseña. Intenta de nuevo.';
            
            if ($isDebug) {
                echo "<div style='background: red; color: white; padding: 10px;'>❌ Error BD: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #c78b42;
            --secondary-color: #1a1a1a;
            --background-color: #fafafa;
            --text-color: #666;
            --highlight-color: #a6722e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--secondary-color);
            overflow-x: hidden;
            background: linear-gradient(135deg, #0f0f0f 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            position: relative;
        }

        /* Elementos geométricos de fondo */
        .geometric-circle {
            position: absolute;
            top: 15%;
            right: 8%;
            width: 400px;
            height: 400px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: rotateCircle 25s linear infinite;
            z-index: 1;
        }

        .geometric-circle::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 250px;
            height: 250px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: rotateCircle 20s linear infinite reverse;
        }

        .geometric-circle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120px;
            height: 120px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: rotateCircle 15s linear infinite;
        }

        .geometric-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.02) 30.5%, rgba(255, 255, 255, 0.02) 31%, transparent 31.5%),
                linear-gradient(-45deg, transparent 30%, rgba(255, 255, 255, 0.02) 30.5%, rgba(255, 255, 255, 0.02) 31%, transparent 31.5%);
            background-size: 80px 80px;
            opacity: 0.4;
            z-index: 1;
        }

        @keyframes rotateCircle {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Container principal */
        .first-login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .first-login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.15),
                0 16px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            width: 100%;
            max-width: 650px;
            padding: 2.5rem 4rem;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
        }

        .first-login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.5), transparent);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header del first login */
        .first-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .security-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--highlight-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: logoFloat 3s ease-in-out infinite;
            box-shadow: 0 8px 32px rgba(199, 139, 66, 0.3);
        }

        .security-icon .material-icons {
            font-size: 40px;
            color: white;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .first-login-title {
            font-family: 'Trajan Pro', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.3rem;
            letter-spacing: 1px;
            background: linear-gradient(20deg, var(--secondary-color), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .first-login-subtitle {
            color: var(--text-color);
            font-size: 0.95rem;
            font-weight: 400;
            font-style: italic;
            margin-bottom: 2rem;
        }

        /* User welcome card */
        .user-welcome {
            background: linear-gradient(135deg, var(--primary-color), var(--highlight-color));
            color: white;
            padding: 1.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(199, 139, 66, 0.3);
            position: relative;
            overflow: hidden;
        }

        .user-welcome::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .user-welcome h2 {
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        .user-welcome p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .subscription-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            margin-top: 0.5rem;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 2;
        }

        /* Password requirements */
        .password-requirements {
            background: rgba(199, 139, 66, 0.08);
            border: 1px solid rgba(199, 139, 66, 0.2);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            position: relative;
        }

        .password-requirements h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .requirements-icon {
            color: var(--primary-color);
        }

        .requirements-icon .material-icons {
            font-size: 20px;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .password-requirements li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            color: var(--secondary-color);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid rgba(0, 0, 0, 0.06);
            border-radius: 16px;
            font-size: 0.95rem;
            font-family: inherit;
            background: rgba(250, 250, 250, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 
                0 0 0 4px rgba(199, 139, 66, 0.1),
                0 8px 32px rgba(199, 139, 66, 0.15);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: var(--text-color);
            opacity: 0.7;
        }

        /* Button styling */
        .btn-change-password {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--highlight-color) 100%);
            color: white;
            padding: 1.1rem 2rem;
            border: none;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(199, 139, 66, 0.3);
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-change-password .material-icons {
            font-size: 20px;
        }

        .btn-change-password::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-change-password:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 48px rgba(199, 139, 66, 0.4);
        }

        .btn-change-password:hover::before {
            left: 100%;
        }

        .btn-change-password:active {
            transform: translateY(-1px);
        }

        .btn-change-password:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Loading state */
        .btn-change-password.loading {
            position: relative;
            color: transparent;
        }

        .btn-change-password.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alert messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #16a34a;
        }

        /* Links */
        .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .logout-arrow .material-icons {
            font-size: 18px;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .logout-link:hover .logout-arrow .material-icons {
            color: var(--primary-color);
            transform: translateX(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .first-login-wrapper {
                padding: 1rem;
            }
            
            .first-login-container {
                padding: 2rem;
                max-width: 100%;
            }
            
            .geometric-circle {
                width: 250px;
                height: 250px;
                top: 10%;
                right: 5%;
            }
            
            .first-login-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .first-login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .first-login-title {
                font-size: 1.6rem;
            }
            
            .geometric-lines {
                background-size: 40px 40px;
            }
        }

        /* Mejoras de accesibilidad */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus states mejorados */
        .btn-change-password:focus-visible,
        .logout-link:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Elementos geométricos de fondo -->
    <div class="geometric-circle"></div>
    <div class="geometric-lines"></div>

    <div class="first-login-wrapper">
        <div class="first-login-container">
            <div class="first-login-header">
                <div class="security-icon">
                    <i class="material-icons">lock</i>
                </div>
                
                <h1 class="first-login-title">Cambio de Contraseña</h1>
                <p class="first-login-subtitle">Por tu seguridad, debes cambiar tu contraseña temporal por una personalizada</p>
            </div>
            
            <div class="user-welcome">
                <h2>¡Hola <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email']); ?>!</h2>
                <p>Bienvenido a Gente Vigente</p>
                <span class="subscription-badge">Plan <?php echo ucfirst($user['subscription_type']); ?></span>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="password-requirements">
                <h4>
                    <span class="requirements-icon">
                        <i class="material-icons">assignment</i>
                    </span>
                    Requisitos de la contraseña
                </h4>
                <ul>
                    <li>Mínimo <?php echo $security['password']['min_length']; ?> caracteres</li>
                    <?php if ($security['password']['require_lowercase']): ?>
                    <li>Al menos una letra minúscula (a-z)</li>
                    <?php endif; ?>
                    <?php if ($security['password']['require_uppercase']): ?>
                    <li>Al menos una letra mayúscula (A-Z)</li>
                    <?php endif; ?>
                    <?php if ($security['password']['require_numbers']): ?>
                    <li>Al menos un número (0-9)</li>
                    <?php endif; ?>
                    <?php if ($security['password']['require_special']): ?>
                    <li>Al menos un carácter especial (!@#$%^&*)</li>
                    <?php endif; ?>
                    <li>Evita usar información personal</li>
                </ul>
            </div>
            
            <form method="POST" id="passwordForm">
                <div class="form-group">
                    <label for="new_password" class="form-label">Nueva Contraseña *</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-input"
                        required
                        placeholder="Ej: MiClave123"
                        minlength="<?php echo $security['password']['min_length']; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        required
                        placeholder="Repite la misma contraseña"
                        minlength="<?php echo $security['password']['min_length']; ?>"
                    >
                </div>
                
                <button type="submit" class="btn-change-password" id="submitBtn">
                    <i class="material-icons">lock</i>
                    Cambiar Contraseña y Continuar
                </button>
            </form>
            
            <div style="text-align: center;">
                <a href="logout.php" class="logout-link">
                    <span class="logout-arrow">
                        <i class="material-icons">exit_to_app</i>
                    </span>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    
    <script>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        // Configuración de seguridad desde PHP
        const securityConfig = {
            minLength: <?php echo $security['password']['min_length']; ?>,
            requireSpecial: <?php echo $security['password']['require_special'] ? 'true' : 'false'; ?>,
            requireNumbers: <?php echo $security['password']['require_numbers'] ? 'true' : 'false'; ?>,
            requireUppercase: <?php echo $security['password']['require_uppercase'] ? 'true' : 'false'; ?>,
            requireLowercase: <?php echo $security['password']['require_lowercase'] ? 'true' : 'false'; ?>
        };
        
        function validatePasswords() {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            
            // Resetear estilos
            newPassword.style.borderColor = 'rgba(0, 0, 0, 0.06)';
            confirmPassword.style.borderColor = 'rgba(0, 0, 0, 0.06)';
            newPassword.style.boxShadow = '';
            confirmPassword.style.boxShadow = '';
            
            let isValid = true;
            
            // Validar longitud mínima
            if (newPass.length < securityConfig.minLength) {
                isValid = false;
                if (newPass.length > 0) {
                    newPassword.style.borderColor = '#eab308';
                    newPassword.style.boxShadow = '0 0 0 4px rgba(234, 179, 8, 0.1)';
                }
            } else {
                // Validaciones adicionales
                if (securityConfig.requireLowercase && !/[a-z]/.test(newPass)) isValid = false;
                if (securityConfig.requireUppercase && !/[A-Z]/.test(newPass)) isValid = false;
                if (securityConfig.requireNumbers && !/[0-9]/.test(newPass)) isValid = false;
                if (securityConfig.requireSpecial && !/[^a-zA-Z0-9]/.test(newPass)) isValid = false;
                
                if (isValid) {
                    newPassword.style.borderColor = '#16a34a';
                    newPassword.style.boxShadow = '0 0 0 4px rgba(34, 197, 94, 0.1)';
                } else {
                    newPassword.style.borderColor = '#dc2626';
                    newPassword.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
                }
            }
            
            // Validar coincidencia
            if (confirmPass.length > 0) {
                if (newPass === confirmPass && isValid) {
                    confirmPassword.style.borderColor = '#16a34a';
                    confirmPassword.style.boxShadow = '0 0 0 4px rgba(34, 197, 94, 0.1)';
                } else {
                    confirmPassword.style.borderColor = '#dc2626';
                    confirmPassword.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
                }
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        // Validación del formulario
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;
            
            if (newPass.length < securityConfig.minLength) {
                e.preventDefault();
                alert(`La contraseña debe tener al menos ${securityConfig.minLength} caracteres`);
                return;
            }
            
            if (securityConfig.requireLowercase && !/[a-z]/.test(newPass)) {
                e.preventDefault();
                alert('La contraseña debe contener al menos una letra minúscula');
                return;
            }
            
            if (securityConfig.requireUppercase && !/[A-Z]/.test(newPass)) {
                e.preventDefault();
                alert('La contraseña debe contener al menos una letra mayúscula');
                return;
            }
            
            if (securityConfig.requireNumbers && !/[0-9]/.test(newPass)) {
                e.preventDefault();
                alert('La contraseña debe contener al menos un número');
                return;
            }
            
            if (securityConfig.requireSpecial && !/[^a-zA-Z0-9]/.test(newPass)) {
                e.preventDefault();
                alert('La contraseña debe contener al menos un carácter especial');
                return;
            }
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Cambiando contraseña...';
        });
        
        // Auto-focus
        setTimeout(() => {
            newPassword.focus();
        }, 500);
    </script>
</body>
</html>